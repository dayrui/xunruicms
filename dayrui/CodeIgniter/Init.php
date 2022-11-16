<?php
/**
 * CodeIgniter运行目录
 */

use CodeIgniter\Config\DotEnv;
use Config\App;
use Config\Autoload;
use Config\Modules;
use Config\Services;

define('SYSTEMPATH', BASEPATH);

define('FRAME_PHP_VERSION', '7.4');  // PHP最低版本
if (version_compare(PHP_VERSION, FRAME_PHP_VERSION) < 0) {
    exit("<font color=red>CodeIgniter-PHP版本要求在".FRAME_PHP_VERSION."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

/*
 * 重写config函数，防止modules被加载
 */
function config ($name, $getShared = true) {

    if ($name == 'Modules') {
        $name = 'Config\Modules';
    }

    return \CodeIgniter\Config\Config::get($name, $getShared);
}

/*
 * 显示完整路径
 */
function clean_path(string $path): string
{
    return $path;
}

/******* Locale  *******/
if (!class_exists('Locale')) {
    class Locale {

        static private $locale;

        public static function getDefault() {
            return self::$locale;
        }

        public static function setDefault($locale) {
            self::$locale = $locale;
        }

    }
}


/******* CodeIgniter Bootstrap *******/

// 定义常量
require FRAMEPATH.'Config/Constants.php';

require BASEPATH.'Common.php';

// 自动加载机制
require SYSTEMPATH . 'Config/AutoloadConfig.php';
require FRAMEPATH . 'Config/Autoload.php';
require SYSTEMPATH . 'Modules/Modules.php';
require FRAMEPATH . 'Config/Modules.php';

require SYSTEMPATH . 'Autoloader/Autoloader.php';
require SYSTEMPATH . 'Config/BaseService.php';
require SYSTEMPATH . 'Config/Services.php';
require FRAMEPATH . 'Config/Services.php';

require SYSTEMPATH . 'Events/Events.php';
require CMSPATH.'Core/Service.php';
require CMSPATH.'Core/Hooks.php';

class_alias('Config\Services', 'CodeIgniter\Services');

$loader = Services::autoloader();
$auto = new Autoload();

// 应用插件的自动识别
$auto = \Phpcmf\Service::Auto($auto);

$loader->initialize($auto, new Modules())->register();

if (is_file(COMPOSER_PATH)) {
    require_once COMPOSER_PATH;
}

// 挂钩点 程序运行之前
\Phpcmf\Hooks::trigger('cms_run');

require BASEPATH . 'Config/DotEnv.php';

$env = new DotEnv(COREPATH);
$env->load();

helper('url');

$app = new \Phpcmf\Extend\CodeIgniter(new App());
$app->initialize();
$app->setContext(is_cli() ? 'php-cli' : 'web');
$app->run();