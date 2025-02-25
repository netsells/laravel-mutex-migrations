<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Processors;

use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelayInterface;
use Netsells\LaravelMutexMigrations\Mutex\NullRelay;

class MutexMigrationProcessor implements MigrationProcessorInterface
{
    public function __construct(
        private readonly Factory $components,
        private MutexRelayInterface $relay
    ) {
        //
    }

    public function start(): void
    {
        $this->components->info('Attempting to acquire mutex lock');

        try {
            if ($this->relay->acquireLock()) {
                $this->components->info('Mutex lock acquired');
            } else {
                $this->components->info('A mutex lock could not be acquired');
            }
        } catch (DatabaseCacheTableNotFoundException $e) {
            // due to this particular exception we can't use the actual relay,
            // so we'll replace it to allow the process to be terminated cleanly
            $this->relay = new NullRelay();

            throw $e;
        }
    }

    public function terminate(): void
    {
        if ($this->relay instanceof NullRelay) {
            return;
        }

        if (! $this->relay->releaseLock()) {
            $this->components->info('The mutex lock was not released');

            return;
        }

        $this->components->info('Mutex lock released');
    }
}
