<?php

return [
    'command' => [

        // configuration for running migrations in maintenance mode
        'down' => [
            // options for the artisan down command called during a down
            // migration @see \Illuminate\Foundation\Console\DownCommand
            'options' => [
                // The path that users should be redirected to
                '--redirect' => null,
                // The view that should be pre-rendered for display during
                // maintenance mode
                '--render' => null,
                // The number of seconds after which the request may be retried
                '--retry' => null,
                // The number of seconds after which the browser may refresh
                '--refresh' => null,
                // The secret phrase that may be used to bypass maintenance mode
                '--secret' => null,
                // The status code that should be used when returning the
                // maintenance mode response
                '--status' => null,
            ],

            // preserves maintenance mode after an exception during a down
            // migration
            'sticky' => true,
        ],
    ],

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
