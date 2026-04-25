<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateAccountRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 3)]
    public string $currency = 'USD';

    #[Assert\Regex(pattern: '/^[0-9]+(?:\.[0-9]{1,2})?$/', message: 'Initial balance must be a positive number with up to 2 decimal places.')]
    public string $initialBalance = '0.00';
}
