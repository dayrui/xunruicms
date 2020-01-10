<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Site_domain extends \Phpcmf\Common
{


    public function index() {

        if (IS_AJAX_POST) {
            $data = $post = \Phpcmf\Service::L('input')->post('data', true);
            foreach ($post as $name => $value) {
                unset($data[$name]);
                if ($value && in_array($value, $data)) {
                    exit($this->_json(0, dr_lang('域名[%s]绑定重复', $value)));
                }
                $data[$name] = $value;
            }
            \Phpcmf\Service::M('Site')->domain($post);
            \Phpcmf\Service::M('cache')->sync_cache('');
            \Phpcmf\Service::L('input')->system_log('设置网站域名参数');
            exit($this->_json(1, dr_lang('操作成功')));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        list($module, $data) = \Phpcmf\Service::M('Site')->domain();

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '域名设置' => ['site_domain/index', 'fa fa-cog'],
                    'help' => ['407'],
                ]
            ),
            'module' => $module,
        ]);
        \Phpcmf\Service::V()->display('site_domain.html');
    }

    public function bang_index() {
        $this->index();
    }

    public function edit() {

        $name = '';
        $is_fclient = is_file(ROOTPATH.'api/fclient/index.php');
        if ($is_fclient && is_file(MYPATH . 'Config/License.php')) {
            $license = require MYPATH . 'Config/License.php';
            $name = $license['name'];
        }
        !$name && $name = dr_lang('软件服务商');

        if (IS_POST) {

            if ($is_fclient) {
                exit($this->_json(0, dr_lang('当前网站不能修改主域名')));
            }

            $domain = trim(\Phpcmf\Service::L('input')->post('domain', true));
            if (!$domain) {
                exit($this->_json(0, dr_lang('域名不能为空')));
            }

            \Phpcmf\Service::M('Site')->edit_domain($domain);
            \Phpcmf\Service::L('input')->system_log('变更网站主域名');
            exit($this->_json(1, dr_lang('操作成功，请域名解析到本站IP')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'fcname' => $name,
            'is_fclient' => $is_fclient,
        ]);
        \Phpcmf\Service::V()->display('site_domain_edit.html');exit;
    }

}

