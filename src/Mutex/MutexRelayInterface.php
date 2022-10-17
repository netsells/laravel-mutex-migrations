<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

interface MutexRelayInterface
{
    public function acquireLock(array $meta = [], callable $feedback = null): bool;

    public function releaseLock(): bool;

    public function hasOwnerWithMeta(array $meta): bool;
}
