<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class System extends \Phpcmf\Common
{
	public function index() {

        if (is_file(WRITEPATH.'config/system.php')) {
            $data = require WRITEPATH.'config/system.php'; // 加载网站系统配置文件
        } else {
            $data = [];
        }

		if (IS_AJAX_POST) {
		    $post = \Phpcmf\Service::L('input')->post('data', true);
            $save = [
                'SYS_DEBUG' => (int)$post['SYS_DEBUG'],
                'SYS_THEME_ROOT' => (int)$post['SYS_THEME_ROOT'],
                'SYS_AUTO_FORM' => (int)$post['SYS_AUTO_FORM'],

                'SYS_CAT_RNAME' => (int)$post['SYS_CAT_RNAME'],
                'SYS_PAGE_RNAME' => (int)$post['SYS_PAGE_RNAME'],
                'SYS_CAT_ZSHOW' => (int)$post['SYS_CAT_ZSHOW'],

                'SYS_EMAIL' => $post['SYS_EMAIL'],
                'SYS_ADMIN_LOG' => intval($post['SYS_ADMIN_LOG']),
                'SYS_ADMIN_CODE' => intval($post['SYS_ADMIN_CODE']),
                'SYS_ADMIN_LOGINS' => intval($post['SYS_ADMIN_LOGINS']),
                'SYS_ADMIN_LOGIN_TIME' => intval($post['SYS_ADMIN_LOGIN_TIME']),
                'SYS_ADMIN_PAGESIZE' => intval($post['SYS_ADMIN_PAGESIZE']),
                'SYS_ADMIN_OAUTH' => intval($post['SYS_ADMIN_OAUTH']),

                'SYS_KEY' => dr_safe_filename($post['SYS_KEY'] == '************' ? $data['SYS_KEY'] : $post['SYS_KEY']),
                'SYS_HTTPS' => (int)$post['SYS_HTTPS'],
                'SYS_CSRF' => (int)$post['SYS_CSRF'],
                'SYS_API_CODE' => (int)$post['SYS_API_CODE'],
                'SYS_BDMAP_API' => $post['SYS_BDMAP_API'],
                'SYS_BDNLP_AK' => $post['SYS_BDNLP_AK'],
                'SYS_BDNLP_SK' => $post['SYS_BDNLP_SK'],
            ];
            foreach ($data as $name => $value) {
                strpos($name, 'SYS_CACHE') === 0 && $save[$name] = intval($post[$name]);
            }
			\Phpcmf\Service::M('System')->save_config($data, $save);
			\Phpcmf\Service::L('input')->system_log('设置系统配置参数');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		$page = (int)\Phpcmf\Service::L('input')->get('page');
		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统参数' => ['system/index', 'fa fa-cog'],
                    'help' => [503],
                ]
            ),
            'config' => \Phpcmf\Service::M('System')->config,
		]);
		\Phpcmf\Service::V()->display('system_index.html');
	}

}
