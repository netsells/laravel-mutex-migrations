<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class MutexRelay implements MutexRelayInterface
{
    private string $owner;

    public function __construct(private readonly MutexQueue $queue)
    {
        $this->owner = Str::random();
    }

    public function acquireLock(array $meta = [], callable $feedback = null): bool
    {
        try {
            return $this->queue->push($this->owner, $meta);
        } catch (\Throwable $th) {
            if ($this->isCacheTableNotFoundException($th)) {
                throw new DatabaseCacheTableNotFoundException();
            }

            throw $th;
        } finally {
            // after pushing an item to the queue, we'll have a maximum of the
            // configured TTL value - 60 seconds by default - to acquire a lock
            // before the queued items expire
            while (! isset($th) && ! $this->queue->isFirst($this->owner)) {
                if (! $this->queue->contains($this->owner)) {
                    throw new MutexRelayTimeoutException($this->owner);
                }

                if (is_callable($feedback)) {
                    $feedback();
                };

                $wait = $this->backOff($wait ?? 1);
            }
        }
    }

    public function releaseLock(): bool
    {
        return $this->queue->pull($this->owner);
    }

    public function hasOwnerWithMeta(array $meta): bool
    {
        if ($this->queue->isEmpty()) {
            return false;
        }

        return $this->queue->contains(fn ($item) => $item === $meta);
    }

    private function isCacheTableNotFoundException(\Throwable $th): bool
    {
        if (! $th instanceof QueryException) {
            return false;
        }

        return Str::contains($th->getMessage(), ['Base table or view new found', 'cache'], true);
    }

    private function backOff(int $wait): int
    {
        sleep($wait++);

        return $wait;
    }
}
