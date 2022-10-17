<?php

namespace Netsells\LaravelMutexMigrations\Processors;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeStateFactory;

class MigrationProcessorFactory
{
    public function __construct(
        private readonly MaintenanceModeStateFactory $stateFactory,
        private readonly MutexRelay $relay,
    ) {
        //
    }

    public function create(bool $mutex, bool $down, Command $command, Factory $components): MigrationProcessorInterface
    {
        $state = $this->stateFactory->create($down, $command, $components);

        return $mutex
            ? new MutexMigrationProcessor($state, $components, $this->relay)
            : new StandardMigrationProcessor($state);
    }
}
