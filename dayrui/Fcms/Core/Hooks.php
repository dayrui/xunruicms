<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 考虑兼容继承Events
 */
class Hooks extends \CodeIgniter\Events\Events {

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
        require CONFIGPATH.'hooks.php';

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
     * 插件中注册钩子
     */
    public static function app_on($app, $eventName, $callback, $priority = EVENT_PRIORITY_NORMAL)
    {
        if (! isset(static::$listeners[$eventName])) {
            static::$listeners[$eventName] = [
                true, // If there's only 1 item, it's sorted.
                [$priority],
                [$callback],
                [$app],
            ];
        } else {
            static::$listeners[$eventName][0]   = false; // Not sorted
            static::$listeners[$eventName][1][] = $priority;
            static::$listeners[$eventName][2][] = $callback;
            static::$listeners[$eventName][3][] = $app;
        }
    }


    /**
     * Returns an array of listeners for a single event. They are
     * sorted by priority.
     *
     * @param string $eventName
     */
    public static function listeners($eventName): array
    {
        if (! isset(static::$listeners[$eventName])) {
            return [[], []];
        }

        // The list is not sorted
        if (! static::$listeners[$eventName][0]) {
            // Sort it!
            array_multisort(static::$listeners[$eventName][1], SORT_NUMERIC, static::$listeners[$eventName][2]);

            // Mark it as sorted already!
            static::$listeners[$eventName][0] = true;
        }

        return [static::$listeners[$eventName][2], static::$listeners[$eventName][3]];
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

        list($listeners, $apps) = static::listeners($eventName);
        if (!$listeners) {
            return false;
        }

        foreach ($listeners as $k => $listener) {

            if (IS_POST && CI_DEBUG && !in_array($eventName, ['DBQuery', 'pre_system'])) {
                log_message('debug', ($apps && isset($apps[$k]) ? '插件【'.$apps[$k].'】' : '' ).'运行钩子【'.$eventName.'】');
            }

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

        list($listeners, $apps) = static::listeners($eventName);
        if (!$listeners) {
            return false;
        }

        foreach ($listeners as $k => $listener) {

            if (IS_POST && CI_DEBUG && !in_array($eventName, ['DBQuery', 'pre_system'])) {
                log_message('debug', ($apps && isset($apps[$k]) ? '插件【'.$apps[$k].'】' : '' ).'运行钩子【'.$eventName.'】');
            }

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

