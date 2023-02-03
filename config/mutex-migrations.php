<?php

return [
    'queue' => [
        // the cache store to use to manage queued migrations; use stores that
        // are available across application instances, such as 'database', or
        // 'redis' to ensure migrations are mutually exclusive. N.B. mutually
        // exclusive migrations using the 'database' store can only work after
        // the store's `cache` table has been created (by a standard migration!)
        'store' => env('MUTEX_MIGRATIONS_STORE', 'database'),

        // the maximum number of seconds a mutex migration should wait while
        // trying to acquire a lock - effectively the time an instance of a
        // mutex migration has to complete - before an exception is thrown
        'ttl_seconds' => env('MUTEX_MIGRATIONS_TTL_SECONDS', 60),
    ]
];
