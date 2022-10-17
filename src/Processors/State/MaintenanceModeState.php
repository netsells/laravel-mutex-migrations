<?php

namespace Netsells\LaravelMutexMigrations\Processors\State;

interface MaintenanceModeState
{
    public function activate(): void;

    public function deactivate(bool $exceptionOccurred): void;
}
