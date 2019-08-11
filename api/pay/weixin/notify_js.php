<?php

/**
 * 微信支付ajax实时监测状态接口
 * $result // 支付记录表的回调详情
 */

define('APPID', $config['appid']);
define('MCHID', $config['mchid']);
define('KEY', $config['key']);
define('APPSECRET', $config['appsecret']);
define('REPORT_LEVENL', 0);

require "WxPay.Data.php";
require "WxPay.Api.php";
require "WxPay.Notify.php";

$sn = \Phpcmf\Service::C()->member_cache['pay']['prefix'].date('YmdHis', $data['inputtime']).'-'.$data['id'];
$input = new WxPayOrderQuery();
$input->SetOut_trade_no($sn);
$rt = WxPayApi::orderQuery($input);

// 判断支付成功
if ($rt['result_code'] == 'SUCCESS' && $rt['return_code'] == 'SUCCESS' && $rt['trade_state'] == 'SUCCESS') {
    $rt =  \Phpcmf\Service::M('Pay')->paysuccess($rt['out_trade_no'], $rt["transaction_id"]);
    $return = ['code' => 1, 'msg' => 'ok'];
} elseif ($rt['return_code'] == 'FAIL') {
    $return = ['code' => 0, 'msg' => $rt['return_msg']];
} elseif (isset($rt['code']) && $rt['code'] == 0) {
    $return = ['code' => 0, 'msg' => $rt['msg']];
} else {
    $return = ['code' => 0, 'msg' => dr_lang('未付款')];
}