<?php

namespace App\Service;

use App\Exception\AccountException;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

final class TransferService
{
    private EntityManagerInterface $entityManager;
    private AccountRepository $accountRepository;
    private AccountCache $accountCache;
    private LockFactory $lockFactory;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        AccountCache $accountCache,
        LockFactory $lockFactory,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->accountRepository = $accountRepository;
        $this->accountCache = $accountCache;
        $this->lockFactory = $lockFactory;
        $this->logger = $logger;
    }

    public function transfer(int $sourceAccountId, int $destinationAccountId, string $currency, string $amount): void
    {
        if ($sourceAccountId === $destinationAccountId) {
            throw new AccountException('Source and destination account must differ.');
        }

        $lockKey = sprintf('transfer_%d_%d', min($sourceAccountId, $destinationAccountId), max($sourceAccountId, $destinationAccountId));
        $lock = $this->lockFactory->createLock($lockKey, 30.0);

        if (!$lock->acquire(true)) {
            throw new AccountException('Unable to acquire transfer lock at this time.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $source = $this->accountRepository->findOneByIdForUpdate($sourceAccountId);
            $destination = $this->accountRepository->findOneByIdForUpdate($destinationAccountId);

            if ($source === null || $destination === null) {
                throw new AccountException('One or both accounts not found.');
            }

            if ($source->getCurrency() !== $currency || $destination->getCurrency() !== $currency) {
                throw new AccountException('Currency mismatch.');
            }

            $source->withdraw($amount);
            $destination->deposit($amount);

            $this->entityManager->persist($source);
            $this->entityManager->persist($destination);
            $this->entityManager->flush();
            $connection->commit();

            $this->accountCache->setBalance($source);
            $this->accountCache->setBalance($destination);
        } catch (AccountException $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->logger->warning('Transfer failed: {message}', [
                'message' => $exception->getMessage(),
                'source' => $sourceAccountId,
                'destination' => $destinationAccountId,
                'amount' => $amount,
            ]);

            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
