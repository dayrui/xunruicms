<?php

/**
 * 余额支付发起接口
 */

// 判断用户权限
if (!$this->uid) {
    $return = dr_return_data(0, dr_lang('你还没有登录'), ['url' => \Phpcmf\Service::L('router')->member_url('login/index')]);
} elseif ($data['type'] == 'recharge') {
    $return = dr_return_data(0, dr_lang('充值不能使用余额支付'));
} elseif ($data['uid'] != $this->uid) {
    $return = dr_return_data(0, dr_lang('无权限操作'));
} elseif ((float)\Phpcmf\Service::C()->member['money'] <= 0 ) {
    $return = dr_return_data(0, dr_lang('账户余额不足'));
} elseif (\Phpcmf\Service::C()->member['money'] - $data['value'] < 0) {
    $return = dr_return_data(0, dr_lang('账户可用余额不足'));
} else {
    $rt = $this->paysuccess('fc-'.$id, '');
    if (!$rt['code']) {
        $return = $rt;
    } else {
        dr_redirect(dr_url('api/pay/call', ['id'=>$id]));exit;
    }
}



