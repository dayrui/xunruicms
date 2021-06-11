<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Seo_module extends \Phpcmf\Common {

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        // 设置url
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                $data = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
                if (!$data) {
                    unset($module[$dir]);
                    continue;
                }
                $site = dr_string2array($data['site']);
                $module[$dir]['site'] = $site[SITE_ID];
                $module[$dir]['setting'] = dr_string2array($data['setting']);
                $module[$dir]['save_url'] = dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]);
            }
        } else {
            $this->_admin_msg(0, dr_lang('系统没有安装内容模块'));
        }

        $one = reset($module);
        $page = \Phpcmf\Service::L('input')->get('page');
        if (!$page) {
            $page = $one['dirname'];
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容模块SEO' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-cogs'],
                    'help' => [398],
                ]
            ),
            'module' => $module,
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('seo_module.html');
    }

    // 存储指定的模块
    public function edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $data = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        $data['site'] = dr_string2array($data['site']);
        $data['setting'] = dr_string2array($data['setting']);

        if (IS_AJAX_POST) {
            $site = \Phpcmf\Service::L('input')->post('site');
            foreach (['html', 'urlrule', 'is_cat',
                         'show_title', 'show_keywords', 'show_description',
                         'list_title', 'list_keywords', 'list_description',
                         'search_title', 'search_keywords', 'search_description',
                         'module_title', 'module_keywords', 'module_description'] as $name) {
                $data['site'][SITE_ID][$name] = $site[$name];
            }
            $data['setting']['module_index_html'] = intval($_POST['module_index_html']);
            \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                'site' => dr_array2string($data['site']),
                'setting' => dr_array2string($data['setting']),
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, dr_lang('操作成功'), [
                'url' => dr_url(\Phpcmf\Service::L('Router')->class.'/index', ['page' => $dir])
            ]);
        }
    }

}
