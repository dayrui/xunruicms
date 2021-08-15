<?php

/**
 * 任务脚本文件
 */

define('IS_API', 'cron'); // 项目标识
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME)); // 该文件的名称
require(dirname(dirname(__FILE__)).'/index.php'); // 引入主文件