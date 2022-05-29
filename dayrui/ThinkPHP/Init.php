<?php

require CMSPATH . 'Core/Auto.php';
require CMSPATH . 'Core/Service.php';
require CMSPATH . 'Core/Hooks.php';
require __DIR__ . '/Extend/Error.php';


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

// 应用插件的自动识别
$loader = new \Phpcmf\Auto();
$loader->initialize(\Phpcmf\Service::Auto(new \Phpcmf\AutoConfig()))->register();

require FRAMEPATH.'Extend/Run.php';

