<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Module_comment extends \Phpcmf\Common
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
            'menu' => \Phpcmf\Service::M('auth')->_iframe_menu($module, $dirname),
            'module' => $module,
            'dirname' => $dirname,
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

    public function show_index() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$cache[$dir]) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        $all = \Phpcmf\Service::M('Module')->get_module_info();
        if (!$all[$dir]) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        if (IS_POST) {

            $data = \Phpcmf\Service::L('input')->post('data');
            foreach ($data as $dir => $t) {
                $module[$dir]['comment'] = $t;
                \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                    'comment' => dr_array2string($module[$dir]['comment']),
                ]);
            }

            $this->_json(1, '操作成功');

        }

        $data = $all[$dir];

        if (!isset($data['comment']['review'])) {
            // 默认点评
            $data['comment']['review']['use'] = 0;
            $data['comment']['review']['score'] = 10;
            $data['comment']['review']['option'] = [];
            // 点评选项
            for ($i = 1; $i <= 9; $i++) {
                $data['comment']['review']['option'][$i] = [
                    'use' => 0,
                    'name' => '选项'.$i,
                ];
            }
            // 点评值
            for ($i = 1; $i <= 5; $i++) {
                $data['comment']['review']['value'][$i] = [
                    'use' => 0,
                    'name' => $i.'星评价',
                ];
            }
        }

        \Phpcmf\Service::V()->assign([
            'page' => $dir,
            'module' => [$dir => $data],
            'save_url' => dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('module_comment.html');
    }

}
