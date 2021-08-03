<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Module_search extends \Phpcmf\Common
{

    public function index() {

        $mid = \Phpcmf\Service::L('input')->get('dir');
        $all = \Phpcmf\Service::M('Module')->get_module_info();
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        // 设置url
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                if (!$all[$dir]) {
                    unset($module[$dir]);
                    continue;
                }
                if ($mid && $mid != $dir) {
                    unset($module[$dir]);
                    continue;
                }
                $data = $all[$dir];

                // 搜索字段
                $data['search_field'] = [
                    'catid' => dr_lang('栏目'),
                    'keyword' => dr_lang('搜索词'),
                    'order' => dr_lang('排序'),
                    'page' => dr_lang('分页'),
                ];
                if (!$data['setting']['search']['field']) {
                    $data['setting']['search']['field'] = 'title,keywords';
                }
                $field = \Phpcmf\Service::M()->db->table('field')
                    ->where('disabled', 0)
                    ->where('ismain', 1)
                    ->where('relatedname', 'module')
                    ->where('relatedid', $data['id'])
                    ->orderBy('displayorder ASC,id ASC')
                    ->get()->getResultArray();
                foreach ($field as $f) {
                    $data['search_field'][$f['fieldname']] = $f['name'];
                }
                $module[$dir] = $data;
                $module[$dir]['field'] = $field;
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
                    '模块搜索设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-search'],
                    'help' => [1041],
                ]
            ),
            'module' => $module,
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('module_search.html');
    }

    public function edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$cache[$dir]) {
            $this->_json(0, dr_lang('模块#%s不存在', $dir));
        }

        $all = \Phpcmf\Service::M('Module')->get_module_info();
        if (!$all[$dir]) {
            $this->_json(0, dr_lang('模块#%s不存在', $dir));
        }

        if (IS_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            foreach ($post as $dir => $t) {
                $all[$dir]['setting']['search'] = $t;
                $all[$dir]['setting']['search']['field'] = implode(',', $_POST['search_field'][$dir]);
                \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                    'setting' => dr_array2string($all[$dir]['setting']),
                ]);
            }

            $this->_json(1, dr_lang('操作成功'), [
                'url' => dr_url(\Phpcmf\Service::L('Router')->class.'/index', ['page' => $dir])
            ]);
        }

        $this->_json(0, dr_lang('请求错误'));
    }

}
