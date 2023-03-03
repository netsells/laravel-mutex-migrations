<?php

namespace Netsells\LaravelMutexMigrations\Tests\Unit\Mutex\Fixtures;

class TestPDOException extends \PDOException
{
    public function __construct(string $message, string $code)
    {
        parent::__construct($message);

        // this argument is type-hinted as int in parent class
        $this->code = $code;
    }
}
