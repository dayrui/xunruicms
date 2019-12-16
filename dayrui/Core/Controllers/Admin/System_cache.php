<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class System_cache extends \Phpcmf\Common
{

    public function index() {

        $file = WRITEPATH.'config/cache.php';
        $module = \Phpcmf\Service::M('Module')->All(1); // 库中已安装模块

        if (IS_AJAX_POST) {

            if (!\Phpcmf\Service::L('Config')->file($file, '缓存配置文件')->to_require_one(\Phpcmf\Service::L('input')->post('data'))) {
                $this->_json(0, dr_lang('配置文件写入失败'));
            }

            $site = \Phpcmf\Service::L('input')->post('site', true);
            foreach ($this->site_info as $sid => $t) {
                \Phpcmf\Service::M('Site')->config_value($sid, 'config', [
                    'SITE_INDEX_HTML' => intval($site[$sid]['SITE_INDEX_HTML'])
                ]);
            }

            $module_value = \Phpcmf\Service::L('input')->post('module', true);
            foreach ($module as $t) {
                $setting = dr_string2array($t['setting']);
                $setting['module_index_html'] = $module_value[$t['id']]['module_index_html'];
                \Phpcmf\Service::M()->table('module')->update($t['id'], [
                    'setting' => dr_array2string($setting),
                ]);
            }

            \Phpcmf\Service::L('input')->system_log('配置缓存参数'); // 记录日志
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'data' => is_file($file) ? require $file : [],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '缓存设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-clock-o'],
                    'help' => [504],
                ]
            ),
            'module' => $module,
            'cache_var' => [
                'SHOW' => '模块内容',
                //'ATTACH' => '网站附件',
                'LIST' => '查询标签',
                'SEARCH' => '内容搜索',
            ],
        ]);
        \Phpcmf\Service::V()->display('system_cache.html');
    }


}
