<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use \Phpcmf\View;

class Service
{

    static private $instances = [];
    static private $help = [];
    static private $logs = [];
    static private $view;
    static private $model;
    static private $require;
    static private $apps;
    static private $mwhere_apps = [];

    /**
     * 控制器对象实例
     *
     * @var object
     */
    public static function C() {
        return class_exists('\Phpcmf\Common') ? \Phpcmf\Common::get_instance() : null;
    }


    // 获取应用自动加载
    public static function Auto($auto) {

        $local = \Phpcmf\Service::Apps();
        if ($local) {
            foreach ($local as $dir => $path) {
                if (!is_file($path.'install.lock')) {
                    continue;
                }
                if (is_file($path.'Config/Auto.php')) {
                    $app_auto = require $path.'Config/Auto.php';
                    isset($app_auto['psr4']) && $app_auto['psr4'] && $auto->psr4 = array_merge($auto->psr4, $app_auto['psr4']);
                    isset($app_auto['classmap']) && $app_auto['classmap'] && $auto->classmap = array_merge($auto->classmap, $app_auto['classmap']);
                    unset($app_auto);
                }
                // 加载钩子
                if (is_file($path.'Config/Hooks.php')) {
                    require $path.'Config/Hooks.php';
                }
                // 判断是否存在自定义where
                if (is_file($path.'Config/Mwhere.php')) {
                    \Phpcmf\Service::Set_Mwhere_App($dir);
                }
            }
        }

        return $auto;
    }

    // 获取应用目录
    public static function Apps() {

        if (isset(static::$apps) && static::$apps) {
            return static::$apps;
        }

        static::$apps = [];
        $source_dir = dr_get_app_list();

        if ($fp = opendir($source_dir)) {
            $filedata = [];
            $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            while (FALSE !== ($file = readdir($fp))) {
                if ($file === '.' OR $file === '..'
                    OR $file[0] === '.'
                    OR !is_dir($source_dir.$file)) {
                    continue;
                }
                if (is_dir($source_dir.$file)) {
                    $filedata[$file] = dr_get_app_dir($file);
                }
            }
            closedir($fp);

            static::$apps = $filedata;
        }

        return static::$apps;
    }

    // 设置mwhere的插件名称
    public static function Set_Mwhere_App($dir) {
        static::$mwhere_apps[] = $dir;
    }

    // 读取mwhere插件名称列表
    public static function Mwhere_Apps() {
        return static::$mwhere_apps;
    }

    // 是否是电脑端模板
    public static function IS_PC_TPL() {
        return static::V()->is_pc();
    }
    public static function IS_PC() {
        return static::V()->is_pc();
    }

    // 是否是移动端模板
    public static function IS_MOBILE_TPL() {
        return static::V()->is_mobile();
    }
    public static function IS_MOBILE() {
        return static::V()->is_mobile();
    }

    // 当前客户端是否是移动端访问
    public static function IS_MOBILE_USER() {
        return dr_is_mobile();
    }
    public static function _is_mobile() {
        return dr_is_mobile();
    }

    // 当前客户端是否是PC端访问
    public static function IS_PC_USER() {
        return !dr_is_mobile();
    }

    // 错误日志记录
    public static function Log($level, $message, array $context = []) {

        if (is_object($message)) {
            $msg = $message->getMessage();
            $code = md5($msg);
            if (dr_in_array($code, static::$logs)) {
                return;
            }
            static::$logs[] = $code;

            $context['trace'] = $message->getTraceAsString();
            $context['sql'] = \Phpcmf\Service::M()->get_sql_query();
            $context['url'] = FC_NOW_URL;
            $context['user'] = dr_safe_replace($_SERVER['HTTP_USER_AGENT']);
            $context['referer'] = dr_safe_url($_SERVER['HTTP_REFERER'], true);

            return \Config\Services::logger(true)->log($level, $msg."\n#SQL：{sql}\n#URL：{url}\n#AGENT：{user}\n".($context['referer'] ? "#REFERER：{referer}\n" : "")."{trace}\n", $context);
        }

        return \Config\Services::logger(true)->log($level, $message, $context);
    }

    /**
     * 获取文件内容
     *
     * @var object
     */
    public static function R($file, $clear = false) {

        $_cname = md5($file);

        if (!$clear) {
            if (isset(static::$require[$_cname])) {
                return static::$require[$_cname];
            } elseif (!is_file($file)) {
                CI_DEBUG && log_message('error', '引用文件不存在：'.$file);
                return false;
            }
        }

        static::$require[$_cname] = require $file;

        return static::$require[$_cname];
    }

    /**
     * 模板视图对象实例
     *
     * @var object
     */
    public static function V() {

        if (!is_object(static::$view)) {
            static::$view = new \Phpcmf\View();
        }

        return static::$view;
    }

    /**
     * 模型类对象实例
     *
     * @var object
     */
    public static function model() {

        if (!is_object(static::$model)) {
            static::$model = new \Phpcmf\Model();
        }

        return static::$model;
    }

    /**
     * 类对象实例
     *
     * @var object
     */
    public static function L($name,  $namespace = '') {

        list($classFile, $extendFile, $appFile) = self::_get_class_file($name, $namespace, 'Library');

        $_cname = md5($classFile.$extendFile.$appFile);
        $className = ucfirst($name);

        if (!isset(static::$instances[$_cname]) or !is_object(static::$instances[$_cname])) {
            require_once $classFile;
            // 自定义继承类
            if ($extendFile && is_file($extendFile)) {
                if ($namespace && is_file($appFile)) {
                    require $appFile;
                    $newClassName = '\\Phpcmf\\Library\\'.ucfirst($namespace).'\\'.$className;
                } else {
                    require $extendFile;
                    $newClassName = '\\My\\Library\\'.$className;
                }
            } else {
                $newClassName = '\\Phpcmf\\Library\\'.$className;
                // 多个应用引用同一个类名称时的区别
                if ($namespace) {
                    $newClassName2 = '\\Phpcmf\\Library\\'.ucfirst($namespace).'\\'.$className;
                    if (class_exists($newClassName2)) {
                        static::$instances[$_cname] = new $newClassName2();
                        return static::$instances[$_cname];
                    }
                }
            }

            static::$instances[$_cname] = new $newClassName();
        }

        return static::$instances[$_cname];

    }

    /**
     * 模型对象实例
     *
     * @var object
     */
    public static function M( $name = '',  $namespace = '') {

        if (!$name) {
            return static::model();
        }

        list($classFile, $extendFile, $appFile) = self::_get_class_file($name, $namespace, 'Model');

        $_cname = md5($classFile.$extendFile.$appFile);
        $className = ucfirst($name);

        if (!isset(static::$instances[$_cname]) or !is_object(static::$instances[$_cname])) {
            require_once $classFile;
            // 自定义继承类
            if ($extendFile && is_file($extendFile)) {
                if ($namespace && is_file($appFile)) {
                    require $appFile;
                    $newClassName = '\\Phpcmf\\Model\\'.ucfirst($namespace).'\\'.$className;
                } else {
                    require $extendFile;
                    $newClassName = '\\My\\Model\\'.$className;
                }
            } else {
                $newClassName = '\\Phpcmf\\Model\\'.$className;
                // 多个应用引用同一个类名称时的区别
                if ($namespace) {
                    $newClassName2 = '\\Phpcmf\\Model\\'.ucfirst($namespace).'\\'.$className;
                    if (class_exists($newClassName2)) {
                        static::$instances[$_cname] = new $newClassName2();
                        return static::$instances[$_cname];
                    }
                }
            }

            static::$instances[$_cname] = new $newClassName();
        }

        return static::$instances[$_cname];
    }

    /**
     * 引用应用的helper
     */
    public static function H($name, $namespace) {


        $file = dr_get_app_dir($namespace).'Helpers/'.ucfirst($name).'.php';
        $_cname = md5($file);
        if (isset(static::$help[$_cname])) {
            return;
        }

        if (!is_file($file)) {
            self::_error('函数文件：'.str_replace(FCPATH, '', $file).'不存在');
        }

        static::$help[$_cname] = 1;

        require $file;
    }

    // 获取类文件路径
    private static function _get_class_file($name, $namespace, $class) {

        $className = ucfirst($name);
        $classFile = CMSPATH.$class.'/'.$className.'.php';

        // 自定义继承类文件
        $extendFile = MYPATH.$class.'/'.$className.'.php';

        // 当前是app时优先考虑本级继承目录文件
        if ($namespace) {
            $appFile = dr_get_app_dir($namespace).($class == 'Library' ? 'Librarie' : $class ).'s/'.$className.'.php';
        } else {
            $appFile = '';
        }

        if (!is_file($extendFile) && $namespace) {
            // 当前是app时优先考虑本级继承目录文件
            $extendFile = $appFile;
        }

        if (!is_file($classFile)) {
            // 相对于APP目录
            if ($namespace) {
                $classFile = $appFile;
                $extendFile = '';
            } else if (is_file($extendFile)) {
                $classFile = $extendFile;
                $extendFile = '';
            }
            // 都不存在就报错
            if (!$classFile || !is_file($classFile)) {
                self::_error('类文件：'.str_replace(FCPATH, '', $classFile).'不存在');
            }
        }

        return [$classFile, $extendFile, $appFile];
    }

    // 错误输出
    private static function _error($msg) {

        log_message('error', $msg);

        if (defined('IS_API_HTTP') && IS_API_HTTP) {
            \Phpcmf\Common::json(0, $msg); // api输出格式
        } else {
            // 报系统故障
            dr_show_error($msg);
        }
    }

}