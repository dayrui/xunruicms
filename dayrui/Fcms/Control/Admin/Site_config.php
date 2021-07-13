<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Site_config extends \Phpcmf\Common
{
	public function index() {

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            if (isset($_POST['theme']) && $_POST['theme']) {
                // 远程资源
                $post['SITE_THEME'] = $post['SITE_THEME2'];
            } else {
                // 本地资源
            }

            // 防止参数丢失
            $data['config']['SITE_NAME'] = $post['SITE_NAME'];
            $data['config']['SITE_CLOSE'] = $post['SITE_CLOSE'];
            $data['config']['SITE_INDEX_HTML'] = $post['SITE_INDEX_HTML'];
            $data['config']['SITE_CLOSE_MSG'] = $post['SITE_CLOSE_MSG'];
            $data['config']['SITE_LANGUAGE'] = $post['SITE_LANGUAGE'];
            $data['config']['SITE_TEMPLATE'] = $post['SITE_TEMPLATE'];
            $data['config']['SITE_TIMEZONE'] = $post['SITE_TIMEZONE'];
            $data['config']['SITE_TIME_FORMAT'] = $post['SITE_TIME_FORMAT'];
            $data['config']['SITE_THEME'] = $post['SITE_THEME'];

            $rt = \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $data['config']);
			if (!is_array($rt)) {
			    $this->_json(0, dr_lang('网站信息(#%s)不存在', SITE_ID));
            }

			\Phpcmf\Service::L('input')->system_log('设置网站参数');

            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['config'],
			'form' => dr_form_hidden(['page' => $page]),
			'lang' => dr_dir_map(ROOTPATH.'api/language/', 1),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '网站设置' => ['site_config/index', 'fa fa-cog'],
                    'help' => [505],
                ]
            ),
			'theme' => dr_get_theme(),
			'is_theme' => strpos($data['config']['SITE_THEME'], '/') !== false ? 1 : 0,
			'template_path' => dr_dir_map(TPLPATH.'pc/', 1),
		]);
		\Phpcmf\Service::V()->display('site_config.html');
	}

}
