<?php

declare(strict_types=1);

namespace Netsells\LaravelMutexMigrations\Commands;

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
        parent::addOption(...MutexMigrateCommand::getMutexGracefulOption());
    }

    public function handle(): int
    {
        if ($this->shouldUseMutex()) {
            try {
                return $this->call(MutexMigrateCommand::class, $this->getCommandOptions());
            } catch (DatabaseCacheTableNotFoundException $e) {
                if ($this->option(MutexMigrateCommand::OPTION_MUTEX)) {
                    return $this->options('graceful') ? self::SUCCESS : self::FAILURE;
                } elseif ($this->option(MutexMigrateCommand::OPTION_MUTEX_GRACEFUL)) {
                    $this->components->warn('Falling back to a standard migration');
                }
            }
        }

        return parent::handle();
    }

    private function shouldUseMutex(): bool
    {
        return $this->option(MutexMigrateCommand::OPTION_MUTEX)
            || $this->option(MutexMigrateCommand::OPTION_MUTEX_GRACEFUL);
    }

    private function getCommandOptions(): array
    {
        return Collection::make($this->options())
            ->reject(fn ($value, $key) => \in_array($key, [
                MutexMigrateCommand::OPTION_MUTEX,
                MutexMigrateCommand::OPTION_MUTEX_GRACEFUL,
            ]))
            ->mapWithKeys(fn ($value, $key) => ["--$key" => $value])
            ->all();
    }
}
