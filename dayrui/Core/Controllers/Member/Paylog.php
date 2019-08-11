<?php namespace Phpcmf\Controllers\Member;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



class Paylog extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单显示名称
        $this->name = dr_lang('资金流水');
        // 初始化数据表
        $this->_init([
            'table' => 'member_paylog',
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
                'url' =>\Phpcmf\Service::L('Router')->member_url('member/paylog/index', $data['param'])
            ];
        }

        \Phpcmf\Service::V()->assign([
            'tid' => $tid,
            'type' => $my,
        ]);
        \Phpcmf\Service::V()->display('paylog_index.html');
    }
    
    public function show() {

        $id = \Phpcmf\Service::L('input')->get('id');
        strpos($id, '-') !== false && list($a, $id) = explode('-', $id);

        list($a, $data) = $this->_Show((int)$id);
        if (!$data) {
            $this->_msg(0, dr_lang('交易记录不存在'));exit;
        } elseif ($data['uid'] != $this->uid) {
            $this->_msg(0, dr_lang('无权限查看'));exit;
        }
        
        \Phpcmf\Service::V()->display('paylog_show.html');
    }
    
}
