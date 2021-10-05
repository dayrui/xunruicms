<?php namespace Phpcmf\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 模块栏目操作类 基于 Table
class Category extends \Phpcmf\Table {

    public $module; // 模块信息
    public $is_scategory; // 选择栏目类型
    protected $cat_config; // 栏目属性
    protected $_is_extend_var = 0; // 继承属性变量

    // 上级公共类
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->_Extend_Init();
    }

    // 继承类初始化
    protected function _Extend_Init() {
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
            //unset($this->module['category_field']['content']);
            // 独立模块
            if (isset($this->module['config']['hcategory']) && $this->module['config']['hcategory']) {
                $this->_admin_msg(0, dr_lang('模块【%s】禁止使用栏目', $dir));
            }
        }

        // 初始化数据表
        $this->_init([
            'table' => dr_module_table_prefix($dir).'_category',
            'field' => $this->module['category_field'],
            'order_by' => 'displayorder ASC,id ASC',
            'show_field' => 'name',
        ]);

        \Phpcmf\Service::M('category')->init($this->init); // 初始化内容模型
        //$this->module['category'] = \Phpcmf\Service::M('category')->get_data(0);

        $file = WRITEPATH.'config/category.php';
        if (is_file($file)) {
            $this->cat_config = require $file;
            if (!isset($this->cat_config[$this->module['dirname']])) {
                $this->cat_config = [
                    $this->module['dirname'] => [
                        'sys_field' => ['order','use','show', 'id','tid','mid','total','html'],
                        'list_field' => [],
                    ],
                ];
            }
        } else {
            $this->cat_config = [
                $this->module['dirname'] => [
                    'sys_field' => ['order','use','show', 'id','tid','mid','total','html'],
                    'list_field' => [],
                ],
            ];
        }

        if (isset($this->cat_config[$this->module['dirname']]['rname']) && $this->cat_config[$this->module['dirname']]['rname']) {
            define('SYS_CAT_RNAME', 1);
        }

        // 写入模板
        \Phpcmf\Service::V()->assign([
            'module' => $this->module,
            'is_seo' => ($this->module['share'] ? 1 : (isset($this->module['site'][SITE_ID]['is_cat']) && $this->module['site'][SITE_ID]['is_cat'])),
            'post_url' => \Phpcmf\Service::L('router')->url(APP_DIR.'/category/add'),
            'reply_url' => \Phpcmf\Service::L('router')->url(APP_DIR.'/category/index'),
            'field_url' => \Phpcmf\Service::L('router')->url('field/index', ['rname' => 'category-'.$this->module['dirname']]),
            'post_all_url' => \Phpcmf\Service::L('router')->url(APP_DIR.'/category/all_add'),
            'config_url' => \Phpcmf\Service::L('router')->url(APP_DIR.'/category/config_add'),
            'is_scategory' => $this->is_scategory,
        ]);
    }

    // ========================
    
    // 获取树形结构列表
    protected function _get_tree_list($data) {

        $cqx = dr_is_app('cqx') ? \Phpcmf\Service::M('content', 'cqx') : null;
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        $is_cat_code = dr_is_app('mbdy') && isset($this->admin['role'][1]) && method_exists(\Phpcmf\Service::M('code', 'mbdy'), 'cat_code') ? 1 : 0;

        $list = "<tr class='\$class'>";
        $head = '<tr class="heading">';

        $list.= "<td class='myselect'>
                    <label class='mt-table mt-table mt-checkbox mt-checkbox-single mt-checkbox-outline'>
                        <input type='checkbox' class='checkboxes' name='ids[]' value='\$id' />
                        <span></span>
                    </label>
                </td>";
        $head.= '<th class="myselect">
                        <label class="mt-table mt-table mt-checkbox mt-checkbox-single mt-checkbox-outline">
                            <input type="checkbox" class="group-checkable" data-set=".checkboxes" />
                            <span></span>
                        </label>
                    </th>';

        if (dr_in_array('order', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head.= '<th width="70" style="text-align:center"> '.dr_lang('排序').' </th>';
            $list.= "<td style='text-align:center'> <input type='text' onblur='dr_cat_ajax_save(this.value, \$id)' value='\$displayorder' class='displayorder form-control input-sm input-inline input-mini'> </td>";
        }

        if (dr_in_array('use', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head .= '<th width="50" style="text-align:center"> ' . dr_lang('可用') . ' </th>';
            $list .= "<td style='text-align:center'>\$is_used_html</td>";
        }

        if (dr_in_array('show', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head .= '<th width="50" style="text-align:center"> ' . dr_lang('显示') . ' </th>';
            $list .= "<td style='text-align:center'>\$is_show_html</td>";
        }

        if (dr_in_array('id', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head .= '<th width="70" style="text-align:center"> Id </th>';
            $list .= "<td style='text-align:center'>\$id</td>";
        }

        $head.= '<th> '.dr_lang('栏目信息').' </th>';
        $list.= "<td>\$spacer<a target='_blank' href='\$url'>\$name</a> \$parent</td>";

        if ($this->is_scategory && dr_in_array('tid', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head.= '<th width="60" style="text-align:center"> '.dr_lang('类型').' </th>';
            $list.= "<td style='text-align:center'>\$type_html</td>";
        }
        if ($this->module['share'] && dr_in_array('mid', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head.= '<th width="150" style="text-align:center"> '.dr_lang('模块 / 目录').' </th>';
            $list.= "<td style='text-align:center'>\$mid</td>";
        }

        if (dr_is_app('chtml') && dr_in_array('html', $this->cat_config[$this->module['dirname']]['sys_field'])) {
            $head.= '<th width="50" style="text-align:center"> ' . dr_lang('静态') . ' </th>';
            $list.= "<td style='text-align:center'>\$is_page_html</td>";
        }

        if (isset($this->cat_config[$this->module['dirname']]['list_field']) && $this->cat_config[$this->module['dirname']]['list_field']) {
            foreach ($this->cat_config[$this->module['dirname']]['list_field'] as $i => $t) {
                if ($t['use']) {
                    $head.= '<th '.($t['width'] ? ' width="'.$t['width'].'"' : '').' '.($t['center'] ? ' class=\"table-center\" style="text-align:center"' : '').'>'.dr_lang($t['name']).'</th>';
                    $list.= '<td '.($t['center'] ? ' class=\"table-center\"' : '').'>$'.$i.'_html</td>';
                }
            }
        }

        $head.= '<th>'.dr_lang('操作').'</th>';
        $list.= "<td>\$option</td>";

        $head.= '</tr>';
        $list.= "</tr>";

        $tree = '';
        foreach($data as $k => $t) {
            if ($cqx && $cqx->is_edit($t['id'])) {
                unset($data[$k]);
                continue;
            }
            $option = '';
            !$t['mid'] && $t['mid'] = APP_DIR;
            $t['tid'] = isset($t['tid']) ? $t['tid'] : 1;
            $t['name'] = dr_strcut($t['name'], 30);
            $t['setting'] = dr_string2array($t['setting']);
            if ($this->module['share']) {
                // 共享栏目时
                //以本栏目为准
                $t['setting']['html'] = intval($t['setting']['html']);
                $t['setting']['urlrule'] = intval($t['setting']['urlrule']);
                $t['child'] = isset($module[$t['mid']]['pcatpost']) && $module[$t['mid']]['pcatpost'] ? 0 : $t['child'];
            } else {
                // 独立模块栏目
                //以站点为准
                if (!isset($t['tid'])) {
                    $t['tid'] = $t['setting']['linkurl'] ? 2 : 1; // 判断栏目类型 2表示外链
                }
                $t['setting']['html'] = intval($this->module['html']);
                $t['setting']['urlrule'] = isset($this->module['site'][SITE_ID]['urlrule']) ? intval($this->module['site'][SITE_ID]['urlrule']) : 0;
                $t['child'] = isset($this->module['setting']['pcatpost']) && $this->module['setting']['pcatpost'] ? 0 : $t['child'];
            }
            $t['url'] = $t['tid'] == 2 && $t['setting']['linkurl'] ? dr_url_prefix($t['setting']['linkurl']) : dr_url_prefix(\Phpcmf\Service::L('router')->category_url($this->module, $t));
            if ($this->_is_admin_auth('add')) {
                // 非外链添加子类 $t['tid'] != 2 &&
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
                if ($t['setting']['cat_field'] && isset($t['setting']['cat_field']['content'])) {
                    // 当开启字段权限时不显示内容
                } else {
                    $option.= '<a class="btn btn-xs dark" href="'.dr_url(APP_DIR.'/category/edit', ['id' => $t['id'], 'page'=>1]).'"> <i class="fa fa-edit"></i> '.dr_lang('编辑内容').'</a>';
                }
            }
            if ($this->_is_admin_auth('edit') && ($t['tid'] == 2 && $this->is_scategory)) {
                $option.= '<a class="btn btn-xs dark" href="javascript:dr_link_url('.$t['id'].');"> <i class="fa fa-edit"></i> '.dr_lang('编辑地址').'</a>';
            }
            // 只对超管有效
            if (isset($this->admin['role'][1])
                && ((!$this->module['share'] && dr_count($this->module['category_field']) > 1) || ($this->module['share'] && dr_count($this->module['category_field']) > 2))) {
                $option.= '<a class="btn btn-xs red" href="javascript:dr_cat_field('.$t['id'].');"> <i class="fa fa-code"></i> '.dr_lang('字段权限').'</a>';
            }
            // 第三方插件接入
            if ($is_cat_code) {
                $option.= '<a class="btn btn-xs yellow" href="javascript:dr_iframe_show(\'show\', \''.dr_url('mbdy/category/cms', ['mid'=>APP_DIR, 'id'=>$t['id']]).'\');"> <i class="fa fa-code"></i> '.dr_lang('前端调用').'</a>';
            }
            $t['option'] = $option;
            // 判断显示和隐藏开关
            $t['is_show_html'] = '<a data-container="body" data-placement="right" data-original-title="'.dr_lang('前端循环调用不会显示，但可以正常访问').'" href="javascript:;" onclick="dr_cat_ajax_show_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/show_edit', ['id'=>$t['id']]).'\', 0);" class="tooltips badge badge-'.(!$t['show'] ? 'no' : 'yes').'"><i class="fa fa-'.(!$t['show'] ? 'times' : 'check').'"></i></a>';
            $t['is_used_html'] = '<a data-container="body" data-placement="right" data-original-title="'.dr_lang('禁用状态下此栏目不能正常访问').'" href="javascript:;" onclick="dr_cat_ajax_show_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/show_edit', ['at'=> 'used', 'id'=>$t['id']]).'\', 1);" class="tooltips badge badge-'.($t['disabled'] ? 'no' : 'yes').'"><i class="fa fa-'.($t['disabled'] ? 'times' : 'check').'"></i></a>';
            //$t['is_ismain_html'] = '<a data-container="body" data-placement="right" data-original-title="'.dr_lang('主栏目具备权限控制和相关参数配置权限；当栏目过多时建议将第一级设置为主栏目，其余子栏目不设置').'" href="javascript:;" onclick="dr_cat_ajax_show_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/show_edit', ['at'=> 'main', 'id'=>$t['id']]).'\', 0);" class="tooltips badge badge-'.(!$t['ismain'] ? 'no' : 'yes').'"><i class="fa fa-'.(!$t['ismain'] ? 'times' : 'check').'"></i></a>';
            // 判断是否生成静态
            $is_html = intval($this->module['share'] ? $t['setting']['html'] : $this->module['html']);
            $t['is_page_html'] = '<a href="javascript:;" onclick="dr_cat_ajax_open_close(this, \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/category/html_edit', ['id'=>$t['id']]).'\', 0);" class="dr_is_page_html badge badge-'.(!$is_html ? 'no' : 'yes').'"><i class="fa fa-'.(!$is_html ? 'times' : 'check').'"></i></a>';

            $t['ctotal'] = "<input type='hidden' name='cid[]' value='".$t['id']."-".$t['mid']."' />";
            //$purl = $this->module['share'] ? ($t['tid'] == 1 ? dr_url($t['mid'].'/home/index', ['catid'=>$t['id']]) : \Phpcmf\Service::L('Router')->url(APP_DIR.'/category/edit', array('id' => $t['id']))) : dr_url(APP_DIR.'/home/index', ['catid'=>$t['id']]);
            //$t['total'] = '<a href="'.$purl.'">'.intval($data[$t['id']]['total']).'</a>';
            // 是否缓存
            //$t['url'] = dr_url_prefix($data[$t['id']]['url'], APP_DIR);
            // 共享模块显示栏类别
            if ($this->is_scategory) {
                // 栏目类型
                if ($t['tid'] == 1) {
                    if ($t['child']) {
                        $t['type_html'] = '<a class="tooltips badge badge-danger" data-container="body" data-placement="right" data-original-title="'.dr_lang('当栏目存在子栏目时我们称之为封面，这个属性无法变更').'"> '.dr_lang('封面').' </span>';
                    } else {
                        $t['type_html'] = '<a class="tooltips badge badge-success" data-container="body" data-placement="right" data-original-title="'.dr_lang('最终的栏目我们称之为列表').'"> '.dr_lang('列表').' </a>';
                    }
                    if ($module[$t['mid']]['name']) {
                        $t['mid'] = $module[$t['mid']]['name'] . ' / '.$t['mid'];
                    } else {
                        $t['mid'] = '<a onclick="dr_tips(0, \''.dr_lang('没有安装此模块（%s）', $t['mid']).'\')" class="label label-sm label-danger circle">'.dr_lang('未安装').'</a>';
                    }
                } elseif ($t['tid'] == 2) {
                    $t['mid'] = '';
                    $t['type_html'] = '<a class="tooltips badge badge-warning" data-container="body" data-placement="right" data-original-title="'.dr_lang('属于外部链接').'"> '.dr_lang('外链').' </a>';
                    $t['is_page_html'] = '';
                } else {
                    $t['mid'] = '';
                    $t['type_html'] = '<a class="tooltips badge badge-info" data-container="body" data-placement="right" data-original-title="'.dr_lang('不可发布内容的介绍性质页面，例如关于我们等页面').'"> '.dr_lang('单页').' </a>';
                }
                //!$t['mid'] && $t['mid'] = '<span class="label label-sm label-danger circle">'.dr_lang('无').'</span>';
            } else {
                $t['url'] = 'javascript:;';
                $t['tid'] = 0;
                $t['mid'] = '';
                $t['type_html'] = '';
                $t['is_page_html'] = '';
            }
            if (isset($this->cat_config[$this->module['dirname']]['list_field']) && $this->cat_config[$this->module['dirname']]['list_field']) {
                foreach ($this->cat_config[$this->module['dirname']]['list_field'] as $i => $tt) {
                    if ($tt['use']) {
                        $t[$i . '_html'] = dr_list_function($tt['func'], $t[$i], [], $t, $this->module['category_field'][$i]);
                    }
                }
            }
            //$t['name'] = $this->module['category'][$t['id']]['total'];
            if ($t['child']) {
                $t['spacer'] = $this->_get_spacer($t['pids']).'<a href="javascript:dr_tree_data('.$t['id'].');" class="blue select-cat-'.$t['id'].'">[+]</a>&nbsp;';
            } else {
                $t['spacer'] = $this->_get_spacer($t['pids']);
            }

            //$t['spacer'] = $num;
            $t['class'] = 'dr_catid_'.$t['id']. ' dr_pid_'.$t['pid'];
            $arr = explode(',', $t['pids']);
            if ($arr) {
                foreach ($arr as $a) {
                    $t['class'].= ' dr_pid_'.$a;
                }
            }
            extract($t);
            eval("\$nstr = \"$list\";");
            $tree.= $nstr;
        }

        return [$head, $tree];
    }

    // 替换空格填充符号
    private function _get_spacer($str) {
        $rt = '';
        $num = substr_count($str, ',') * 2;
        if ($num) {
            for ($i = 0; $i < $num; $i ++) {
                $rt.= '&nbsp;&nbsp;&nbsp;';
            }
        }
        return $rt;
    }

    // 设置栏目属性
    public function config_add() {

        $sysfield = [
            'order' => ['排序', '设置栏目的排列顺序'],
            'use' => ['可用', '设置栏目是否可用的快捷开关'],
            'show' => ['显示', '设置栏目是否用于循环显示'],
            //'ismain' => ['主栏目', '设置是否为主栏目的快捷开关'],
            'id' => ['Id', '显示栏目的id号'],
            'tid' => ['类型', '显示栏目的类型，有：单页、模块、外链'],
            'mid' => ['模块', '显示所属模块的名称'],
            'html' => ['静态', '设置栏目是否生成静态的开关'],
        ];
        if (!$this->is_scategory) {
            unset($sysfield['tid']);
        }
        if (!$this->module['share']) {
            unset($sysfield['mid']);
        }
        if (!dr_is_app('chtml')) {
            unset($sysfield['html']);
        }

        if (IS_POST) {
            $this->cat_config[$this->module['dirname']] = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/category.php', '栏目配置文件', 32)->to_require($this->cat_config);
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
        }

        // 主表字段
        $field = \Phpcmf\Service::M()->db->table('field')
            ->where('disabled', 0)
            ->where('relatedname', 'category-'.$this->module['dirname'])
            ->orderBy('displayorder ASC,id ASC')
            ->get()->getResultArray();
        $field = dr_list_field_value($this->cat_config[$this->module['dirname']]['list_field'], [], $field);

        \Phpcmf\Service::V()->assign([
            'data' => $this->cat_config[$this->module['dirname']],
            'field' => $field,
            'sysfield' => $sysfield,
        ]);
        \Phpcmf\Service::V()->display('share_category_config.html');exit;
    }

    public function list_index() {
        $pid = intval(\Phpcmf\Service::L('input')->get('pid'));
        list($a, $b) = $this->_get_tree_list(\Phpcmf\Service::M('category')->cat_data($pid));
        $this->_json(1, $b);
    }

    // 后台查看列表
    protected function _Admin_List() {

        list($a, $b) = $this->_get_tree_list(\Phpcmf\Service::M('category')->cat_data(0));
        \Phpcmf\Service::V()->assign([
            'list' => '你在My/View/share_category_list.html，目录中定义过栏目文件，需要删除此文件',
            'cat_head' => $a,
            'cat_list' => $b,
            'list_url' => dr_url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'move_select' => \Phpcmf\Service::L('tree')->select_category(
                $this->module['category'],
                0,
                'name="catid"',
                dr_lang('顶级栏目'),
                0, 0
            ),
            'uriprefix' => trim(APP_DIR.'/'.\Phpcmf\Service::L('router')->class, '/'),
        ]);
        \Phpcmf\Service::V()->display('share_category_list.html');
    }

    // 后台添加内容
    protected function _Admin_Add() {

        $id = 0;
        $pid = intval(\Phpcmf\Service::L('input')->get('pid'));
        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        $psize = (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'pagesize');
        $msize = (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'mpagesize');

        // 默认数据
        $value = [
            'show' => 1,
            'urlrule' => (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_URLRULE'),
            'setting' => [
                'edit' => 1,
                'disabled' => 0,
                'template' => [
                    'page' => 'page.html',
                    'list' => 'list.html',
                    'show' => 'show.html',
                    'category' => 'category.html',
                    'search' => 'search.html',
                    'pagesize' => $psize ? $psize : 20,
                    'mpagesize' => $msize ? $msize : 20,
                ],
                'seo' => [
                    'list_title' => '[第{page}页{join}]{catpname}{join}{SITE_NAME}',
                    'show_title' => '[第{page}页{join}]{title}{join}{catpname}{join}{SITE_NAME}',
                ],
            ]
        ];

        if ($pid) {
            if (!$this->module['category'][$pid]) {
                $this->_admin_msg(0, dr_lang('栏目【%s】缓存不存在', $pid));
            }
            $value['setting'] = $this->module['category'][$pid]['setting'];
            $value['setting']['getchild'] = 0;
        }

        $value['mid'] = $this->module['category'][$pid]['mid'];
        $value['setting'] = dr_string2array($value['setting']);
        list($tpl) = $this->_Post($id, $value, 1);

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $value,
            'form' =>  dr_form_hidden(['page' => $page]),
            'select' => \Phpcmf\Service::L('tree')->select_category($this->module['category'], $pid, 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' => dr_url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'select_tpl' => $this->_select_tpl(),
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
        if (!$data['id']) {
            $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' =>  dr_form_hidden(['page' => $page]),
            'select' => \Phpcmf\Service::L('tree')->select_category($this->module['category'], $data['pid'], 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' => dr_url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'select_tpl' => $this->_select_tpl($data['mid']),
            'is_edit_mid' => 1,
            'module_share' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 模板选择
    protected function _select_tpl($mid = '') {

        $path = dr_tpl_path();
        $rpath = dirname(dirname($path));

        $ini = dirname($path).'/config.ini';
        if (is_file($ini)) {
            $data = json_decode(file_get_contents($ini), true);
        } else {
            $data = [];
        }

        $html = '';
        $html.= '<ul class="dropdown-menu dr_select_tpl" style="max-height: 350px;overflow: scroll;">';
        $files = dr_file_map($path);
        if ($files) {
            foreach ($files as $file) {
                $key = md5(trim(str_replace(['/', '\\'], '*', str_replace($rpath, '', $path.$file)), '*'));
                $name = isset($data[$key]) && $data[$key] ? $data[$key].'（'.$file.'）' : $file;
                $html.= '<li><a href="javascript:dr_select_tpl(\''.$file.'\', \'{name}\');"> '.$name.' </a></li>';
            }
        }

        if ($mid) {
            $path.= $mid.'/';
            $files = dr_file_map($path);
            if ($files) {
                $html.= '<li class="divider"> </li>';
                foreach ($files as $file) {
                    $key = md5(trim(str_replace(['/', '\\'], '*', str_replace($rpath, '', $path.$file)), '*'));
                    $name = isset($data[$key]) && $data[$key] ? $data[$key].'（'.$mid.'/'.$file.'）' : $mid.'/'.$file;
                    $html.= '<li><a href="javascript:dr_select_tpl(\''.$file.'\', \'{name}\');"> '.$name.' </a></li>';
                }
            }
        }
        $html.= '</ul>';

        return $html;
    }

    // 后台批量添加
    protected function _Admin_All_Add() {

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $list = explode(PHP_EOL, $post['list']);
            if (!$list) {
                $this->_json(0, dr_lang('内容填写不完整'));
            }

            $pid = intval($post['pid']);
            if ($pid && !$this->module['category'][$pid]) {
                $this->_json(0, dr_lang('栏目【%s】缓存不存在', $pid));
            } elseif ($pid && $post['tid'] != 2 && $this->module['category'][$pid]['tid'] == 2) {
                return dr_return_data(0, dr_lang('父级栏目是外部地址类型，下级栏目只能选择外部地址'));
            } elseif ($pid && $this->module['category'][$pid]['pcatpost'] == 0
                && $this->module['category'][$pid]['child'] == 0 && $this->module['category'][$pid]['tid'] == 1) {
                $mid = $this->module['category'][$pid]['mid'] ? $this->module['category'][$pid]['mid'] : APP_DIR;
                if (dr_is_module($mid) && \Phpcmf\Service::M()->table(dr_module_table_prefix($mid))->where('catid', $pid)->counts()) {
                    $this->_json(0, dr_lang('目标栏目【%s】存在内容数据，无法作为父栏目', $this->module['category'][$pid]['name']));
                }
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
                $cf = \Phpcmf\Service::M('category')->check_dirname(0, $data['dirname']);

                $data['pid'] = $pid;
                $data['show'] = 1;
                //$data['ismain'] = 0;
                $data['disabled'] = 0;
                $data['pids'] = '';
                $data['thumb'] = '';
                $data['pdirname'] = '';
                $data['childids'] = '';
                if ($this->module['share']) {
                    $data['mid'] = $post['mid'];
                    $data['tid'] = (int)$post['tid'];
                    $save['domain'] = '';
                    $data['content'] = '';
                    $save['mobile_domain'] = '';
                    // 作为内容模块的栏目判断
                    if ($data['tid'] == 1 && !$data['mid']) {
                        $this->_json(0, dr_lang('必须选择一个模块'));
                        // 判断逐个父级栏目的mid值
                        /*
                        list($pmid, $ids) = \Phpcmf\Service::M('category')->get_parent_mid($this->module['category'], $pid);
                        if ($pmid && $pmid != $data['mid']) {
                            $this->_json(0, dr_lang('必须选择与上级栏目相同的内容模块（%s）', $pmid));
                        }*/
                    }
                }
                $psize = (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'pagesize');
                $msize = (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'mpagesize');
                $data['setting'] = isset($this->module['category'][$pid]['setting']) ? $this->module['category'][$pid]['setting'] : [
                    'edit' => 1,
                    'disabled' => 0,
                    'template' => [
                        'list' => 'list.html',
                        'show' => 'show.html',
                        'category' => 'category.html',
                        'search' => 'search.html',
                        'pagesize' => $psize ? $psize : 20,
                        'mpagesize' => $msize ? $msize : 20,
                    ],
                    'seo' => [
                        'list_title' => '[第{page}页{join}]{name}{join}{SITE_NAME}',
                        'show_title' => '[第{page}页{join}]{title}{join}{catname}{join}{SITE_NAME}',
                    ],
                ];
                $data['setting']['getchild'] = 0;
                $save['setting']['urlrule'] = (int)\Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_URLRULE');
                $data['setting'] = dr_array2string($data['setting']);

                $rt = \Phpcmf\Service::M('category')->insert($data);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                if (!$cf['code']) {
                    // 重复验证
                    \Phpcmf\Service::M('category')->update($rt['code'], [
                        'dirname' => $data['dirname'].$rt['code']
                    ]);
                }
                $count ++;
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('批量添加%s个栏目', $count));
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('tree')->select_category($this->module['category'], 0, 'name=\'data[pid]\'', dr_lang('顶级栏目')),
            'list_url' => dr_url(APP_DIR.'/category/index'),
            'list_name' => ' <i class="fa fa-reorder"></i>  '.dr_lang('栏目管理'),
            'module_share' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display('share_category_all.html');
    }

    // 后台批量设置URL
    protected function _Admin_Url_Edit() {

        if (!$this->module['share']) {
            $this->_admin_msg(2, dr_lang('独立模块在模块配置中设置URL规则'), dr_url('seo_module/show', ['dir' => $this->module['dirname'], 'page' => 2, 'hide_menu' => 1]));
        }

        if (IS_AJAX_POST) {

            $c = 0;
            $catid = \Phpcmf\Service::L('input')->post('catid');
            $urlrule = \Phpcmf\Service::L('input')->post('urlrule');

            foreach ($this->module['category'] as $id => $t) {
                if (dr_in_array($id, $catid)) {
                    $c++;
                    $t['setting']['urlrule'] = $urlrule;
                    \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($t['setting'])]);
                }
            }

            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('批量设置%s个栏目', $c));
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', ''),
            'list_url' =>\Phpcmf\Service::L('router')->url(APP_DIR.'/category/url_edit'),
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
        $category = \Phpcmf\Service::M('category')->data_for_delete();

        // 筛选栏目id
        $catid = '';
        foreach ($ids as $id) {
            $catid.= ','.($category[$id]['childids'] ? $category[$id]['childids'] : $id);
        }
        
        $catid = explode(',', trim($catid, ','));
        $catid = array_flip(array_flip($catid));

        parent::_Del(
            $catid,
            function ($rows) {
                // 判断删除的栏目是否存在数据
                if ($this->module['share']) {
                    foreach ($rows as $t) {
                        if (!$t['mid']) {
                            continue;
                        }
                        $mod = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-content', $t['mid']);
                        if ($mod) {
                            $table = dr_module_table_prefix($t['mid']);
                            if (!\Phpcmf\Service::M()->db->tableExists(\Phpcmf\Service::M()->dbprefix($table))) {
                                continue;
                            } elseif ($num = \Phpcmf\Service::M()->table($table)->where('catid', $t['id'])->counts()) {
                                return dr_return_data(0, dr_lang('目标栏目【%s】内容存在%s条数据，无法删除', $t['name'], $num));
                            }
                        }
                    }
                } else {
                    foreach ($rows as $t) {
                        $table = dr_module_table_prefix($this->module['dirname']);
                        if (!\Phpcmf\Service::M()->db->tableExists(\Phpcmf\Service::M()->dbprefix($table))) {
                            continue;
                        } elseif ($num = \Phpcmf\Service::M()->table($table)->where('catid', $t['id'])->counts()) {
                            return dr_return_data(0, dr_lang('目标栏目【%s】内容存在%s条数据，无法删除', $t['name'], $num));
                        }
                    }
                }
                return dr_return_data(1);
            },
            function ($rows) {
                // 计算栏目
                // 删除之后记得删除相关模块数据
                //\Phpcmf\Service::M('Category')->delete_content($rows, $this->module);
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

            $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
            if (!$row) {
                $this->_json(0, dr_lang('栏目数据不存在'));
            }

            $row['setting'] = dr_string2array($row['setting']);
            $row['setting']['html'] = 1;
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目状态为: 静态模式 ['. $id.']');
        }

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache();

        $this->_json(1, dr_lang('操作成功'));
    }

    // 复制栏目规则
    public function copy_edit() {

        $at = \Phpcmf\Service::L('input')->get('at');
        $catid = (int)\Phpcmf\Service::L('input')->get('catid');
        $row = \Phpcmf\Service::M('category')->init($this->init)->get($catid);
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
                    \Phpcmf\Service::M('category')->init($this->init)->copy_value($at, $row['setting'], $id);
                }
            } else {
                foreach ($catids as $id) {
                    $c ++;
                    // 指定栏目
                    \Phpcmf\Service::M('category')->init($this->init)->copy_value($at, $row['setting'], $id);
                }
            }

            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('共同步到%s个栏目', $c));
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('tree')->select_category(
                $this->module['category'],
                0,
                'id=\'dr_catid\' name=\'catid[]\' multiple="multiple"  data-actions-box="true"',
                '',
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

            $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
            if (!$row) {
                $this->_json(0, dr_lang('栏目数据不存在'));
            }

            $row['setting'] = dr_string2array($row['setting']);
            $row['setting']['html'] = 0;
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
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
		foreach ($ids as $id) {
			if ($id == $topid) {
				$this->_json(0, dr_lang('栏目上级不能为本身'));
			}
		}

        /*
        $mid = $topid ? '' : $category[$topid]['mid'];
        // 判断mid
        $mmid = '';*/
        if ($topid) {
            // 重新获取数据
            $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
            $category = \Phpcmf\Service::M('category')->data_for_move();
            if (!$category[$topid]) {
                $this->_json(0, dr_lang('目标栏目不存在'));
            } elseif ($this->is_scategory && $category[$topid]['child'] == 0 && $category[$topid]['tid'] == 1) {
                $mid = $category[$topid]['mid'] ? $category[$topid]['mid'] : APP_DIR;
                if (isset($module[$mid]['pcatpost']) && $module[$mid]['pcatpost']) {
                    // 说明父栏目允许发布，不怕你的父栏目数据
                } else {
                    if (dr_is_module($mid) && \Phpcmf\Service::M()->table(dr_module_table_prefix($mid))->where('catid', $topid)->counts()) {
                        $this->_json(0, dr_lang('目标栏目【%s】存在内容数据，无法作为父栏目', $category[$topid]['name']));
                    }
                }
            }
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
        $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $at = \Phpcmf\Service::L('input')->get('at');
        if ($at == 'used') {
            // 可用状态
            $v = $row['disabled'] ? 0 : 1;
            if ($v) {
                $num = 0;
                if ($this->module['share'] && $row['tid'] == 1 && dr_is_module($row['mid'])) {
                    $num = \Phpcmf\Service::M()->table(dr_module_table_prefix($row['mid']))->where('catid in ('.($row['childids'] ? $row['childids'] : $id).')')->counts();
                } elseif (!$this->module['share']) {
                    $num = \Phpcmf\Service::M()->table(dr_module_table_prefix($this->module['dirname']))->where('catid in ('.($row['childids'] ? $row['childids'] : $id).')')->counts();
                }
                if ($num) {
                    $this->_json(0, dr_lang('当前栏目内容存在%s条数据，无法禁用', $num));
                }
            }
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['disabled' => $v]);
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            \Phpcmf\Service::L('input')->system_log('修改栏目的可用状态: '. $id);
            $this->_json(1, dr_lang($v ? '禁用状态' : '可用状态'), ['value' => $v, 'share' => 0]);
        } elseif ($at == 'main') {
            // 显示状态
            $v = $row['ismain'] ? 0 : 1;
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['ismain' => $v]);

            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            \Phpcmf\Service::L('input')->system_log('修改栏目的主栏目状态: '. $id);
            $this->_json(1, dr_lang($v ? '主栏目状态' : '非主栏目状态'), ['value' => $v, 'share' => 0]);
        } else {
            // 显示状态
            $v = $row['show'] ? 0 : 1;
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['show' => $v]);
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            \Phpcmf\Service::L('input')->system_log('修改栏目的显示状态: '. $id);
            $this->_json(1, dr_lang($v ? '显示状态' : '隐藏状态'), ['value' => $v, 'share' => 0]);
        }
    }

    // 后台批量保存是否生成静态的状态
    protected function _Admin_Html_Edit() {

        if (!dr_is_app('chtml')) {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
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
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目状态为: '. $name . '['. $id.']');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang($v ? '静态模式' : '动态模式'), ['value' => $v, 'share' => 1]);
        } else {
            // 独立模块
            $html = (int)$this->module['html'];
            $v = $html ? 0 : 1;
            $name = $v ? '静态模式' : '动态模式';
            $module = \Phpcmf\Service::M()->db->table('module')->where('id', $this->module['id'])->get()->getRowArray();
            if (!$module) {
                $this->_json(0, dr_lang('模块不存在'));
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
            $this->_json(1, dr_lang($name), ['value' => $v, 'share' => 0]);
        }
    }

    // 生成栏目静态
    public function scjt_edit() {

        if (!dr_is_app('chtml')) {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('没有选择任何栏目'));
        }

        if ($this->module['share']) {
            $this->_json(1, dr_url('chtml/html/category_index', ['app' => '', 'ids' => implode(',', $ids)]));
        } else {
            $this->_json(1, dr_url('chtml/html/category_index', ['app' => APP_DIR, 'ids' => implode(',', $ids)]));
        }
    }

    // 生成内容静态
    public function scjt2_edit() {

        if (!dr_is_app('chtml')) {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('没有选择任何栏目'));
        }

        if ($this->module['share']) {
            $this->_json(1, dr_url('chtml/html/show_index', ['app' => '', 'catids' => implode(',', $ids)]));
        } else {
            $this->_json(1, dr_url('chtml/html/show_index', ['app' => APP_DIR, 'catids' => implode(',', $ids)]));
        }
    }

    // 编辑外链
    public function link_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $row['setting'] = dr_string2array($row['setting']);

        if (IS_POST) {
            $row['setting']['linkurl'] = \Phpcmf\Service::L('input')->post('url');
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目外链地址: '. $row['name'] . '['. $id.']');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $is_html = $this->module['share'] ? $this->module['category'][$id]['setting']['html'] : $this->module['html'];
            if ($is_html) {
                // 生成权限文件
                if (!dr_html_auth(1)) {
                    $this->_json(0, dr_lang('/cache/html/ 无法写入文件'));
                }
                $list = WEB_DIR.'index.php?s='.APP_DIR.'&c=html&m=categoryfile&id='.$id;
                $this->_json(1, dr_lang('操作成功'), ['htmlfile' => $list]);
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'myurl' => $row['setting']['linkurl'],
        ]);
        \Phpcmf\Service::V()->display('share_category_linkurl.html');exit;
    }

    // 编辑自定义字段权限
    public function field_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('category')->init($this->init)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('栏目数据不存在'));
        }

        $row['setting'] = dr_string2array($row['setting']);

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('ids');
            $save = $row['setting']['cat_field'] ? $row['setting']['cat_field'] : [];
            foreach ($this->module['category_field'] as $t) {
                if ($t['id']) {
                    if (dr_in_array($t['fieldname'], $post)) {
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
            $save['name'] = 'test';
            $row['setting']['cat_field'] = $save;
            \Phpcmf\Service::M('category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目自定义字段权限: '. $row['name'] . '['. $id.']');

            $catids = \Phpcmf\Service::L('input')->post('catid');
            if ($catids) {
                $c = 0;
                $row['setting'] = dr_string2array($row['setting']);
                if (isset($catids[0]) && $catids[0] == 0) {
                    foreach ($this->module['category'] as $id => $t) {
                        $c ++;
                        // 全部栏目
                        \Phpcmf\Service::M('category')->init($this->init)->copy_value('cat_field', $row['setting'], $id);
                    }
                } else {
                    foreach ($catids as $id) {
                        $c ++;
                        // 指定栏目
                        \Phpcmf\Service::M('category')->init($this->init)->copy_value('cat_field', $row['setting'], $id);
                    }
                }
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'field' => $this->module['category_field'],
            'select' => \Phpcmf\Service::L('tree')->select_category(
                $this->module['category'],
                0,
                'id=\'dr_catid\' name=\'catid[]\' multiple="multiple"  data-actions-box="true"',
                '',
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

        $row = \Phpcmf\Service::M('category')->get($id);
        if (!$row) {
            return [];
        }

        $row['setting'] = dr_string2array($row['setting']);
        if ($row['setting']['cat_field']) {
            foreach ($row['setting']['cat_field'] as $key => $v) {
                unset($this->field[$key]);
            }
        }

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
                }
                $rt = \Phpcmf\Service::M('category')->check_dirname($id, $save['dirname']);
                if (!$rt['code']) {
                    return dr_return_data(0, $rt['msg'], ['field' => 'dirname']);
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
                        //!$save['tid'] && $save['setting']['template']['list'] == 'list.html' && $save['setting']['template']['list'] = 'page.html';
                    }
					if ($old && $old['mid'] && $old['mid'] != $save['mid']
                        && \Phpcmf\Service::M()->is_table_exists(dr_module_table_prefix($old['mid']))
                        && \Phpcmf\Service::M()->table_site($old['mid'])->where('catid', $old['id'])->counts()) {
                        $this->_json(0, dr_lang('本栏目存在所属模块内容数据，请删除数据后，再变更模块操作'));
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
                    $pid = $save['pid'];
                } elseif (!$old && $save['pid']) {
                    $pid = $save['pid'];
                } else {
                    $pid = 0;
                }

                if ($pid) {
                    if (!$this->module['category'][$save['pid']]) {
                        $this->_json(0, dr_lang('父栏目不存在'));
                    } elseif ($this->is_scategory && $this->module['category'][$save['pid']]['pcatpost'] == 0
                        && $this->module['category'][$save['pid']]['child'] == 0 && $this->module['category'][$save['pid']]['tid'] == 1) {
                        $mid = $this->module['category'][$save['pid']]['mid'] ? $this->module['category'][$save['pid']]['mid'] : APP_DIR;
                        if (dr_is_module($mid) && \Phpcmf\Service::M()->table(dr_module_table_prefix($mid))->where('catid', $save['pid'])->counts()) {
                            $this->_json(0, dr_lang('目标栏目【%s】存在内容数据，无法作为父栏目', $this->module['category'][$save['pid']]['name']));
                        }
                    }
                }

                // 不出现在编辑器中的字段
                $save['setting']['cat_field'] = $old['setting']['cat_field'];
                $save['setting']['module_field'] = $old['setting']['module_field'];
                if (isset($save['tid']) && $save['tid'] != 1) {
                    $save['setting']['notedit'] = 0;
                }

                if (!$old && (!isset($save['setting']['urlrule']) || !$save['setting']['urlrule'])) {
                    $save['setting']['urlrule'] = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_URLRULE');
                }

                // 数组json化
                $save['pids'] = '';
                $save['setting'] = dr_array2string($save['setting']);
                $save['pdirname'] = '';
                $save['childids'] = '';
                $save['disabled'] = 0;

                return dr_return_data(1, '', [ 1 => $save]);
            }, function ($id, $data, $old) {
                // 自动更新缓存
                \Phpcmf\Service::M('cache')->sync_cache();
                $is_html = $this->module['share'] ? $this->module['category'][$data[1]['id']]['setting']['html'] : $this->module['html'];
                if ($is_html) {
                    // 生成权限文件
                    if (!dr_html_auth(1)) {
                        $this->_json(0, dr_lang('/cache/html/ 无法写入文件'));
                    }
                    $list = WEB_DIR.'index.php?s='.APP_DIR.'&c=html&m=categoryfile&id='.$data[1]['id'];
                    $this->_json(1, dr_lang('操作成功'), ['htmlfile' => $list]);
                }
            }
        );
    }
}
