<?php namespace Phpcmf;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 考虑兼容继承Events
 */
class Hooks extends \CodeIgniter\Events\Events
{

    protected static $initialized_hook = false;

    /**
     * 重定义钩子类
     */
    public static function initialize()
    {
        // 防止重复加载
        if (static::$initialized_hook)
        {
            return;
        }

        // 框架主钩子
        require ROOTPATH.'config/hooks.php';

        // 加载全部插件的钩子
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock') && is_file($path.'Config/Hooks.php')) {
                require $path.'Config/Hooks.php';
            }
        }

        self::on('pre_system', function () {
            while (\ob_get_level() > 0)
            {
                \ob_end_flush();
            }

            \ob_start(function ($buffer) {
                return $buffer;
            });

            /*
             * --------------------------------------------------------------------
             * Debug Toolbar Listeners.
             * --------------------------------------------------------------------
             * If you delete, they will no longer be collected.
             */
            if (CI_DEBUG)
            {
                self::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
                \Config\Services::toolbar()->respond();
            }
        });

        static::$initialized = true;
        static::$initialized_hook = true;
    }

}

