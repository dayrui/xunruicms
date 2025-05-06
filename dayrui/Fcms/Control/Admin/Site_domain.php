<?php namespace Phpcmf\Control\Admin;

class Site_domain extends \Phpcmf\Common
{

    public function index() {

        if (IS_AJAX_POST) {
            $data = $post = \Phpcmf\Service::L('input')->post('data');
            if ($data['site_domain'] == $data['mobile_domain']) {
                $this->_json(0, dr_lang('手机域名不能与电脑相同'));
            }
            foreach ($post as $name => $value) {
                unset($data[$name]);
                if ($value) {
                    if (strpos($name, 'webpath') === 0) {
                        // 目录不验证
                    } elseif ($name == 'site_domain') {
                        if (!\Phpcmf\Service::L('Form')->check_domain_dir($value)) {
                            $this->_json(0, dr_lang('域名（%s）格式不正确', $value));
                        }
                    } else {
                        // 验证域名可用性
                        if (dr_in_array($value, $data)) {
                            $this->_json(0, dr_lang('域名（%s）绑定重复', $value));
                        } elseif (!\Phpcmf\Service::L('Form')->check_domain($value)) {
                            $this->_json(0, dr_lang('域名（%s）格式不正确', $value));
                        }
                        list($cname, $mid) = explode('_', $name);
                        if ($cname == 'module' && $mid != 'mobile' && $post['module_'.$mid] && !$post['webpath_'.$mid]) {
                            $this->_json(0, dr_lang('模块（%s）的Web目录必须填写', $mid));
                        }
                    }
                }
                $data[$name] = $value;
            }
            \Phpcmf\Service::M('Site')->domain($post);
            \Phpcmf\Service::M('cache')->sync_cache('');
            \Phpcmf\Service::L('input')->system_log('设置域名参数');
            $this->_json(1, dr_lang('操作成功'));
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
                $this->_json(0, dr_lang('当前网站不能修改主域名'));
            }

            $domain = trim(\Phpcmf\Service::L('input')->post('domain'));
            if (!\Phpcmf\Service::L('Form')->check_domain($domain)) {
                $this->_json(0, dr_lang('域名（%s）格式不正确', $domain));
            }

            \Phpcmf\Service::M('Site')->edit_domain($domain);
            \Phpcmf\Service::L('input')->system_log('变更网站主域名');
            \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存

            $this->_json(1, dr_lang('操作成功，请更新全站缓存'), [
                'tourl' => dr_url('cache/index')
            ]);
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'fcname' => $name,
            'is_fclient' => $is_fclient,
        ]);
        \Phpcmf\Service::V()->display('site_domain_edit.html');exit;
    }

}

