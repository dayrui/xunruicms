<?php

/**
 * 支付宝发起接口
 */

if (\Phpcmf\Service::IS_PC() || !$config['wap']) {
	require 'pc_pay.php';
} else {
	
	require 'wap_pay.php';
}