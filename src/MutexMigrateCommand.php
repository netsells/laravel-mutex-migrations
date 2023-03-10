<?php

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Processors\MigrationProcessorFactory;
use Netsells\LaravelMutexMigrations\Processors\MigrationProcessorInterface;
use Symfony\Component\Console\Input\InputOption;

class MutexMigrateCommand extends MigrateCommand
{
    public const OPTION_MUTEX = 'mutex';

    private MigrationProcessorInterface $processor;

    public static function getMutexOption(): array
    {
        return [self::OPTION_MUTEX, null, InputOption::VALUE_NONE, 'Run a mutually exclusive migration'];
    }

    public function __construct(
        Migrator $migrator,
        Dispatcher $dispatcher,
        private readonly MigrationProcessorFactory $factory
    ) {
        parent::__construct($migrator, $dispatcher);
    }

    public function handle()
    {
        $this->processor = $this->createProcessor();

        $this->trap([SIGINT, SIGTERM], fn () => $this->processor->terminate());

        try {
            $this->processor->start();

            return parent::handle();
        } catch (DatabaseCacheTableNotFoundException $e) {
            $this->components->error($e->getMessage());

            throw $e;
        } finally {
            $this->processor->terminate();
        }
    }

    private function createProcessor(): MigrationProcessorInterface
    {
        return $this->factory->create($this->components);
    }
}
