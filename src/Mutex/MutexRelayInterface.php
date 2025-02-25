<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Mutex;

interface MutexRelayInterface
{
    public function acquireLock(): bool;

    public function releaseLock(): bool;
}
