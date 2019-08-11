<?php

/**
 * 支付回调接口URL
 */

define('IS_API', 'pay'); // 项目标识
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME)); // 该文件的名称
require('../../../index.php'); // 引入主文件