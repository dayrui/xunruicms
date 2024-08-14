<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Service {

    static private $instances = [];
    static private $help = [];
    static private $logs = [];
    static private $view;
    static private $model;
    static private $require;
    static private $apps = [
		1 => [],
		0 => [],
	];
    static private $mwhere_apps = [];
    static private $filters = [
        'home' => ['install/index'],
        'member' => [],
        'admin' => [],
    ];

    // 获取应用自动加载
    public static function Auto($auto) {

        $auto->psr4 = array_merge($auto->psr4, [

            'Phpcmf\Controllers'            => APPPATH.'Controllers',

            'Phpcmf\Control'                => CMSPATH.'Control',
            'Phpcmf\Extend'                 => FRAMEPATH.'Extend',
            'Phpcmf\Library'                => CMSPATH.'Library',
            'Phpcmf\Field'                  => CMSPATH.'Field',
            'Phpcmf\ThirdParty'             => FCPATH.'ThirdParty',

            'My\Field'                      => MYPATH.'Field',
            'My\Library'                	=> MYPATH.'Library',
            'My\Model'                	    => MYPATH.'Model',
        ]);

        $classmap = [
            'Phpcmf\App'                  => CMSPATH.'Core/App.php',
            'Phpcmf\Table'                => CMSPATH.'Core/Table.php',
            'Phpcmf\Model'                => CMSPATH.'Core/Model.php',
            'Phpcmf\View'                 => CMSPATH.'Core/View.php',
            'Phpcmf\Common'               => CMSPATH.'Core/Common.php',
        ];

        if (IS_USE_MODULE) {
            $classmap['Phpcmf\Home\Module'] = IS_USE_MODULE.'Extends/Home/Module.php';
            $classmap['Phpcmf\Admin\Config'] = IS_USE_MODULE.'Extends/Admin/Config.php';
            $classmap['Phpcmf\Admin\Module'] = IS_USE_MODULE.'Extends/Admin/Module.php';
            $classmap['Phpcmf\Model\Content'] = IS_USE_MODULE.'Models/Content.php';
            $classmap['Phpcmf\Model\Search'] = IS_USE_MODULE.'Models/Search.php';
            $classmap['Phpcmf\Admin\Category'] = IS_USE_MODULE.'Extends/Admin/Category.php';
            if (IS_USE_MEMBER) {
                $classmap['Phpcmf\Member\Module'] = IS_USE_MODULE.'Extends/Member/Module.php';
            }
        }

        $auto->classmap = array_merge($auto->classmap, $classmap);

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
                // 判断是否存在CSRF白名单
                if (is_file($path.'Config/Filters.php')) {
                    $Filters = require $path.'Config/Filters.php';
                    if ($Filters) {
                        $Filters['home'] && static::$filters['home'] = array_merge(static::$filters['home'], $Filters['home']);
                        $Filters['member'] && static::$filters['member'] = array_merge(static::$filters['member'], $Filters['member']);
                        $Filters['admin'] && static::$filters['admin'] = array_merge(static::$filters['admin'], $Filters['admin']);
                    }
                }
            }
        }

        return $auto;
    }

    // 获取应用目录
    public static function Apps($is_install = 0) {

		$is_install = $is_install ? 1 : 0;

        if (isset(static::$apps[$is_install]) && static::$apps[$is_install]) {
            return static::$apps[$is_install];
        }

        static::$apps[$is_install] = [];
        $source_dir = dr_get_app_list();
        if ($fp = opendir($source_dir)) {
            while (FALSE !== ($file = readdir($fp))) {
                $path = dr_get_app_dir($file);
                if ($file === '.' OR $file === '..'
                    OR $file[0] === '.'
                    OR !is_dir($path)) {
                    continue;
                }
                if ($is_install && !is_file($path . 'install.lock')) {
                    continue;
                }
                static::$apps[$is_install][$file] = $path;
            }
            closedir($fp);
        }

        if (function_exists('dr_get_app_extend')) {
            $extend = dr_get_app_extend($is_install);
           if ($extend) {
               foreach ($extend as $i => $t) {
                   static::$apps[$is_install][$i] = $t;
               }
           }
        }

        return static::$apps[$is_install];
    }

    // 设置mwhere的插件名称
    public static function Set_Mwhere_App($dir) {
        static::$mwhere_apps[] = $dir;
    }

    // 读取mwhere插件名称列表
    public static function Mwhere_Apps() {
        return static::$mwhere_apps;
    }

    // 读取Filters白名单
    public static function Filters($type = 'auto') {

        if ($type == 'auto') {
            if (IS_ADMIN) {
                $type = 'admin';
            } elseif (IS_MEMBER) {
                $type = 'member';
            } else {
                $type = 'home';
            }
        } elseif ($type == '') {
            return static::$filters;
        }

        return isset(static::$filters[$type]) ? static::$filters[$type] : [];
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

        if ($level == 'debug' && defined('IS_FB_DEBUG') && IS_FB_DEBUG) {
            return;
        }

        if (is_object($message)) {
            $msg = $message->getMessage();
            $code = md5($msg);
            if (is_array( static::$logs) && in_array($code, static::$logs)) {
                return;
            }

            static::$logs[] = $code;

            $context['trace'] = $message->getTraceAsString();
            $context['sql'] = \Phpcmf\Service::M()->get_sql_query();
            $context['url'] = FC_NOW_URL;
            $context['user'] = dr_safe_replace($_SERVER['HTTP_USER_AGENT']);
            $context['referer'] = dr_safe_url($_SERVER['HTTP_REFERER'], true);

            return \Phpcmf\Service::L('input')->log($level, $msg."\n#SQL：{sql}\n#URL：{url}\n#AGENT：{user}\n".($context['referer'] ? "#REFERER：{referer}\n" : "")."{trace}\n", $context);
        }

        $message.= '---'.FC_NOW_URL.PHP_EOL;
        return \Phpcmf\Service::L('input')->log($level, $message, $context);
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
     * 控制器对象实例
     *
     * @var object
     */
    public static function C() {
        return class_exists('\Phpcmf\Common') ? \Phpcmf\Common::get_instance() : null;
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
                //CI_DEBUG && log_message('debug', '引用文件不存在：'.$file);
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
    public static function M($name = '',  $namespace = '') {

        if (!$name) {
            return static::model();
        }

        $className = ucfirst($name);
        if (!$namespace) {
            switch ($className) {

                case 'Content':
                    if (!IS_USE_MODULE) {
                        \dr_exit_msg(0, '没有安装「建站系统」插件');
                    }
                    $namespace = 'module';
                    break;

                case 'Search':
                    if (!IS_USE_MODULE) {
                        \dr_exit_msg(0, '没有安装「建站系统」插件');
                    }
                    $namespace = 'module';
                    break;

                case 'Category':
                    if (!IS_USE_MODULE) {
                        \dr_exit_msg(0, '没有安装「建站系统」插件');
                    }
                    $namespace = 'module';
                    break;

                case 'Module':
                    if (!IS_USE_MODULE) {
                        \dr_exit_msg(0, '没有安装「建站系统」插件');
                    }
                    $namespace = 'module';
                    break;

                case 'Pay':
                    if (!dr_is_app('pay')) {
                        \dr_exit_msg(0, '没有安装「支付系统」插件');
                    }
                    $namespace = 'pay';
                    break;
            }
        } else {
            if (IS_USE_MODULE && in_array($className, ['Content', 'Search'])
                && !is_file(dr_get_app_dir($namespace).'Models/'.$className.'.php')) {
                $namespace = 'module';
            }
        }

        list($classFile, $extendFile, $appFile) = self::_get_class_file($name, $namespace, 'Model');

        $_cname = md5($classFile.$extendFile.$appFile);

        if (!isset(static::$instances[$_cname]) or !is_object(static::$instances[$_cname])) {
            require_once $classFile;
            // 自定义继承类
            if ($extendFile && is_file($extendFile)) {
                if ($namespace && is_file($appFile)) {
                    require_once $appFile;
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

        if (defined('IS_API_HTTP') && IS_API_HTTP) {
            log_message('error', $msg . '（'.FC_NOW_URL.'）');
            \Phpcmf\Service::C()->_json(0, $msg); // api输出格式
        } else {
            // 报系统故障
            dr_show_error($msg);
        }
    }

}