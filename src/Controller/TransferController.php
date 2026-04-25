<?php

namespace App\Controller;

use App\DTO\TransferRequest;
use App\Exception\AccountException;
use App\Service\TransferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transfers')]
final class TransferController extends AbstractController
{
    public function __construct(
        private TransferService $transferService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'transfer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $requestDto = $this->serializer->deserialize($request->getContent(), TransferRequest::class, 'json');
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

        try {
            $this->transferService->transfer(
                $requestDto->fromAccountId,
                $requestDto->toAccountId,
                strtoupper($requestDto->currency),
                $requestDto->amount
            );

            return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
        } catch (AccountException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
