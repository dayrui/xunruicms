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



class Notice extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单显示名称
        $this->name = dr_lang('提醒');
        // 初始化数据表
        $this->_init([
            'table' => 'member_notice',
            'order_by' => 'inputtime desc',
        ]);
        \Phpcmf\Service::M()->db->table($this->init['table'])->where('uid', $this->uid)->update(['isnew' => 0]);
    }

    // index
    public function index() {

        $tid = (int)\Phpcmf\Service::L('input')->get('tid');
        $where = ['`uid`='.$this->uid];
        $tid && $where[] = '`type`='.$tid;
        
        \Phpcmf\Service::M()->set_where_list(implode(' AND ', $where));
        list($tpl, $data) = $this->_List(['tid' => $tid]);

        // 初始化
        $data['param']['tid'] = $data['param']['total'] = 0;

        // 列出类别
        $type = [
            0 => [
                'name' => dr_lang('全部'),
                'icon' => '<i class="fa fa-bell"></i>',
            ],
        ];
        $type = $type + dr_notice_info();
        foreach ($type as $i => $t) {
            $data['param']['tid'] = $i;
            $type[$i]['url'] =\Phpcmf\Service::L('Router')->member_url('member/notice/index', $data['param']);
        }

        \Phpcmf\Service::V()->assign([
            'tid' => $tid,
            'type' => $type,
        ]);
        \Phpcmf\Service::V()->display('notice_index.html');
    }

    public function go() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table('member_notice')->where('id', $id)->where('uid', $this->uid)->getRow();
        if (!$data) {
            $this->_msg(0, dr_lang('此消息不存在'));
        } elseif ($data['url']) {
            \Phpcmf\Service::M()->db->table('member_notice')->where('id', $id)->update(['isnew' => 0]);
            dr_redirect($data['url']);
        } else {
            dr_redirect(dr_member_url('notice/index'));
        }

        exit;
    }

    
}
