<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function findOneByIdForUpdate(int $id): ?Account
    {
        $account = $this->find($id);

        if ($account === null) {
            return null;
        }

        $this->getEntityManager()->lock($account, LockMode::PESSIMISTIC_WRITE);

        return $account;
    }

    public function persist(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);
        $em->flush();
    }
}
