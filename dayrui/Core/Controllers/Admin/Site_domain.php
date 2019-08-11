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



class Site_domain extends \Phpcmf\Common
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '域名设置' => ['site_domain/index', 'fa fa-cog'],
                    '域名绑定说明' => ['site_domain/bang_index', 'fa fa-code'],
                    'help' => ['407'],
                ]
            ),
        ]);
    }

    public function index() {

        if (IS_AJAX_POST) {
            $data = $post = \Phpcmf\Service::L('input')->post('data', true);
            foreach ($post as $name => $value) {
                unset($data[$name]);
                if ($value && in_array($value, $data)) {
                    exit($this->_json(0, dr_lang('域名[%s]绑定重复', $value)));
                }
                $data[$name] = $value;
            }
            \Phpcmf\Service::M('Site')->domain($post);
            \Phpcmf\Service::M('cache')->sync_cache('');
            \Phpcmf\Service::L('input')->system_log('设置网站域名参数');
            exit($this->_json(1, dr_lang('操作成功')));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        list($module, $data) = \Phpcmf\Service::M('Site')->domain();

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'module' => $module,
        ]);
        \Phpcmf\Service::V()->display('site_domain.html');
    }

    public function bang_index() {

        list($module, $data) = \Phpcmf\Service::M('Site')->domain();

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'module' => $module
        ]);
        \Phpcmf\Service::V()->display('site_domain_bang.html');
    }

    public function edit() {

        if (IS_POST) {
            $domain = trim(\Phpcmf\Service::L('input')->post('domain', true));
            if (!$domain) {
                exit($this->_json(0, dr_lang('域名不能为空')));
            }

            \Phpcmf\Service::M('Site')->edit_domain($domain);
            \Phpcmf\Service::L('input')->system_log('变更网站主域名');
            exit($this->_json(1, dr_lang('操作成功，请域名解析到本站IP')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('site_domain_edit.html');exit;
    }

}

