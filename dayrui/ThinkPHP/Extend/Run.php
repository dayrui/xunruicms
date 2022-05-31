<?php

// [ 应用入口文件 ]
namespace think;
require FRAMEPATH . 'System/vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->debug(CI_DEBUG ? true : false)->http;

$response = $http->run();

$response->send();

$http->end($response);