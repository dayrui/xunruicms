<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



class Member_pay extends \Phpcmf\Common
{
    public function index() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $user = \Phpcmf\Service::M()->db->table('member')->where('username', $post['username'])->get()->getRowArray();
            if (!$user) {
                $this->_json(0, dr_lang('账号[%s]不存在', $post['username']), ['field' => 'username']);
            } elseif (!$post['value']) {
                $this->_json(0, dr_lang('金额值未填写'), ['field' => 'value']);
            }  elseif (!$post['unit']) {
                $this->_json(0, dr_lang('充值类型未选择'), ['field' => 'unit']);
            }  elseif (!$post['note']) {
                $this->_json(0, dr_lang('备注说明未填写'), ['field' => 'note']);
            }
            if ($post['unit'] == 1) {
                // 金币
                if ($user['score'] + $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号余额不足'), ['field' => 'value']);
                }
                // 付款方的钱
                $rt = \Phpcmf\Service::M('member')->add_score($user['id'], $post['value'], $post['note']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                $this->_json(1, dr_lang('充值%s成功', SITE_SCORE.$post['value']));
            } elseif ($post['unit'] == 3) {
                // 升级
                if ($post['value'] < 0) {
                    $this->_json(0, dr_lang('%s不能是负数', SITE_EXPERIENCE), ['field' => 'value']);
                }
                // 付款方的钱
                $rt = \Phpcmf\Service::M('member')->add_experience($user['id'], $post['value'], $post['note']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                $this->_json(1, dr_lang('充值%s成功', SITE_EXPERIENCE.$post['value']));
            } else {
                // rmb
                if ($user['money'] + $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号余额不足'), ['field' => 'value']);
                }
                // 付款方的钱
                $rt = \Phpcmf\Service::M('Pay')->add_money($user['id'], $post['value']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                // 增加到交易流水
                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                    'uid' => $user['id'],
                    'username' => $user['username'],
                    'touid' => $user['id'],
                    'tousername' => $user['username'],
                    'mid' => 'system',
                    'title' => dr_lang('后台充值'),
                    'value' => $post['value'],
                    'type' => 'system',
                    'status' => 1,
                    'result' => $post['note'],
                    'paytime' => SYS_TIME,
                    'inputtime' => SYS_TIME,
                ]);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }

                $call = [
                    'uid' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'value' => $post['value'],
                    'url' => \Phpcmf\Service::L('Router')->member_url('paylog/show', ['id' => $rt['code']]),
                    'result' => $post['note'],
                ];
                // 通知
                \Phpcmf\Service::L('Notice')->send_notice('pay_admin', $call);
                // 钩子
                \Phpcmf\Hooks::trigger('pay_admin_after', $call);

                $this->_json(1, dr_lang('充值%s成功', 'RMB'.$post['value']));
            }
        }

        $fdata = \Phpcmf\Service::L('Field')->sys_field(['username']);
        $fdata['username']['name'] = dr_lang('充值账号');

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户充值' => ['member_pay/index', 'fa fa-rmb'],
                    'help' => [ 600 ],
                ]
            ),
            'myfield' => \Phpcmf\Service::L('Field')->toform(0, $fdata),
        ]);
        \Phpcmf\Service::V()->display('member_pay.html');
    }


}
