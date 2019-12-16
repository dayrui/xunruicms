<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
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
            $module[$dir]['url'] = dr_url(\Phpcmf\Service::L('Router')->class.'/show_index', ['dir' => $dir]);
        }

        if ($share) {
            $tmp['share'] = [
                'name' => '共享',
                'icon' => 'fa fa-share-alt',
                'title' => '共享',
                'url' => dr_url(\Phpcmf\Service::L('Router')->class.'/show_index', ['dir' => 'share']),
                'dirname' => 'share',
            ];
            $one = $tmp['share'];
            $module = dr_array22array($tmp, $module);
        } else {
            $one = reset($module);
        }

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_iframe_menu($module, $one['dirname'], 496),
            'module' => $module,
            'dirname' => $one['dirname'],
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

    // 获取树形结构列表
    protected function _get_tree_list($data) {

        $tree = [];
        $rule = $this->get_cache('urlrule');
        foreach($data as $t) {
            $t['name'] = dr_strcut($t['name'], 30);
            $t['setting'] = dr_string2array($t['setting']);
            $t['option'] = '<a class="btn btn-xs green" href="javascript:edit_seo('.$t['id'].', \''.$t['name'].'\');"> <i class="fa fa-edit"></i> '.dr_lang('设置SEO').'</a>';
            $t['option'].= '<a class="btn btn-xs red" href="javascript:dr_iframe(\''.dr_lang('复制').'\', \''.dr_url(($this->module['share'] ? '' : $this->module['dirname']).'/category/copy_edit').'&at=seo&catid='.$t['id'].'\');"> <i class="fa fa-copy"></i> '.dr_lang('同步到其他栏目').'</a>';
            // 判断是否生成静态
            $is_html = intval($this->module['share'] ? $t['setting']['html'] : $this->module['html']);
            $t['is_page_html'] = '<a href="javascript:;" onclick="dr_cat_ajax_open_close(this, \''.\Phpcmf\Service::L('Router')->url(($this->module['share'] ? '' : $this->module['dirname']).'/category/html_edit', ['id'=>$t['id']]).'\', 0);" class="dr_is_page_html badge badge-'.(!$is_html ? 'no' : 'yes').'"><i class="fa fa-'.(!$is_html ? 'times' : 'check').'"></i></a>';
            $t['html'] = '';
            $name = $t['setting']['urlrule'] && isset($rule[$t['setting']['urlrule']]['name']) ? $rule[$t['setting']['urlrule']]['name'] : '';
            !$name && $name = dr_lang('动态模式');
            if ($this->module['share']) {
                $t['html'] = '<a href="javascript:edit_seo('.$t['id'].', \''.$t['name'].'\');" class="btn btn-xs '.($t['setting']['urlrule'] && isset($rule[$t['setting']['urlrule']]['name']) ? 'red' : 'green').'"> <i class="fa fa-code"></i> '.$name.'</a>';
            } else {
                $t['html'] = '<a href="javascript:edit_seo2();" class="btn btn-xs green">  <i class="fa fa-code"></i> '.$name.'</a>';
            }
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

    public function edit() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir);
        if (!$module) {
            $this->_admin_msg(0, dr_lang('模块[%s]缓存不存在', $dir));
            return;
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table(SITE_ID.'_'.$dir.'_category')->where('id', $id)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('栏目#%s不存在', $id));
        }
        $data['setting'] = dr_string2array($data['setting']);

        if (IS_AJAX_POST) {
            $seo = \Phpcmf\Service::L('input')->post('seo', true);
            $set = \Phpcmf\Service::L('input')->post('setting', true);
            foreach (['list_title', 'list_keywords', 'list_description'] as $name) {
                $data['setting']['seo'][$name] = $seo[$name];
            }
            $data['setting']['html'] = (int)$set['html'];
            $data['setting']['urlrule'] = (int)$set['urlrule'];
            \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_category')->where('id', $id)->update([
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
