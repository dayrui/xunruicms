<?php

/**
 * 模块自定义域名入口
 */

define('SITE_ID', '{SITE_ID}');
$_GET['s'] = isset($_GET['s']) ? ($_GET['s'] == 'api' ? 'api' : '{MOD_DIR}') : '{MOD_DIR}';

// 执行主站程序
require '{ROOTPATH}index.php';