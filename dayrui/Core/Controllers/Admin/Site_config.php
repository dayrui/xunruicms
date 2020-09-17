<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Site_config extends \Phpcmf\Common
{
	public function index() {

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $field = [
            'logo' => [
                'ismain' => 1,
                'fieldtype' => 'File',
                'fieldname' => 'logo',
                'setting' => ['option' => ['ext' => 'jpg,gif,png,jpeg', 'size' => 10, 'input' => 1]]
            ]
        ];

		if (IS_AJAX_POST) {

		    $tj = $_POST['data']['SITE_TONGJI'];
            $post = \Phpcmf\Service::L('input')->post('data');
            $post['SITE_TONGJI'] = $tj;
            if ($_POST['theme']) {
                // 远程资源
                $post['SITE_THEME'] = $post['SITE_THEME2'];
            } else {
                // 本地资源
            }

            $rt = \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $post);
			if (!is_array($rt)) {
			    $this->_json(0, dr_lang('网站信息(#%s)不存在', SITE_ID));
            }

			\Phpcmf\Service::L('input')->system_log('设置网站参数');

            // 附件归档
            if (SYS_ATTACHMENT_DB) {
                list($post, $return, $attach) = \Phpcmf\Service::L('form')->validation($post, null, $field);
                $attach && \Phpcmf\Service::M('Attachment')->handle($this->member['id'], \Phpcmf\Service::M()->dbprefix('site'), $attach);
            }

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
            'logofield' => dr_fieldform($field['logo'], $data['config']['logo']),
			'template_path' => dr_dir_map(TPLPATH.'pc/', 1),
			'my_site_info' => is_file(MYPATH.'View/site_info.html') ? MYPATH.'View/site_info.html' : '',
		]);
		\Phpcmf\Service::V()->display('site_config.html');
	}

}
