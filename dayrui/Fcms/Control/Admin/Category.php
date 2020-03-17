<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 模块栏目操作类 基于 Ftable
class Category extends \Phpcmf\Table
{
    public $module; // 模块信息
    public $is_scategory; // 选择栏目类型
    protected $_is_extend_var = 0; // 继承属性变量

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $dir = APP_DIR ? APP_DIR : 'share';
        $this->_module_init($dir);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'share_category_';
        // 单独模板命名
        $this->tpl_name = 'category_content';
        // 模块显示名称
        $this->name = dr_lang('内容模块[%s]', $dir);
        $this->is_scategory = $this->module['share'] || (isset($this->module['config']['scategory']) && $this->module['config']['scategory']);
        if ($this->is_scategory) {
            // 共享栏目时显示单页内容字段
            if ($this->module['share'] && $dir != 'share' && !$this->_is_extend_var) {
                // 当共享模块进入了独立模块的栏目，就跳转条共享模块
                dr_redirect(dr_url('category/index'));
                exit;
            }
        } else {
            unset($this->module['category_field']['content']);
            // 独立模块
            if (isset($this->module['config']['hcategory']) && $this->module['config']['hcategory']) {
                $this->_admin_msg(0, dr_lang('模块【%s】禁止使用栏目', $dir));
            }
        }

        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_'.$dir.'_category',
            'field' => $this->module['category_field'],
            'show_field' => 'name',
            'order_by' => 'displayorder ASC,id ASC',
        ]);
        \Phpcmf\Service::M('Category')->init($this->init); // 初始化内容模型

        // 写入模板
        \Phpcmf\Service::V()->assign([
            'module' => $this->module,
            'post_url' => \Phpcmf\Service::L('Router')->url(APP_DIR.'/category/add'),
            'reply_url' => \Phpcmf\Service::L('Router')->url(APP_DIR.'/category/index'),
            'field_url' => \Phpcmf\Service::L('router')->url('field/index', ['rname' => 'category-'.$this->module['dirname']]),
            'post_all_url' => \Phpcmf\Service::L('Router')->url(APP_DIR.'/category/all_add'),
            'is_scategory' => $this->is_scategory,
        ]);
    }

    // ========================
    
    // 获取树形结构列表
    protected function _get_tree_list($data) {

        $tree = [];
        foreach($data as $t) {
            !$t['mid'] && $t['mid'] = APP_DIR;
            $t['name'] = dr_strcut($t['name'], 30);
            $t['child'] = $t['pcatpost'] ? 0 : $t['child'];
            $t['setting'] = dr_string2array($t['setting']);
            $option = '';
            $t['tid'] = isset($t['tid']) ? $t['tid'] : 1;
            if ($t['tid'] != 2 && $this->_is_admin_auth('add')) {
                // 非外链添加子类
                $option.= '<a class="btn btn-xs blue" href='.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/add', array('pid' => $t['id'])).'> <i class="fa fa-plus"></i> '.dr_lang('子类').'</a>';
            }
            if ($this->_is_admin_auth('edit')) {
                $option.= '<a class="btn btn-xs green" href='.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/edit', array('id' => $t['id'])).'> <i class="fa fa-edit"></i> '.dr_lang('修改').'</a>';
            }
            if (($t['tid'] == 1 && !$t['child'] && $t['mid']) && $this->_is_admin_auth($t['mid'].'/home/add'))  {
                $option.= '<a class="btn btn-xs dark" href='.\Phpcmf\Service::L('Router')->url($t['mid'].'/home/add', array('catid' => $t['id'])).'> <i class="fa fa-plus"></i> '.dr_lang('发布').'</a>';
            }
            if (($t['tid'] == 1 && $t['mid']) && $this->_is_admin_auth($t['mid'].'/home/index'))  {
                $option.= '<a class="btn btn-xs blue" href='.\Phpcmf\Service::L('Router')->url($t['mid'].'/home/index', array('catid' => $t['id'])).'> <i class="fa fa-th-large"></i> '.dr_lang('管理').'</a>';
            }
            if ($this->_is_admin_auth('edit') && ($t['tid'] == 0 && $this->is_scategory)) {
                $option.= '<a class="btn btn-xs dark" href="javascript:dr_page_content('.$t['id'].');"> <i class="fa fa-edit"></i> '.dr_lang('编辑内容').'</a>';
            }
            if ($this->_is_admin_auth('edit') && ($t['tid'] == 2 && $this->is_scategory)) {
                $option.= '<a class="btn btn-xs dark" href="javascript:dr_link_url('.$t['id'].');"> <i class="fa fa-edit"></i> '.dr_lang('编辑地址').'</a>';
            }
            if ($this->_is_admin_auth('edit')
                && ( (!$this->module['share'] && dr_count($this->module['category_field']) > 1) || ($this->module['share'] && dr_count($this->module['category_field']) > 2))) {
                $option.= '<a class="btn btn-xs red" href="javascript:dr_cat_field('.$t['id'].');"> <i class="fa fa-code"></i> '.dr_lang('字段权限').'</a>';
            }

            $t['option'] = $option;
            // 判断显示和隐藏开关
            $t['is_show_html'] = '<a href="javascript:;" onclick="dr_cat_ajax_show_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/show_edit', ['id'=>$t['id']]).'\', 0);" class="badge badge-'.(!$t['show'] ? 'no' : 'yes').'"><i class="fa fa-'.(!$t['show'] ? 'times' : 'check').'"></i></a>';
            // 判断是否生成静态
            $is_html = intval($this->module['share'] ? $t['setting']['html'] : $this->module['html']);
            $t['is_page_html'] = '<a href="javascript:;" onclick="dr_cat_ajax_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/html_edit', ['id'=>$t['id']]).'\', 0);" class="dr_is_page_html badge badge-'.(!$is_html ? 'no' : 'yes').'"><i class="fa fa-'.(!$is_html ? 'times' : 'check').'"></i></a>';

            $purl = $this->module['share'] ? ($t['tid'] == 1 ? dr_url($t['mid'].'/home/index', ['catid'=>$t['id']]) : \Phpcmf\Service::L('Router')->url(APP_DIR.'/category/edit', array('id' => $t['id']))) : dr_url(APP_DIR.'/home/index', ['catid'=>$t['id']]);
            $t['total'] = '<a href="'.$purl.'">'.intval($this->module['category'][$t['id']]['total']).'</a>';
            // 是否缓存
            if ($this->module['category'][$t['id']]) {
                $t['url'] = dr_url_prefix($this->module['category'][$t['id']]['url'], APP_DIR);
                // 共享模块显示栏类别
                if ($this->is_scategory) {
                    // 栏目类型
                    if ($t['tid'] == 1) {
                        $t['type_html'] = '<span class="badge badge-success"> '.dr_lang('模块').' </span>';
                    } elseif ($t['tid'] == 2) {
                        $t['type_html'] = '<span class="badge badge-warning"> '.dr_lang('外链').' </span>';
                        $t['is_page_html'] = '';
                        $t['mid'] = '';
                    } else {
                        $t['mid'] = '';
                        $t['type_html'] = '<span class="badge badge-info"> '.dr_lang('单页').' </span>';
                    }
                    !$t['mid'] && $t['mid'] = '<span class="label label-sm label-danger circle">'.dr_lang('无').'</span>';
                }
            } else {
                $t['name'].= '&nbsp;<span class="label label-sm label-danger circle">'.dr_lang('请更新缓存').'</span>';
                $t['url'] = 'javascript:;';
                $t['tid'] = 0;
                $t['mid'] = '';
                $t['type_html'] = '';
                $t['is_page_html'] = '';
            }
            //$t['name'] = $this->module['category'][$t['id']]['total'];
            $tree[$t['id']] = $t;
        }


        $str = "<tr class='\$class'>";
        $str.= "<td class='myselect'>
                    <label class='mt-table mt-table mt-checkbox mt-checkbox-single mt-checkbox-outline'>
                        <input type='checkbox' class='checkboxes' name='ids[]' value='\$id' />
                        <span></span>
                    </label>
                </td>";
        $str.= "<td style='text-align:center'> <input type='text' onblur='dr_cat_ajax_save(this.value, \$id)' value='\$displayorder' class='displayorder form-control input-sm input-inline input-mini'> </td>";
        $str.= "<td style='text-align:center'>\$is_show_html</td>";
        $str.= "<td style='text-align:center'>\$id</td>";
        $str.= "<td>\$spacer<a target='_blank' href='\$url'>\$name</a> \$parent</td>";
        if ($this->is_scategory) {
            $str.= "<td style='text-align:center'>\$type_html</td>";
        }
        if ($this->module['share']) {
            $str.= "<td style='text-align:center'>\$mid</td>";
        }
        $str.= "<td style='text-align:center'>\$total</td>";
        $str.= "<td style='text-align:center'>\$is_page_html</td>";
        $str.= "<td>\$option</td>";
        $str.= "</tr>";


        return \Phpcmf\Service::L('Tree')->init($tree)->html_icon()->get_tree(0, $str);
    }

    // 后台查看列表
    protected function _Admin_List() {

        list($tpl, $data) = $this->_List([], -1);

        $category = \Phpcmf\Service::M('Category')->repair($data['list']);

        \Phpcmf\Service::V()->assign([
            'list' => $this->_get_tree_list($category),
            'list_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'move_select' => \Phpcmf\Service::L('Tree')->select_category(
                $category,
                0,
                'name="catid"',
                dr_lang('顶级栏目'),
                0, 0
            ),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台添加内容
    protected function _Admin_Add() {

        $id = 0;
        $pid = intval(\Phpcmf\Service::L('input')->get('pid'));
        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        // 默认数据
        $value = [
            'show' => 1,
            'setting' => [
                'edit' => 1,
                'template' => [
                    'page' => 'page.html',
                    'list' => 'list.html',
                    'show' => 'show.html',
                    'category' => 'category.html',
                    'search' => 'search.html',
                    'pagesize' => 10,
                    'mpagesize' => 10,
                ],
                'seo' => [
                    'list_title' => '[第{page}页{join}]{catpname}{join}{modname}{join}{SITE_NAME}',
                    'show_title' => '[第{page}页{join}]{title}{join}{catpname}{join}{modname}{join}{SITE_NAME}',
                ],
            ]
        ];

        if ($pid) {
            if (!$this->module['category'][$pid]) {
                $this->_admin_msg(0, dr_lang('栏目【%s】缓存不存在', $pid));
            }
            $value['setting'] = $this->module['category'][$pid]['setting'];
        }

        $value['mid'] = $this->module['category'][$pid]['mid'];
        
        list($tpl) = $this->_Post($id, $value, 1);

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $value,
            'form' =>  dr_form_hidden(['page' => $page]),
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], $pid, 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'is_edit_mid' => $pid && $value['mid'] ? 1 : 0,
            'module_share' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台修改内容
    protected function _Admin_Edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        list($tpl, $data) = $this->_Post($id, null, 1);
        if (!$data['data']) {
            $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' =>  dr_form_hidden(['page' => $page]),
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], $data['data']['pid'], 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'is_edit_mid' => 1,
            'module_share' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台批量添加
    protected function _Admin_All_Add() {

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data', true);
            $list = explode(PHP_EOL, $post['list']);
            if (!$list) {
                $this->_json(0, dr_lang('内容填写不完整'));
            }

            $pid = intval($post['pid']);
            if ($pid && !$this->module['category'][$pid]) {
                $this->_json(0, dr_lang('栏目【%s】缓存不存在', $pid));
            } elseif ($this->module['category'][$pid]['tid'] == 2) {
                $this->_json(0, dr_lang('外部地址类型不允许添加子栏目'));
            }

            $count = 0;

            foreach ($list as $t) {
                list($name, $dir) = explode('|', $t);
                $data = [];
                $data['name'] = trim($name);
                if (!$data['name']) {
                    continue;
                }
                $data['dirname'] = trim($dir);
                !$data['dirname'] && $data['dirname'] = \Phpcmf\Service::L('pinyin')->result($data['name']);
                $cf = \Phpcmf\Service::M('Category')->check_dirname(0, $data['dirname']);

                $data['pid'] = $pid;
                $data['show'] = 1;
                $data['thumb'] = '';
                $data['pids'] = '';
                $data['pdirname'] = '';
                $data['childids'] = '';
                if ($this->module['share']) {
                    $data['content'] = '';
                    $data['tid'] = (int)$post['tid'];
                    $save['domain'] = '';
                    $save['mobile_domain'] = '';
                    //$data['mid'] = $pid ? $this->module['category'][$pid]['mid'] : $post['mid'];
                    $data['mid'] = $post['mid'];
                    // 作为内容模块的栏目判断
                    if ($data['tid'] == 1) {
                        if (!$data['mid']) {
                            $this->_json(0, dr_lang('必须选择一个模块'));
                        }
                        // 判断逐个父级栏目的mid值
                        list($pmid, $ids) = \Phpcmf\Service::M('Category')->get_parent_mid($this->module['category'], $pid);
                        if ($pmid && $pmid != $data['mid']) {
                            $this->_json(0, dr_lang('必须选择与上级栏目相同的内容模块（%s）', $pmid));
                        }
                    }
                }
                $data['setting'] = dr_array2string(isset($this->module['category'][$pid]['setting']) ? $this->module['category'][$pid]['setting'] : [
                    'edit' => 1,
                    'template' => [
                        'list' => 'list.html',
                        'show' => 'show.html',
                        'category' => 'category.html',
                        'search' => 'search.html',
                        'pagesize' => 20,
                    ],
                    'seo' => [
                        'list_title' => '[第{page}页{join}]{name}{join}{modname}{join}{SITE_NAME}',
                        'show_title' => '[第{page}页{join}]{title}{join}{catname}{join}{modname}{join}{SITE_NAME}',
                    ],
                ]);
                $rt = \Phpcmf\Service::M('Category')->insert($data);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                if ($cf) {
                    // 重复验证
                    \Phpcmf\Service::M('Category')->update($rt['code'], [
                        'dirname' => $data['dirname'].$rt['code']
                    ]);
                }
                $count ++;
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('批量添加%s个栏目', $count));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'module_share' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display('share_category_all.html');
    }

    // 后台批量设置URL
    protected function _Admin_Url_Edit() {


        if (!$this->module['share']) {
            $this->_admin_msg(2, dr_lang('独立模块在模块配置中设置URL规则'),\Phpcmf\Service::L('Router')->url('seo_module/show', ['dir' => $this->module['dirname'], 'page' => 2, 'hide_menu' => 1]));
            exit;
        }

        if (IS_AJAX_POST) {

            $c = 0;
            $catid = \Phpcmf\Service::L('input')->post('catid');
            $urlrule = \Phpcmf\Service::L('input')->post('urlrule', true);

            foreach ($this->module['category'] as $id => $t) {
                if (in_array($id, $catid)) {
                    $c ++;
                    $t['setting']['urlrule'] = $urlrule;
                    \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($t['setting'])]);
                }
            }

            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('批量设置%s个栏目', $c));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', ''),
            'list_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/url_edit'),
            'list_name' => ' <i class="fa fa-link"></i>  '.dr_lang('自定义URL'),
        ]);
        \Phpcmf\Service::V()->display('share_category_url.html');
    }

    // 后台删除内容
    protected function _Admin_Del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选栏目不存在'));
        }

        // 重新获取数据
        $category = \Phpcmf\Service::M('Category')->data_for_delete();

        // 筛选栏目id
        $catid = '';
        foreach ($ids as $id) {
            $catid.= ','.($category[$id]['childids'] ? $category[$id]['childids'] : $id);
        }
        
        $catid = explode(',', trim($catid, ','));
        $catid = array_flip(array_flip($catid));

        parent::_Del(
            $catid,
            null,
            function ($rows) {
                // 计算栏目
                // 删除之后记得删除相关模块数据
                \Phpcmf\Service::M('Category')->delete_content($rows, $this->module);
                // 自动更新缓存
                \Phpcmf\Service::M('cache')->sync_cache();
                return dr_return_data(1);
            },
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // 批量设置静态模式
    protected function _Admin_Html_All_Edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选栏目不存在'));
        }

        foreach ($ids as $id) {

            $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
            if (!$row) {
                $this->_json(0, dr_lang('栏目数据不存在'));
            }

            $row['setting'] = dr_string2array($row['setting']);
            $row['setting']['html'] = 1;
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目状态为: 静态模式 ['. $id.']');
        }

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache();

        $this->_json(1, dr_lang('操作成功'));
    }

    public function copy_edit() {

        $at = \Phpcmf\Service::L('input')->get('at');
        $catid = (int)\Phpcmf\Service::L('input')->get('catid');
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($catid);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        if (IS_AJAX_POST) {

            $catids = \Phpcmf\Service::L('input')->post('catid');
            if (!$catids) {
                $this->_json(0, dr_lang('你还没有选择栏目呢'));
            }
            $c = 0;
            $row['setting'] = dr_string2array($row['setting']);
            if (isset($catids[0]) && $catids[0] == 0) {
                foreach ($this->module['category'] as $id => $t) {
                    $c ++;
                    // 全部栏目
                    \Phpcmf\Service::M('Category')->init($this->init)->copy_value($at, $row['setting'], $id);
                }
            } else {
                foreach ($catids as $id) {
                    $c ++;
                    // 指定栏目
                    \Phpcmf\Service::M('Category')->init($this->init)->copy_value($at, $row['setting'], $id);
                }
            }

            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('共同步到%s个栏目', $c));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('Tree')->select_category(
                $this->module['category'],
                0,
                'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                dr_lang('全部栏目'),
                0,
                0
            ),
        ]);
        \Phpcmf\Service::V()->display('share_category_copy.html');exit;
    }

    // 批量设置动态模式
    protected function _Admin_Php_All_Edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选栏目不存在'));
        }

        foreach ($ids as $id) {

            $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
            if (!$row) {
                $this->_json(0, dr_lang('栏目数据不存在'));
            }

            $row['setting'] = dr_string2array($row['setting']);
            $row['setting']['html'] = 0;
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目状态为: 动态模式 ['. $id.']');
        }

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache();
        $this->_json(1, dr_lang('操作成功'));
    }

    // 后台批量保存排序值
    protected function _Admin_Order() {
        $this->_Display_Order(
            intval(\Phpcmf\Service::L('input')->get('id')),
            intval(\Phpcmf\Service::L('input')->get('value'))
        );
    }

    // 后台批量移动栏目
    protected function _Admin_Move_Edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选栏目不存在'));
        }

        $topid = (int)\Phpcmf\Service::L('input')->post('catid');

        /*
        $mid = $topid ? '' : $category[$topid]['mid'];
        // 判断mid
        $mmid = '';*/
        if ($topid) {
            // 重新获取数据
            $category = \Phpcmf\Service::M('Category')->data_for_move();
            if (!$category[$topid]) {
                $this->_json(0, dr_lang('目标栏目不存在'));
            } elseif ($this->is_scategory && $category[$topid]['child'] == 0 && $category[$topid]['tid'] == 1) {
                $mid = $category[$topid]['mid'] ? $category[$topid]['mid'] : APP_DIR;
                if (dr_is_module($mid) && \Phpcmf\Service::M()->table(dr_module_table_prefix($mid))->where('catid', $topid)->counts()) {
                    $this->_json(0, dr_lang('目标栏目【%s】存在内容数据，无法作为父栏目', $category[$topid]['name']));
                }
            }
            /*
        foreach ($ids as $id) {
            if ($mid) {
                // 本身有模块属性的栏目
                if ($category[$id]['mid'] && $category[$id]['mid'] != $mid) {
                    $this->_json(0, dr_lang('所选栏目【%s】与目标栏目的模块不一致', $category[$id]['name']));
                }
            } else {
                // 本身没有模块属性的栏目
                !$mmid && $category[$id]['mid'] && $mmid = $category[$id]['mid'];
                if ($category[$id]['mid'] && $category[$id]['mid'] != $mmid) {
                    $this->_json(0, dr_lang('所选栏目【%s】与其他栏目的模块不一致', $category[$id]['name']));
                }
            }
            // 批量更新内容栏目
            if ($mid) {
                //$this->content_model->_init($mid)->update_catids($id, $cid);
            }
            }*/
        }

        // 批量更换栏目
        \Phpcmf\Service::M()->db->table($this->init['table'])->whereIn('id', $ids)->update(['pid' => $topid]);

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache();
        $this->_json(1, dr_lang('操作成功'));
    }

    // 后台批量保存显示状态
    protected function _Admin_Show_Edit() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $v = $row['show'] ? 0 : 1;
        \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['show' => $v]);

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache();
        \Phpcmf\Service::L('input')->system_log('修改栏目的显示状态: '. $id);
        exit($this->_json(1, dr_lang($v ? '显示状态' : '隐藏状态'), ['value' => $v, 'share' => 0]));
    }

    // 后台批量保存是否生成静态的状态
    protected function _Admin_Html_Edit() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        if ($this->module['share']) {
            // 共享模块
            $row['setting'] = dr_string2array($row['setting']);
            if (!$row['setting']['urlrule']) {
                $this->_json(0, dr_lang('此栏目是动态地址，无法开启静态'));
            }
            $html = (int)$row['setting']['html'];
            $v = $html ? 0 : 1;
            $name = $v ? '静态模式' : '动态模式';
            $row['setting']['html'] = $v;
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目状态为: '. $name . '['. $id.']');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            exit($this->_json(1, dr_lang($v ? '静态模式' : '动态模式'), ['value' => $v, 'share' => 1]));
        } else {
            // 独立模块
            $html = (int)$this->module['html'];
            $v = $html ? 0 : 1;
            $name = $v ? '静态模式' : '动态模式';
            $module = \Phpcmf\Service::M()->db->table('module')->where('id', $this->module['id'])->get()->getRowArray();
            if (!$module) {
                exit($this->_json(0, dr_lang('模块不存在')));
            }
            $site = dr_string2array($module['site']);
            $site[SITE_ID]['html'] = $v;
            if (!$site[SITE_ID]['urlrule']) {
                $this->_json(0, dr_lang('此模块是动态地址，无法开启静态'));
            }
            \Phpcmf\Service::M()->db->table('module')->where('id', $this->module['id'])->update([
                'site' => dr_array2string($site)
            ]);
            \Phpcmf\Service::L('input')->system_log('修改模块状态为: '. $name);
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            exit($this->_json(1, dr_lang($name), ['value' => $v, 'share' => 0]));
        }
    }

    // 生成栏目静态
    public function scjt_edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('没有选择任何栏目'));
        }

        if (IS_SHARE) {
            $this->_json(1, dr_url('html/category_index', ['app' => '', 'ids' => implode(',', $ids)]));
        } else {
            $this->_json(1, dr_url('html/category_index', ['app' => APP_DIR, 'ids' => implode(',', $ids)]));
        }
    }

    // 生成内容静态
    public function scjt2_edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('没有选择任何栏目'));
        }

        if (IS_SHARE) {
            $this->_json(1, dr_url('html/show_index', ['app' => '', 'catids' => implode(',', $ids)]));
        } else {
            $this->_json(1, dr_url('html/show_index', ['app' => APP_DIR, 'catids' => implode(',', $ids)]));
        }
    }

    // 编辑单页内容
    public function content_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        if (!$row) {
            $this->_admin_msg(0, dr_lang('栏目数据不存在'));
        }
        $row['setting'] = dr_string2array($row['setting']);
        if ($row['setting']['getchild']) {
            $this->_admin_msg(0, dr_lang('本栏目已开启【继承下级】请编辑它下级第一个单页面数据'));
        }

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', false);
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['content' => ($post['content'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目内容: '. $row['name'] . '['. $id.']');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'myfield' => dr_fieldform($this->init['field']['content'], $row['content']),
        ]);
        \Phpcmf\Service::V()->display('share_category_content.html');exit;

    }

    // 编辑外链
    public function link_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $row['setting'] = dr_string2array($row['setting']);

        if (IS_POST) {
            $row['setting']['linkurl'] = \Phpcmf\Service::L('input')->post('url');
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目外链地址: '. $row['name'] . '['. $id.']');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'myurl' => $row['setting']['linkurl'],
        ]);
        \Phpcmf\Service::V()->display('share_category_linkurl.html');exit;
    }

    // 编辑自定义字段
    public function field_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $row['setting'] = dr_string2array($row['setting']);

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('ids');
            $save = $row['setting']['cat_field'] ? $row['setting']['cat_field'] : [];
            foreach ($this->module['category_field'] as $t) {
                if ($t['id']) {
                    if (in_array($t['fieldname'], $post)) {
                        // 说明勾选了这个字段
                        if (isset($save[$t['fieldname']])) {
                            unset($save[$t['fieldname']]);
                        }
                    } else {
                        // 没勾选
                        $save[$t['fieldname']] = 1;
                    }
                }
            }
            $row['setting']['cat_field'] = $save;
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目自定义字段权限: '. $row['name'] . '['. $id.']');

            $catids = \Phpcmf\Service::L('input')->post('catid');
            if ($catids) {
                $c = 0;
                $row['setting'] = dr_string2array($row['setting']);
                if (isset($catids[0]) && $catids[0] == 0) {
                    foreach ($this->module['category'] as $id => $t) {
                        $c ++;
                        // 全部栏目
                        \Phpcmf\Service::M('Category')->init($this->init)->copy_value('cat_field', $row['setting'], $id);
                    }
                } else {
                    foreach ($catids as $id) {
                        $c ++;
                        // 指定栏目
                        \Phpcmf\Service::M('Category')->init($this->init)->copy_value('cat_field', $row['setting'], $id);
                    }
                }
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'field' => $this->module['category_field'],
            'select' => \Phpcmf\Service::L('Tree')->select_category(
                $this->module['category'],
                0,
                'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                dr_lang('全部栏目'),
                0,
                0
            ),
            'cat_field' => $row['setting']['cat_field'],
        ]);
        \Phpcmf\Service::V()->display('share_category_field.html');exit;
    }


    // ===========================

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {

        $row = \Phpcmf\Service::M('Category')->get($id);
        if (!$row) {
            return [];
        }

        $row['setting'] = dr_string2array($row['setting']);
        if ($row['setting']['cat_field']) {
            foreach ($row['setting']['cat_field'] as $key => $v) {
                unset($this->init['field'][$key]);
            }
        }

        $this->_init($this->init);

        return $row;
    }
    
    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        return parent::_Save($id, $data, $old,
            function ($id, $data, $old){
                // 保存之前的判断
                $save = \Phpcmf\Service::L('input')->post('system');
                if (!$save['name']) {
                    return dr_return_data(0, dr_lang('栏目名称不能为空'), ['field' => 'name']);
                } elseif (!$save['dirname']) {
                    return dr_return_data(0, dr_lang('目录名称不能为空'), ['field' => 'dirname']);
                } elseif (\Phpcmf\Service::M('Category')->check_dirname($id, $save['dirname'])) {
                    return dr_return_data(0, dr_lang('目录名称不可用'), ['field' => 'dirname']);
                }

                // 默认数据
                $save['show'] = (int)$save['show'];
                foreach ($data[1] as $n => $t) {
                    $save[$n] = $t ? $t : '';
                }

                // 判断共享栏目
                if ($this->module['share']) {
                    // 判断模块tid是否正确
                    if (isset($save['tid'])) {
                        $save['tid'] = intval($save['tid']);
                        if ($save['tid'] == 1 && !$save['mid']) {
                            return dr_return_data(0, dr_lang('必须选择一个模块'));
                        } elseif ($save['pid'] && $save['tid'] != 2 && $this->module['category'][$save['pid']]['tid'] == 2) {
                            return dr_return_data(0, dr_lang('父级栏目是外部地址类型，下级栏目只能选择外部地址'));
                        } elseif ($save['tid'] == 2 && !$save['setting']['linkurl']) {
                            return dr_return_data(0, dr_lang('外部地址类型必须填写地址'));
                        }
                        // 单页模板识别
                        !$save['tid'] && $save['setting']['template']['list'] == 'list.html' && $save['setting']['template']['list'] = 'page.html';
                    }
                    // 判断存在mid
                    /*
                    if ($save['mid']) {
                        // 判断逐个父级栏目的mid值
                        list($pmid, $ids) = \Phpcmf\Service::M('Category')->get_parent_mid($this->module['category'], $id ? $id : $save['pid']);
                        $pmid && $pmid != $save['mid'] && $this->_json(0, dr_lang('必须选择与上级栏目相同的内容模块（%s）', $pmid));
                        // 更新上级相关mid
                        \Phpcmf\Service::M()->db->table($this->init['table'])->whereIn('id', $ids)->update(['mid' => $pmid]);
                    }*/
                    $save['domain'] = '';
                    $save['mobile_domain'] = '';
                }

                if ($save['pid'] && $id && $save['pid'] == $id) {
                    return dr_return_data(0, dr_lang('栏目上级不能为本身'));
                }

                // 变更栏目时
                if ($old && $save['pid'] && $save['pid'] != $old['pid']) {
                    if (!$this->module['category'][$save['pid']]) {
                        $this->_json(0, dr_lang('父栏目不存在'));
                    } elseif ($this->is_scategory && $this->module['category'][$save['pid']]['child'] == 0 && $this->module['category'][$save['pid']]['tid'] == 1) {
                        $mid = $this->module['category'][$save['pid']]['mid'] ? $this->module['category'][$save['pid']]['mid'] : APP_DIR;
                        if (dr_is_module($mid) && \Phpcmf\Service::M()->table(dr_module_table_prefix($mid))->where('catid', $save['pid'])->counts()) {
                            $this->_json(0, dr_lang('目标栏目【%s】存在内容数据，无法作为父栏目', $this->module['category'][$save['pid']]['name']));
                        }
                    }
                }

                // 不出现在编辑器中的字段
                $save['setting']['cat_field'] = $old['setting']['cat_field'];

                // 数组json化
                $save['pids'] = '';
                $save['setting'] = dr_array2string($save['setting']);
                $save['pdirname'] = '';
                $save['childids'] = '';

                return dr_return_data(1, '', [1 => $save]);
            }, function ($id, $data, $old) {
                // 自动更新缓存
                \Phpcmf\Service::M('cache')->sync_cache();
            }
        );
    }
}
