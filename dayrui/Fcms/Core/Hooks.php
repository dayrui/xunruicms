<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

define('EVENT_PRIORITY_LOW', 200);
define('EVENT_PRIORITY_NORMAL', 10);
define('EVENT_PRIORITY_HIGH', 10);

/**
 * 钩子类
 */
class Hooks {

    protected static $initialized_hook = false;

    /**
     * The list of listeners.
     *
     * @var array
     */
    protected static $listeners = [];

    /**
     * Flag to let us know if we've read from the Config file(s)
     * and have all of the defined events.
     *
     * @var bool
     */
    protected static $initialized = false;

    /**
     * If true, events will not actually be fired.
     * Useful during testing.
     *
     * @var bool
     */
    protected static $simulate = false;

    /**
     * Stores information about the events
     * for display in the debug toolbar.
     *
     * @var array<array<string, float|string>>
     */
    protected static $performanceLog = [];

    /**
     * A list of found files.
     *
     * @var string[]
     */
    protected static $files = [];

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

        if (is_file(FRAMEPATH.'Extend/Hook.php')) {
            require FRAMEPATH.'Extend/Hook.php';
        }

        static::$initialized = true;
        static::$initialized_hook = true;
    }

    /**
     * Registers an action to happen on an event. The action can be any sort
     * of callable:
     *
     *  Events::on('create', 'myFunction');               // procedural function
     *  Events::on('create', ['myClass', 'myMethod']);    // Class::method
     *  Events::on('create', [$myInstance, 'myMethod']);  // Method on an existing instance
     *  Events::on('create', function() {});              // Closure
     *
     * @param string   $eventName
     * @param callable $callback
     * @param int      $priority
     */
    public static function on($eventName, $callback, $priority = EVENT_PRIORITY_NORMAL)
    {
        if (! isset(static::$listeners[$eventName])) {
            static::$listeners[$eventName] = [
                true, // If there's only 1 item, it's sorted.
                [$priority],
                [$callback],
            ];
        } else {
            static::$listeners[$eventName][0]   = false; // Not sorted
            static::$listeners[$eventName][1][] = $priority;
            static::$listeners[$eventName][2][] = $callback;
        }
    }


    /**
     * Removes a single listener from an event.
     *
     * If the listener couldn't be found, returns FALSE, else TRUE if
     * it was removed.
     *
     * @param string $eventName
     */
    public static function removeListener($eventName, callable $listener): bool
    {
        if (! isset(static::$listeners[$eventName])) {
            return false;
        }

        foreach (static::$listeners[$eventName][2] as $index => $check) {
            if ($check === $listener) {
                unset(
                    static::$listeners[$eventName][1][$index],
                    static::$listeners[$eventName][2][$index]
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Removes all listeners.
     *
     * If the event_name is specified, only listeners for that event will be
     * removed, otherwise all listeners for all events are removed.
     *
     * @param string|null $eventName
     */
    public static function removeAllListeners($eventName = null)
    {
        if ($eventName !== null) {
            unset(static::$listeners[$eventName]);
        } else {
            static::$listeners = [];
        }
    }

    /**
     * Sets the path to the file that routes are read from.
     */
    public static function setFiles(array $files)
    {
        static::$files = $files;
    }

    /**
     * Returns the files that were found/loaded during this request.
     *
     * @return string[]
     */
    public function getFiles()
    {
        return static::$files;
    }

    /**
     * Turns simulation on or off. When on, events will not be triggered,
     * simply logged. Useful during testing when you don't actually want
     * the tests to run.
     */
    public static function simulate(bool $choice = true)
    {
        static::$simulate = $choice;
    }

    /**
     * Getter for the performance log records.
     *
     * @return array<array<string, float|string>>
     */
    public static function getPerformanceLogs()
    {
        return static::$performanceLog;
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
                // 只要遇到返回成功的钩子就中断执行直接返回
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

