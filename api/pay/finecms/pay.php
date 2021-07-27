<?php

/**
 * 余额支付发起接口
 */

// 判断用户权限
if (!$this->uid) {
    $return = dr_return_data(0, dr_lang('你还没有登录'), [
        'url' => dr_member_url('login/index')
    ]);
} elseif ($data['type'] == 'recharge') {
    $return = dr_return_data(0, dr_lang('充值不能使用余额支付'), [
        'url' => dr_member_url('paylog/index')
    ]);
} elseif ($data['uid'] != $this->uid) {
    $return = dr_return_data(0, dr_lang('无权限操作'), [
        'url' => dr_member_url('paylog/index')
    ]);
} elseif ((float)\Phpcmf\Service::C()->member['money'] <= 0 ) {
    $return = dr_return_data(0, dr_lang('账户余额不足'), [
        'url' => dr_member_url('paylog/index')
    ]);
} elseif ((float)\Phpcmf\Service::C()->member['money'] - $data['value'] < 0) {
    $return = dr_return_data(0, dr_lang('账户可用余额不足'), [
        'url' => dr_member_url('paylog/index')
    ]);
} else {
    $rt = $this->paysuccess('fc-'.$id, '');
    if (!$rt['code']) {
        $return = $rt;
    } else {
        $return = dr_return_data(1, 'url', dr_url('api/pay/call', ['id'=>$id]));
    }
}



