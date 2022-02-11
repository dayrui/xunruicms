<?php

/**
 * 子站入口
 */

!defined('SITE_ID') && define('SITE_ID', '{SITE_ID}');
define('WEBPATH', dirname(__FILE__).'/');
!defined('FIX_WEB_DIR') && define('FIX_WEB_DIR', '{FIX_WEB_DIR}');

// 执行主站程序
require '{ROOTPATH}index.php';