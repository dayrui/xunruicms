<?php

// [ 应用入口文件 ]
namespace think;

if (!is_file(FRAMEPATH . 'System/vendor/autoload.php')) {
    exit('缺少文件（'.FRAMEPATH . 'System/vendor/autoload.php'.'）请在官网下载ThinkPHP内核包');
}

require FRAMEPATH . 'System/vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->debug(CI_DEBUG ? true : false)->http;

$response = $http->run();

$response->send();

$http->end($response);