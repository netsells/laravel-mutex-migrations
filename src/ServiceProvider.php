<?php

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Netsells\LaravelMutexMigrations\Mutex\MutexQueue;
use Netsells\LaravelMutexMigrations\Processors\MigrationProcessorFactory;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeStateFactory;

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

        $this->app->bind(MaintenanceModeStateFactory::class, function ($app) {
            return new MaintenanceModeStateFactory(
                Config::get('mutex-migrations.command.down')
            );
        });

        $this->app->singleton(MutexQueue::class, function ($app) {
            return new MutexQueue(
                Cache::store(Config::get('mutex-migrations.queue.store')),
                Config::get('mutex-migrations.queue.ttl_seconds')
            );
        });

        $this->app->bind(MigrateCommand::class, function ($app) {
            return new MigrateCommandExtension(
                $app['migrator'],
                $app[Dispatcher::class],
                $app[MigrationProcessorFactory::class]
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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mutex-migrations.php' => config_path('mutex-migrations.php'),
            ], 'mutex-migrations-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            MaintenanceModeStateFactory::class,
            MigrateCommand::class,
            MutexQueue::class,
        ];
    }
}
