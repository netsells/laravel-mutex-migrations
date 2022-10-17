<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;

class MutexQueue
{
    public const KEY = 'laravel-mutex-migrations';

    public function __construct(
        private readonly Repository $cache,
        private readonly int $ttl_seconds = 60
    ) {
        //
    }

    public function push(string $item, array $meta = []): bool
    {
        if ($this->contains($item)) {
            return false;
        }

        return $this->withAtomicLock(function () use ($item, $meta) {
            $items = $this->getItems();
            $items[$item] = $meta;

            return $this->putItems($items);
        });
    }

    public function pull(string $item): bool
    {
        if (! $this->contains($item)) {
            return false;
        }

        return $this->withAtomicLock(function () use ($item) {
            $filteredItems = array_filter(
                $this->getItems(),
                fn (string $key) => $key !== $item,
                ARRAY_FILTER_USE_KEY
            );

            return empty($filteredItems)
                ? $this->cache->forget(self::KEY)
                : $this->putItems($filteredItems);
        });
    }

    public function contains(string|callable $item): bool
    {
        if (is_string($item)) {
            return in_array($item, array_keys($this->getItems()));
        }

        return ! empty(array_filter($this->getItems(), $item, ARRAY_FILTER_USE_BOTH));
    }

    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }

    public function isFirst(string $item): bool
    {
        $keys = array_keys($this->getItems());

        return reset($keys) === $item;
    }

    private function withAtomicLock(callable $callback)
    {
        /** @var LockProvider $provider */
        $provider = $this->cache->getStore();

        return $provider->lock(self::KEY . '.lock', 3)->block(2, $callback);
    }

    private function getItems(): array
    {
        return $this->cache->get(self::KEY, []);
    }

    private function putItems(array $items): bool
    {
        return $this->cache->put(self::KEY, $items, $this->ttl_seconds);
    }
}
