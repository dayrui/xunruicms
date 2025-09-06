<?php

require CMSPATH . 'Core/Auto.php';
require CMSPATH . 'Core/Service.php';
require CMSPATH . 'Core/Hooks.php';
require __DIR__ . '/Extend/Error.php';

define('FRAME_PHP_VERSION', '8.0.0');  // PHP最低版本
if (version_compare(PHP_VERSION, FRAME_PHP_VERSION) < 0) {
    exit("<font color=red>ThinkPHP-PHP版本要求在".FRAME_PHP_VERSION."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

if (defined('CMSURI') && CMSURI && isset($_SERVER['REQUEST_URI'])) {
    unset($_SERVER['REQUEST_URI']);
}

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_token()
    {
        return '_token';
    }
}


if (! function_exists('csrf_hash')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_hash()
    {
        return substr(SYS_KEY, 3, 10);
    }
}

// 应用插件的自动识别
$loader = new \Phpcmf\Auto();
$loader->initialize(\Phpcmf\Service::Auto(new \Phpcmf\AutoConfig()))->register();

// apache环境参数修正
if (isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL']) {
    $_SERVER['REDIRECT_URL'] = '';
}

// 避免冲突
if (isset($_GET['s'])) {
    unset($_GET['s']);
}

require FRAMEPATH.'Extend/Run.php';

