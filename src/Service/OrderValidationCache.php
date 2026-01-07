<?php

namespace App\Service;

use App\Message\Command\SaveOrder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ValidatorInterface;

readonly class OrderValidationCache
{
    private const CACHE_KEY_PREFIX = 'order_validation_';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private CacheItemPoolInterface $cache,
        private ValidatorInterface $validator,
    ) {
    }

    public function validateWithCache(SaveOrder $command): ConstraintViolationList
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $this->generateCommandHash($command);

        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $violations = $this->validator->validate($command);

        // Only cache if validation passes (no violations) or has few violations
        if ($violations->count() === 0 || $violations->count() <= 2) {
            $cacheItem->set($violations);
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);
        }

        return $violations;
    }

    private function generateCommandHash(SaveOrder $command): string
    {
        // Create a hash based on command properties to identify similar commands
        $data = [
            $command->userId,
            $command->stockSymbol,
            $command->quantity,
            $command->price,
        ];

        return hash('sha256', serialize($data));
    }

    public function invalidateCache(SaveOrder $command): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $this->generateCommandHash($command);
        $this->cache->deleteItem($cacheKey);
    }
}
