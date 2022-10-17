<?php

namespace Netsells\LaravelMutexMigrations\Processors;

use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelayInterface;
use Netsells\LaravelMutexMigrations\Mutex\NullRelay;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeState;
use Netsells\LaravelMutexMigrations\Processors\State\MigrateDownState;

class MutexMigrationProcessor implements MigrationProcessorInterface
{
    private bool $isDownMigration;

    public function __construct(
        private readonly MaintenanceModeState $state,
        private readonly Factory $components,
        private MutexRelayInterface $relay
    ) {
        $this->isDownMigration = $this->state instanceof MigrateDownState;
    }

    public function start(): void
    {
        $this->state->activate();

        $this->components->info('Attempting to acquire mutex lock');

        try {
            if ($this->acquireLock()) {
                $this->components->info('Mutex lock acquired');
            }
        } catch (DatabaseCacheTableNotFoundException $e) {
            // due to this particular exception we can't use the actual relay,
            // so we'll replace it to allow the process to be terminated cleanly
            $this->relay = new NullRelay();

            throw $e;
        }
    }

    private function acquireLock(): bool
    {
        return $this->relay->acquireLock(['down' => $this->isDownMigration], function () {
            $this->components->warn('...awaiting mutex lock');
        });
    }

    public function terminate(bool $exceptionOccurred): void
    {
        if ($this->relay->releaseLock()) {
            $this->components->info('Mutex lock released');
        }

        if ($this->shouldRestoreApplication()) {
            $this->state->deactivate($exceptionOccurred);
        }
    }

    private function shouldRestoreApplication(): bool
    {
        if (! $this->downMigrationsAreQueueing()) {
            return true;
        }

        if ($this->isDownMigration) {
            $this->components->info('Application will be restored by a queued migration');
        }

        return false;
    }

    private function downMigrationsAreQueueing(): bool
    {
        return $this->relay->hasOwnerWithMeta(['down' => true]);
    }
}
