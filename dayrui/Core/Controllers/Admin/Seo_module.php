<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Seo_module extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        // 设置url
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                $module[$dir]['url'] = dr_url(\Phpcmf\Service::L('Router')->class.'/show_index', ['dir' => $dir]);
            }
        } else {
            $this->_admin_msg(0, dr_lang('系统没有安装内容模块'));
        }

        $one = reset($module);

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        $dirname = $one['dirname'];

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_iframe_menu($module, $dirname, 495),
            'module' => $module,
            'dirname' => $dirname,
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

    public function show_index() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $data = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        $data['site'] = dr_string2array($data['site']);
        $data['setting'] = dr_string2array($data['setting']);

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'site' => $data['site'][SITE_ID],
            'save_url' => dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('seo_module.html');
    }

    public function edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $data = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        $data['site'] = dr_string2array($data['site']);
        $data['setting'] = dr_string2array($data['setting']);

        if (IS_AJAX_POST) {
            $site = \Phpcmf\Service::L('input')->post('site', true);
            foreach (['html', 'urlrule', 'domain', 'mobile_domain', 'module_title', 'module_keywords', 'module_description'] as $name) {
                $data['site'][SITE_ID][$name] = $site[$name];
            }
            $data['setting']['module_index_html'] = intval($_POST['module_index_html']);
            \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                'site' => dr_array2string($data['site']),
                'setting' => dr_array2string($data['setting']),
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, '操作成功');
        }
    }

}
