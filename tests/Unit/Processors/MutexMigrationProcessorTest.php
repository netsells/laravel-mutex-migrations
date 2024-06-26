<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Processors;

use Illuminate\Console\View\Components\Factory;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;
use Netsells\LaravelMutexMigrations\Processors\MutexMigrationProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MutexMigrationProcessorTest extends TestCase
{
    private Factory $components;

    private MutexRelay|MockObject $relay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->components = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->relay = $this->getMockBuilder(MutexRelay::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testStart(): void
    {
        $this->components->expects($this->exactly(2))->method('__call');

        $this->relay->expects($this->once())
            ->method('acquireLock')
            ->willReturn(true);

        $this->getProcessorInstance()->start();
    }

    public function testStartThrowsSpecificException(): void
    {
        $this->components->expects($this->once())->method('__call');

        $this->relay->expects($this->once())
            ->method('acquireLock')
            ->willThrowException(new DatabaseCacheTableNotFoundException());

        $this->expectException(DatabaseCacheTableNotFoundException::class);

        $this->getProcessorInstance()->start();
    }

    public function testTerminate(): void
    {
        $this->components->expects($this->once())->method('__call');

        $this->relay->expects($this->once())
            ->method('releaseLock')
            ->willReturn(true);

        $this->getProcessorInstance()->terminate();
    }

    private function getProcessorInstance(): MutexMigrationProcessor
    {
        return new MutexMigrationProcessor($this->components, $this->relay);
    }
}
