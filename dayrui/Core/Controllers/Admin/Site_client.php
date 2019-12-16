<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Site_client extends \Phpcmf\Common
{
	public function index() {

		if (IS_AJAX_POST) {
		    $save = [];
		    $post = \Phpcmf\Service::L('input')->post('data', true);
		    if ($post) {
                foreach ($post as $i => $t) {
                    if (isset($t['name'])) {
                        if (!preg_match('/^[a-z]+/i', $t['name'])) {
                            $this->_json(0, dr_lang('终端目录必须是英文字母'));
                        } elseif (!$t['name']) {
                            $this->_json(0, dr_lang('终端目录必须填写'));
                        }
                        $save[$i]['name'] = $t['name'];
                    } else {
                        if (!$t['domain']) {
                            $this->_json(0, dr_lang('域名必须填写'));
                        } elseif (strpos($t['domain'], '//') !== false) {
                            $this->_json(0, dr_lang('域名只能填写纯域名，不能加http://'));
                        }
                        $save[$i-1]['domain'] = $t['domain'];
                    }
                }
            }

			$rt = \Phpcmf\Service::M('Site')->save_config(
			    SITE_ID,
                'client',
                $save
            );
            if (!is_array($rt)) {
                $this->_json(0, dr_lang('网站终端(#%s)不存在', SITE_ID));
            }
            \Phpcmf\Service::M('cache')->sync_cache('');
			\Phpcmf\Service::L('input')->system_log('设置网站自定义终端参数');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        list($module, $domain) = \Phpcmf\Service::M('Site')->domain();

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['client'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '网站终端' => ['site_client/index', 'fa fa-cog'],
                    'help' => [478],
                ]
            ),
            'pc_domain' => $domain['site_domain'],
            'mobile_domain' => $domain['mobile_domain'],
		]);
		\Phpcmf\Service::V()->display('site_client.html');
	}

	
}
