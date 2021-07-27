<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Member_pay extends \Phpcmf\Common {

    public function __construct(...$params) {
        parent::__construct(...$params);
        $fdata = \Phpcmf\Service::L('Field')->sys_field(['uid']);
        $fdata['uid']['name'] = dr_lang('用户账号');
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户充值' => ['member_pay/index', 'fa fa-rmb'],
                    '资金冻结' => ['member_pay/freeze_index', 'bi bi-dash-circle-fill'],
                    'help' => [ 600 ],
                ]
            ),
            'myfield' => dr_rp(\Phpcmf\Service::L('Field')->toform(0, $fdata), 'width:100%;', 'width:300px;'),
        ]);
    }

    public function index() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['uid']) {
                $this->_json(0, dr_lang('账号不能为空'), ['field' => 'uid']);
            }
            $user = \Phpcmf\Service::M()->db->table('member')->where('username', $post['uid'])->get()->getRowArray();
            if (!$user) {
                $this->_json(0, dr_lang('账号[%s]不存在', $post['uid']), ['field' => 'uid']);
            } elseif (!$post['value']) {
                $this->_json(0, dr_lang('金额值未填写'), ['field' => 'value']);
            } elseif (!$post['unit']) {
                $this->_json(0, dr_lang('充值类型未选择'), ['field' => 'unit']);
            } elseif (!$post['note']) {
                $this->_json(0, dr_lang('备注说明未填写'), ['field' => 'note']);
            }
            if ($post['type'] == 1) {
                // 增加
                $post['value'] = abs($post['value']);
                $msg = '充值%s成功';
            } else {
                // 减少
                $post['value'] = abs($post['value']) * -1;
                $msg = '扣减%s成功';
            }
            if ($post['unit'] == 1) {
                // 积分
                if ($user['score'] + $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号%s不足', SITE_SCORE), ['field' => 'value']);
                }
                // 付款方的钱
                $rt = \Phpcmf\Service::M('member')->add_score($user['id'], $post['value'], $post['note']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                $this->_json(1, dr_lang($msg, SITE_SCORE.$post['value']));
            } elseif ($post['unit'] == 3) {
                // 升级值
                if ($user['experience'] + $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号%s不足', SITE_EXPERIENCE), ['field' => 'value']);
                }
                // 付款方的钱
                $rt = \Phpcmf\Service::M('member')->add_experience($user['id'], $post['value'], $post['note']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                $this->_json(1, dr_lang($msg, SITE_EXPERIENCE.$post['value']));
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
                    'touid' => $user['id'],
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
                $this->_json(1, dr_lang($msg, 'RMB'.$post['value']));
            }
        }

        \Phpcmf\Service::V()->display('member_pay.html');
    }

    public function freeze_index() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['uid']) {
                $this->_json(0, dr_lang('账号不能为空'), ['field' => 'uid']);
            }
            $user = \Phpcmf\Service::M()->db->table('member')->where('username', $post['uid'])->get()->getRowArray();
            if (!$user) {
                $this->_json(0, dr_lang('账号[%s]不存在', $post['uid']), ['field' => 'uid']);
            } elseif (!$post['note']) {
                $this->_json(0, dr_lang('备注说明未填写'), ['field' => 'note']);
            }

            $post['value'] = abs($post['value']);
            if ($post['type'] == 1) {
                // 冻结
                if (!$post['value']) {
                    $this->_json(0, dr_lang('金额值未填写'), ['field' => 'value']);
                } elseif ($user['money'] - $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号可用金额不足'), ['field' => 'value']);
                }
                \Phpcmf\Service::M('member')->add_freeze($user['id'], $post['value']);
                // 增加到交易流水
                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                    'uid' => $user['id'],
                    'touid' => $user['id'],
                    'mid' => 'admin-freeze',
                    'title' => '后台冻结资金',
                    'value' => -$post['value'],
                    'type' => 'system',
                    'status' => 1,
                    'result' => $post['note'],
                    'paytime' => SYS_TIME,
                    'inputtime' => SYS_TIME,
                ]);
                $msg = '冻结%s成功';
            } elseif ($post['type'] == 0) {
                // 解冻
                if (!$post['value']) {
                    $this->_json(0, dr_lang('金额值未填写'), ['field' => 'value']);
                } elseif ($user['freeze'] - $post['value'] < 0) {
                    $this->_json(0, dr_lang('账号可用冻结金额不足'), ['field' => 'value']);
                }
                \Phpcmf\Service::M('member')->cancel_freeze($user['id'], $post['value']);
                // 增加到交易流水
                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                    'uid' => $user['id'],
                    'touid' => $user['id'],
                    'mid' => 'admin-freeze',
                    'title' => '后台解冻资金',
                    'value' => abs($post['value']),
                    'type' => 'system',
                    'status' => 1,
                    'result' => $post['note'],
                    'paytime' => SYS_TIME,
                    'inputtime' => SYS_TIME,
                ]);
                $msg = '解冻%s成功';
            } else {
                // 指定任意值
                \Phpcmf\Service::M()->table('member')->update($user['id'], ['freeze' => $post['value'] ]);
                // 增加到交易流水
                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                    'uid' => $user['id'],
                    'touid' => $user['id'],
                    'mid' => 'admin-freeze',
                    'title' => '后台设置指定冻结资金',
                    'value' => abs($post['value']),
                    'type' => 'system',
                    'status' => 1,
                    'result' => $post['note'],
                    'paytime' => SYS_TIME,
                    'inputtime' => SYS_TIME,
                ]);
                $msg = '设置指定冻结资金%s';
            }

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
            \Phpcmf\Service::L('Notice')->send_notice('pay_admin_freeze', $call);
            // 钩子
            \Phpcmf\Hooks::trigger('pay_admin_freeze', $call);
            $this->_json(1, dr_lang($msg, abs($post['value'])));

        }

        \Phpcmf\Service::V()->display('member_pay_freeze.html');
    }

}
