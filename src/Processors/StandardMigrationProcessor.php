<?php

namespace Netsells\LaravelMutexMigrations\Processors;

use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeState;

class StandardMigrationProcessor implements MigrationProcessorInterface
{
    public function __construct(private readonly MaintenanceModeState $state)
    {
        //
    }

    public function start(): void
    {
        $this->state->activate();
    }

    public function terminate(bool $exceptionOccurred): void
    {
        $this->state->deactivate($exceptionOccurred);
    }
}
