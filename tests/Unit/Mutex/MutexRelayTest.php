<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Mutex;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\Filesystem;
use Netsells\LaravelMutexMigrations\Mutex\DatabaseCacheTableNotFoundException;
use Netsells\LaravelMutexMigrations\Mutex\MutexRelay;
use Netsells\LaravelMutexMigrations\Tests\Unit\Mutex\fixtures\TestPDOException;
use PHPUnit\Framework\TestCase;

class MutexRelayTest extends TestCase
{
    private Repository $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(
            new FileStore(new Filesystem(), __DIR__ . '/cache')
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cache->flush();
    }

    public function testAcquireLockHandlesMissingCacheTable(): void
    {
        $store = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->expects($this->once())
            ->method('lock')
            ->willThrowException(new QueryException(
                'select * from cache_locks',
                [],
                new TestPDOException('Base table or view not found', '42S02')
            ));

        $this->expectException(DatabaseCacheTableNotFoundException::class);

        /** @var \Illuminate\Contracts\Cache\Store $store */
        (new MutexRelay(new Repository($store)))->acquireLock();
    }

    public function testAcquireLockHandlesUnexpectedExceptions(): void
    {
        $lock = $this->getMockBuilder(Lock::class)
            ->getMock();

        $lock->expects($this->once())
            ->method('block')
            ->willThrowException(new \Exception());

        $store = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->expects($this->once())
            ->method('lock')
            ->willReturn($lock);

        $this->expectException(\Exception::class);

        /** @var \Illuminate\Contracts\Cache\Store $store */
        (new MutexRelay(new Repository($store)))->acquireLock();
    }

    public function testAcquireLockThrowsLockTimeoutException(): void
    {
        $relay = new MutexRelay($this->cache, -1);

        $this->assertTrue($relay->acquireLock());

        $this->expectException(LockTimeoutException::class);

        $relay->acquireLock();
    }

    public function testReleaseLockHandlesUnacquiredLock(): void
    {
        $relay = new MutexRelay($this->cache);

        $this->assertFalse($relay->releaseLock());
    }

    public function testReleaseLockUnblocksOtherRelays(): void
    {
        $relay1 = new MutexRelay($this->cache, 1);
        $relay2 = new MutexRelay($this->cache, 1);

        $this->assertTrue($relay1->acquireLock());

        // exception occurs since the lock isn't released before the initial
        // acquire attempt expires
        $this->expectException(LockTimeoutException::class);
        $relay2->acquireLock();

        $this->assertTrue($relay1->releaseLock());

        $this->assertTrue($relay2->acquireLock());
    }
}
