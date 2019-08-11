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



class Member_setting_group extends \Phpcmf\Common
{

    public function __construct(...$params) {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
            [
                '用户组权限' => ['member_setting_group/index', 'fa fa-cog'],
            ]
        ));
    }

    public function index() {

        // 用户组
        $list = [
            0 => [

                'use' => 1,
                'name' => dr_lang('游客'),
                'space' => '',
            ]
        ];

        foreach ($this->member_cache['group'] as $t) {
            $list[$t['id']] = [
                'use' => 1,
                'name' => dr_lang($t['name']),
                'space' => '',
            ];
            if ($t['level']) {
                foreach ($t['level'] as $lv) {
                    $list[$t['id'].'-'.$lv['id']] = [
                        'use' => 1,
                        'name' => dr_lang($lv['name']),
                        'space' => ' style="padding-left:40px"'
                    ];
                    $list[$t['id']]['use'] = 0;
                }
            }
        }

        // 获取会员全部配置信息
        $data = [];
        $result = \Phpcmf\Service::M()->db->table('member_setting')->get()->getResultArray();
        if ($result) {
            foreach ($result as $t) {
                $data[$t['name']] = dr_string2array($t['value']);
            }
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'group' => $list,
        ]);
        \Phpcmf\Service::V()->display('member_setting_group.html');
    }

    // 保存配置
    public function add() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'auth',
                'value' => dr_array2string(\Phpcmf\Service::L('input')->post('data', true))
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        } else {
            $this->_json(0, dr_lang('异常请求'));
        }
    }

}
