<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class PackageProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->mergeConfigFrom(__DIR__.'/../config/mutex-migrations.php', 'mutex-migrations');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/mutex-migrations.php' => config_path('mutex-migrations.php'),
        ], 'mutex-migrations-config');
    }
}
