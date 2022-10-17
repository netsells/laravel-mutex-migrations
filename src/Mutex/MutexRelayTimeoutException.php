<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

class MutexRelayTimeoutException extends \Exception
{
    public function __construct(string $owner)
    {
        parent::__construct(
            "A lock could not be acquired for owner $owner as the item is no longer in the queue"
        );
    }
}
