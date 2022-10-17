<?php

namespace Netsells\LaravelMutexMigrations\Processors\State;

class MigrateUpState implements MaintenanceModeState
{
    public function activate(): void
    {
        //
    }

    public function deactivate(bool $exceptionOccurred): void
    {
        //
    }
}
