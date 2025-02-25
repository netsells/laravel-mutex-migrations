<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Netsells\LaravelMutexMigrations\MigrateCommandExtension;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;

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
            MutexMigrateCommand::class,
            MutexRelay::class,
        ];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MigrateCommand::class, MigrateCommandExtension::class);

        $this->app->when([MigrateCommandExtension::class, MutexMigrateCommand::class])
            ->needs(Migrator::class)
            ->give(function ($app) {
                return $app['migrator'];
            });

        $this->app->bind(MutexRelay::class, function ($app) {
            $store = Config::get('mutex-migrations.lock.store');

            return new MutexRelay(
                cache: Cache::store($store),
                lockDurationSeconds: Config::get('mutex-migrations.lock.ttl_seconds'),
                lockTable: Config::get("cache.stores.{$store}.lock_table", MutexRelay::DEFAULT_LOCK_TABLE),
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
