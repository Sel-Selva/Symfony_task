<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'account')]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 3)]
    #[Assert\Length(min: 3, max: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $balance = '0.00';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $currency = 'USD', string $initialBalance = '0.00')
    {
        $this->currency = strtoupper($currency);
        $this->balance = $this->normalizeAmount($initialBalance);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBalance(): string
    {
        return $this->normalizeAmount($this->balance);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function withdraw(string $amount): self
    {
        $amount = $this->normalizeAmount($amount);

        if ($this->getBalanceComparison($amount) < 0) {
            throw new \App\Exception\InsufficientFundsException('Account has insufficient funds.');
        }

        $this->balance = bcsub($this->balance, $amount, 2);

        return $this;
    }

    public function deposit(string $amount): self
    {
        $amount = $this->normalizeAmount($amount);
        $this->balance = bcadd($this->balance, $amount, 2);

        return $this;
    }

    private function normalizeAmount(string $amount): string
    {
        if (!is_string($amount)) {
            throw new \InvalidArgumentException('Amount must be a string.');
        }

        $amount = trim($amount);

        if ($amount === '') {
            throw new \InvalidArgumentException('Amount must not be empty.');
        }

        if (!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('Amount must be a positive decimal number with up to two decimals.');
        }

        return number_format($amount, 2, '.', '');
    }

    private function getBalanceComparison(string $amount): int
    {
        return bccomp($this->balance, $amount, 2);
    }
}
