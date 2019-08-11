<?php namespace Phpcmf\Controllers\Admin;

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



// 任务队列
class Cron extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '任务管理' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-indent'],
                    'help' => [353],
                ]
            )
        ]);
        // 表单显示名称
        $this->name = dr_lang('任务队列');
        $this->is_data = 0;
        // 初始化数据表
        $this->_init([
            'table' => 'cron',
            'field' => [
                'type' => [
                    'ismain' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'type',
                ],
            ],
            'date_field' => 'inputtime',
            'order_by' => 'id desc,status asc',
        ]);
    }

    // 任务管理
    public function index() {

        list($tpl, $data) = $this->_List();
        if ($data['list']) {
            foreach ($data['list'] as $i => $t) {
                $data['list'][$i]['value'] = ('<pre>'.str_replace([PHP_EOL, "'", '"'], ["<br>", "", ""], var_export(dr_string2array($t['value']), true)).'</pre>');
                $t['error'] && $data['list'][$i]['error'] = ('<pre>'.str_replace([PHP_EOL, "'", '"'], ["<br>", "", ""], var_export(dr_string2array($t['error']), true)).'</pre>');
            }
        }

        \Phpcmf\Service::V()->assign([
            'type' => [
                'weibo' => dr_lang('微博分享'),
                'email' => dr_lang('邮件发送'),
                'notice' => dr_lang('消息通知'),
            ],
            'list' => $data['list']
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    public function show() {

        list($tpl, $data) = $this->_Show(\Phpcmf\Service::L('input')->get('id'));

        \Phpcmf\Service::V()->assign([
            'show_error' => var_export(dr_string2array($data['error']), true),
            'show_value' => var_export(dr_string2array($data['value']), true),
        ]);
        \Phpcmf\Service::V()->display($tpl);exit;
    }

    // 后台删除任务
    public function del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            null,
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }


    // 执行任务
    public function post_add() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if ($ids) {
            foreach ($ids as $id) {
                //\Phpcmf\Service::M('cron')->do_cron_id($id);
                \Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $id ], 1);
            }
            $this->_json(1, dr_lang('任务已提交，等待执行结果'));
        } else {
            $this->_json(0, dr_lang('所选数据不存在'));
        }
    }




}
