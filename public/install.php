<?php

/**
 * 安装程序（正式上线后可删除本文件）
 */

header('Content-Type: text/html; charset=utf-8');

// 最低支持php版本
$min = '7.4.0';

!defined('WEBPATH') && define('WEBPATH', dirname(__FILE__).'/');
if (is_file(WEBPATH.'config/api.php')) {
    define('CONFIGPATH',WEBPATH.'config/');
    if (is_dir(WEBPATH.'/dayrui/CodeIgniter72/')) {
        $min = '7.2.0';
    }
} else {
    define('CONFIGPATH',dirname(dirname(__FILE__)).'/config/');
	define('WRITEPATH', dirname(dirname(__FILE__)).'/cache/');
    if (is_dir(dirname(dirname(__FILE__)).'/CodeIgniter72/')) {
        $min = '7.2.0';
    }
}
if (!defined('WRITEPATH')) {
    if (is_dir(WEBPATH.'cache/')) {
        define('WRITEPATH', WEBPATH.'cache/');
    } elseif (is_dir(dirname(dirname(__FILE__)).'/cache/')) {
        define('WRITEPATH', dirname(dirname(__FILE__)).'/cache/');
    } else {
        exit('无法识别cache目录，请联系官方人员');
    }
}

// 判断环境
if (version_compare(PHP_VERSION, $min) < 0) {
    exit("<font color=red>PHP版本建议在".$min."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

$pos = strpos(trim($_SERVER['SCRIPT_NAME'], '/'), '/');
if ($pos !== false && $pos > 1) {
    echo "<font color=red>本程序必须在域名根目录中安装</font>，查看手册：https://www.xunruicms.com/doc/741.html";exit;
}

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
             CONFIGPATH,
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