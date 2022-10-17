<?php

namespace Netsells\LaravelMutexMigrations\Tests\Integration;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Spatie\Fork\Fork;

class CommandConcurrencyTest extends AbstractIntegrationTest
{
    public function testCommandCanBeCalledConcurrently(): void
    {
        $app = $this->app[Kernel::class];

        $dir = __DIR__ . '/migrations';

        [$call1, $call2] = Fork::new()
            ->run(
                fn () => $app->call("migrate --mutex --path={$dir}/create_bars_table.php --realpath"),
                fn () => $app->call("migrate --mutex --path={$dir}/create_foos_table.php --realpath"),
            );

        $this->assertSame(Command::SUCCESS, $call1);
        $this->assertSame(Command::SUCCESS, $call2);
    }
}
