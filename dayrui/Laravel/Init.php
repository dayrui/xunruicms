<?php


require FRAMEPATH . 'Autoloader/Autoloader.php';
require FRAMEPATH . 'Autoloader/Config.php';
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

// 应用插件的自动识别
$loader = new \Laravel\Autoloader\Autoloader();
$loader->initialize(\Phpcmf\Service::Auto(new \Laravel\Autoloader\Config()))->register();

require __DIR__.'/vendor/autoload.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app = new Illuminate\Foundation\Application(
    FRAMEPATH
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

