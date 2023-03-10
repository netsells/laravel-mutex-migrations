Mutex Migrations for Laravel
============================

Run mutually exclusive migrations from more than one server at a time.

Using Laravel's functionality for [Atomic Locks](https://laravel.com/docs/9.x/cache#atomic-locks), this package extends the built-in `MigrateCommand` class to allow migrations to be run safely when there is the chance that they may be run concurrently against the same database.

## Installation

Install the package with:

`composer require netsells/laravel-mutex-migrations`

Optionally publish the package config file:

`php artisan vendor:publish --tag=mutex-migrations-config`

## Usage

Running a mutex migration using the default `database` store requires the existence of a table to store cache locks. If it does not exist the command will automatically fallback to a standard migration.

`php artisan migrate --mutex`

If two or more migrations happen to run concurrently, the first to acquire a lock will block the next one from running until it has finished, or until the lock times out - after 60 seconds, by default.

## Testing

`./vendor/bin/phpunit`
