<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Mutex;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Netsells\LaravelMutexMigrations\Mutex\MutexQueue;
use PHPUnit\Framework\TestCase;
use Spatie\Fork\Fork;

class MutexQueueTest extends TestCase
{
    private MutexQueue $queue;

    private Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(
            new FileStore(new Filesystem(), __DIR__ . '/cache')
        );

        $this->queue = new MutexQueue($this->cache);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cache->flush();
    }

    public function testPushAddsAnItemToTheQueue(): void
    {
        $item = Str::random();

        $this->queue->push($item);

        $this->assertArrayHasKey($item, $this->cache->get(MutexQueue::KEY));
    }

    /**
     * @dataProvider pushCanStoreItemMetaProvider
     */
    public function testPushCanStoreItemMeta($meta): void
    {
        $item = Str::random();

        $this->queue->push($item, $meta);

        $items = $this->cache->get(MutexQueue::KEY);

        $this->assertArrayHasKey($item, $items);
        $this->assertEquals($meta, $items[$item]);
    }

    public function pushCanStoreItemMetaProvider(): array
    {
        return [
            'Default meta is empty array' => [[]],
            'Given meta is stored' => [['foo' => 'bar']],
        ];
    }

    public function testPushHandlesConcurrentCalls(): void
    {
        $item1 = Str::random();
        $item2 = Str::random();

        [$result1, $result2] = Fork::new()->run(
            fn () => $this->queue->push($item1),
            fn () => $this->queue->push($item2),
        );

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        $this->assertArrayHasKey($item1, $this->cache->get(MutexQueue::KEY));
        $this->assertArrayHasKey($item2, $this->cache->get(MutexQueue::KEY));
    }

    public function testContainsItem(): void
    {
        $this->assertFalse($this->queue->contains(Str::random()));

        $item = Str::random();

        $this->queue->push($item);

        $this->assertTrue($this->queue->contains($item));
    }

    public function testContainsFilter(): void
    {
        $item = Str::random();

        $this->queue->push($item, ['foo' => 'bar']);

        $this->assertFalse(
            $this->queue->contains(fn ($value, $key) => $key === null)
        );

        $this->assertTrue(
            $this->queue->contains(fn ($value, $key) => $key === $item)
        );

        $this->assertTrue(
            $this->queue->contains(fn ($value, $key) => $value['foo'] = 'bar')
        );
    }

    public function testPullIgnoresNonExistentItems(): void
    {
        $this->assertFalse($this->queue->pull(Str::random()));
    }

    public function testPullRemovesItems(): void
    {
        $item = Str::random();

        $this->queue->push($item);
        $this->queue->push(Str::random());

        $this->assertArrayHasKey($item, $this->cache->get(MutexQueue::KEY));

        $this->assertTrue($this->queue->pull($item));

        $this->assertArrayNotHasKey($item, $this->cache->get(MutexQueue::KEY));
    }

    public function testPullLastItemFlushesQueue(): void
    {
        $item = Str::random();

        $this->queue->push($item);

        $this->assertArrayHasKey($item, $this->cache->get(MutexQueue::KEY));

        $this->assertTrue($this->queue->pull($item));

        $this->assertNull($this->cache->get(MutexQueue::KEY));
    }

    public function testPullHandlesConcurrentCalls(): void
    {
        $item1 = Str::random();
        $item2 = Str::random();

        $this->queue->push($item1);
        $this->queue->push($item2);

        [$result1, $result2] = Fork::new()->run(
            fn () => $this->queue->pull($item1),
            fn () => $this->queue->pull($item2),
        );

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        $this->assertNull($this->cache->get(MutexQueue::KEY));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->queue->isEmpty());

        $this->queue->push(Str::random());

        $this->assertFalse($this->queue->isEmpty());
    }

    public function testIsFirst(): void
    {
        $this->assertFalse($this->queue->isFirst(Str::random()));

        $item1 = Str::random();
        $item2 = Str::random();

        $this->queue->push($item1);

        $this->assertTrue($this->queue->isFirst($item1));

        $this->queue->push($item2);

        $this->assertTrue($this->queue->isFirst($item1));
        $this->assertFalse($this->queue->isFirst($item2));

        $this->queue->pull($item1);

        $this->assertFalse($this->queue->isFirst($item1));
        $this->assertTrue($this->queue->isFirst($item2));

        $this->queue->pull($item2);

        $this->assertFalse($this->queue->isFirst($item2));
    }
}
