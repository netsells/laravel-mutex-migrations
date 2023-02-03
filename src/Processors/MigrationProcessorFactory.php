<?php

namespace Netsells\LaravelMutexMigrations\Processors;

use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;

class MigrationProcessorFactory
{
    public function __construct(
        private readonly MutexRelay $relay,
    ) {
        //
    }

    public function create(Factory $components, bool $mutex): MigrationProcessorInterface
    {
        return $mutex
            ? new MutexMigrationProcessor($components, $this->relay)
            : new StandardMigrationProcessor();
    }
}
