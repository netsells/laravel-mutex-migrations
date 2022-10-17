<?php

namespace Netsells\LaravelMutexMigrations;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Netsells\LaravelMutexMigrations\Processors\MigrationProcessorFactory;
use Netsells\LaravelMutexMigrations\Processors\MigrationProcessorInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputOption;

class MigrateCommandExtension extends MigrateCommand implements SignalableCommandInterface
{
    private const OPTION_DOWN = 'down';

    private const OPTION_MUTEX = 'mutex';

    private MigrationProcessorInterface $processor;

    public function __construct(
        Migrator $migrator,
        Dispatcher $dispatcher,
        private readonly MigrationProcessorFactory $factory
    ) {
        $this->extendSignature();

        parent::__construct($migrator, $dispatcher);
    }

    public function handle()
    {
        $this->processor = $this->createProcessor();

        try {
            $this->processor->start();

            return parent::handle();
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            return self::FAILURE;
        } finally {
            $this->processor->terminate(isset($th));
        }
    }

    private function createProcessor(): MigrationProcessorInterface
    {
        return $this->factory->create(
            $this->option(self::OPTION_MUTEX),
            $this->option(self::OPTION_DOWN),
            $this,
            $this->components
        );
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $this->processor->terminate(false);
    }

    private function extendSignature(): void
    {
        $this->signature = join(PHP_EOL, array_merge(
            [$this->signature],
            array_map(function ($option) {
                return '{--' . join(" : ", [$option[0], $option[3]]) . '}';
            }, $this->getAdditionalOptions())
        ));
    }

    /**
     * Additional options to add to the command.
     *
     * @return array
     */
    private function getAdditionalOptions(): array
    {
        return [
            [self::OPTION_MUTEX, null, InputOption::VALUE_NONE, 'Run a mutually exclusive migration'],
            [self::OPTION_DOWN, null, InputOption::VALUE_NONE, 'Enable maintenance mode during a migration']
        ];
    }
}
