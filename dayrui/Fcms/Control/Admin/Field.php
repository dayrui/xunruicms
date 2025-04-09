<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Field extends \Phpcmf\Common {

    public $name;
    public $data;

    public $ftype;

    public $backurl; // 返回链接
    public $cachename; // 缓存名称

    public $namespace;
    public $relatedid;
    public $relatedname;

    public function __construct() {
        parent::__construct();

        $this->name = dr_lang('字段管理');
        $this->namespace = ''; // 设置应用目录

        // 字段来源相关表
        \Phpcmf\Service::M('Field')->relatedid = $this->relatedid = (int)\Phpcmf\Service::L('input')->get('rid');
        \Phpcmf\Service::M('Field')->relatedname = $this->relatedname = \Phpcmf\Service::L('input')->get('rname');

        list($ismain, $issearch, $iscategory) = $this->_set_init();

        // 可用字段类别
        $this->ftype = \Phpcmf\Service::L('Field')->app($this->namespace)->type(\Phpcmf\Service::M('Field')->func);

        // 判断类别权限
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '返回' => ['url:'.$this->backurl, 'fa fa-reply'],
                    $this->name => ['url:'.dr_url('field/index', ['rname'=>$this->relatedname, 'rid'=>$this->relatedid]), 'fa fa-code', 'field/index'],
                    '添加' => ['url:'.dr_url('field/add', ['rname'=>$this->relatedname, 'rid'=>$this->relatedid]), 'fa fa-plus', 'field/add'],
                    '导入' => ['add:field/import_add{rname='.$this->relatedname.'&rid='.$this->relatedid.'}', 'fa fa-sign-in', '60%', '70%'],
                    '修改' => ['hide:field/edit', 'fa fa-edit'],
                ]
            ),
            'rid' => $this->relatedid,
            'rname' => $this->relatedname,
            'ftype' => $this->ftype,
            'ismain' => $ismain,
            'issearch' => $issearch,
            'namespace' => $this->namespace,
            'iscategory' => $iscategory,
        ]);
    }

    public function index() {

        $field = \Phpcmf\Service::M('Field')->get_all_field();
        if ($field) {
            uasort($field, function($a, $b){
                if($a['displayorder'] == $b['displayorder']){
                    return 0;
                }
                return($a['displayorder']<$b['displayorder']) ? -1 : 1;
            });
            $group = [];
            $mygroup = [];
            // 分组和合并字段筛选
            foreach ($field as $t) {
                if ($t['fieldtype'] == 'Group' || $t['fieldtype'] == 'Merge') {
                    if (preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                        foreach ($value[1] as $v) {
                            $group[$t['fieldtype']][$v] = $t['fieldname'];
                        }
                    }
                    $mygroup[$t['fieldtype']][$t['fieldname']] = $t;
                }
            }

            $data = [];
            $group_data = [];

            $ftype = [];
            foreach ($this->ftype as $t) {
                $ftype[$t['id']] = strpos($t['id'], $t['name']) !== false ? $t['id'] : $t['name'].' '.$t['id'];
            }

            // 主字段
            foreach ($field as $t) {

                // 验证字段对象的有效性
                $obj = \Phpcmf\Service::L('Field')->get($t['fieldtype']);
                if ($obj) {
                    if ($obj->use_xss) {
                        // 强制开启xss
                        $t['setting']['validate']['xss'] = 1;
                    } elseif ($obj->close_xss) {
                        // 强制关闭xss
                        $t['setting']['validate']['xss'] = 0;
                    }
                }
                $t['ftype'] = isset($ftype[$t['fieldtype']]) ? $ftype[$t['fieldtype']] : $t['fieldtype'];
                // 重复了 删除记录
                if (isset($data[$t['fieldname']]) && $data[$t['fieldname']]) {
                    \Phpcmf\Service::M()->table('field')->delete($t['id']);
                }
                if (isset($group['Merge'][$t['fieldname']])) {
                    $group_data['Merge'][$t['fieldname']] = $t;
                } elseif (isset($group['Group'][$t['fieldname']])) {
                    // 属于分组字段
                    $group_data['Group'][$t['fieldname']] = $t;
                } elseif ($t['fieldtype'] == 'Group') {
                    $data[$t['fieldname']] = '';
                } elseif ($t['fieldtype'] == 'Merge') {
                    $data[$t['fieldname']] = '';
                } else {
                    $data[$t['fieldname']] = $t;
                }
            }

            if ($mygroup['Merge']) {
                foreach ($mygroup['Merge'] as $m) {
                    $list = [];
                    foreach ($group['Merge'] as $fieldname => $t) {
                        $m['fieldname'] == $t && $list[] = $group_data['Merge'][$fieldname];
                    }
                    $list && $data[$m['fieldname']] = $list;
                }
            }
            if ($mygroup['Group']) {
                foreach ($mygroup['Group'] as $m) {
                    $list = [];
                    foreach ($group['Group'] as $fieldname => $t) {
                        $m['fieldname'] == $t && $list[] = $group_data['Group'][$fieldname];
                    }
                    $list && $data[$m['fieldname']] = $list;
                }
            }

            $list = [];
            foreach ($data as $fname => $t) {
                if (isset($t['id'])) {
                    $list[$t['fieldname']] = $t;
                } elseif ($mygroup['Group'][$fname])  {
                    $mygroup['Group'][$fname]['spacer'] = $group['Merge'][$fname] ? '<span class="tree-icon">└</span>' : '';
                    $list[$fname] = $mygroup['Group'][$fname];
                    foreach ($t as $f) {
                        $f['spacer'] = $group['Merge'][$fname] ? '<span class="tree-icon">└</span><span class="tree-icon">└</span>' :  '<span class="tree-icon">└</span>';
                        $f['id'] && $list[$f['fieldname']] = $f;
                    }
                } elseif ($mygroup['Merge'][$fname])  {
                    $list[] = $mygroup['Merge'][$fname];
                    foreach ($t as $f) {
                        $f['spacer'] = '<span class="tree-icon">└</span>';
                        if ($f['id']) {
                            $list[$f['fieldname']] = $f;
                            if ($mygroup['Group'][$f['fieldname']] && $data[$f['fieldname']]) {
                                foreach ($data[$f['fieldname']] as $ff) {
                                    $ff['spacer'] = '<span class="tree-icon">└</span><span class="tree-icon">└</span>';
                                    $ff['id'] && $list[$ff['fieldname']] = $ff;
                                }
                            }
                        }

                    }
                }
            }
            //print_r($data);
        } else {
            $list = [];
        }

        \Phpcmf\Service::V()->assign(array(
            'list' => $list,
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
        ));
        \Phpcmf\Service::V()->display('field_index.html');
    }

    public function add() {

        $id = 0;
        $page = max((int)\Phpcmf\Service::L('input')->post('page'), 0);

        // 初始化部分值
        $data = [
            'ismember' => 1,
            'ismain' => 1,
            'fieldtype' => '',
            'setting' => [
                'option' => [],
                'validate' => [
                    'xss' => 1,
                    'required' => 0,
                ],
            ],
        ];

        // 提交表单
        if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('input')->post('data', false);
            $field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
            if (!$field) {
                $this->_json(0, dr_lang('字段类别（%s）文件不存在', $data['fieldtype']));
            } elseif (empty($data['name'])) {
                $this->_json(0, dr_lang('字段显示名称不能为空'));
            } elseif (empty($data['fieldname'])) {
                $this->_json(0, dr_lang('字段名称不能为空'));
            } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $data['fieldname'])) {
                $this->_json(0, dr_lang('字段（%s）名称不规范', $data['fieldname']));
            } elseif (strlen($data['fieldname']) > 30) {
                $this->_json(0, dr_lang('字段（%s）名称太长', $data['fieldname']));
            } elseif (\Phpcmf\Service::M('Field')->exitsts($data['fieldname'])) {
                $this->_json(0, dr_lang('字段（%s）已经存在', $data['fieldname']));
            } else {
                $rt = $field->edit_config($data);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                $rt = \Phpcmf\Service::M('Field')->add($data, $field);
                if (!$rt['code']) {
                    $this->_json(0, dr_lang($rt['msg']));
                }
                $this->_cache(); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log('添加'.$this->name.'【'.$data['fieldname'].'】'.$data['name']); // 记录日志
                $this->_json(1, dr_lang('操作成功'));
            }
        }

        \Phpcmf\Service::V()->assign([
            'id' => $id,
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
            'cat_show' => 0,
        ]);
        \Phpcmf\Service::V()->display('field_add.html');
    }

    public function edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $page = max((int)\Phpcmf\Service::L('input')->get('page'), 0);
        $data = \Phpcmf\Service::M()->table('field')->get($id);
        if (!$data) {
            $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

        $data['setting'] = dr_string2array($data['setting']);

        // 加载系统编辑器
        if ($data['fieldtype'] == 'Ueditor' && !is_file(CMSPATH.'Field/Ueditor.php')) {
            $data['fieldtype'] = 'Editor';
        }

        // 验证字段对象的有效性
        $obj = \Phpcmf\Service::L('Field')->get($data['fieldtype']);
        if ($obj) {
            if ($obj->use_xss) {
                // 强制开启xss
                $data['setting']['validate']['xss'] = 1;
            } elseif ($obj->close_xss) {
                // 强制关闭xss
                $data['setting']['validate']['xss'] = 0;
            }
        }

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', false);
            $field = \Phpcmf\Service::L('field')->get($post['fieldtype']);
            if (!$field) {
                $this->_json(0, dr_lang('字段类别（%s）文件不存在', $post['fieldtype']));
            }
            $rt = $field->edit_config($post);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            $rt = \Phpcmf\Service::M('Field')->edit(
                $data,
                $post,
                $field->alter_sql($data['fieldname'], $post['setting']['option'], $data['name'])
            );
            if (!$rt['code']) {
                $this->_json(0, dr_lang($rt['msg']));
            }
            $this->_cache(); // 自动更新缓存
            \Phpcmf\Service::L('input')->system_log('修改'.$this->name.'【'.$data['fieldname'].'】'.$data['name']); // 记录日志
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'id' => $id,
            'data' => $data,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
            'is_edit' => $obj->is_edit,
        ]);
        \Phpcmf\Service::V()->display('field_add.html');
    }

    public function type_edit() {

        $my = dr_safe_filename(\Phpcmf\Service::L('input')->get('my'));
        $to = dr_safe_filename(\Phpcmf\Service::L('input')->get('to'));

        $int = ['Cat', 'Date', 'Linkage', 'Touchspin', 'Uid'];
        $text = ['Catids', 'Files', 'Ftable', 'Image', 'Property', 'Redirect', 'Textarea', 'Editor'];
        $json = ['Checkbox', 'Linkage', 'Members', 'Related', 'Selects'];
        $char = ['Color', 'File', 'Text', 'Textbtn', 'Time', 'Select', 'Radio'];

        if (in_array($my, $int)) {
            if (in_array($to, $int)) {
                $this->_json(1, dr_lang('同一类型'));
            }
            $this->_json(0, dr_lang('当前字段存储的数据是%s，变更后建议手动去数据库将其字段数据类型改成合适的类型，确定继续变更吗？', 'INT'));
        } elseif (in_array($my, $text)) {
            if (in_array($to, $text)) {
                $this->_json(1, dr_lang('同一类型'));
            } elseif (stripos($to, 'editor') !== false) {
                $this->_json(1, dr_lang('同一类型'));
            }
            $this->_json(0, dr_lang('当前字段存储的数据是%s，变更后建议手动去数据库将其字段数据类型改成合适的类型，确定继续变更吗？', 'TEXT'));
        } elseif (in_array($my, $json)) {
            if (in_array($to, $json)) {
                $this->_json(1, dr_lang('同一类型'));
            }
            $this->_json(0, dr_lang('当前字段存储的数据是%s，变更后建议手动去数据库将其字段数据类型改成合适的类型，确定继续变更吗？', 'JSON'));
        } elseif (in_array($my, $char)) {
            if (in_array($to, $char)) {
                $this->_json(1, dr_lang('同一类型'));
            }
            $this->_json(0, dr_lang('当前字段存储的数据是%s，变更后建议手动去数据库将其字段数据类型改成合适的类型，确定继续变更吗？', 'CHAR'));
        } elseif (stripos($my, 'editor') !== false && stripos($to, 'editor') !== false) {
            $this->_json(1, dr_lang('同一类型'));
        } else {
            $this->_json(0, dr_lang('变更字段类别可能会影响已有的数据，你确定吗？'));
        }
    }

    /**
     * 通用操作
     */
    public function option() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table('field')->get($id);
        if (!$data) {
            $this->_json(0, dr_lang('字段不存在'));
        }

        switch (\Phpcmf\Service::L('input')->get('op')) {
            case 'disabled':
                $value = $data['disabled'] == 1 ? 0 : 1;
                \Phpcmf\Service::M()->table('field')->save($id, 'disabled', $value);
                $this->_cache(); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log(($value ? '禁用' : '启用').$this->name.'【'.$data['fieldname'].'】'); // 记录日志
                $this->_json(1, dr_lang(($value ? '禁用' : '启用').'成功'), ['value' => $value]);
                break;
            case 'xss':
                // 验证字段对象的有效性
                $obj = \Phpcmf\Service::L('Field')->get($data['fieldtype']);
                if ($obj) {
                    if ($obj->use_xss) {
                        // 强制开启xss
                        $this->_json(0, dr_lang('该字段已经强制启用了XSS过滤'));
                    } elseif ($obj->close_xss) {
                        // 强制关闭xss
                        $this->_json(0, dr_lang('该字段已经强制关闭了XSS过滤'));
                    }
                }
                $data['setting'] = dr_string2array($data['setting']);
                $data['setting']['validate']['xss'] = $value = $data['setting']['validate']['xss'] ? 0 : 1;
                \Phpcmf\Service::M()->table('field')->save($id, 'setting', dr_array2string($data['setting']));
                $this->_cache(); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log($this->name.'【'.$data['fieldname'].'】'.($value ? '开启XSS' : '关闭XSS')); // 记录日志
                $this->_json(1, dr_lang('操作成功'), ['value' => $value]);
                break;
            case 'member':
                $value = $data['ismember'] ? 0 : 1;
                \Phpcmf\Service::M()->table('field')->save($id, 'ismember', $value);
                $this->_cache(); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log($this->name.'【'.$data['fieldname'].'】'.($value ? '前端显示' : '前端隐藏')); // 记录日志
                $this->_json(1, dr_lang('操作成功'), ['value' => $value]);
                break;
            case 'save':
                \Phpcmf\Service::M()->table('field')->save($id, 'displayorder', dr_safe_replace(\Phpcmf\Service::L('input')->get('value')));
                $this->_cache(); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log('修改排序值: '. $this->name.'【'.$data['fieldname'].'】');
                $this->_json(1, dr_lang('操作成功'));
                break;
        }

        $this->_json(0, dr_lang('未知操作'));
    }

    // 删除字段
    public function del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $rt = \Phpcmf\Service::M('Field')->delete_field($ids);
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        $this->_cache(); // 自动更新缓存
        \Phpcmf\Service::L('input')->system_log('删除字段'. $this->name.' '. implode(',', $ids));

        $this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
    }

    // 导出
    public function export() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $data = \Phpcmf\Service::M()->table('field')->get($id);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('字段（%s）不存在', $id));
        }

        unset($data['id']);
        unset($data['relatedid']);
        unset($data['relatedname']);

        $data['setting'] = dr_string2array($data['setting']);

        \Phpcmf\Service::V()->assign([
            'data' => dr_array2string($data),
        ]);
        \Phpcmf\Service::V()->display('api_export_code.html');exit;
    }

    // 导出
    public function export_all() {

        $ids = \Phpcmf\Service::L('input')->get('ids');
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $rt = '';
        foreach ($ids as $id) {

            $id = intval($id);
            $data = \Phpcmf\Service::M()->table('field')->get($id);
            if (!$data) {
                $this->_admin_msg(0, dr_lang('字段（%s）不存在', $id));
            }

            unset($data['id']);
            unset($data['relatedid']);
            unset($data['relatedname']);

            $data['setting'] = dr_string2array($data['setting']);

            $rt.= dr_array2string($data).PHP_EOL;
        }


        \Phpcmf\Service::V()->assign([
            'data' => $rt,
        ]);
        \Phpcmf\Service::V()->display('api_export_code.html');exit;
    }

    // 导入
    public function import_add() {

        if (IS_AJAX_POST) {
            $code = \Phpcmf\Service::L('input')->post('code');
            $arr = explode(PHP_EOL, $code);
            if (!$arr) {
                $this->_json(0, dr_lang('代码不能为空'));
            }
            $save = [];
            foreach ($arr as $t) {
                if ($t) {
                    $data = dr_string2array($t);
                    if (!$data) {
                        $this->_json(0, dr_lang('代码解析失败'));
                    }
                    $field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
                    if (!$field) {
                        $this->_json(0, dr_lang('字段类别（%s）文件不存在', $data['fieldtype']));
                    } elseif (empty($data['name'])) {
                        $this->_json(0, dr_lang('字段显示名称不能为空'));
                    }
                    if (empty($data['fieldname'])) {
                        $data['fieldname'] = $data['name'];
                    }
                    if (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $data['fieldname'])) {
                        $this->_json(0, dr_lang('字段（%s）名称不规范', $data['fieldname']));
                    } elseif (strlen($data['fieldname']) > 30) {
                        $this->_json(0, dr_lang('字段（%s）名称太长', $data['fieldname']));
                    } elseif (\Phpcmf\Service::M('Field')->exitsts($data['fieldname'])) {
                        $this->_json(0, dr_lang('字段（%s）已经存在', $data['fieldname']));
                    } else {
                        $save[] = $data;
                    }
                }
            }
            if (!$save) {
                $this->_json(0, dr_lang('没有可用的导入字段'));
            }
            // 入库操作
            foreach ($save as $data) {
                $field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
                if (isset($data['id'])) {
                    unset($data['id']);
                }
                $data['setting'] = dr_string2array($data['setting']);
                $rt = \Phpcmf\Service::M('Field')->add($data, $field);
                if (!$rt['code']) {
                    $this->_json(0, dr_lang($rt['msg']));
                }
            }

            $this->_cache(); // 自动更新缓存
            \Phpcmf\Service::L('input')->system_log('导入字段：'.$this->name); // 记录日志
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => '',
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('api_export_code.html');
        exit;
    }

    // 联动更新缓存
    private function _cache() {

        list($case_name, $a) = explode('-', $this->relatedname);

        if (function_exists('myfield_cache_'.$case_name)) {
            call_user_func('myfield_cache_'.$case_name);
        } else {

            switch ($case_name) {

                case 'form':
                    // _网站表单 form-站点id, 表单id
                    \Phpcmf\Service::M('cache')->sync_cache('form', 'form', 1); // 自动更新缓存
                    break;

                case 'tag':
                    // _网站tag
                    \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1); // 自动更新缓存
                    break;

                case 'linkage':
                    // 联动菜单
                    \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
                    break;

                case 'member':
                    // _用户主表
                    \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
                    break;

                case 'navigator':
                    // _导航链接
                    \Phpcmf\Service::M('cache')->sync_cache('navigator', 'navigator', 1); // 自动更新缓存
                    break;

                case 'order':
                    // 订单插件
                    break;

                case 'page':
                    // 网站单页
                    \Phpcmf\Service::M('cache')->sync_cache('page', 'page', 1); // 自动更新缓存
                    break;

                case 'table':
                    // 任意表
                    break;

                case 'module':
                    // 模块字段
                    \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                    break;

                case 'mform':
                    // 模块表单
                    \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                    break;

                case 'category':
                    // 栏目自定义字段
                    \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                    break;

                case 'site':
                    // 网站信息
                    \Phpcmf\Service::L('cache')->del_data('my-site-'.SITE_ID);
                    break;

                default:
                    \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                    break;
            }
        }

    }

    // 初始化设置
    private function _set_init() {

        $local = \Phpcmf\Service::Apps(true);
        if ($local) {
            foreach ($local as $dir => $path) {
                if (is_file($path.'Config/Myfield.php')) {
                    require $path.'Config/Myfield.php';
                }
            }
        }

        $ismain = $issearch = $iscategory = 0;

        list($case_name, $a) = explode('-', $this->relatedname);

        if (function_exists('myfield_init_'.$case_name)) {
            $rt = call_user_func_array('myfield_init_'.$case_name, [
                $this->relatedname,
                $this->relatedid
            ]);
            if (!$rt['code']) {
                $this->_admin_msg(0, $rt['msg']);
            }
            $this->data = $rt['data']['data'];
            $this->name = $rt['data']['name'];
            $this->backurl = $rt['data']['backurl'];
            if (isset($rt['data']['ismain']) && $rt['data']['ismain']) {
                $ismain = 1;
            }
            \Phpcmf\Service::M('Field')->func = $case_name; // 重要标识: 函数和识别码
            \Phpcmf\Service::M('Field')->data = $this->data;
        } else {

            switch ($case_name) {

                case 'form':
                    // _网站表单 form-站点id, 表单id
                    list($a, $siteid) = explode('-', $this->relatedname);
                    $this->data = \Phpcmf\Service::M()->init(['db' => $siteid, 'table' => $siteid.'_form'])->get($this->relatedid);
                    if (!$this->data) {
                        $this->_admin_msg(0, dr_lang('表单【%s】不存在', $this->relatedid));
                    }
                    $this->name = dr_lang('表单【%s】字段', $this->data['name']);
                    $this->backurl = ''; // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'form'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $this->data;
                    break;

                case 'site':
                    // 网站信息
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('site_param/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'site'; // 重要标识: 函数和识别码
                    break;

                case 'tag':
                    // _网站tag
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('tag/home/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'tag'; // 重要标识: 函数和识别码
                    break;

                case 'linkage':
                    // 联动菜单
                    $ismain = 1;
                    $this->name = dr_lang('联动菜单字段');
                    $this->backurl = ''; // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'linkage'; // 重要标识: 函数和识别码
                    break;

                case 'member':
                    // _用户主表
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('member/field/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'member'; // 重要标识: 函数和识别码
                    break;

                case 'navigator':
                    // _导航链接
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('navigator/home/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'navigator'; // 重要标识: 函数和识别码
                    break;

                case 'order':
                    // 订单插件
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('order/field/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'order'; // 重要标识: 函数和识别码
                    break;

                case 'page':
                    // 网站单页
                    $ismain = 1;
                    $this->name = dr_lang('自定义字段');
                    $this->backurl = \Phpcmf\Service::L('Router')->url('page/home/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'page'; // 重要标识: 函数和识别码
                    break;

                case 'table':
                    // 任意表
                    $ismain = 1;
                    $this->name = '【'.\Phpcmf\Service::M()->dbprefix($a).'】';
                    \Phpcmf\Service::M('Field')->data = $a;
                    \Phpcmf\Service::M('Field')->func = 'table'; // 重要标识: 函数和识别码
                    break;

                case 'module':
                    // 模块字段
                    $this->data = \Phpcmf\Service::M()->table('module')->get($this->relatedid);
                    if (!$this->data) {
                        $this->_admin_msg(0, dr_lang('模块【%s】不存在', $this->relatedid));
                    }
                    $this->backurl = ''; // 返回uri地址
                    $this->name = dr_lang('模块【%s】字段', $this->data['dirname']);
                    \Phpcmf\Service::M('Field')->func = 'module'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $this->data;
                    $this->namespace = $this->data['dirname'];
                    break;

                case 'mform':
                    // 模块表单
                    $this->data = \Phpcmf\Service::M()->table('module_form')->get($this->relatedid);
                    if (!$this->data) {
                        $this->_admin_msg(0, dr_lang('模块【%s】不存在', $this->relatedid));
                    }
                    $this->backurl = ''; // 返回uri地址
                    $this->name = dr_lang('模块【%s】的表单【%s】字段', $a, $this->data['name']);
                    \Phpcmf\Service::M('Field')->func = 'mform'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $this->data;
                    $this->namespace = $this->data['module'];
                    break;

                case 'category':
                    // 栏目自定义字段
                    $ismain = 1;
                    $this->name = dr_lang('栏目自定义字段');
                    $this->backurl = ''; // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'category'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $a;
                    $this->module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$a);
                    if (!$this->module) {
                        $this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $a));
                    }
                    break;

                case 'catmodule':
                    // 识别栏目模型字段
                    $ismain = 1;
                    $issearch = 1;
                    $iscategory = 1;
                    $module = $a;
                    $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);
                    if (!$cache) {
                        $this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $module));
                    }
                    if ($this->relatedid) {
                        $this->data = dr_cat_value($cache['mid'], $this->relatedid);
                        if (!$this->data) {
                            $this->_admin_msg(0, dr_lang('模块【%s】栏目【%s】缓存不存在', $module, $this->relatedid));
                        }
                        if ($module == 'share') {
                            $this->data['tid'] != 1 && $this->_admin_msg(0, dr_lang('模块栏目才支持创建'));
                            $this->data['dirname'] = $this->data['mid'];
                            $this->backurl = \Phpcmf\Service::L('Router')->url('category/index'); // 返回uri地址
                            $this->name = dr_lang('模块【%s】栏目【#%s】模型字段', $this->data['mid'], $this->relatedid);
                        } else {
                            $this->data['dirname'] = $module;
                            $this->backurl = \Phpcmf\Service::L('Router')->url($module.'/category/index'); // 返回uri地址
                            $this->name = dr_lang('模块【%s】栏目【#%s】模型字段', $module, $this->relatedid);
                        }
                    } else {
                        $this->data = [
                            'dirname' => $module,
                        ];
                        $this->name = dr_lang('模块【%s】栏目公共模型字段', $module);
                        $this->backurl = \Phpcmf\Service::L('Router')->url('module/module_category/field_index', ['dir' => $module]); // 返回uri地址
                    }

                    $this->module = $cache;

                    \Phpcmf\Service::M('Field')->func = 'category_data'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $this->data;
                    $this->namespace = $module;
                    break;

                default:
                    if (strpos($this->relatedname, 'comment-module') !== false) {
                        // 模块评论字段
                        if (!dr_is_app('comment')) {
                            $this->_admin_msg(0, dr_lang('系统没有安装评论插件'));
                        }
                        $ismain = 1;
                        list($a, $b, $module) = explode('-', $this->relatedname);
                        $cache = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $module);
                        if (!$cache) {
                            $this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $module));
                        }
                        $this->name = '模块【' . $cache['name'] . '】评论字段';
                        $this->data = $cache['dirname'];
                        $this->backurl = ''; // 返回uri地址
                        \Phpcmf\Service::M('Field')->func = 'comment'; // 重要标识: 函数和识别码
                        \Phpcmf\Service::M('Field')->data = $cache['dirname'];
                        $this->namespace = $cache['dirname'];
                    } elseif (strpos($this->relatedname, 'comment-mform') !== false) {
                        // 模块评论字段
                        if (!dr_is_app('comment')) {
                            $this->_admin_msg(0, dr_lang('系统没有安装评论插件'));exit;
                        }
                        $ismain = 1;
                        list($a, $b, $module, $fid) = explode('-', $this->relatedname);
                        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);
                        if (!$cache) {
                            $this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $module));
                        } elseif (!$cache['form'][$fid]) {
                            $this->_admin_msg(0, dr_lang('模块【%s】表单【%s】缓存不存在', $module, $fid));
                        }
                        $this->name = '模块【'.$cache['name'].'】表单【'.$cache['form'][$fid]['name'].'】评论字段';
                        $this->data = $cache['dirname'].'_form_'.$cache['form'][$fid]['table'];
                        $this->backurl = ''; // 返回uri地址
                        \Phpcmf\Service::M('Field')->func = 'comment'; // 重要标识: 函数和识别码
                        \Phpcmf\Service::M('Field')->data = $this->data;
                        $this->namespace = $cache['dirname'];
                    } elseif (strpos($this->relatedname, 'comment-form') !== false) {
                        // 表单评论字段
                        if (!dr_is_app('comment')) {
                            $this->_admin_msg(0, dr_lang('系统没有安装评论插件'));exit;
                        }
                        $ismain = 1;
                        list($a, $b, $fid) = explode('-', $this->relatedname);
                        $cache = \Phpcmf\Service::L('cache')->get('form-'.$this->relatedid, $fid);
                        if (!$cache) {
                            $this->_admin_msg(0, dr_lang('表单【%s】缓存不存在', $fid));
                        }
                        $this->name = '表单【'.$cache['name'].'】评论字段';
                        $this->data = 'form_'.$cache['table'];
                        $this->backurl = ''; // 返回uri地址
                        \Phpcmf\Service::M('Field')->func = 'comment'; // 重要标识: 函数和识别码
                        \Phpcmf\Service::M('Field')->data = $this->data;
                    } elseif (function_exists('myfield_info_'.$case_name)) {
                        // 其他自定义
                        $rt = call_user_func_array('myfield_info_'.$case_name, array(
                            $this->relatedname,
                            $this->relatedid
                        ));
                        if (is_array($rt)) {
                            list(
                                $ismain,
                                $this->name,
                                $this->data,
                                \Phpcmf\Service::M('Field')->func,
                                $this->backurl
                                ) = $rt;
                            \Phpcmf\Service::M('Field')->data = $this->data;
                        } else {
                            $this->_admin_msg(0, $rt);
                        }
                    }
                    break;
            }

        }

        \Phpcmf\Service::V()->assign('fmid', \Phpcmf\Service::M('Field')->func);

        if (!$this->name) {
            $this->_admin_msg(0, dr_lang('字段来源未定义'));
        }

        return [$ismain, $issearch, $iscategory];
    }

}
