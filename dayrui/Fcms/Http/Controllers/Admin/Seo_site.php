<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


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
            if (!is_array($rt)) {
                $this->_json(0, dr_lang('网站SEO(#%s)不存在', SITE_ID));
            }
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
