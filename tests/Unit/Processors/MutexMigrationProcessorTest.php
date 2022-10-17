<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Processors;

use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;
use Netsells\LaravelMutexMigrations\Processors\MutexMigrationProcessor;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeState;
use Netsells\LaravelMutexMigrations\Processors\State\MigrateDownState;
use Netsells\LaravelMutexMigrations\Processors\State\MigrateUpState;
use PHPUnit\Framework\TestCase;

class MutexMigrationProcessorTest extends TestCase
{
    private MaintenanceModeState $downState;

    private MaintenanceModeState $upState;

    private Factory $components;

    private MutexRelay $relay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->downState = $this->getMockBuilder(MigrateDownState::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->upState = $this->getMockBuilder(MigrateUpState::class)
            ->getMock();

        $this->components = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->addMethods(['info', 'warn'])
            ->getMock();

        $this->relay = $this->getMockBuilder(MutexRelay::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testStartInMigrateUpMode(): void
    {
        $this->upState->expects($this->once())->method('activate');

        $this->components->expects($this->exactly(2))->method('info');

        $this->relay->expects($this->once())
            ->method('acquireLock')
            ->with(['down' => false])
            ->will($this->returnValue(true));

        $this->getProcessorInstance($this->upState)->start();
    }

    public function testStartInMigrateDownMode(): void
    {
        $this->downState->expects($this->once())->method('activate');

        $this->components->expects($this->exactly(2))->method('info');

        $this->relay->expects($this->once())
            ->method('acquireLock')
            ->with(['down' => true])
            ->will($this->returnValue(true));

        $this->getProcessorInstance($this->downState)->start();
    }

    /**
     * @dataProvider terminateLoneInMigrateUpModeProvider
     */
    public function testTerminateLoneInMigrateUpMode(bool $hasException): void
    {
        $this->relay->expects($this->once())
            ->method('releaseLock')
            ->will($this->returnValue(true));

        $this->components->expects($this->once())->method('info');

        $this->relay->expects($this->once())
            ->method('hasOwnerWithMeta')
            ->will($this->returnValue(false));

        $this->upState->expects($this->once())
            ->method('deactivate')
            ->with($hasException);

        $this->getProcessorInstance($this->upState)->terminate($hasException);
    }

    public function terminateLoneInMigrateUpModeProvider(): array
    {
        return [
            'terminate with exception' => [
                'hasException' => true,
            ],
            'terminate without exception' => [
                'hasException' => false,
            ],
        ];
    }

    /**
     * @dataProvider terminateLoneInMigrateDownModeProvider
     */
    public function testTerminateLoneInMigrateDownMode(bool $hasException): void
    {
        $this->relay->expects($this->once())
            ->method('releaseLock')
            ->will($this->returnValue(true));

        $this->components->expects($this->once())->method('info');

        $this->relay->expects($this->once())
            ->method('hasOwnerWithMeta')
            ->will($this->returnValue(false));

        $this->downState->expects($this->once())
            ->method('deactivate')
            ->with($hasException);

        $this->getProcessorInstance($this->downState)->terminate($hasException);
    }

    public function terminateLoneInMigrateDownModeProvider(): array
    {
        return [
            'terminate with exception' => [
                'hasException' => true,
            ],
            'terminate without exception' => [
                'hasException' => false,
            ],
        ];
    }

    /**
     * @dataProvider terminateMultipleInMigrateUpModeProvider
     */
    public function testTerminateMultipleInMigrateUpMode(bool $hasException): void
    {
        $this->relay->expects($this->once())
            ->method('releaseLock')
            ->will($this->returnValue(true));

        $this->components->expects($this->once())->method('info');

        $this->relay->expects($this->once())
            ->method('hasOwnerWithMeta')
            ->will($this->returnValue(true));

        $this->upState->expects($this->never())
            ->method('deactivate');

        $this->getProcessorInstance($this->upState)->terminate($hasException);
    }

    public function terminateMultipleInMigrateUpModeProvider(): array
    {
        return [
            'terminate with exception' => [
                'hasException' => true,
            ],
            'terminate without exception' => [
                'hasException' => false,
            ],
        ];
    }

    /**
     * @dataProvider terminateMultipleInMigrateDownModeProvider
     */
    public function testTerminateMultipleInMigrateDownMode(bool $hasException): void
    {
        $this->relay->expects($this->once())
            ->method('releaseLock')
            ->will($this->returnValue(true));

        $this->components->expects($this->exactly(2))->method('info');

        $this->relay->expects($this->once())
            ->method('hasOwnerWithMeta')
            ->will($this->returnValue(true));

        $this->downState->expects($this->never())->method('deactivate');

        $this->getProcessorInstance($this->downState)->terminate($hasException);
    }

    public function terminateMultipleInMigrateDownModeProvider(): array
    {
        return [
            'terminate with exception' => [
                'hasException' => true,
            ],
            'terminate without exception' => [
                'hasException' => false,
            ],
        ];
    }

    public function testStartThrowsSpecificException(): void
    {
        $this->upState->expects($this->once())->method('activate');

        $this->components->expects($this->once())->method('info');

        $this->relay->expects($this->once())
            ->method('acquireLock')
            ->with(['down' => false])
            ->willThrowException(new DatabaseCacheTableNotFoundException());

        $this->expectException(DatabaseCacheTableNotFoundException::class);

        $this->getProcessorInstance($this->upState)->start();
    }

    private function getProcessorInstance(MaintenanceModeState $state): MutexMigrationProcessor
    {
        return new MutexMigrationProcessor($state, $this->components, $this->relay);
    }
}
