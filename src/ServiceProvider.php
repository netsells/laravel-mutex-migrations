<?php

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Netsells\LaravelMutexMigrations\MigrateCommandExtension;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;

class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->mergeConfigFrom(__DIR__.'/../config/mutex-migrations.php', 'mutex-migrations');

        $this->app->bind(MigrateCommand::class, MigrateCommandExtension::class);

        $this->app->when([MigrateCommandExtension::class, MutexMigrateCommand::class])
            ->needs(Migrator::class)
            ->give(function ($app) {
                return $app['migrator'];
            });

        $this->app->bind(MutexRelay::class, function ($app) {
            return new MutexRelay(
                Cache::store(Config::get('mutex-migrations.lock.store')),
                Config::get('mutex-migrations.lock.ttl_seconds')
            );
        });
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/mutex-migrations.php' => config_path('mutex-migrations.php'),
        ], 'mutex-migrations-config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            MigrateCommand::class,
            MutexMigrateCommand::class,
            MutexRelay::class,
        ];
    }
}
