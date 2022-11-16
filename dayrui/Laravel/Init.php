<?php

define('FRAME_PHP_VERSION', '8.0.2');  // PHP最低版本
if (version_compare(PHP_VERSION, FRAME_PHP_VERSION) < 0) {
    exit("<font color=red>Laravel-PHP版本要求在".FRAME_PHP_VERSION."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

require CMSPATH . 'Core/Auto.php';
require CMSPATH . 'Core/Service.php';
require CMSPATH . 'Core/Hooks.php';


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

    }
}

if (!is_file(__DIR__.'/System/vendor/autoload.php')) {
    exit('缺少文件（'.__DIR__.'/System/vendor/autoload.php'.'）请在官网下载Laravel内核包');
}

require __DIR__.'/System/vendor/autoload.php';
require __DIR__.'/Extend/Error.php';

// 应用插件的自动识别
$loader = new \Phpcmf\Auto();
$loader->initialize(\Phpcmf\Service::Auto(new \Phpcmf\AutoConfig()))->register();

// 挂钩点 程序运行之前
\Phpcmf\Hooks::trigger('cms_run');

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app = new Illuminate\Foundation\Application(
    FRAMEPATH.'System/'
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

