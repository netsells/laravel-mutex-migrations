<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Tests\Integration;

use Netsells\LaravelMutexMigrations\DependencyBindingProvider;
use Orchestra\Testbench\TestCase;

abstract class AbstractIntegrationTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DependencyBindingProvider::class,
        ];
    }
}
