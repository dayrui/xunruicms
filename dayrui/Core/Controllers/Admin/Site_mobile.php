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



class Site_mobile extends \Phpcmf\Common
{
	public function index() {

		if (IS_AJAX_POST) {
			$rt = \Phpcmf\Service::M('Site')->config(
			    SITE_ID,
                'mobile',
                \Phpcmf\Service::L('input')->post('data', true)
            );
            !is_array($rt) && $this->_json(0, dr_lang('网站信息(#%s)不存在', SITE_ID));
			\Phpcmf\Service::L('input')->system_log('设置手机网站参数');
            \Phpcmf\Service::M('cache')->sync_cache('');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['mobile'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '手机网站' => ['site_mobile/index', 'fa fa-mobile'],
                    'help' => [506],
                ]
            ),
			'is_tpl' => is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html'),
		]);
		\Phpcmf\Service::V()->display('site_mobile.html');
	}

	
}
