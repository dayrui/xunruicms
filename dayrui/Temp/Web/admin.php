<?php

/**
 * 后台管理中心
 */

define('IS_ADMIN', TRUE); // 项目标识
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME)); // 该文件的名称
require('index.php'); // 引入主文件