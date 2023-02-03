<?php

namespace Netsells\LaravelMutexMigrations\Processors;

interface MigrationProcessorInterface
{
    public function start(): void;

    public function terminate(): void;
}
