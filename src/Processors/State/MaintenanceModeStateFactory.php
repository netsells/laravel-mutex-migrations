<?php

namespace Netsells\LaravelMutexMigrations\Processors\State;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;

class MaintenanceModeStateFactory
{
    public function __construct(private readonly array $config)
    {
        //
    }

    public function create(bool $down, Command $command, Factory $components): MaintenanceModeState
    {
        return $down ? $this->createMigrateDownState($command, $components) : new MigrateUpState();
    }

    private function createMigrateDownState(Command $command, Factory $components): MigrateDownState
    {
        return new MigrateDownState(
            $command,
            $components,
            $this->config['options'] ?? [],
            $this->config['sticky'] ?? true
        );
    }
}
