<?php

namespace App\Controller;

use App\DTO\CreateAccountRequest;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Service\AccountCache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/accounts')]
final class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private AccountCache $accountCache,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'account_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $requestDto = $this->serializer->deserialize($request->getContent(), CreateAccountRequest::class, 'json');
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }
        $errors = $this->validator->validate($requestDto);

        if (count($errors) > 0) {
            $messages = [];

            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }

            return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $account = new Account($requestDto->currency, $requestDto->initialBalance);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        $this->accountCache->setBalance($account);

        return new JsonResponse([
            'id' => $account->getId(),
            'currency' => $account->getCurrency(),
            'balance' => $account->getBalance(),
            'createdAt' => $account->getCreatedAt()->format(DATE_ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'account_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return new JsonResponse(['error' => 'Account not found.'], Response::HTTP_NOT_FOUND);
        }

        $cachedBalance = $this->accountCache->getBalance($id);

        if ($cachedBalance === null) {
            $this->accountCache->setBalance($account);
            $cachedBalance = $account->getBalance();
        }

        return new JsonResponse([
            'id' => $account->getId(),
            'currency' => $account->getCurrency(),
            'balance' => $cachedBalance,
            'createdAt' => $account->getCreatedAt()->format(DATE_ATOM),
        ]);
    }
}
