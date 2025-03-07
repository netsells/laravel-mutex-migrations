<?php

declare(strict_types=1);

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

    public function create(Factory $components): MigrationProcessorInterface
    {
        return new MutexMigrationProcessor($components, $this->relay);
    }
}
