<?php

/**
 * 微信支付回调接口
 */

define('APPID', $config['appid']);
define('MCHID', $config['mchid']);
define('KEY', $config['key']);
define('APPSECRET', $config['appsecret']);
define('REPORT_LEVENL', 0);

require "WxPay.Data.php";
require "WxPay.Api.php";
require "WxPay.Notify.php";


class PayNotifyCallBack extends WxPayNotify
{

	
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $notfiyOutput = array();

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
		
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
		
		 // 处理支付表状态
		 $rt = \Phpcmf\Service::M('Pay')->paysuccess($data['out_trade_no'], $data["transaction_id"]);
		 //file_put_contents(WEBPATH."wx.txt", var_export($data, true));
        return true;
    }
}

$notify = new PayNotifyCallBack();
$notify->Handle(false);