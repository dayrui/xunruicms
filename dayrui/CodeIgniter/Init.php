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
 * 重写日志记录函数
 */
function log_message($level, $message, array $context = []) {
    return \Phpcmf\Service::Log($level, $message, $context);
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

require BASEPATH . 'Config/DotEnv.php';

$env = new DotEnv(COREPATH);
$env->load();

helper('url');

$app = new \Phpcmf\Extend\CodeIgniter(new App());
$app->initialize();
$app->run();