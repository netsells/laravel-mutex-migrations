<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

class NullRelay implements MutexRelayInterface
{
    public function acquireLock(array $meta = [], callable $feedback = null): bool
    {
        return false;
    }

    public function releaseLock(): bool
    {
        return false;
    }

    public function hasOwnerWithMeta(array $meta): bool
    {
        return false;
    }
}
