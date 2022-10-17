<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Processors\State;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;
use Netsells\LaravelMutexMigrations\Processors\State\MaintenanceModeStateException;
use Netsells\LaravelMutexMigrations\Processors\State\MigrateDownState;
use PHPUnit\Framework\TestCase;

class MigrateDownStateTest extends TestCase
{
    private Command $command;

    private Factory $components;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->components = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->addMethods(['info', 'warn'])
            ->getMock();
    }

    public function testActivateThrowsExceptionOnDownFailure(): void
    {
        $this->command->expects($this->once())
            ->method('call')
            ->with($this->equalTo(DownCommand::class))
            ->will($this->returnValue(Command::FAILURE));

        $this->expectException(MaintenanceModeStateException::class);

        $this->getStateInstance()->activate();
    }

    public function testActivate(): void
    {
        $this->command->expects($this->once())
            ->method('call')
            ->with($this->equalTo(DownCommand::class))
            ->will($this->returnValue(Command::SUCCESS));

        $this->getStateInstance()->activate();
    }

    public function testDeactivateWithExceptionWhenNotSticky(): void
    {
        $this->components->expects($this->never())->method('warn');

        $this->command->expects($this->once())
            ->method('call')
            ->with($this->equalTo(UpCommand::class))
            ->will($this->returnValue(Command::SUCCESS));

        $this->getStateInstance()->deactivate(true);
    }

    public function testDeactivateWithExceptionWhenSticky(): void
    {
        $this->components->expects($this->once())->method('warn');

        $this->command->expects($this->never())->method('call');

        $this->getStateInstance(true)->deactivate(true);
    }

    public function testDeactivateThrowsExceptionOnUpFailure(): void
    {
        $this->command->expects($this->once())
            ->method('call')
            ->with($this->equalTo(UpCommand::class))
            ->will($this->returnValue(Command::FAILURE));

        $this->expectException(MaintenanceModeStateException::class);

        $this->getStateInstance()->deactivate(false);
    }

    private function getStateInstance(bool $isSticky = false): MigrateDownState
    {
        return new MigrateDownState($this->command, $this->components, [], $isSticky);
    }
}
