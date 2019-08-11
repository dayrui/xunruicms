<?php

/**
 * 微信支付发起接口
 */

if (IS_API_HTTP) {
    define('APPID', \Phpcmf\Service::C()->get_cache('weixin', 'xcx', 'appid'));
    define('APPSECRET', \Phpcmf\Service::C()->get_cache('weixin', 'xcx', 'appsecret'));
} else {
    define('APPID', $config['appid']);
    define('APPSECRET', $config['appsecret']);
}

define('MCHID', $config['mchid']);
define('KEY', $config['key']);
define('NOTIFY_URL', ROOT_URL."api/pay/".$data['type']."/notify_url.php");
define('NOTIFY_API_URL', ROOT_URL."index.php?s=api&c=pay&m=ajax&id=".$id);
define('REPORT_LEVENL', 0);

require "WxPay.Data.php";
require "WxPay.Api.php";
require "WxPay.JsApiPay.php";
require "WxPay.NativePay.php";

// 付款界面模板
$htmlfile = is_file(WEBPATH.'config/pay/payweixin.html') ? WEBPATH.'config/pay/payweixin.html' : ROOTPATH.'config/pay/payweixin.html';
$member = \Phpcmf\Service::C()->member;

if (IS_API_HTTP) {
    // 客户端小程序请求
    //①、获取用户openid
    $oauth = $this->table('member_oauth')->where('uid', $data['uid'])->where('oauth', 'wxxcx')->getRow();
    if (!$oauth) {
        $return = dr_return_data(0, '服务器没有此用户');
    } else {
        $input = new WxPayUnifiedOrder();
        $input->SetBody($data['title']);
        $input->SetOut_trade_no($sn);
        $input->SetTotal_fee($data['value'] * 100); // 金额
        $input->SetTime_start(date("YmdHis", SYS_TIME));
        $input->SetTime_expire(date("YmdHis", SYS_TIME + 7200));
        $input->SetNotify_url(NOTIFY_URL);
        $input->SetTrade_type("JSAPI"); // JSAPI，NATIVE，APP
        $input->SetProduct_id($pid);
        $input->SetOpenid($oauth['oid']);
        $order = WxPayApi::unifiedOrder($input);
        if (isset($order['code']) && $order['code'] == 0) {
            $return = dr_return_data(0, $order['msg']);
        } elseif ($order["err_code_des"]) {
            $return = dr_return_data(0, $order['err_code_des']);
        } else {
            // 存储支付结果
            $order['sn'] = $sn;
            $tools = new JsApiPay();
            $param = $tools->GetJsApiParameters($order);
            \Phpcmf\Service::C()->_json(1, 'ok', json_decode($param, true));
        }
    }
} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
    // 手机微信客户端调用jsapi
    //①、获取用户openid
    $tools = new JsApiPay();
    $openId = $tools->GetOpenid();
    if (!$openId) {
        $return = dr_return_data(0, 'openId获取失败');
    } else {
        // 统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($data['title']);
        $input->SetOut_trade_no($sn);
        $input->SetTotal_fee($data['value'] * 100); // 金额
        $input->SetTime_start(date("YmdHis", SYS_TIME));
        $input->SetTime_expire(date("YmdHis", SYS_TIME + 7200));
        $input->SetNotify_url(NOTIFY_URL);
        $input->SetTrade_type("JSAPI"); // JSAPI，NATIVE，APP
        $input->SetProduct_id($pid);
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        if (isset($order['code']) && $order['code'] == 0) {
            $return = dr_return_data(0, $order['msg']);
        } elseif ($order["err_code_des"]) {
            $return = dr_return_data(0, $order['err_code_des']);
        } else {
            $jsApiParameters = $tools->GetJsApiParameters($order);
            //获取共享收货地址js函数参数
            $editAddress = $tools->GetEditAddressParameters();
            // 存储支付结果
            $order['sn'] = $sn;
            $code = '
<script type="text/javascript">
    //调用微信JS api 支付
    function jsApiCall()
    {
        WeixinJSBridge.invoke(
            \'getBrandWCPayRequest\',
            ' . $jsApiParameters . ',
            function(res){
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    // 付款成功
                    window.location.href = "'.ROOT_URL.'index.php?s=api&c=pay&m=call&id='.$id.'";
                } else if (res.err_msg == "get_brand_wcpay_request:cancel") {
                    dr_tips(0, "'.dr_lang('付款取消').'");
                } else {
                    dr_tips(0, "'.dr_lang('服务端错误: ').'"+res.err_msg);
                }
            }
        );
    }

    function callpay()
    {
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener(\'WeixinJSBridgeReady\', jsApiCall, false);
            }else if (document.attachEvent){
                document.attachEvent(\'WeixinJSBridgeReady\', jsApiCall); 
                document.attachEvent(\'onWeixinJSBridgeReady\', jsApiCall);
            }
        }else{
            jsApiCall();
        }
    }
    </script>
        <button class="fc-weixin-pay" type="button" onclick="callpay()" >立即支付</button>
    ';

            // 获取付款界面代码
            ob_start();
            $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
            require_once $file;
            $html = ob_get_clean();
            $return = dr_return_data(1, 'ok', $html);
        }
    }
} elseif (\Phpcmf\Service::C()->_is_mobile()) {
    // 手机端H5支付
    $input = new WxPayUnifiedOrder();
    $input->SetBody($data['title']);
    $input->SetOut_trade_no($sn);
    $input->SetTotal_fee($data['value'] * 100); // 金额
    $input->SetTime_start(date("YmdHis", SYS_TIME));
    $input->SetTime_expire(date("YmdHis", SYS_TIME + 7200));
    $input->SetNotify_url(NOTIFY_URL);
    $input->SetTrade_type("MWEB"); // JSAPI，NATIVE，APP
    $input->SetProduct_id($pid);
    $input->SetOpenid($openId);
    $order = WxPayApi::unifiedOrder($input);

    if (isset($order['code']) && $order['code'] == 0) {
        $return = dr_return_data(0, $order['msg']);
    } elseif ($order["err_code_des"]) {
        $return = dr_return_data(0, $order['err_code_des']);
    } else {

        // 存储支付结果
        $order['sn'] = $sn;
        $code = '
    <a class="fc-weixin-pay" style="padding-top:10px;" href="'.$order['mweb_url'].'" >立即支付</a>
    <script>
        function dr_weixin_notify() {
            $.ajax({
                type : "post",
                url : "'.NOTIFY_API_URL.'",
                dataType : "jsonp",
                jsonp: "callback",
                jsonpCallback:"success_jsonpCallback",
                success : function(html){
                    if (html.code == 1) {
                        window.location.href = "'.ROOT_URL.'index.php?s=api&c=pay&m=call&id='.$id.'";
                    }
                },
                error:function(){ }
            });
        }
        $(function(){
            setInterval(\'dr_weixin_notify()\', 1000);
        });
    </script>';

        // 获取付款界面代码
        ob_start();
        $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
        require_once $file;
        $html = ob_get_clean();
        $return = dr_return_data(1, 'ok', $html);
    }

} else {
    // 电脑扫码支付
    $notify = new NativePay();
    // 统一下单
    $input = new WxPayUnifiedOrder();
    $input->SetBody($data['title']);
    $input->SetOut_trade_no($sn);
    $input->SetTotal_fee($data['value'] * 100); // 金额
    $input->SetTime_start(date("YmdHis", SYS_TIME));
    $input->SetTime_expire(date("YmdHis", SYS_TIME + 7200));
    $input->SetNotify_url(NOTIFY_URL);
    $input->SetTrade_type("NATIVE"); // JSAPI，NATIVE，APP
    $input->SetProduct_id($pid);
    $result = $notify->GetPayUrl($input);
    if (isset($result['code']) && $result['code'] == 0) {
        $return = dr_return_data(0, $result['msg']);
    } elseif ($result["err_code_des"]) {
        $return = dr_return_data(0, $result['err_code_des']);
    } else {
        // 存储支付结果
        $result['sn'] = $sn;
        $code = '
    <img  src="'.dr_qrcode($result["code_url"]).'" style="width:150px;height:150px;margin-top:20px;"/>
    <script>
        function dr_weixin_notify() {
            $.ajax({
                type : "post",
                url : "'.NOTIFY_API_URL.'",
                dataType : "jsonp",
                jsonp: "callback",
                jsonpCallback:"success_jsonpCallback",
                success : function(html){
                    if (html.code == 1) {
                        window.location.href = "'.ROOT_URL.'index.php?s=api&c=pay&m=call&id='.$id.'";
                    }
                },
                error:function(){ }
            });
        }
        $(function(){
            setInterval(\'dr_weixin_notify()\', 1000);
        });
    </script>';
        // 获取付款界面代码
        ob_start();
        $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
        require_once $file;
        $html = ob_get_clean();
        $return = dr_return_data(1, 'ok', $html);
    }
}