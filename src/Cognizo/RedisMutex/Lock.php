<?php

/*
 * This file is part of the Cognizo\RedisMutex package.
 *
 * (c) Graham Floyd <gfloyd@catsone.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cognizo\RedisMutex;

class Lock
{
    const KEY_PREFIX = 'redis-mutex';
    const MAX_LOCK_TIME = 10;
    const USLEEP_TIME = 100000;

    /**
     * @var \Credis_Client
     */
    protected static $_redis;

    /**
     * Lock tokens.
     *
     * @var array
     */
    protected static $_tokens;

    /**
     * Gets a lock, optionally waiting for it to become available. Returns true if the lock is acquired, false if the
     * lock is not available before the specified timeout.
     *
     * @param string $key Key to lock
     * @param bool|int $timeout Time to wait for lock in seconds
     * @param bool|int $maxLockTime Maximum time the lock can be held if acquired
     * @return bool
     */
    public static function get($key, $timeout = false, $maxLockTime = self::MAX_LOCK_TIME)
    {
        $key = self::KEY_PREFIX . ":{$key}";

        $start = time();

        self::$_tokens[$key] = uniqid('', true);

        do
        {
            $result = self::_getRedis()->setNx($key, self::$_tokens[$key]);

            if ($result)
            {
                if (is_numeric($maxLockTime))
                {
                    self::_getRedis()->expire($key, $maxLockTime);
                }

                return true;
            }
            else if (!is_numeric($timeout))
            {
                return false;
            }

            usleep(self::USLEEP_TIME);
        }
        while (is_numeric($timeout) && time() < $start + $timeout);

        return false;
    }

    /**
     * Release a lock.
     *
     * @param string $key Key to release
     * @return bool
     */
    public static function release($key)
    {
        $key = self::KEY_PREFIX . ":{$key}";

        $result = self::_getRedis()->get($key);

        if (!$result)
        {
            unset(self::$_tokens[$key]);

            return true;
        }

        if ($result === self::$_tokens[$key])
        {
            self::_getRedis()->del($key);

            unset(self::$_tokens[$key]);

            return true;
        }

        return false;
    }

    /**
     * Get an instance of the Credis client.
     *
     * @return \Credis_Client
     */
    protected static function _getRedis()
    {
        if (self::$_redis === null)
        {
            if (defined('REDIS_PATH'))
            {
                self::$_redis = new \Credis_Client(REDIS_PATH);
            }
            else if (defined('REDIS_HOST') && defined('REDIS_PORT'))
            {
                self::$_redis = new \Credis_Client(REDIS_HOST, REDIS_PORT);
            }
            else
            {
                self::$_redis = new \Credis_Client();
            }
        }

        return self::$_redis;
    }
}
