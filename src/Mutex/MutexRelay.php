<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Mutex;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class MutexRelay implements MutexRelayInterface
{
    public const DEFAULT_LOCK_TABLE = 'cache_locks';

    public const DEFAULT_TTL_SECONDS = 60;

    public const KEY = 'laravel-mutex-migrations';

    private ?Lock $lock = null;

    public function __construct(
        private readonly Repository $cache,
        private readonly int $lockDurationSeconds = self::DEFAULT_TTL_SECONDS,
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

        /** @var \Illuminate\Contracts\Cache\LockProvider $store */
        $store = $this->cache->getStore();

        return $this->lock = $store->lock(self::KEY . '.lock');
    }

    private function isCacheTableNotFoundException(\Throwable $th): bool
    {
        if (! $th instanceof QueryException) {
            return false;
        }

        return Str::contains($th->getMessage(), $this->lockTable) && \in_array($th->getCode(), [
            '42S02', // mysql
            'HY000', // sqlite
        ], true);
    }
}
