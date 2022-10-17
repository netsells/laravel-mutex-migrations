<?php

namespace Netsells\LaravelMutexMigrations\Tests\Integration;

use Netsells\LaravelMutexMigrations\ServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class AbstractIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }
}
