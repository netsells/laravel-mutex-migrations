<?php

namespace Netsells\LaravelMutexMigrations\Processors\State;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Support\Collection;

class MigrateDownState implements MaintenanceModeState
{
    public function __construct(
        private readonly Command $command,
        private readonly Factory $components,
        private readonly array $options,
        private readonly bool $isSticky
    ) {
        //
    }

    public function activate(): void
    {
        if ($this->down() !== Command::SUCCESS) {
            throw new MaintenanceModeStateException('An error occurred enabling maintenance mode');
        }
    }

    public function deactivate(bool $exceptionOccurred): void
    {
        if ($exceptionOccurred && $this->isSticky) {
            $this->components->warn(
                'Application will remain in maintenance mode due to an exception'
            );

            return;
        }

        if ($this->up() !== Command::SUCCESS) {
            throw new MaintenanceModeStateException('An error occurred disabling maintenance mode');
        }
    }

    private function down(): int
    {
        return $this->command->call(DownCommand::class, $this->getApplicableOptions());
    }

    private function up(): int
    {
        return $this->command->call(UpCommand::class);
    }

    private function getApplicableOptions(): array
    {
        return Collection::make($this->options)
            ->reject(fn ($option) => is_null($option))
            ->all();
    }
}
