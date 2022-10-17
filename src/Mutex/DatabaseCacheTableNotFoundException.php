<?php

namespace Netsells\LaravelMutexMigrations\Mutex;

class DatabaseCacheTableNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct(
            'Mutex migrations cannot be run using the database store until the required cache tables have been created. Run `php artisan cache:table` to create the required migration followed by a standard migration to create the tables.'
        );
    }
}
