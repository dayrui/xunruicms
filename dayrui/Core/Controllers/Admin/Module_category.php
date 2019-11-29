<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Module_category extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$module) {
            $this->_admin_msg(0, dr_lang('系统没有安装内容模块'), dr_url('module/index'));
        }

        $share = 0;

        // 设置url
        foreach ($module as $dir => $t) {
            if ($t['share']) {
                $share = 1;
                unset($module[$dir]);
                continue;
            } elseif ($t['system'] == 2) {
                unset($module[$dir]);
                continue;
            }
            $module[$dir]['url'] =\Phpcmf\Service::L('Router')->url($dir.'/category/index');
        }

        if ($share) {
            $tmp['share'] = [
                'name' => '共享',
                'icon' => 'fa fa-share-alt',
                'title' => '共享',
                'url' =>\Phpcmf\Service::L('Router')->url('category/index'),
                'dirname' => 'share',
            ];
            $one = $tmp['share'];
            $module = dr_array22array($tmp, $module);
        } else {
            $one = reset($module);
        }

        if (!$module) {
            $this->_admin_msg(0, dr_lang('系统没有可用内容模块'), dr_url('module/index'));
        }

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_iframe_menu($module, $one['dirname']),
            'module' => $module,
            'dirname' => $one['dirname'],
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

    public function field_index() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        if (!$dir) {
            $this->_admin_msg(0, dr_lang('系统没有可用内容模块'));
        }

        $module = $this->get_cache('module-'.SITE_ID.'-'.$dir);
        if (!$module) {
            $this->_admin_msg(0, dr_lang('系统没有可用内容模块'));
        }

        $list = [];

        // 字段查询
        $like = [$dir.'-'.SITE_ID];
        if ($module['share']) {
            $like[] = 'share-'.SITE_ID;
        }
        $field = \Phpcmf\Service::M()->db->table('field')->where('disabled', 0)->whereIn('relatedname', $like)->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                $f['setting'] = dr_string2array($f['setting']);
                if ($f['relatedid']) {
                    $f['setting']['diy']['cat_field_catids'][] = $f['relatedid'];
                }
                $f['select'] = \Phpcmf\Service::L('Tree')->select_category(
                    $module['category'],
                    $f['setting']['diy']['cat_field_catids'],
                    'name=\'data['.$f['id'].'][]\' multiple="multiple" class="multi-select" style="height:200px"',
                    '',
                    0,
                    0
                );
                $list[$f['id']] = $f;
            }
        }

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            foreach ($list as $i => $t) {
                $t['setting']['diy']['cat_field_catids'] = $post[$i];
                \Phpcmf\Service::M()->table('field')->update($i, [
                    'setting' => dr_array2string($t['setting']),
                    'relatedid' => 0,
                ]);
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
        }


        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '模块【'.$module['name'].'】栏目模型字段' => ['url:'.\Phpcmf\Service::L('Router')->url('module_category/field_index', ['dir'=>$dir]), 'fa fa-code', 'module_category/field_index'],
                    '自定义字段' => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname'=>$dir.'-'.SITE_ID, 'rid'=>0]), 'fa fa-code', 'field/add'],
                    'help' => [798],
                ]
            ),
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('module_category_field.html');exit;
    }

}
