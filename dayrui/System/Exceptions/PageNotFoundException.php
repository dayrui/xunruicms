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

namespace CodeIgniter\Exceptions;

class PageNotFoundException extends RuntimeException implements HTTPExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 404;

    /**
     * @return static
     */
    public static function forPageNotFound(?string $message = null)
    {
        return new static($message ?? lang('页面未找到'));
    }

    /**
     * @return static
     */
    public static function forControllerNotFound(string $controller)
    {
        return new static(lang('控制器(%s)不存在', $controller));
    }

    /**
     * @return static
     */
    public static function forMethodNotFound(string $controller, string $method)
    {
        return new static(lang('控制器(%s)的方法(%s)不存在', $controller, $method));
    }


}
