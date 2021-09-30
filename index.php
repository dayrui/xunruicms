<?php

/**
 * 迅睿CMS框架入口程序
 * 开发者可在这里定义系统目录变量
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
//header('X-Frame-Options: SAMEORIGIN'); // 防止被站外加入iframe中浏览

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

// ======开始，自动进入安装界面监测代码 
if (!is_file(WRITEPATH.'install.lock') && !isset($_GET['c'])) {
	require WEBPATH.'install.php';
	exit;
}
// 判断环境
if (version_compare(PHP_VERSION, '7.2.0') < 0) {
    echo "<font color=red>PHP版本必须在7.3以上，当前".PHP_VERSION."</font>";exit;
}
//=======结束，安装之后可以删除此段代码

// 执行主程序
require FCPATH.'Fcms/Init.php';