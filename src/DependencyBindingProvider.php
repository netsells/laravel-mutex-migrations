<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Netsells\LaravelMutexMigrations\Commands;
use Netsells\LaravelMutexMigrations\Mutex;

class DependencyBindingProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            MigrateCommand::class,
            Commands\MutexMigrateCommand::class,
            Mutex\MutexRelay::class,
        ];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MigrateCommand::class, Commands\MigrateCommandExtension::class);

        $this->app->when([Commands\MigrateCommandExtension::class, Commands\MutexMigrateCommand::class])
            ->needs(Migrator::class)
            ->give(function ($app) {
                return $app['migrator'];
            });

        $this->app->bind(Mutex\MutexRelay::class, function ($app) {
            $store = Config::get('mutex-migrations.lock.store');

            return new Mutex\MutexRelay(
                cache: Cache::store($store),
                lockDurationSeconds: Config::get('mutex-migrations.lock.ttl_seconds')
                    ?? Mutex\MutexRelay::DEFAULT_TTL_SECONDS,
                lockTable: Config::get("cache.stores.{$store}.lock_table")
                    ?? Mutex\MutexRelay::DEFAULT_LOCK_TABLE,
            );
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        //
    }
}
