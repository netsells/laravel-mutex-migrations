<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class MutexRelay implements MutexRelayInterface
{
    public const KEY = 'laravel-mutex-migrations';

    private ?Lock $lock = null;

    public function __construct(
        private readonly Repository $cache,
        private readonly int $ttl_seconds = 60
    ) {
        //
    }

    public function acquireLock(): bool
    {
        try {
            return $this->getLock()->block($this->ttl_seconds);
        } catch (\Throwable $th) {
            if ($this->isCacheTableNotFoundException($th)) {
                throw new DatabaseCacheTableNotFoundException();
            }

            throw $th;
        }
    }

    public function releaseLock(): bool
    {
        return $this->lock?->release() ?? false;
    }

    private function getLock(): Lock
    {
        /** @var LockProvider $provider */
        $provider = $this->cache->getStore();

        return $this->lock = $provider->lock(self::KEY . '.lock');
    }

    private function isCacheTableNotFoundException(\Throwable $th): bool
    {
        if (! $th instanceof QueryException) {
            return false;
        }

        return Str::contains($th->getMessage(), ['Base table or view new found', 'cache'], true);
    }
}
