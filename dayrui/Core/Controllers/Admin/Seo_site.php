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



class Seo_site extends \Phpcmf\Common
{
	public function index() {

		if (IS_AJAX_POST) {
			$rt = \Phpcmf\Service::M('Site')->config(
			    SITE_ID,
                'seo',
                \Phpcmf\Service::L('input')->post('data', true)
            );
            \Phpcmf\Service::M('Site')->config_value(SITE_ID, 'config', [
                'SITE_INDEX_HTML' => intval(\Phpcmf\Service::L('input')->post('SITE_INDEX_HTML'))
            ]);
            !is_array($rt) && $this->_json(0, dr_lang('网站SEO(#%s)不存在', SITE_ID));
			\Phpcmf\Service::L('input')->system_log('设置网站SEO');
            \Phpcmf\Service::M('cache')->sync_cache('');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['seo'],
			'SITE_INDEX_HTML' => $data['config']['SITE_INDEX_HTML'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '站点SEO' => ['seo_site/index', 'fa fa-cog'],
                    'help' => [494],
                ]
            ),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
		]);
		\Phpcmf\Service::V()->display('seo_site.html');
	}

	
}
