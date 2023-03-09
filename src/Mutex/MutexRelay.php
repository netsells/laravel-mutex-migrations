<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class MutexRelay implements MutexRelayInterface
{
    public const DEFAULT_LOCK_TABLE = 'cache_locks';

    public const KEY = 'laravel-mutex-migrations';

    private ?Lock $lock = null;

    public function __construct(
        private readonly Repository $cache,
        private readonly int $lockDurationSeconds = 60,
        private readonly string $lockTable = self::DEFAULT_LOCK_TABLE,
    ) {
        //
    }

    public function acquireLock(): bool
    {
        try {
            return $this->getLock()->block($this->lockDurationSeconds);
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
        if ($this->lock) {
            return $this->lock;
        }

        /** @var LockProvider $provider */
        $provider = $this->cache->getStore();

        return $this->lock = $provider->lock(self::KEY . '.lock');
    }

    private function isCacheTableNotFoundException(\Throwable $th): bool
    {
        if (! $th instanceof QueryException) {
            return false;
        }

        return $th->getCode() === '42S02' && Str::contains($th->getMessage(), $this->lockTable);
    }
}
