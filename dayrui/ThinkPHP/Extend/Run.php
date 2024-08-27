<?php

// [ 应用入口文件 ]
namespace think;

if (!is_file(FRAMEPATH . 'System/vendor/autoload.php')) {
    exit('缺少文件（'.FRAMEPATH . 'System/vendor/autoload.php'.'）请在官网下载ThinkPHP内核包');
}

require FRAMEPATH . 'System/vendor/autoload.php';

$app = new App();
$app->debug(CI_DEBUG ? true : false);
$app->setRuntimePath(WRITEPATH.'thinkphp_runtime/');

// 挂钩点 程序运行之前
\Phpcmf\Hooks::trigger('cms_run');

// 执行HTTP应用并响应
$http = $app->http;

$response = $http->run();

$response->send();

$http->end($response);