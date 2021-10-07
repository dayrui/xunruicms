<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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
            $module[$dir]['name'] = dr_lang('%s栏目', $t['name']);
            $module[$dir]['url'] = \Phpcmf\Service::L('Router')->url($dir.'/category/index');
        }

        if ($share) {
            $tmp['share'] = [
                'name' => '共享栏目',
                'icon' => 'fa fa-share-alt',
                'title' => '共享',
                'url' => \Phpcmf\Service::L('Router')->url('category/index'),
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
        $mid = $dir;
        $like = ['catmodule-'.$dir];
        if ($module['share']) {
            $like[] = 'catmodule-share';
            $mid = 'share';
        }
        $field = \Phpcmf\Service::M()->db->table('field')
            ->where('ismain', 1)
            ->where('disabled', 0)
            ->whereIn('relatedname', $like)
            ->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                /*
                $f['setting'] = dr_string2array($f['setting']);
                if ($f['relatedid']) {
                    $f['setting']['diy']['cat_field_catids'][] = $f['relatedid'];
                }*/
                $catids = [];
                foreach ($module['category'] as $t) {
                    if ($t['setting']['module_field'] && isset($t['setting']['module_field'][$f['fieldname']])) {
                        $catids[] = $t['id'];
                    }
                }
                $f['select'] = \Phpcmf\Service::L('Tree')->ismain(1)->select_category(
                    $module['category'],
                    $catids,
                    'name=\'data['.$f['fieldname'].'][]\' multiple="multiple" data-actions-box="true"',
                    '',
                    0,
                    0
                );
                $list[$f['id']] = $f;
            }
        }

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $table = $module['share'] ? 'share_category' : $module['dirname'].'_category';
            foreach ($module['category'] as $t) {
                if ($t['ismain']) {
                    $setting = dr_string2array($t['setting']);
                    $setting['module_field'] = [];
                    if ($post) {
                        foreach ($post as $fname => $catids) {
                            if (in_array($t['id'], $catids)) {
                                $setting['module_field'][$fname] = 1;
                            }
                        }
                    }
                    \Phpcmf\Service::M()->table_site($table)->update($t['id'], ['setting' => dr_array2string($setting)]);
                }
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'mid' => $mid,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '模块【'.$module['name'].'】栏目模型字段' => ['url:'.\Phpcmf\Service::L('Router')->url('module_category/field_index', ['dir'=>$dir]), 'fa fa-code', 'module_category/field_index'],
                    '自定义字段' => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname'=>'catmodule-'.$dir, 'rid'=>0]), 'fa fa-code', 'field/add'],
                    'help' => [798],
                ]
            ),
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('module_category_field.html');
    }

}
