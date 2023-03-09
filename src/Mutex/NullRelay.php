<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

class NullRelay implements MutexRelayInterface
{
    public function acquireLock(): bool
    {
        return false;
    }

    public function releaseLock(): bool
    {
        return false;
    }
}
