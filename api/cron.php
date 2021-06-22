<?php

/**
 * 任务脚本文件
 */

define('IS_API', 'cron'); // 项目标识
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME)); // 该文件的名称
require('../index.php'); // 引入主文件