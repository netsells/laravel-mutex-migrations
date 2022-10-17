<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Processors;

use Netsells\LaravelMutexMigrations\Processors\StandardMigrationProcessor;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeState;
use PHPUnit\Framework\TestCase;

class StandardMigrationProcessorTest extends TestCase
{
    private MaintenanceModeState $state;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = $this->getMockBuilder(MaintenanceModeState::class)
            ->getMock();
    }

    public function testStart(): void
    {
        $this->state->expects($this->once())->method('activate');

        (new StandardMigrationProcessor($this->state))->start();
    }

    public function testTerminate(): void
    {
        $this->state->expects($this->once())->method('deactivate');

        (new StandardMigrationProcessor($this->state))->terminate(false);
    }
}
