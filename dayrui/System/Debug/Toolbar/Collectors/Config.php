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

namespace CodeIgniter\Debug\Toolbar\Collectors;

/**
 * Debug toolbar configuration
 */
class Config
{
    /**
     * Return toolbar config values as an array.
     */
    public static function display(): array
    {

        return [
            'ciVersion'   => FRAME_VERSION,
            'phpVersion'  => PHP_VERSION,
            'phpSAPI'     => PHP_SAPI,
            'environment' => ENVIRONMENT,
            'baseURL'     => dr_now_url(),
        ];
    }
}
