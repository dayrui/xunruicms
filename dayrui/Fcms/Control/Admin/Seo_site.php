<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Seo_site extends \Phpcmf\Common {

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
            $this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['seo'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '站点SEO' => ['seo_site/index', 'fa fa-cog'],
                    'help' => [494],
                ]
            ),
            'module' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
            'SITE_INDEX_HTML' => $data['config']['SITE_INDEX_HTML'],
		]);
		\Phpcmf\Service::V()->display('seo_site.html');
	}

	public function sync_index() {

        $value = intval(\Phpcmf\Service::L('input')->get('value'));
        if (!$value) {
            $this->_json(0, dr_lang('未选择URL规则'));
        }

        if ($value == 999) {
            $value = 0;
        }

        $category = \Phpcmf\Service::M()->table_site('share_category')->getAll();
        if (!$category) {
            $this->_json(0, dr_lang('系统没有创建共享栏目'));
        }

        foreach ($category as $data) {
            $data['setting'] = dr_string2array($data['setting']);
            $data['setting']['urlrule'] = $value;
            \Phpcmf\Service::M()->table_site('share_category')->update($data['id'], [
                'setting' => dr_array2string($data['setting']),
            ]);
        }

        \Phpcmf\Service::M('cache')->sync_cache('');
        $this->_json(1, dr_lang('共设置%s个共享栏目', count($category)));
    }

}
