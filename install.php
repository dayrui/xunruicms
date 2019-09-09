<?php

/**
 * 安装程序
 */

header('Content-Type: text/html; charset=utf-8');

// 判断环境
if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    echo "<font color=red>PHP版本必须在7.2以上</font>";exit;
}

$pos = strpos(trim($_SERVER['SCRIPT_NAME'], '/'), '/');
if ($pos !== false && $pos > 1) {
    echo "<font color=red>本程序必须在域名根目录中安装</font>，查看手册：http://help.xunruicms.com/741.html";exit;
}

define('WEBPATH', dirname(__FILE__).'/');
define('WRITEPATH', WEBPATH.'cache/');

// 判断目录权限
foreach (array(
             WRITEPATH,
             WRITEPATH.'data/',
             WRITEPATH.'template/',
             WRITEPATH.'session/',
             WEBPATH.'config/',
             WEBPATH.'uploadfile/',
         ) as $t) {
    if (!dr_check_put_path($t)) {
        exit('目录（'.$t.'）不可写');
    }
}

header('Location: index.php?c=install');

// 检查目录权限
function dr_check_put_path($dir) {

    if (!$dir) {
        return 0;
    } elseif (!is_dir($dir)) {
        return 0;
    }

    $size = @file_put_contents($dir.'test.html', 'test');
    if ($size === false) {
        return 0;
    } else {
        @unlink($dir.'test.html');
        return 1;
    }
}