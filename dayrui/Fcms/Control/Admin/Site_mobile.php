<?php namespace Phpcmf\Control\Admin;

class Site_mobile extends \Phpcmf\Common
{

	public function index() {

		if (IS_AJAX_POST) {
		    $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['mode']) {
                if (!\Phpcmf\Service::L('Form')->check_domain($post['domain'])) {
                    $this->_json(0, dr_lang('域名（%s）格式不正确', $post['domain']));
                } elseif ($this->site_info[SITE_ID]['SITE_DOMAIN'] == $post['domain']) {
                    $this->_json(0, dr_lang('手机域名不能与电脑相同'));
                }
            }
            if ($post['mode'] == -1) {
                $post['auto'] = $post['auto2'];
                $post['tohtml'] = 0;
                $post['dirname'] = $post['domain'] = '';
            } elseif ($post['mode'] == 1) {
                // 生成手机目录
                $rt = \Phpcmf\Service::M('cache')->update_mobile_webpath(WEBPATH, $post['dirname']);
                if ($rt) {
                    $this->_json(0, dr_lang($rt));
                }
            }
			$rt = \Phpcmf\Service::M('Site')->config(SITE_ID, 'mobile', $post);
            if (!is_array($rt)) {
                $this->_json(0, dr_lang('项目信息(#%s)不存在', SITE_ID));
            }
			\Phpcmf\Service::L('input')->system_log('设置手机项目参数');
            \Phpcmf\Service::M('cache')->sync_cache('');
			$this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

        if (!isset($data['mobile']['dirname']) || !$data['mobile']['dirname']) {
            $data['mobile']['dirname'] = 'mobile';
        }
        if (!isset($data['mobile']['mode']) || (!$data['mobile']['mode'] && !$data['mobile']['domain'])) {
            $data['mobile']['mode'] = -1;
        }

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['mobile'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '手机项目' => ['site_mobile/index', 'fa fa-mobile'],
                    'help' => [506],
                ]
            ),
			'is_tpl' => is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html'),
		]);
		\Phpcmf\Service::V()->display('site_mobile.html');
	}
	
}
