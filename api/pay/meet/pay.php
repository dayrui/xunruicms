<?php

/**
 * 上门收款发起接口
 */

// 判断用户权限
if (!$this->uid) {
    $return = dr_return_data(0, dr_lang('你还没有登录'), ['url' => \Phpcmf\Service::L('router')->member_url('login/index')]);
} elseif ($data['uid'] != $this->uid) {
    $return = dr_return_data(0, dr_lang('无权限操作'));
} else {
    // 如果来自订单先判断收获地址
    if (IS_POST && $data['status'] == 0) {
        $post = \Phpcmf\Service::L('Input')->post('data');
        if (!$post['name'] || !$post['phone'] || !$post['address']) {
            \Phpcmf\Service::C()->_json(0, dr_lang('填写不完整'));
        }
        // 上门地址
        $result = dr_safe_replace($post['name']).'('.dr_safe_replace($post['phone']).')'.dr_safe_replace($post['address']);
        // 确认申请上门收款
        $this->table('member_paylog')->update($id, [
            'status' => 5,
            'result' => $result,
        ]);
        // 通知后台
        \Phpcmf\Service::M('member')->admin_notice(0, 'pay', \Phpcmf\Service::C()->member, dr_lang('上门收款服务通知'), 'member_paymeet/edit:id/'.$id);
        \Phpcmf\Service::C()->_json(1, dr_lang('提交成功'));
    }
    // 付款界面模板
    $htmlfile = is_file(WEBPATH.'config/pay/paymeet.html') ? WEBPATH.'config/pay/paymeet.html' : ROOTPATH.'config/pay/paymeet.html';
    $member = \Phpcmf\Service::C()->member;
    $url = \Phpcmf\Service::L('router')->member_url('paylog/show', ['id'=>$id]);
    // 获取付款界面代码
    ob_start();
    $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
    require_once $file;
    $html = ob_get_clean();
    $return =  dr_return_data(1, 'ok', $html);
}



