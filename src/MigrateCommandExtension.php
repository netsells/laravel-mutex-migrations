<?php

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;

class MigrateCommandExtension extends MigrateCommand
{
    public function __construct(Migrator $migrator, Dispatcher $dispatcher)
    {
        parent::__construct($migrator, $dispatcher);

        parent::addOption(...MutexMigrateCommand::getMutexOption());
    }

    public function handle(): int
    {
        if ($this->option(MutexMigrateCommand::OPTION_MUTEX)) {
            try {
                return $this->call(MutexMigrateCommand::class, $this->getCommandOptions());
            } catch (DatabaseCacheTableNotFoundException $e) {
                $this->components->warn('Falling back to a standard migration');
            }
        }

        return parent::handle();
    }

    private function getCommandOptions(): array
    {
        return Collection::make($this->options())
            ->reject(fn ($value, $key) => $key === MutexMigrateCommand::OPTION_MUTEX)
            ->mapWithKeys(fn ($value, $key) => ["--$key" => $value])
            ->all();
    }
}
