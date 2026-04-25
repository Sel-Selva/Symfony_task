<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class TransferRequest
{
    #[Assert\Positive]
    public int $fromAccountId;

    #[Assert\Positive]
    public int $toAccountId;

    #[Assert\Regex(pattern: '/^[0-9]+(?:\.[0-9]{1,2})?$/', message: 'Amount must be a positive number with up to 2 decimal places.')]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 3)]
    public string $currency;
}
