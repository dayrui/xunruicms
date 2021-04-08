<?php namespace Config;
/**
 * www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统
 **/

use CodeIgniter\Config\BaseConfig;
use Kint\Renderer\Renderer;

/**
 * --------------------------------------------------------------------------
 * Kint
 * --------------------------------------------------------------------------
 *
 * We use Kint's `RichRenderer` and `CLIRenderer`. This area contains options
 * that you can set to customize how Kint works for you.
 *
 * @see https://kint-php.github.io/kint/ for details on these settings.
 */
class Kint extends BaseConfig
{
    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */

    public $plugins = null;

    public $maxDepth = 6;

    public $displayCalledFrom = true;

    public $expanded = false;

    /*
    |--------------------------------------------------------------------------
    | RichRenderer Settings
    |--------------------------------------------------------------------------
    */
    public $richTheme = 'aante-light.css';

    public $richFolder = false;

    public $richSort = Renderer::SORT_FULL;

    public $richObjectPlugins = null;

    public $richTabPlugins = null;

    /*
    |--------------------------------------------------------------------------
    | CLI Settings
    |--------------------------------------------------------------------------
    */
    public $cliColors = true;

    public $cliForceUTF8 = false;

    public $cliDetectWidth = true;

    public $cliMinWidth = 40;
}
