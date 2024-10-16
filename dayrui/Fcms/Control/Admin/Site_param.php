<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Site_param extends \Phpcmf\Common {

	public function index() {

        if (IS_USE_MODULE) {
            dr_redirect(dr_url('module/site_param/index'));
            exit;
        }

        $logo = [
            'logo' => [
                'ismain' => 1,
                'fieldtype' => 'File',
                'fieldname' => 'logo',
                'setting' => ['option' => ['ext' => 'jpg,gif,png,jpeg,webp,svg', 'size' => 10, 'input' => 1]]
            ]
        ];

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $field = \Phpcmf\Service::M('field')->get_mysite_field(SITE_ID);

        // 初始化自定义字段类
        \Phpcmf\Service::L('Field')->app('');

		if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data', false);

            // param
            if ($field) {
                list($save, $return, $attach, $notfield) = \Phpcmf\Service::L('form')->validation($post, null, $field, $data['param']);
                // 输出错误
                if ($return) {
                    $this->_json(0, $return['error'], ['field' => $return['name']]);
                }
                if ($notfield) {
                    // 保留无权限的字段值
                    foreach ($notfield as $t) {
                        $save[1][$t] = $data['param'][$t];
                    }
                }
                $rt = \Phpcmf\Service::M('Site')->config(
                    SITE_ID,
                    'param',
                    $save[1]
                );
                if (!is_array($rt)) {
                    $this->_json(0, dr_lang('项目信息(#%s)不存在', SITE_ID));
                }
                // 附件归档
                if (SYS_ATTACHMENT_DB) {
                    $attach && \Phpcmf\Service::M('Attachment')->handle($this->member['id'], \Phpcmf\Service::M()->dbprefix('site'), $attach);
                }
                foreach ($field as $t) {
                    if (isset($post[$t['fieldname']])) {
                        unset($post[$t['fieldname']]);
                    }
                }
            }

		    // config
            $config = \Phpcmf\Service::L('input')->post('data');

            // 防止参数丢失
            $config['SITE_LANGUAGE'] = $data['config']['SITE_LANGUAGE'];
            $config['SITE_TEMPLATE'] = $data['config']['SITE_TEMPLATE'];
            $config['SITE_TIMEZONE'] = $data['config']['SITE_TIMEZONE'];
            $config['SITE_TIME_FORMAT'] = $data['config']['SITE_TIME_FORMAT'];
            $config['SITE_THEME'] = $data['config']['SITE_THEME'];
            $rt = \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $config);
            if (!is_array($rt)) {
                $this->_json(0, dr_lang('项目信息(#%s)不存在', SITE_ID));
            }
            // 附件归档
            if (SYS_ATTACHMENT_DB) {
                list($post, $return, $attach) = \Phpcmf\Service::L('form')->validation($config, null, []);
                $attach && \Phpcmf\Service::M('Attachment')->handle($this->member['id'], \Phpcmf\Service::M()->dbprefix('site'), $attach);
            }

			\Phpcmf\Service::L('input')->system_log('设置项目自定义参数');
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '项目信息' => ['site_param/index', 'fa fa-edit'],
                    '自定义字段' => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname' => 'site', 'rid' => SITE_ID]), 'fa fa-code'],
                    'help' => [1125],
                ]
            ),
            'data' => $data['config'],
            'field' => $field,
            'myfield' => $field ? \Phpcmf\Service::L('Field')->toform(0, $field, $data['param']) : '',
            'mymerge' => $field ? \Phpcmf\Service::L('Field')->merge : '',
            'logofield' => dr_fieldform($logo['logo'], $data['config']['logo']),
		]);

		\Phpcmf\Service::V()->display('site_param.html');
	}

	
}
