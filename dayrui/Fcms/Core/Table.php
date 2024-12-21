<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 内容操作类
class Table extends \Phpcmf\Common {
    
    public $dfield; // 自定义字段对象
    public $init; // 数据表初始化 [ fmode init方法参数 ]
    public $is_get_catid; // 当期栏目id

    public $mytable; // 表格列表属性
    protected $my_field; // 预定义变量
    protected $my_clink; // 自定义clink按钮html

    protected $model; // 模型类
    protected $db_source; // 数据源
    protected $field; // 自定义字段 [ 1 = [主表], 0 = [附表]]
    protected $sys_field; // 系统字段 [ 1 = [主表], 0 = [附表]]
    protected $not_field; // 无用的字段
    protected $form_rule; // 表单配置规则
    protected $is_data; // 是否支持附表
    protected $is_post_code; // 是否提交验证码
    protected $is_module_index; // 是否支持模块索引
    protected $is_category_data_field; // 是否支持模块栏目模型字段

    protected $list_where; // 列表数据时的条件
    protected $edit_where; // 修改数据时的条件
    protected $delete_where; // 删除数据时的条件
    protected $is_diy_where_list; // 是否支持自定义参数查询的条件

    protected $name; // 定义一个操作显示名称
    protected $tpl_name; // 模板命名名称
    protected $tpl_prefix; // 模板前缀
    protected $list_pagesize; // 模板列表分页量

    protected $auto_save; // 自动存储
    protected $replace_id; // 替换主id(link_id)

    protected $url_params; // url参数固定
    protected $admin_tpl_path; // 后台模板指定目录
    protected $fix_admin_tpl_path; // 修正值的后台模板指定目录

    protected $fix_table_list; //
    protected $is_ajax_list; // 是否作为ajax请求列表数据，不进行第一次查询
    protected $is_search; // 是否开启列表上方的搜索功能
    protected $is_recycle = 0; // 是否启用回收站索功能
    protected $is_recycle_init = 0; // 是否来自回收站操作
    protected $is_iframe_post = 0; // 编辑新增是否采用弹窗模式
    protected $iframe_post_area = ['', '']; // 弹窗模式尺寸
    protected $is_fixed_columns; // 是否开启列表右侧一行浮动固定
    protected $is_show_search_bar; // 是否默认显示搜索区域

    public function __construct() {
        parent::__construct();
        $this->is_data = 0;
        $this->tpl_name = '';
        $this->auto_save = 1;
        $this->is_search = 1;
        $this->is_show_search_bar = 1;
        $this->tpl_prefix = \Phpcmf\Service::L('Router')->class.'_';
        $this->delete_where = '';
        $this->is_module_index = 0;
        $this->is_category_data_field = 0;
        $this->is_diy_where_list = 0;
        $this->admin_tpl_path = (APP_DIR ? APPPATH.'Views/' : COREPATH.'View/');
    }

    // 数据库对象
    protected function _db() {

        if ($this->db_source) {
            return \Phpcmf\Service::M()->db_source($this->db_source);
        }

        return \Phpcmf\Service::M();
    }
    
    // 数据表初始化
    protected function _init($data) {
        !$data['show_field'] && $data['show_field'] = 'id';
        $this->field = $data['field'] ? $data['field'] : $this->field;
        $this->not_field = [];
        $this->sys_field = $data['sys_field'] ? \Phpcmf\Service::L('Field')->sys_field($data['sys_field']) : [];
        $data['field'] = $this->sys_field && $this->field ? $this->field + $this->sys_field : ($this->field ? $this->field : $this->sys_field);
        $data['is_diy_where_list'] = $this->is_diy_where_list;
        $this->init = $data;
        $this->db_source = isset($data['db_source']) ? $data['db_source'] : '';
        return $this;
    }

    // 获取入库时的字段
    protected function _field_save($catid = 0) {

        $field = $this->sys_field ? dr_array22array($this->sys_field, $this->field) : $this->field;

        // 栏目模型字段
        if ($this->is_category_data_field && $catid) {
            if (function_exists('dr_module_category_data_field')) {
                $field = dr_module_category_data_field(dr_cat_value($this->module['mid'], $catid), $field, $this->module);
            } else {
                log_message('error', '内容建站系统插件版本需要升级');
            }
        }

        if ($field) {
            foreach ($field as $i => $t) {
                if (!IS_ADMIN && !$t['ismember']) {
                    // 非管理平台验证字段显示权限
                    $this->not_field[$i] = $t;
                    unset($field[$i]);
                } elseif (IS_ADMIN && $t['setting']['show_admin'] && !dr_in_array(1, $this->admin['roleid'])
                    && dr_array_intersect($this->admin['roleid'], $t['setting']['show_admin'])) {
                    // 后台时 判断管理员权限
                    $this->not_field[$i] = $t;
                    unset($field[$i]);
                }
            }
        }

        return $field;
    }

    /**
     * 字段进行分组
     * */ 
    protected function _field_group($data) {

        $field = $this->field;
        $my_field = $sys_field = $diy_field = $cat_field = [];

        // 栏目模型字段
        if ($this->is_category_data_field && $data['catid']) {
            if (function_exists('dr_module_category_data_field')) {
                $field = dr_module_category_data_field(dr_cat_value($this->module['mid'], $data['catid']), $field, $this->module);
            } else {
                log_message('error', '内容建站系统插件版本需要升级');
            }
        }

        $field && uasort($field, function($a, $b){
            if($a['displayorder'] == $b['displayorder']){
                return 0;
            }
            return($a['displayorder']<$b['displayorder']) ? -1 : 1;
        });

        foreach ($field as $i => $t) {
            if ($t['setting']['is_right'] == 1) {
                // 右边字段归类为系统字段
                if (IS_ADMIN) {
                    $sys_field[$i] = $t;
                } else {
                    $my_field[$i] = $t;
                }

            } elseif ($t['setting']['is_right'] == 2) {
                // diy字段
                $diy_field[$i] = $t;
            } else {
                $my_field[$i] = $t;
            }
        }

        $this->sys_field && $sys_field = $this->sys_field + $sys_field ;

        return [$my_field, $sys_field, $diy_field, $cat_field];
    }

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {

        if (!$id) {
            return [];
        }

        $row = $this->_db()->init($this->init)->get($id);
        if (!$row) {
            return [];
        }

        if ($this->is_recycle_init) {
            // 来自回收站的数据阅读
            return dr_array22array(dr_string2array($row['content']), dr_string2array($row['content2']));
        }

        // 附表存储
        if ($this->is_data) {
            $r = $this->_db()->table($this->init['table'] . '_data_'.intval($row['tableid']))->get($id);
            $row = $r ? $r + $row : $row;
        }

        return $row;
    }

    /**
     * 排序值操作
     * $id      内容id
     * */
    protected function _Display_Order($id, $value, $after = null) {
        $this->_Save_Value($id, 'displayorder', $value, $after);
    }

    /**
     * 单个字段存储值
     * $id      内容id
     * $name    字段名称
     * $value   字段值
     * */
    protected function _Save_Value($id, $name, $value, $after = null, $before = null) {

        $table = $this->init['table'];
        if (!$this->_db()->is_table_exists($table)) {
            $this->_json(0, dr_lang('数据表（%s）不存在', $this->init['table']));
        } elseif (!$this->_db()->is_field_exists($table, $name)) {
            if (isset($this->init['stable']) && $this->init['stable']
                && $this->_db()->is_table_exists($this->init['stable'])
                && $this->_db()->is_field_exists($this->init['stable'], $name)) {
                $table = $this->init['stable'];
            } else {
                $this->_json(0, dr_lang('数据表（%s）字段（%s）不存在', $this->init['table'], $name));
            }
        }

        // 查询数据
        $row = $this->_db()->table($table)->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('数据%s不存在', $id));
        } elseif ($row[$name] == $value) {
            $this->_json(1, dr_lang('没有变化'));
        }

        // 存储之前
        if ($before) {
            $rt = call_user_func_array($before, [$row]);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            $rt['data'] && $value = $rt['data'];
        }

        $rt = $this->_db()->table($table)->save($id, $name, $value, $this->edit_where);
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::L('input')->system_log($this->name.'：修改('.$row[$this->init['show_field']].')表字段('.$name.')的值为'.$value);

        // 自动更新缓存
        IS_ADMIN && \Phpcmf\Service::M('cache')->sync_cache();

        // 提交之后的操作
        if ($after) {
            call_user_func_array($after, [$row]);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 用于控制器的存储
    public function save_value_edit() {

        $cache_uid = $this->session()->get('function_list_save_text_value');
        if (!$cache_uid) {
            $this->_json(0, dr_lang('权限认证过期，请重试'));
        } elseif ($this->uid != $cache_uid) {
            $this->_json(0, dr_lang('权限认证失败，请重试'));
        }

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $name = dr_safe_filename(\Phpcmf\Service::L('input')->get('name'));
        $value = urldecode((string)\Phpcmf\Service::L('input')->get('value'));
        $after = dr_safe_filename(\Phpcmf\Service::L('input')->get('after'));
        $before = dr_safe_filename(\Phpcmf\Service::L('input')->get('before'));
        if ($before) {
            if (strpos($before, 'dr_') === 0 or strpos($before, 'my_') === 0) {

            } else {
                $this->_json(0, '函数【'.$before.'】必须以dr_或者my_开头');
            }
        }

        if (!$id) {
            $this->_json(0, dr_lang('缺少id参数'));
        } elseif (!$name) {
            $this->_json(0, dr_lang('缺少name参数'));
        }

        $this->_Save_Value($id, $name, $value, $after, $before);
    }
    
    // 格式化保存数据
    protected function _Format_Data($id, $data, $old) {
        return $data;
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $old     老数据
     * $func    格式化提交的数据 提交前   
     * $func    格式化提交的数据 保存后
     * */ 
    protected function _Save($id = 0, $data = [], $old = [], $before = null, $after = null) {

        // 附表id号
        $this->is_data && $tid = intval($old['tableid']);

        // 格式化提交的数据
        if ($before) {
            $rt = call_user_func_array($before, [$id, $data, $old]);
            if (!$rt['code']) {
                return $rt;
            }
            $data = $rt['data'];
        }

        // 模块数据
        if ($this->is_module_index) {
            $rt = $this->content_model->save_content($id, $data, $old);
            if (!$rt['code']) {
                return $rt;
            }
            $data = $rt['data'];
            $data[1]['id'] = $data[0]['id'] = $id = $rt['code'];
        } else {
            // 主表数据
            $main = isset($data[1]) ? $data[1] : $data;
            if ($id) {
                // 更新数据
                $rt = $this->_db()->table($this->init['table'])->update($id, $main, $this->edit_where);
                if (!$rt['code']) {
                    return $rt;
                }
            } else {
                // 新增数据
                $rt = $this->_db()->table($this->init['table'])->replace($main);
                if (!$rt['code']) {
                    return $rt;
                }
                // 新增获取id
                $_id = $rt['code'];
                // 副表据量无限分表
                if ($this->is_data) {
                    $tid = \Phpcmf\Service::M()->get_table_id($_id);
                    $this->_db()->table($this->init['table'])->update($_id, ['tableid' => $tid], $this->edit_where);
                }
            }
            // 附表存储
            if ($this->is_data) {
                // 判断附表是否存在,不存在则创建
                $this->_db()->is_data_table($this->init['table'].'_data_', $tid);
                $table = $this->init['table'].'_data_'.$tid;
                if ($id) {
                    if ($data[0]) {
                        $rt = $this->_db()->table($table)->update($id, $data[0], $this->edit_where);
                        if ($rt['msg']) {
                            // 删除主表
                            $this->_db()->table($this->init['table'])->delete($id);
                            // 删除索引
                            $this->is_module_index && $this->_db()->table($this->init['table'].'_index')->delete($id);
                            return $rt;
                        } 
                    } else {
                        // 有种情况就是附表没有数据;
                    }
                } else {
                    $data[0]['id'] = $_id; // 录入主表id
                    $rt = $this->_db()->table($table)->replace($data[0]);
                    if ($rt['msg']) {
                        // 删除主表
                        $this->_db()->table($this->init['table'])->delete($_id);
                        // 删除索引
                        $this->is_module_index && $this->_db()->table($this->init['table'].'_index')->delete($_id);
                        return $rt;
                    }
                }
            }

            // 获取真实id
            $data[1]['id'] = $data[0]['id'] = $id = $id ? $id : $_id;
        }

        // 提交之后的操作
        if ($after) {
            $rt = call_user_func_array($after, [$id, $data, $old]);
            if ($rt && isset($rt['code'])) {
                return $rt;
            }
        }

        return dr_return_data($id, 'ok', $data);
    }

    /**
     * 提交内容
     * $id      内容id,新增为0,否则视为修改
     * $draft   草稿数据
     * $is_data 将内容数据返回到data数组里面
     * $is_post 强制post执行
     * */
    protected function _Post($id = 0, $draft = [], $is_data = 0, $is_post = 0) {

        $uri =\Phpcmf\Service::L('Router')->uri();
        $name = md5($id.$uri); // 当前页面唯一标识

        // 表单操作类
        \Phpcmf\Service::L('Form')->id($id); // 初始化id

        // 获取数据
        $data = $this->_Data($id);
        $this->replace_id && $id = $this->replace_id; // 替换主id

        // 初始化自定义字段类
        \Phpcmf\Service::L('Field')->app(APP_DIR);

        if (IS_AJAX_POST || $is_post) {
            // 内容不存在
            if (!$data && $id) {
                $this->_json(0, dr_lang('数据#%s不存在', $id));
            } elseif ($this->is_post_code && !\Phpcmf\Service::L('Form')->check_captcha('code')) {
                // 验证码验证
                $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
            }
            // 验证数据
            \Phpcmf\Service::L('field')->value = $data;
            $post = \Phpcmf\Service::L('input')->post('data', false);
            list($post, $return, $attach) = \Phpcmf\Service::L('Form')->validation(
                $post, 
                $this->form_rule,
                $this->_field_save(intval(\Phpcmf\Service::L('input')->post('catid'))),
                $data
            );
            // 输出错误
            if ($return) {
                $this->_json(0, $return['error'], ['field' => $return['name']]);
            }
            if ($this->not_field && $data) {
                // 将无权限的字段赋值为老数据
                foreach ($this->not_field as $key => $val) {
                    if (isset($data) && $data[$key]) {
                        $post[$val['ismain']][$key] = is_array($data[$key]) ? dr_array2string($data[$key]) : $data[$key];
                    }
                }
            }
            // 格式化数据
            $post = $this->_Format_Data($id, $post, $id ? $data : []);
            // 保存数据
            $rt = $this->_Save($id, $post, $id ? $data : []);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg'], $rt['data']);
            }
            $post['id'] = $rt['code'];
            // 记录日志
            $logname = 'id：'.$post['id'];
            if (isset($post[$this->init['show_field']]) && $post[$this->init['show_field']]) {
                $logname = ($this->init['show_field'] != 'id' ? $logname.' ' : '').$this->init['show_field'].'：'.$post[$this->init['show_field']];
            } elseif (isset($data[$this->init['show_field']]) && $data[$this->init['show_field']]) {
                $logname = ($this->init['show_field'] != 'id' ? $logname.' ' : '').$this->init['show_field'].'：'.$data[$this->init['show_field']];
            }
            \Phpcmf\Service::L('input')->system_log(
                $this->name . dr_lang($id ? '修改' : '新增').' ('.$logname.')',
                0,
                $post
            );
            // 获取新的存储id
            $id = $rt['code'];
            // 附件归档
            if (SYS_ATTACHMENT_DB && $attach) {
                \Phpcmf\Service::M('Attachment')->handle(
                    isset($data['uid']) ? $data['uid'] : $this->member['id'],
                    \Phpcmf\Service::M()->dbprefix($this->init['table']).'-'.$id,
                    $attach
                );
            }
            // 删除临时存储数据
            \Phpcmf\Service::L('Form')->auto_form_data_delete($name);
            // 执行回调方法
            $cp = $this->_Call_Post($rt['data']);
            $this->_json($cp['code'], $cp['msg'], $cp['data']);
        }

        // 内容不存在
        if (!$data && $id) {
            IS_ADMIN ? $this->_admin_msg(0, dr_lang('数据#%s不存在', $id)) : $this->_msg(0, dr_lang('数据#%s不存在', $id));
            return [null, null];
        }

        // 默认获取表单自动存储的数据
        if (defined('SYS_AUTO_FORM') && SYS_AUTO_FORM && !$id && $this->auto_save) {
			$data = \Phpcmf\Service::L('Form')->auto_form_data($name, $data);
		}

        // 当存在草稿时系统默认加载草稿数据
        $draft && $data = $draft;

        // 主要数据
        $mydata = $data;

        // 获取从get中栏目参数
        $this->is_get_catid && $data['catid'] = $mydata['catid'] = $this->is_get_catid;

        // 是否包在data里面
        //$is_data && $data['data'] = $mydata;

        // 获取自定义字段表单控件
        list($my_field, $sys_field, $diy_field, $cat_field) = $this->_field_group($mydata);
        $data['myfield'] = \Phpcmf\Service::L('Field')->toform($id, $my_field, $mydata);
        $data['sysfield'] = \Phpcmf\Service::L('Field')->toform($id, $sys_field, $mydata);
        $data['diyfield'] = \Phpcmf\Service::L('Field')->toform($id, $diy_field, $mydata);
        $data['catfield'] = \Phpcmf\Service::L('Field')->toform($id, $cat_field, $mydata);
        $data['mymerge'] = \Phpcmf\Service::L('Field')->merge;

        // 动态实时存储表单值
        if (defined('SYS_AUTO_FORM') && SYS_AUTO_FORM && !$id && $this->auto_save) {
			$data['auto_form_data_ajax'] = \Phpcmf\Service::L('Form')->auto_form_data_ajax($name);
		}

        // 表单隐藏域
        $data['form'] = dr_form_hidden([
            'id' => $id,
            'table' => IS_ADMIN ? $this->init['table'] : '',
        ]);

        // 获取添加URL
        $data['post_url'] = IS_MEMBER ? \Phpcmf\Service::L('Router')->member_url(\Phpcmf\Service::L('Router')->uri('add'), $this->url_params) :  \Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->uri('add'), $this->url_params);

        // 获取返回URL
        $data['reply_url'] = \Phpcmf\Service::L('Router')->get_back(\Phpcmf\Service::L('Router')->uri('index'), $this->url_params, true);
        $data['uriprefix'] = trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/'); // uri前缀部分
        
        // 判断是否是编辑,返回id号
        $data['is_edit'] = $id;

        \Phpcmf\Service::V()->assign($data);

        return [$this->_tpl_filename('post', $id ? 'edit' : ''), $data];
    }

    /**
     * 回调保存或添加结果
     * */
    protected function _Call_Post($data) {
        return dr_return_data(1, dr_lang('操作成功'), $data);
    }

    /**
     * 显示内容
     * $id      内容id,新增为0,否则视为修改
     * */
    protected function _Show($id) {

        // 获取数据
        $data = $this->_Data($id);
        // 内容不存在
        if (!$data) {
            return [null, null];
        }

        // 初始化自定义字段类
        \Phpcmf\Service::L('Field')->app(APP_DIR);

        // 获取自定义字段表单控件
        list($my_field, $sys_field, $diy_field, $cat_field) = $this->_field_group($data);
        $data['myfield'] = \Phpcmf\Service::L('Field')->toform($id, $my_field, $data, 1);
        $data['sysfield'] = \Phpcmf\Service::L('Field')->toform($id, $sys_field, $data, 1);
        $data['diyfield'] = \Phpcmf\Service::L('Field')->toform($id, $diy_field, $data, 1);
        $data['catfield'] = \Phpcmf\Service::L('Field')->toform($id, $cat_field, $data, 1);

        $fields = $this->field;
        $fields['inputtime'] = ['fieldtype' => 'Date'];
        $fields['updatetime'] = ['fieldtype' => 'Date'];

        // 格式化字段
        $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $data = \Phpcmf\Service::L('Field')->format_value($fields, $data, $page);

        // 获取返回URL
        $data['reply_url'] = \Phpcmf\Service::L('Router')->get_back(\Phpcmf\Service::L('Router')->uri('index'), $this->url_params);
        $data['uriprefix'] = trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/'); // uri前缀部分

        \Phpcmf\Service::V()->assign($data);

        return [$this->_tpl_filename('post', 'show'), $data];
    }

    /**
     * 批量删除数据
     * $ids
     * $before 删除前执行的操作
     * $after 删除后执行的操作
     * $attach 删除关联附件
     * */ 
    protected function _Del($ids, $before = null, $after = null, $attach = 0) {

        if (!$ids) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        $rows = $this->_db()->init($this->init)->where_in('id', $ids)->getAll();
        if (!$rows) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        if ($this->is_recycle) {
            // 软删除加入回收站
            $this->_Recycle_Add($rows);
            $this->_json(1, dr_lang('操作成功'));
        }

        // 删除之前执行
        if ($before) {
            $rt = call_user_func_array($before, [$rows]);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            $rt['data'] && $rows = $rt['data'];
        }
        
        // 删除数据
        $ids = [];
        foreach ($rows as $t) {
            $id = intval($t['id']);
            $rt = $this->_db()->init($this->init)->delete($id, $this->delete_where);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            if ($this->is_data && !$this->is_recycle_init) {
                // 附表存储 清空回收站时不删除附表因为已经无数据了
                $rt = $this->_db()->init($this->init)->table($this->init['table'].'_data_'.intval($t['tableid']))->delete($id, $this->delete_where);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
            }
            // 删除附件
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->cid_delete($this->member, $id, $attach);
            $ids[] = $id;
        }

        // 删除之后执行
        $after && call_user_func_array($after, [$rows]);

        // 写入日志
        \Phpcmf\Service::L('input')->system_log($this->name.'：删除('.implode(', ', $ids).')');

        // 返回参数
        \Phpcmf\Service::L('Router')->clear_back(\Phpcmf\Service::L('Router')->uri('index'));
        
        $this->_json(1, dr_lang('操作成功'));
    }

    /**
     * 数据列表显示
     * $p      URL指定参数
     * $size   指定分页数据量
     * */
    protected function _List($p = [], $size = 0) {

        // 分页数量控制
        if (!$this->list_pagesize) {
            if (!$size) {
                if (IS_ADMIN) {
                    $size = (int)SYS_ADMIN_PAGESIZE;
                } else {
                    $size = (int)$this->member_cache['config']['pagesize'];
                    if (IS_API_HTTP) {
                        $size = (int)$this->member_cache['config']['pagesize_api'];
                    } elseif (\Phpcmf\Service::IS_MOBILE()) {
                        $size = (int)$this->member_cache['config']['pagesize_mobile'];
                    }
                }
            }
            !$size && $size = 10;
        } else {
            $size = $this->list_pagesize;
        }

        $uriprefix = trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/');

        // 按ajax返回
        if (isset($_GET['is_ajax']) && $_GET['is_ajax']) {
            // 按ajax分页
            if (isset($_GET['pagesize']) && $_GET['pagesize']) {
                $size = intval($_GET['pagesize']);
            }
            // 查询数据结果
            list($list, $total, $param) = $this->_db()->init($this->init)->limit_page($size, $this->list_where);
            $sql = $this->_db()->get_sql_query();
            // 格式化字段
            if ($this->init['list_field'] && $list) {
                $field = $this->_field_save(0);
                if ($this->not_field) {
                    $field = dr_array22array($field, $this->not_field);
                }
                $dfield = \Phpcmf\Service::L('Field')->app(APP_DIR);
                foreach ($list as $k => $v) {
                    $this->my_clink && $v['link_tpl'] = $this->_Clink_tpl($uriprefix, $v);
                    $list[$k] = $dfield->format_value($field, $v, 1);
                    foreach ($this->init['list_field'] as $i => $t) {
                        if ($t['use']) {
                            $list[$k][$i] = dr_list_function($t['func'], $list[$k][$i], $param, $list[$k], $field[$i], $i);
                        }
                    }
                }
            }
            // 格式化结果集
            $list = $this->_Call_List($list);

            // 存储当前页URL
            unset($param['is_ajax']);
            \Phpcmf\Service::L('Router')->set_back(\Phpcmf\Service::L('Router')->uri(), $param);
            $this->_json(1, $total, $list, '', ['sql' => $sql]);
        }


        $list_field = [];
        // 筛选出可用的字段
        if ($this->init['list_field']) {
            foreach ($this->init['list_field'] as $i => $t) {
                $t['use'] && $list_field[$i] = $t;
            }
        }

        // 默认显示字段
        !$list_field && $this->init['show_field'] && $list_field = [
            $this->init['show_field'] => [
                'name' => 'Id',
                'func' => '',
                'width' => 100,
            ],
        ];

        if ($this->is_ajax_list && !CI_DEBUG) {

            $param = \Phpcmf\Service::L('input')->get();
            unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);

            // 默认以显示字段为搜索字段
            if (!isset($param['field']) && !$param['field']) {
                $param['field'] = isset($this->init['search_first_field']) && $this->init['search_first_field'] ? $this->init['search_first_field'] : $this->init['show_field'];
            }
            if ($param['keyword']) {
                $param['keyword'] = htmlspecialchars($param['keyword']);
            }

            // 返回数据
            $data = [
                'param' => dr_htmlspecialchars($param),
                'my_file' => $this->_tpl_filename('table'),
                'uriprefix' => trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/'), // uri前缀部分
                'list_field' => $list_field, // 列表显示的可用字段
            ];
        } else {
            // 查询数据结果
            list($list, $total, $param) = $this->_db()->init($this->init)->limit_page($size, $this->list_where);
            $p && $param = $p + $param;
            $sql = $this->_db()->get_sql_query();

            // 默认以显示字段为搜索字段
            if (!isset($param['field']) && !$param['field']) {
                $param['field'] = isset($this->init['search_first_field']) && $this->init['search_first_field'] ? $this->init['search_first_field'] : $this->init['show_field'];
            }
            // 分页URL格式
            $this->url_params && $param = dr_array22array($param, $this->url_params);
            $uri = \Phpcmf\Service::L('Router')->uri();
            $url = IS_ADMIN ? \Phpcmf\Service::L('Router')->url($uri, $param) : \Phpcmf\Service::L('Router')->member_url($uri, $param);
            $url = $url.'&page={page}';

            // 分页输出样式
            $config = $this->_Page_Config();

            // 存储当前页URL
            \Phpcmf\Service::L('Router')->set_back(\Phpcmf\Service::L('Router')->uri(), $param);

            // 查询表名称
            $list_table = \Phpcmf\Service::M()->dbprefix($this->init['table']);
            if (isset($this->init['join_list'][0]) && $this->init['join_list'][0]) {
                $list_table.= ','.\Phpcmf\Service::M()->dbprefix($this->init['join_list'][0]);
            }
            // 格式化字段
            if ($list) {
                $field = $this->_field_save(0);
                if ($this->not_field) {
                    $field = dr_array22array($field, $this->not_field);
                }
                $dfield = \Phpcmf\Service::L('Field')->app(APP_DIR);
                foreach ($list as $k => $v) {
                    $this->my_clink && $v['link_tpl'] = $this->_Clink_tpl($uriprefix, $v);
                    $list[$k] = $dfield->format_value($field, $v, 1);
                }
            }
            // 格式化结果集
            $list = $this->_Call_List($list);

            // 返回数据
            $data = [
                'list' => $list,
                'total' => $total,
                'param' => dr_htmlspecialchars($param),
                'mypages' => \Phpcmf\Service::L('input')->table_page($url, $total, $config, $size),
                'my_file' => $this->_tpl_filename('table'),
                'uriprefix' => $uriprefix, // uri前缀部分
                'list_field' => $list_field, // 列表显示的可用字段
                'list_query' => urlencode(dr_authcode($sql, 'ENCODE')), // 查询列表的sql语句
                'list_table' => $list_table, // 查询列表的数据表名称
                'extend_param' => $p, // 附加参数
            ];
        }

        if (!$this->mytable) {
            $this->mytable = [
                'foot_tpl' => $this->_is_admin_auth('del') ? '<label class="table_select_all"><input onclick="dr_table_select_all(this)" type="checkbox"><span></span></label>
        <label><button type="button" onclick="dr_table_option(\''.(IS_ADMIN ? dr_url($uriprefix.'/del') : dr_member_url($uriprefix.'/del')).'\', \''.dr_lang('你确定要删除它们吗？').'\')" class="btn red btn-sm"> <i class="fa fa-trash"></i> '.dr_lang('删除').'</button></label>' : '',
                'link_tpl' => '',
                'link_var' => 'html = html.replace(/\{id\}/g, row.id);',
            ];
            if ($this->_is_admin_auth('del') && $this->is_recycle && method_exists($this, 'recycle_del')) {
                // 回收站按钮
                $this->mytable['foot_tpl'].= '<label><button type="button" onclick="javascript:dr_iframe_show(\''.dr_lang('回收站').'\', \''.(IS_ADMIN ? dr_url($uriprefix.'/recycle_del') : dr_member_url($uriprefix.'/recycle_del')).'\');" class="btn green btn-sm"> <i class="fa fa-recycle"></i> '.dr_lang('回收站').'</button></label>';
            }
            if (!$this->my_clink && $this->_is_admin_auth('edit')) {
                $lurl = (IS_ADMIN ? dr_url($uriprefix.'/edit') : dr_member_url($uriprefix.'/edit')).'&id={id}';
                if ($this->is_iframe_post) {
                    // 弹窗模式修改
                    $lurl = 'javascript:dr_iframe(\'edit\', \''.$lurl.'\', \''.$this->iframe_post_area[0].'\', \''.$this->iframe_post_area[1].'\', \'noclose\');';
                }
                $this->mytable['link_tpl'].= '<label><a href="'.$lurl.'" class="btn btn-xs red"> <i class="fa fa-edit"></i> '.dr_lang('修改').'</a></label>';
            }
        }

        if ($this->my_clink) {
            // 防止右边链接菜单不显示
            $this->mytable['link_tpl'] = '&nbsp;';
        }

        $data['mytable'] = $this->mytable;
        $data['mytable_name'] = $this->name ? $this->name : 'mytable';
        $data['mytable_pagesize'] = $size;
        $data['is_search'] = $this->is_search;
        $data['is_show_export'] = true;
        $data['is_fixed_columns'] =  $this->is_fixed_columns;
        $data['is_show_search_bar'] = $this->is_show_search_bar;

        \Phpcmf\Service::V()->assign($data);

        return [$this->_tpl_filename('list'), $data];
    }

    /**
     * Clink内容右侧部分
     * */
    protected function _Clink_tpl($uriprefix, $data) {
        return '';
    }

    /**
     * 回调结果集
     * */
    protected function _Call_List($data) {
        return $data;
    }

    /**
     * 配置属性
     * */
    public function _Config($table) {

        $data = \Phpcmf\Service::L('cache')->get_file('table-config-'.$table, 'table');
        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Service::L('cache')->set_file('table-config-'.$table, $post, 'table');
            $this->_json(1, dr_lang('操作成功'));
        }

        if ($data['list_field']) {
            $arr = [];
            $field = \Phpcmf\Service::V()->get_value('field');
            if (!$field || !isset($field['id'])) {
                $field['id'] = [
                    'name' => 'Id',
                    'fieldname' => 'id',
                    'fieldtype' => 'Text',
                ];
            }
            foreach ($data['list_field'] as $f => $v) {
                $arr[] = $f;
            }
            foreach ($field as $f) {
                if (!dr_in_array($f['fieldname'], $arr)) {
                    $arr[] = $f['fieldname'];
                }
            }
            $new = [];
            foreach ($arr as $f) {
                if ($f && !is_array($f) && isset($field[$f]) && $field[$f]) {
                    $new[$f] = $field[$f];
                }
            }
            \Phpcmf\Service::V()->assign('field', $new);
        }

        $field = \Phpcmf\Service::V()->get_value('field');
        if (!$field || !isset($field['id'])) {
            $field['id'] = [
                'name' => 'Id',
                'fieldname' => 'id',
                'fieldtype' => 'Text',
            ];
            \Phpcmf\Service::V()->assign('field', $field);
        }

        \Phpcmf\Service::V()->assign('data', $data);

        return $data;
    }

    /**
     * 回收站初始化表
     * */
    protected function _Recycle_Init() {
        $table = $this->init['table'];
        $rtable = $table.'_recycle';
        if (strpos($rtable, '_recycle_recycle')) {
            $rtable = str_replace($rtable, '_recycle_recycle', '_recycle');
        }
        if (!$this->_db()->is_table_exists($rtable)) {
            // 回收表不存在时创建新表
            $this->_db()->query('
CREATE TABLE IF NOT EXISTS `'.$this->_db()->dbprefix($rtable).'` (
  `id` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `content2` mediumtext DEFAULT NULL,
  `inputtime` int(10) unsigned NOT NULL,
  UNIQUE KEY (`id`),
  KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT=\'回收站表\';            
            ');
        }
        $this->init['table'] = $rtable;
        $this->init['field'] = [
            'content' => [
                'name' => dr_lang('全文'),
                'fieldname' => 'content',
            ]
        ];
        $this->is_recycle_init = 1;
        return [$rtable, $table];
    }

    /**
     * 清空回收站前置执行
     * */
    protected function _Recycle_Clear() {
        list($rtable) = $this->_Recycle_Init();
        $rows = $this->_db()->table($rtable)->getAll();
        if (!$rows) {
            $this->_json(0, dr_lang('回收站已被清空'));
        }
        foreach ($rows as $t) {
            $_POST['ids'][] = $t['id'];
        }
    }

    /**
     * 回收站恢复数据
     * */
    protected function _Recycle_Restore($ids) {

        if (!$ids) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        list($rtable, $table) = $this->_Recycle_Init();

        $rows = $this->_db()->table($rtable)->where_in('id', $ids)->getAll();
        if (!$rows) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        foreach ($rows as $row) {
            $content = dr_string2array($row['content']);
            $content2 = dr_string2array($row['content2']);
            $field = $this->_db()->db->getFieldNames($this->_db()->dbprefix($table));
            foreach ($content as $i => $t) {
                if (!dr_in_array($i, $field)) {
                    unset($content[$i]);
                }
            }
            if (!$content) {
                $this->_json(0, dr_lang('数据异常无法恢复'));
            }
            $this->_db()->table($table)->replace($content);
            if ($this->is_data) {
                $table2 = $table.'_data_'.intval($row['tableid']);
                $field = $this->_db()->db->getFieldNames($this->_db()->dbprefix($table2));
                foreach ($content2 as $i => $t) {
                    if (!dr_in_array($i, $field)) {
                        unset($content2[$i]);
                    }
                }
                if ($content2) {
                    $this->_db()->table($table2)->replace($content);
                }
            }
            $this->_db()->table($rtable)->delete($row['id']);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    /**
     * 回收数据到回收站
     * */
    protected function _Recycle_Add($rows) {

        list($rtable, $table) = $this->_Recycle_Init();

        foreach ($rows as $t) {
            $id = intval($t['id']);
            $save = [
                'id' => $id,
                'uid' => $this->uid,
                'inputtime' => SYS_TIME,
                'content' => dr_array2string($t),
                'content2' => '',
            ];
            if ($this->is_data) {
                $t2 = $this->_db()->table($table.'_data_'.intval($t['tableid']))->get($id);
                if ($t2) {
                    $save['content2'] = dr_array2string($t2);
                }
            }
            $rt = $this->_db()->table($rtable)->replace($save);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            // 删除数据
            $rt = $this->_db()->table($table)->delete($id, $this->delete_where);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            if ($this->is_data) {
                // 附表存储
                $rt = $this->_db()->table($table.'_data_'.intval($t['tableid']))->delete($id, $this->delete_where);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
            }
        }
    }

    /**
     * 回收站数据
     * */
    protected function _Recycle_List() {

        $this->_Recycle_Init();

        // 分页数量控制
        if (!$this->list_pagesize) {
            if (IS_ADMIN) {
                $size = (int)SYS_ADMIN_PAGESIZE;
            } else {
                $size = (int)$this->member_cache['config']['pagesize'];
                if (IS_API_HTTP) {
                    $size = (int)$this->member_cache['config']['pagesize_api'];
                } elseif (\Phpcmf\Service::IS_MOBILE()) {
                    $size = (int)$this->member_cache['config']['pagesize_mobile'];
                }
            }
        } else {
            $size = $this->list_pagesize;
        }

        // 按ajax返回
        if (isset($_GET['is_ajax']) && $_GET['is_ajax']) {
            // 按ajax分页
            if (isset($_GET['pagesize']) && $_GET['pagesize']) {
                $size = intval($_GET['pagesize']);
            }
            // 查询数据结果
            list($list, $total, $param) = $this->_db()->init($this->init)->limit_page($size, $this->list_where);
            $sql = $this->_db()->get_sql_query();
            // 格式化字段
            if ($this->init['list_field'] && $list) {
                $field = $this->_field_save(0);
                if ($this->not_field) {
                    $field = dr_array22array($field, $this->not_field);
                }
                $dfield = \Phpcmf\Service::L('Field')->app(APP_DIR);
                foreach ($list as $k => $v) {
                    $list[$k] = $dfield->format_value($field, dr_string2array($v['content']), 1);
                    foreach ($this->init['list_field'] as $i => $t) {
                        if ($t['use']) {
                            $list[$k][$i] = dr_list_function($t['func'], $list[$k][$i], $param, $list[$k], $field[$i], $i);
                        }
                    }
                    $list[$k]['delete_uid'] = dr_list_function('uid', $v['uid']);
                    $list[$k]['delete_time'] = dr_date($v['inputtime']);
                }
            }
            // 存储当前页URL
            unset($param['is_ajax']);
            \Phpcmf\Service::L('Router')->set_back(\Phpcmf\Service::L('Router')->uri(), $param);
            $this->_json(1, $total, $list, '', ['sql' => $sql]);
        }

        $uriprefix = trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/');

        // 默认显示字段
        $list_field = [
            'delete_uid' => [
                'use' => '1',
                'name' => dr_lang('删除人'),
                'width' => '120',
            ],
            'delete_time' => [
                'use' => '1',
                'name' => dr_lang('删除时间'),
                'width' => '170',
            ],
        ];
        if ($this->init['list_field']) {
            $ii = 0;
            foreach ($this->init['list_field'] as $i => $t) {
                if ($ii > 2) {
                    break;
                }
                $list_field[$i] = $t;
                $ii++;
            }
        }

        $param = \Phpcmf\Service::L('input')->get();
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);

        // 默认以显示字段为搜索字段
        $param['field'] = 'content';
        if ($param['keyword']) {
            $param['keyword'] = htmlspecialchars($param['keyword']);
        }

        // 返回数据
        $data = [
            'param' => dr_htmlspecialchars($param),
            'my_file' => $this->_tpl_filename('table'),
            'uriprefix' => trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class, '/'), // uri前缀部分
            'list_field' => $list_field, // 列表显示的可用字段
        ];

        $this->mytable = [
            'foot_tpl' => $this->_is_admin_auth('del') ? '<label class="table_select_all"><input onclick="dr_table_select_all(this)" type="checkbox"><span></span></label>
    <label><button type="button" onclick="dr_table_option(\''.(IS_ADMIN ? dr_url($uriprefix.'/recycle_all_del') : dr_member_url($uriprefix.'/recycle_all_del')).'\', \''.dr_lang('你确定要彻底销毁它们吗？').'\')" class="btn red btn-sm"> <i class="fa fa-trash"></i> '.dr_lang('销毁').'</button></label>' : '',
            'link_tpl' => '',
            'link_var' => 'html = html.replace(/\{id\}/g, row.id);',
        ];
        $this->mytable['foot_tpl'].= '<label><button type="button" onclick="dr_table_option(\''.(IS_ADMIN ? dr_url($uriprefix.'/recycle_restore_del') : dr_member_url($uriprefix.'/recycle_restore_del')).'\', \''.dr_lang('你确定要还原它们吗？').'\')" class="btn green btn-sm"> <i class="fa fa-rotate-left"></i> '.dr_lang('还原').'</button></label>';
        $this->mytable['foot_tpl'].= '<label><button type="button" onclick="dr_ajax_confirm_url(\''.(IS_ADMIN ? dr_url($uriprefix.'/recycle_clear_del') : dr_member_url($uriprefix.'/recycle_clear_del')).'\', \''.dr_lang('你确定要清空回收站吗？').'\', \''.dr_now_url().'\')" class="btn red btn-sm"> <i class="fa fa-close"></i> '.dr_lang('清空回收站').'</button></label>';

        $lurl = (IS_ADMIN ? dr_url($uriprefix.'/recycle_show_del') : dr_member_url($uriprefix.'/recycle_show_del')).'&id={id}';
        $lurl = 'javascript:dr_iframe_show(\''.dr_lang('查看').'\', \''.$lurl.'\');';
        $this->mytable['link_tpl'].= '<label><a href="'.$lurl.'" class="btn btn-xs red"> <i class="fa fa-edit"></i> '.dr_lang('查看').'</a></label>';


        $data['mytable'] = $this->mytable;
        $data['mytable_name'] = 'mytable';
        $data['mytable_pagesize'] = $size;
        $data['is_search'] = true;
        $data['is_show_export'] = false;
        $data['is_fixed_columns'] =  false;
        $data['is_show_search_bar'] = true;
        $data['menu'] = '';
        $data['field'] = $this->init['field'];

        \Phpcmf\Service::V()->assign($data);

        \Phpcmf\Service::V()->display('table_list.html');
    }

    // 分页配置文件加载
    protected function _Page_Config() {

        if (IS_ADMIN) {
            // 后台的分页配置
            $config = require CMSPATH.'Config/Apage.php';
        } else {
            // 用户中心的分页配置
            $file = 'page/'.(\Phpcmf\Service::IS_PC() ? 'pc' : 'mobile').'/member.php';
            if (is_file(WEBPATH.'config/'.$file)) {
                $config = require WEBPATH.'config/'.$file;
            } elseif (is_file(CONFIGPATH.$file)) {
                $config = require CONFIGPATH.$file;
            } else {
                //exit('无法找到分页配置文件【'.$file.'】');
                $config = require CMSPATH.'Config/Apage.php';
            }
        }

        return $config;
    }

    // 获取模板文件名 name模板文件；fname为优先的模板
    public function _tpl_filename($name, $fname = '') {

        $my_file = '';
        if (IS_ADMIN) {
            // 存在优先模板
            if ($fname) {
                $my_file = is_file($this->admin_tpl_path.$this->tpl_name.'_'.$fname.'.html') ? $this->tpl_name.'_'.$fname.'.html' : $this->tpl_prefix.$fname.'.html';
            }
            // 优先模板不存在的情况下
            if (!$my_file || !is_file($this->admin_tpl_path.$my_file)) {
                $my_file = is_file($this->admin_tpl_path.$this->tpl_name.'_'.$name.'.html') ? $this->tpl_name.'_'.$name.'.html' : $this->tpl_prefix.$name.'.html';
            }
            \Phpcmf\Service::V()->admin($this->admin_tpl_path, $this->fix_admin_tpl_path);
			return $my_file;
        } else {
            $path = dr_tpl_path();
            // 存在优先模板
            if ($fname) {
                $my_file = is_file($path.$this->tpl_name.'_'.$fname.'.html') ? $this->tpl_name.'_'.$fname.'.html' : $this->tpl_prefix.$fname.'.html';
            }
            // 优先模板不存在的情况下
            if (!$my_file || !is_file($path.$my_file)) {
                $my_file = is_file($path.$this->tpl_name.'_'.$name.'.html') ? $this->tpl_name.'_'.$name.'.html' : $this->tpl_prefix.$name.'.html';
            }
        }

        return $my_file;
    }

}
