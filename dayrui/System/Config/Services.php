<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Config;

use CodeIgniter\Cache\CacheFactory;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Debug\Exceptions;
use Config\Cache;
use Config\Exceptions as ExceptionsConfig;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This is used in place of a Dependency Injection container primarily
 * due to its simplicity, which allows a better long-term maintenance
 * of the applications built on top of CodeIgniter. A bonus side-effect
 * is that IDEs are able to determine what class you are calling
 * whereas with DI Containers there usually isn't a way for them to do this.
 *
 * @see http://blog.ircmaxell.com/2015/11/simple-easy-risk-and-change.html
 * @see http://www.infoq.com/presentations/Simple-Made-Easy
 * @see \CodeIgniter\Config\ServicesTest
 */
class Services extends BaseService
{
    /**
     * The cache class provides a simple way to store and retrieve
     * complex data for later.
     *
     * @return CacheInterface
     */
    public static function cache(?Cache $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('cache', $config);
        }

        $config ??= config(Cache::class);

        return CacheFactory::getHandler($config);
    }

    /**
     * The Exceptions class holds the methods that handle:
     *
     *  - set_exception_handler
     *  - set_error_handler
     *  - register_shutdown_function
     *
     * @return Exceptions
     */
    public static function exceptions(
        ?ExceptionsConfig $config = null,
        bool $getShared = true,
    ) {
        if ($getShared) {
            return static::getSharedInstance('exceptions', $config);
        }

        $config ??= config(ExceptionsConfig::class);

        return new Exceptions($config);
    }

}
