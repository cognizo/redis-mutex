# RedisMutex

RedisMutex is a simple Redis-backed distributed mutex for PHP.

## Basic Usage

    <?php

    use Cognizo\RedisMutex\Lock;

    if (Lock::get('myLock'))
    {
        // Do something exclusively

        Lock::release('myLock');
    }
    else
    {
        echo "Couldn't get exclusive lock!";
    }

You can also wait for a lock to be released:

    // Wait up to 30 seconds for 'myLock' to become available
    Lock::get('myLock', 30);

Specifying the Redis connection:

    define('REDIS_PATH', 'tcp://127.0.0.1:6379');

    // Or

    define('REDIS_HOST', '127.0.0.1');
    define('REDIS_PORT', 6379);

## Install

Install the cognizo/redis-mutex package with [Composer](http://getcomposer.org/).
