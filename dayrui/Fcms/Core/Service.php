<?php namespace Phpcmf;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use \Phpcmf\View;

class Service
{

    static private $instances = [];
    static private $help = [];
    static private $init = [];
    static private $view;
    static private $model;
    static private $require;

    /**
     * 控制器对象实例
     *
     * @var object
     */
    public static function C() {
        return class_exists('\Phpcmf\Common') ? \Phpcmf\Common::get_instance() : null;
    }

    // 是否是电脑端
    public static function IS_PC() {
        return !static::C()->is_mobile;
    }

    // 是否是移动端
    public static function IS_MOBILE() {
        return static::C()->is_mobile;
    }

    // 当前客户端是否是移动端访问
    public static function _is_mobile() {
        return static::C()->_is_mobile();
    }

    /**
     * 获取文件内容
     *
     * @var object
     */
    public static function R($file) {

        $_cname = md5($file);
        if (isset(static::$require[$_cname])) {
            return static::$require[$_cname];
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
    public static function L( $name,  $namespace = '') {

        list($classFile, $extendFile) = self::_get_class_file($name, $namespace, 'Library');
        $_cname = md5($classFile.$extendFile);
        $className = ucfirst($name);
        if (!isset(static::$instances[$_cname]) or !is_object(static::$instances[$_cname])) {
            require_once $classFile;
            // 自定义继承类
            if ($extendFile && is_file($extendFile)) {
                require $extendFile;
                $newClassName = $namespace ? '\\Phpcmf\\Library\\'.ucfirst($namespace).'\\'.$className : '\\My\\Library\\'.$className;
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

        list($classFile, $extendFile) = self::_get_class_file($name, $namespace, 'Model');

        $_cname = md5($classFile.$extendFile);
        $className = ucfirst($name);
        if (!isset(static::$instances[$_cname]) or !is_object(static::$instances[$_cname])) {
            require_once $classFile;
            // 自定义继承类
            if ($extendFile && is_file($extendFile)) {
                require $extendFile;
                $newClassName = $namespace ? '\\Phpcmf\\Model\\'.ucfirst($namespace).'\\'.$className : '\\My\\Model\\'.$className;
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
            defined('IS_API_HTTP') && IS_API_HTTP ? \Phpcmf\Common::json(0, '函数文件：'.str_replace(FCPATH, '', $file).'不存在') : exit('函数文件：'.str_replace(FCPATH, '', $file).'不存在');
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
        if (!is_file($extendFile) && $namespace) {
            // 当前是app时优先考虑本级继承目录文件
            $extendFile = dr_get_app_dir($namespace).($class == 'Library' ? 'Librarie' : $class ).'s/'.$className.'.php';
        }
        if (!is_file($classFile)) {
            // 相对于APP目录
            if ($namespace) {
                $classFile = dr_get_app_dir($namespace).($class == 'Library' ? 'Librarie' : $class ).'s/'.$className.'.php';
                $extendFile = '';
            } else if (is_file($extendFile)) {
                $classFile = $extendFile;
                $extendFile = '';
            }
            // 都不存在就报错
            if (!$classFile || !is_file($classFile)) {
                defined('IS_API_HTTP') && IS_API_HTTP ? \Phpcmf\Common::json(0, '类文件：'.str_replace(FCPATH, '', $classFile).'不存在') : exit('类文件：'.str_replace(FCPATH, '', $classFile).'不存在');
            }
        }

        return [$classFile, $extendFile];
    }
}