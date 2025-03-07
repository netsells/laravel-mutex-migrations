<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Processors;

interface MigrationProcessorInterface
{
    public function start(): void;

    public function terminate(): void;
}
