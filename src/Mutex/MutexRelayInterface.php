<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

interface MutexRelayInterface
{
    public function acquireLock(): bool;

    public function releaseLock(): bool;
}
