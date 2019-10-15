<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Scorelog extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单显示名称
        $this->name = dr_lang('金币流水');
        // 初始化数据表
        $this->_init([
            'table' => 'member_scorelog',
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
        ]);
    }

    // index
    public function index() {

        $tid = (int)\Phpcmf\Service::L('input')->get('tid');
        $where = ['`uid`='.$this->uid];
        switch ($tid) {
            case 1: // 收入
                $where[] = '`value` > 0';
                break;
            case -1: // 消费
                $where[] = '`value` < 0';
                break;
            default : // 全部
                break;
        }

        \Phpcmf\Service::M()->set_where_list(implode(' AND ', $where));
        list($tpl, $data) = $this->_List(['tid' => $tid]);

        // 初始化
        $data['param']['tid'] = $data['param']['total'] = 0;

        // 列出类别
        $my = [];
        $type = ['0' => '全部', '1' => '收入', '-1' => '消费'];
        foreach ($type as $i => $t) {
            $data['param']['tid'] = $i;
            $my[$i] = [
                'name' => dr_lang($t),
                'url' =>\Phpcmf\Service::L('Router')->member_url('member/scorelog/index', $data['param'])
            ];
        }

        $my[9] = [
            'name' => dr_lang('兑换'),
            'url' =>\Phpcmf\Service::L('Router')->member_url('member/scorelog/add')
        ];
        $my[10] = [
            'name' => dr_lang('充值'),
            'url' =>\Phpcmf\Service::L('Router')->member_url('member/scorelog/pay')
        ];

        \Phpcmf\Service::V()->assign([
            'tid' => $tid,
            'type' => $my,
        ]);
        \Phpcmf\Service::V()->display('scorelog_index.html');
    }

    /**
     * 兑换金币
     */
    public function add()
    {
        if (IS_POST) {

            $value = intval(\Phpcmf\Service::L('input')->post('value'));
            if (!$this->member_cache['pay']['convert']) {
                $this->_json(0, dr_lang('系统没有设置兑换比例'));
            } elseif (!$value) {
                if ($_POST['value'] && strpos($_POST['value'], '.') !== false) {
                    $this->_json(0, dr_lang('兑换数量必须是整数'), ['field' => 'value']);
                }
                $this->_json(0, dr_lang('兑换数量必须填写'), ['field' => 'value']);
            }

            $price = floatval($value / $this->member_cache['pay']['convert']);
            if ($price <= 0) {
                $this->_json(0, dr_lang('支付价格有误'), ['field' => 'value']);
            } elseif ($this->member['money'] - $price < 0) {
                $this->_json(0, dr_lang('账户余额不足'));
            }

            $rt = \Phpcmf\Service::M('Pay')->add_money($this->uid, -$price);
            !$rt['code'] && $this->_json(0, $rt['msg']);
            $rt = \Phpcmf\Service::M('member')->add_score($this->uid, $value, dr_lang('自助兑换'));
            if (!$rt['code']) {
                \Phpcmf\Service::M('Pay')->add_money($this->uid, $price);
                $this->_json(0, $rt['msg']);
            }
            // 增加到交易流水
            \Phpcmf\Service::M('Pay')->add_paylog([
                'uid' => $this->member['id'],
                'username' => $this->member['username'],
                'touid' => 0,
                'tousername' => '',
                'mid' => 'system',
                'title' => dr_lang('兑换（%s）: %s', SITE_SCORE, $value),
                'value' => -$price,
                'type' => 'finecms',
                'status' => 1,
                'result' => '',
                'paytime' => SYS_TIME,
                'inputtime' => SYS_TIME,
            ]);

            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('scorelog_add.html');
    }

    /**
     * 在线充值
     */
    public function pay() {
        define('FC_PAY', 1);
        !$this->member_cache['pay']['convert'] && $this->_msg(0, dr_lang('系统没有设置兑换比例'));
        \Phpcmf\Service::V()->assign([
            'payfield' => dr_payform('score', '', '', '', 1),
        ]);
        \Phpcmf\Service::V()->display('scorelog_pay.html');
    }
}
