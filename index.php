<?php

/**
 * Cms 主程序
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');

// 是否是开发者模式（1开启、0关闭）
define('IS_DEV', 0);

// 是否允许后台修改模板文件（1开启、0关闭），请不要长期开启此功能
define('IS_EDIT_TPL', 0);

// 主网站目录,表示index.php文件的目录
define('ROOTPATH', dirname(__FILE__).'/');

// 当前站点目录
!defined('WEBPATH') && define('WEBPATH', dirname(__FILE__).'/');

// 缓存文件存储目录,支持自定义路径,建议固态硬盘存储缓存文件
define('WRITEPATH', ROOTPATH.'cache/');

// 系统核心程序目录,支持自定义路径和改名
define('FCPATH', dirname(__FILE__).'/dayrui/');

// 入口文件名称
!defined('SELF') && define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// 后台管理标识
!defined('IS_ADMIN') && define('IS_ADMIN', FALSE);

// 显示错误提示
IS_ADMIN || IS_DEV ? error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT) : error_reporting(0);

// 执行主程序
require FCPATH.'Fcms/Init.php';