<?php

/**
 * 入口程序
 * 开发者可在这里定义系统目录变量
 */

declare(strict_types=1); // 部分虚拟主机禁用函数时 时可以删除本行
header('Content-Type: text/html; charset=utf-8');
//header('X-Frame-Options: SAMEORIGIN'); // 防止被站外加入iframe中浏览

// 是否是开发者模式（1开启、0关闭），上线之后建议关闭此开关
define('IS_DEV', 0);

// 是否允许后台修改模板文件（1开启、0关闭），请不要长期开启此功能
define('IS_EDIT_TPL', 0);

// 网站目录,表示index.php文件的目录
define('ROOTPATH', dirname(__FILE__).'/');

// 当前站点目录
!defined('WEBPATH') && define('WEBPATH', dirname(__FILE__).'/');

// 系统核心程序目录,支持自定义路径和改名
define('FCPATH', dirname(dirname(__FILE__)).'/dayrui/');

// 缓存文件存储目录,支持自定义路径
define('WRITEPATH', dirname(FCPATH).'/cache/');

// 公共配置文件
define('CONFIGPATH', dirname(FCPATH).'/config/');

// 入口文件名称
!defined('SELF') && define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// 后台管理标识
!defined('IS_ADMIN') && define('IS_ADMIN', FALSE);

// 版本标识
define('IS_VERSION', 1);

// ======开始，自动进入安装界面监测代码 
if (!is_file(WRITEPATH.'install.lock') && !isset($_GET['c'])) {
	require WEBPATH.'install.php';
	exit;
}
//=======结束，安装之后可以删除此段代码

// 执行主程序
require FCPATH.'Fcms/Init.php';