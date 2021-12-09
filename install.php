<?php

/**
 * 安装程序
 */

header('Content-Type: text/html; charset=utf-8');

// 判断环境
if (version_compare(PHP_VERSION, '7.3.0') < 0) {
    exit("<font color=red>PHP版本建议在7.3及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

$pos = strpos(trim($_SERVER['SCRIPT_NAME'], '/'), '/');
if ($pos !== false && $pos > 1) {
    echo "<font color=red>本程序必须在域名根目录中安装</font>，查看手册：https://www.xunruicms.com/doc/741.html";exit;
}

!defined('WEBPATH') && define('WEBPATH', dirname(__FILE__).'/');
!defined('WRITEPATH') && define('WRITEPATH', WEBPATH.'cache/');

foreach (array(' ', '[', ']') as $t) {
    if (strpos(WEBPATH, $t) !== false) {
        exit('<font color=red>WEB目录'.WEBPATH.'不允许出现'.($t ? $t : '空格').'符号</font>');
    }
}

// 判断目录权限
foreach (array(
             WRITEPATH,
             WRITEPATH.'data/',
             WRITEPATH.'template/',
             WRITEPATH.'file/',
             WRITEPATH.'session/',
             WEBPATH.'config/',
             WEBPATH.'uploadfile/',
         ) as $t) {
    if (!dr_check_put_path($t)) {
        exit('目录（'.$t.'）不可写');
    }
}
// 判断支持函数
foreach (array(
             'chmod',
         ) as $t) {
    if ($t && !function_exists($t)) {
        exit('PHP自带的函数（'.$t.'）被服务器禁用了，需要联系服务商开启：https://www.xunruicms.com/doc/1191.html');
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