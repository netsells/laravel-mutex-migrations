<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Mutex;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\Filesystem;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexQueue;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelayTimeoutException;
use PHPUnit\Framework\TestCase;
use Spatie\Fork\Fork;

class MutexRelayTest extends TestCase
{
    private MutexQueue $queue;

    private Repository $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(
            new FileStore(new Filesystem(), __DIR__ . '/cache')
        );

        $this->queue = new MutexQueue($this->cache);

        $this->relay = new MutexRelay($this->queue);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cache->flush();
    }

    public function testAcquireLockHandlesMissingCacheTable(): void
    {
        /** @var \Netsells\LaravelMutexMigrations\Mutex\MutexQueue $queue */
        $queue = $this->getMockBuilder(MutexQueue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue->expects($this->once())
            ->method('push')
            ->willThrowException(
                new QueryException('select * from cache', [], new \Exception('Base table or view not found'))
            );

        $this->expectException(DatabaseCacheTableNotFoundException::class);

        (new MutexRelay($queue))->acquireLock();
    }

    public function testAcquireLockHandlesUnexpectedExceptions(): void
    {
        /** @var \Netsells\LaravelMutexMigrations\Mutex\MutexQueue $queue */
        $queue = $this->getMockBuilder(MutexQueue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue->expects($this->once())
            ->method('push')
            ->willThrowException(new \Exception());

        $this->expectException(\Exception::class);

        (new MutexRelay($queue))->acquireLock();
    }

    public function testAcquireLockThrowsExceptionOnTimeout(): void
    {
        $relay = new MutexRelay(new MutexQueue($this->cache, -1));

        $this->expectException(MutexRelayTimeoutException::class);

        $relay->acquireLock();
    }

    public function testAcquireLockIsAcquiredOnce(): void
    {
        $this->assertTrue($this->relay->acquireLock());
        $this->assertFalse($this->relay->acquireLock());
    }

    public function testReleaseLockUnblocksOtherRelays(): void
    {
        $queue = new MutexQueue($this->cache, 1);

        $relay1 = new MutexRelay($queue);
        $relay2 = new MutexRelay($queue);

        $this->assertTrue($relay1->acquireLock());

        // exception occurs since the lock isn't released before the initial
        // acquire attempt expires
        $this->expectException(MutexRelayTimeoutException::class);
        $relay2->acquireLock();

        $this->assertTrue($relay1->releaseLock());

        $this->assertTrue($relay2->acquireLock());
    }

    public function testAcquireAndReleaseHandleConcurrentCalls(): void
    {
        $relay1 = new MutexRelay($this->queue);
        $relay2 = new MutexRelay($this->queue);

        [$result1, $result2] = Fork::new()->run(
            fn () => [$relay1->acquireLock(), $relay1->releaseLock()],
            fn () => [$relay2->acquireLock(), $relay2->releaseLock()],
        );

        $this->assertSame([true, true], $result1);
        $this->assertSame([true, true], $result2);
    }

    public function testHasOwnerWithMeta(): void
    {
        $this->assertFalse($this->relay->hasOwnerWithMeta(['foo' => 'bar']));

        $this->relay->acquireLock(['foo' => 'bar']);

        $this->assertFalse($this->relay->hasOwnerWithMeta(['foo' => 'baz']));

        $this->assertTrue($this->relay->hasOwnerWithMeta(['foo' => 'bar']));
    }
}
