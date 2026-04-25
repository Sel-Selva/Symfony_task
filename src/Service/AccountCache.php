<?php

namespace App\Service;

use App\Entity\Account;
use Psr\Cache\CacheItemPoolInterface;

final class AccountCache
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getBalance(int $accountId): ?string
    {
        $item = $this->cache->getItem($this->getCacheKey($accountId));

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    public function setBalance(Account $account): void
    {
        $item = $this->cache->getItem($this->getCacheKey($account->getId()));
        $item->set($account->getBalance());
        $item->expiresAfter(300);
        $this->cache->save($item);
    }

    public function invalidate(int $accountId): void
    {
        $this->cache->deleteItem($this->getCacheKey($accountId));
    }

    private function getCacheKey(int $accountId): string
    {
        return sprintf('account_balance_%d', $accountId);
    }
}
