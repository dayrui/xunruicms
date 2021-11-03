<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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

        self::on('pre_system', function () {

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


    /**
     * 运行带返回参数的钩子点，其中某个钩子返回值时终止运行
     *
     * @param string $eventName
     * @param mixed  $arguments
     *
     * @return boolean | array
     */
    public static function trigger_callback($eventName, ...$arguments)
    {

        if (! static::$initialized)
        {
            static::initialize();
        }

        $listeners = static::listeners($eventName);
        if (!$listeners) {
            return false;
        }

        if (IS_POST && CI_DEBUG && !in_array($eventName, ['DBQuery', 'pre_system'])) {
            log_message('debug', '运行钩子【'.$eventName.'】'.count($listeners).'次');
        }

        foreach ($listeners as $listener) {

            $start = microtime(true);
            $rt = call_user_func($listener, ...$arguments);

            if (CI_DEBUG)
            {
                static::$performanceLog[] = [
                    'start' => $start,
                    'end'   => microtime(true),
                    'event' => strtolower($eventName),
                ];
            }

            if ($rt && isset($rt['code'])) {
                return $rt;
            }
        }

        return false;
    }

    /**
     * 运行不带返回参数的钩子点
     *
     * @param string $eventName
     * @param mixed  $arguments
     *
     * @return boolean
     */
    public static function trigger($eventName, ...$arguments): bool
    {
        // Read in our Config/Events file so that we have them all!
        if (! static::$initialized)
        {
            static::initialize();
        }

        $listeners = static::listeners($eventName);
        if (!$listeners) {
            return false;
        }

        if (IS_POST && CI_DEBUG && !in_array($eventName, ['DBQuery', 'pre_system'])) {
            log_message('debug', '运行钩子【'.$eventName.'】'.count($listeners).'次');
        }

        foreach ($listeners as $listener) {

            $start = microtime(true);
            $result = static::$simulate === false ? call_user_func($listener, ...$arguments) : true;

            if (CI_DEBUG)
            {
                static::$performanceLog[] = [
                    'start' => $start,
                    'end'   => microtime(true),
                    'event' => strtolower($eventName),
                ];
            }

            if ($result === false)
            {
                return false;
            }
        }

        return true;
    }
}

