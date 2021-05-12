<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Seo_category extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$module) {
            $this->_admin_msg(0, dr_lang('系统没有安装内容模块'));
        }

        $share = 0;

        // 设置url
        foreach ($module as $dir => $t) {
            if ($t['share']) {
                $share = 1;
                unset($module[$dir]);
                continue;
            } elseif ($t['hlist'] == 1) {
                //1表示不出现在模块管理、评论tab、搜索tab、内容维护tab的列表之中
                unset($module[$dir]);
                continue;
            } elseif ($t['hcategory']) {
                //1表示不使用栏目功能和发布权限功能
                unset($module[$dir]);
                continue;
            }
            $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir);
            if (!$cache) {
                unset($module[$dir]);
                continue;
            }

            $module[$dir]['list'] = $this->_get_tree_list($dir, $cache['category']);
            $module[$dir]['save_url'] = dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]);
        }

        if ($share) {
            $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share');
            $tmp['share'] = [
                'name' => '共享',
                'icon' => 'fa fa-share-alt',
                'title' => '共享',
                'save_url' => dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => 'share']),
                'dirname' => 'share',
                'list' => $this->_get_tree_list('share', $cache['category']),
            ];
            $module = dr_array22array($tmp, $module);
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
                    '栏目SEO' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-reorder'],
                    'help' => [496],
                ]
            ),
            'module' => $module,
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('seo_category.html');
    }

    // 选择规则
    private function _select_rule($dir, $rule, $t) {

        $html = '<label>';
        $html.= '<select class="form-control" onchange="dr_save_urlrule(\''.$dir.'\', \''.$t['id'].'\', this.value)">';
        $html.= '<option value="0"> '.dr_lang('动态地址').' </option>';
        if ($rule) {
            foreach ($rule as $b) {
                $select = isset($t['setting']['urlrule']) && $t['setting']['urlrule'] == $b['id'] ? 'selected' : '';
                if ($dir == 'share' && $b['type'] == 3) {
                    $html.= '<option '.$select.' value="'.$b['id'].'"> '.dr_lang($b['name']).' </option>';
                } elseif ($b['type'] == 1) {
                    $html.= '<option '.$select.' value="'.$b['id'].'"> '.dr_lang($b['name']).' </option>';
                }
            }
        }
        $html.= '</select>';
        $html.= '</label>';

        return $html;
    }

    // 获取树形结构列表
    private function _get_tree_list($dir, $data) {

        $mod = $tree = [];
        $rule = $this->get_cache('urlrule');
        if ($dir != 'share') {
            $mod = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
            if (!$mod) {
                $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
            }
            $mod['site'] = dr_string2array($mod['site']);
        }

        foreach($data as $t) {
            $t['name'] = dr_strcut($t['name'], 30);
            $t['setting'] = dr_string2array($t['setting']);
            $t['option'] = '<a class="btn btn-xs green" href="javascript:edit_seo('.$t['id'].', \''.$t['name'].'\', \''.$dir.'\');"> <i class="fa fa-edit"></i> '.dr_lang('设置SEO').'</a>';
            $t['option'].= '<a class="btn btn-xs red" href="javascript:dr_iframe(\''.dr_lang('复制').'\', \''.dr_url(($dir == 'share' ? '' : $dir).'/category/copy_edit').'&at=seo&catid='.$t['id'].'\', \'\', \'500px\', \'nogo\');"> <i class="fa fa-copy"></i> '.dr_lang('同步到其他栏目').'</a>';
            // 判断是否生成静态
            $is_html = intval($t['setting']['html']);
            $t['is_page_html'] = '<a href="javascript:;" onclick="dr_cat_ajax_open_close(this, \''.\Phpcmf\Service::L('Router')->url(($dir == 'share' ? '' : $dir).'/category/html_edit', ['id'=>$t['id']]).'\', 0);" class="dr_is_page_html badge badge-'.(!$is_html ? 'no' : 'yes').'"><i class="fa fa-'.(!$is_html ? 'times' : 'check').'"></i></a>';
            if ($mod) {
                $t['setting']['urlrule'] = isset($mod['site'][SITE_ID]['urlrule']) ? $mod['site'][SITE_ID]['urlrule'] : 0;
            }
            $t['html'] = $this->_select_rule($dir, $rule, $t);
            $t['url'] = dr_url_prefix($t['url']);
            $tree[$t['id']] = $t;
        }

        $str = "<tr class='\$class'>";
        $str.= "<td style='text-align:center'>\$id</td>";
        $str.= "<td>\$spacer<a target='_blank' href='\$url'>\$name</a> </td>";
        $str.= "<td>\$html</td>";
        $str.= "<td style='text-align:center'>\$is_page_html</td>";
        $str.= "<td>\$option</td>";
        $str.= "</tr>";

        return \Phpcmf\Service::L('Tree')->init($tree)->html_icon()->get_tree(0, $str);
    }

    public function show_index() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $this->module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir);
        if (!$this->module) {
            $this->_admin_msg(0, dr_lang('模块[%s]缓存不存在', $dir));
            return;
        }

        \Phpcmf\Service::V()->assign([
            'list' => $this->_get_tree_list($this->module['category']),
            'dirname' => $dir,
            'save_url' => dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('seo_category.html');
    }

    public function rule_edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir);
        if (!$module) {
            $this->_admin_msg(0, dr_lang('模块[%s]缓存不存在', $dir));
            return;
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $value = (int)\Phpcmf\Service::L('input')->get('value');

        if ($dir == 'share') {
            $data = \Phpcmf\Service::M()->table(dr_module_table_prefix($dir).'_category')->where('id', $id)->getRow();
            if (!$data) {
                $this->_admin_msg(0, dr_lang('栏目#%s不存在', $id));
            }
            $data['setting'] = dr_string2array($data['setting']);
            $data['setting']['urlrule'] = $value;
            \Phpcmf\Service::M()->db->table(dr_module_table_prefix($dir).'_category')->where('id', $id)->update([
                'setting' => dr_array2string($data['setting']),
            ]);
        } else {
            $data = \Phpcmf\Service::M()->table('module')->where('dirname', $dir)->getRow();
            if (!$data) {
                $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
            }

            $data['site'] = dr_string2array($data['site']);
            $data['site'][SITE_ID]['urlrule'] = $value;

            \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                'site' => dr_array2string($data['site']),
            ]);
        }

        \Phpcmf\Service::M('cache')->sync_cache('');
        $this->_json(1, '操作成功，更新缓存生效');
    }

    public function edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir);
        if (!$module) {
            $this->_admin_msg(0, dr_lang('模块[%s]缓存不存在', $dir));
            return;
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table(dr_module_table_prefix($dir).'_category')->where('id', $id)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('栏目#%s不存在', $id));
        }
        $data['setting'] = dr_string2array($data['setting']);

        if (IS_AJAX_POST) {
            $seo = \Phpcmf\Service::L('input')->post('seo');
            $set = \Phpcmf\Service::L('input')->post('setting');
            foreach (['list_title', 'list_keywords', 'list_description'] as $name) {
                $data['setting']['seo'][$name] = $seo[$name];
            }
            $data['setting']['html'] = isset($set['html']) ? (int)$set['html'] : 0;
            $data['setting']['urlrule'] = isset($set['urlrule']) ? (int)$set['urlrule'] : 0;
            \Phpcmf\Service::M()->db->table(dr_module_table_prefix($dir).'_category')->where('id', $id)->update([
                'setting' => dr_array2string($data['setting']),
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, '操作成功，更新缓存生效');
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'dirname' => $dir,
        ]);
        \Phpcmf\Service::V()->display('seo_category_edit.html');exit;
    }

}
