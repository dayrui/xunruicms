<?php
/**
 * 环境检测文件
 */

header('Content-Type: text/html; charset=utf-8');

// 判断环境
$min = '7.4.0';
if (version_compare(PHP_VERSION, $min) < 0) {
    exit("<font color=red>PHP版本需要在".$min."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");
}

echo '请将public目录设置为网站主目录：https://www.xunruicms.com/doc/1280.html';