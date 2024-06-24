<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 字段操作表
class Field extends \Phpcmf\Model {

    public $data;
    public $func;
    public $relatedid;
    public $relatedname;

    public $_table_field = [];

    // 通过字段来查询表名称
    public function get_table_name($siteid, $field) {

        $table = '';
        list($case_name, $a) = explode('-', $field['relatedname']);

        if (function_exists('myfield_tablename_'.$case_name)) {
            $table = call_user_func_array('myfield_tablename_'.$case_name, [
                $field,
                $siteid,
                $this->relatedname,
                $this->relatedid
            ]);
        } else {
            switch ($case_name) {

                case 'form':
                    // _网站表单 form-站点id, 表单id
                    list($a, $siteid) = explode('-', $this->relatedname);
                    $data = $this->table($siteid.'_form')->get($this->relatedid);
                    if (!$data) {
                        return;
                    }
                    $table = $field['ismain'] ? $siteid.'_form_'.$data['table'] : $siteid.'_form_'.$data['table'].'_data_{tableid}';
                    break;

                case 'tag':
                    // _网站tag
                    $table = $field['relatedid'].'_tag';
                    break;

                case 'linkage':
                    // 联动菜单
                    $table = 'linkage_data_'.$field['relatedid'];
                    break;

                case 'member':
                    // _用户主表
                    $table = 'member_data';
                    break;

                case 'navigator':
                    // _导航链接
                    $table = $field['relatedid'].'_navigator';
                    break;

                case 'order':
                    // 订单插件
                    $table = $field['relatedid'].'_order';
                    break;

                case 'page':
                    // 网站单页
                    $table = $field['relatedid'].'_order';
                    break;

                case 'table':
                    // 任意表
                    return $a;
                    break;

                case 'module':
                    // 模块字段
                    $data = \Phpcmf\Service::M()->table('module')->get($field['relatedid']);
                    if ($data) {
                        $table = $field['ismain'] ? '{siteid}_'.$data['dirname'] : '{siteid}_'.$data['dirname'].'_data_{tableid}';
                    }
                    break;

                case 'mform':
                    // 模块表单
                    $data = \Phpcmf\Service::M()->table('module_form')->get($field['relatedid']);
                    if (!$data) {
                        $table = $field['ismain'] ? '{siteid}_'.$a.'_form_'.$data['table'] : '{siteid}_'.$a.'_form_'.$data['table'].'_data_{tableid}';
                    }
                    break;

                case 'category':
                    // 栏目自定义字段
                    $table = $siteid.'_'.$a.'_category';
                    break;

                default:
                    if (strpos($field['relatedname'], 'comment-module') !== false) {
                        // 模块评论字段
                        list($a, $b, $module) = explode('-', $field['relatedname']);
                        $cache = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $module);
                        if (!$cache) {
                            $table = $siteid . '_' . $cache['dirname'] . '_comment';
                        }
                    } elseif (strpos($field['relatedname'], 'comment-mform') !== false) {
                        // 模块表单评论字段
                        list($a, $b, $module, $fid) = explode('-', $field['relatedname']);
                        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);
                        if (!$cache) {
                            $table = $siteid.'_'.$cache['dirname'].'_form_'.$fid.'_comment';
                        }
                    } elseif (strpos($field['relatedname'], 'comment-form') !== false) {
                        // 网站评论字段
                        list($a, $b, $fid) = explode('-', $field['relatedname']);
                        $cache = \Phpcmf\Service::L('cache')->get('form-'.$field['relatedid'], $fid);
                        if (!$cache) {
                            $table = $siteid.'_form_'.$cache['table'].'_comment';
                        }
                    } else {
                        // 识别栏目模型字段
                        list($module, $s) = explode('-', $field['relatedname']);
                        $cache = \Phpcmf\Service::L('cache')->get('module-'.$s.'-'.$module);
                        if ($cache) {
                            $data = dr_cat_value($module, $field['relatedid']);
                            if ($data) {
                                if ($module == 'share') {
                                    if ($data['tid'] != 1) {
                                    } else {
                                        $table = dr_module_table_prefix($data['mid']).'_category_data';
                                    }
                                } else {
                                    $table = dr_module_table_prefix($module).'_category_data';
                                }
                            }
                        }

                    }
                    break;
            }
        }

        return str_replace('{siteid}', $siteid, $table);
    }

    // 全部字段
    public function get_all_field() {
        
        if (!$this->relatedname) {
            return null;
        }

        $data = $this->db->table('field')
                    ->where('relatedid', $this->relatedid)
                    ->where('relatedname', $this->relatedname)
                    ->orderBy('disabled ASC,displayorder ASC,id ASC')
                    ->get()
                    ->getResultArray();
        if (!$data) {
            return null;
        }

        $rt = [];
        foreach ($data as $i => $t) {
            $t['spacer'] = '';
            $t['setting'] = dr_string2array($t['setting']);
            $rt[$t['id']] = $t;
        }

        list($case_name, $a) = explode('-', $this->relatedname);
        if ($case_name == 'module') {
            // 模块字段时，加载栏目模型字段
            $module = \Phpcmf\Service::M()->table('module')->get($this->relatedid);
            if ($module) {
                $like = ['catmodule-'.$module['dirname']];
                if ($module['share']) {
                    $like[] = 'catmodule-share';
                }
                $field = \Phpcmf\Service::M()->db->table('field')
                    ->where('ismain', 1)
                    ->where('disabled', 0)
                    ->whereIn('relatedname', $like)
                    ->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
                if ($field) {
                    foreach ($field as $t) {
                        $t['spacer'] = '';
                        $t['setting'] = dr_string2array($t['setting']);
                        $rt[$t['id']] = $t;
                    }
                }
            }
        }

        return $rt;
    }

    // 获取任意表的自定义字段
    public function get_mytable_field($table, $siteid = 0) {

        $name = 'my-table-'.$table;
        $value = \Phpcmf\Service::L('cache')->get_data($name);
        if (IS_ADMIN || !$value) {
            $field = $this->db->table('field')
                        ->where('disabled', 0)
                        ->where('relatedid', $siteid)
                        ->where('relatedname', 'table-'.$table)
                        ->orderBy('displayorder ASC,id ASC')
                        ->get()
                        ->getResultArray();
            if ($field) {
                foreach ($field as $t) {
                    $t['setting'] = dr_string2array($t['setting']);
                    $value[$t['fieldname']] = $t;
                }
            }
            \Phpcmf\Service::L('cache')->set_data($name, $value);
        }

        return $value;
    }

    // 获取网站信息的自定义字段
    public function get_mysite_field($siteid = SITE_ID) {

        $name = 'my-site-'.$siteid;
        $value = \Phpcmf\Service::L('cache')->get_data($name);
        if (IS_ADMIN || !$value) {
            $field = $this->db->table('field')
                        ->where('disabled', 0)
                        ->where('relatedid', $siteid)
                        ->where('relatedname', 'site')
                        ->orderBy('displayorder ASC,id ASC')
                        ->get()
                        ->getResultArray();
            if ($field) {
                foreach ($field as $t) {
                    $t['setting'] = dr_string2array($t['setting']);
                    $value[$t['fieldname']] = $t;
                }
            }
            \Phpcmf\Service::L('cache')->set_data($name, $value);
        }

        return $value;
    }

    // 删除字段
    public function delete_field($ids) {

        foreach ($ids as $id) {
            $id = intval($id);
            $data = $this->table('field')->get($id);
            if (!$data) {
                return dr_return_data(0, dr_lang('字段不存在(id:%s)', $id));
            } elseif ($data['issystem']) {
                return dr_return_data(0, dr_lang('系统字段不允许删除(id:%s)', $id));
            }
            $rt = $this->table('field')->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            // 删除表中数据
            $field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
            if ($field) {
                // 非系统字段才支持删除
                $sql = $field->drop_sql($data['fieldname']);
                // 需要分别更新各站点
                $sql && $this->update_table($sql, $data['ismain']);
            }
        }

        return dr_return_data(1, '');
    }

    /**
     * 添加字段
     *
     * @param	array	$data
     * @param	object	$field
     * @return	void
     */
    public function add($data, $field) {

        // 验证字段上限
        /*
        if ($this->_check_table_name) {
            $max = 500;
            foreach ($this->_check_table_name as $t) {
                if (dr_count($this->db->getFieldNames($t)) > $max) {
                    return dr_return_data(0, dr_lang('数据表[%s]字段个数已经达到上限(%s)', dr_count($this->db->getFieldNames($t)), $max));
                }
            }
        }*/

        // 先读取sql语句
        $sql = $field->create_sql($data['fieldname'], $data['setting']['option'], dr_safe_filename($data['name']));

        // 当为编辑器类型时，关闭xss过滤
        //$data['fieldtype'] == 'Ueditor' && $data['setting']['validate']['xss'] = 1;

        $data['ismain'] = (int)$data['ismain'];
        $data['setting'] = dr_array2string($data['setting']);
        $data['issystem'] = 0;
        $data['issearch'] = (int)$data['issearch'];
        $data['ismember'] = (int)$data['ismember'];
        $data['disabled'] = (int)$data['disabled'];
        $data['relatedid'] = (int)$this->relatedid;
        $data['relatedname'] = (string)$this->relatedname;
        $data['displayorder'] = (int)$data['displayorder'];

        // 入库字段表
        $rt = $this->table('field')->insert($data);

        // 执行数据库语句
        if ($rt['code'] && $sql) {
            $this->_table_field = [];
            $this->update_table($sql, $data['ismain']);
            // 验证字段是否上传成功
            $this->db->resetDataCache();// 清除缓存，影响字段存在的重复
            if ($this->_table_field && $yz = $field->test_sql($this->_table_field, $data['fieldname'])) {
                // 删除本字段
                $this->table('field')->delete($rt['code']);
                return dr_return_data(0, dr_lang('字段创建失败: %s', $yz));
            }
        }
        
        return $rt;
    }

    /**
     * 修改字段
     *
     * @param	array	$_data	旧数据
     * @param	array	$data	新数据
     * @param	string	$sql	执行该操作的sql语句
     * @return	string
     */
    public function edit($_data, $data, $sql) {

        if (!$_data || !$data) {
            return dr_return_data(0, dr_lang('参数不完整'));
        }

        // 如果字段类型、长度变化时，分别更新各站点
        ($data['setting']['option']['fieldtype'] != $_data['setting']['option']['fieldtype']
            || $data['setting']['option']['fieldlength'] != $_data['setting']['option']['fieldlength'])
        && $this->update_table($sql, $_data['ismain']);

        // 判断关联字段权限
        if (in_array($data['fieldtype'], ['Merge', 'Group'])) {
            $setting = dr_string2array($data['setting']);
            if ($setting['show_admin']) {
                if (preg_match_all('/\{(.+)\}/U', $setting['option']['value'], $value)) {
                    foreach ($value[1] as $v) {
                        $gl = $this->table('field')
                            ->where('fieldname', $v)
                            ->where('relatedid', $this->relatedid)
                            ->where('relatedname', $this->relatedname)
                            ->getRow();
                        if (!$gl) {
                            return dr_return_data(0, dr_lang('关联字段【%s】未定义', $v));
                        }
                        $gl_setting = dr_string2array($gl['setting']);
                        if (!$gl_setting['show_admin']) {
                            $gl_setting['show_admin'] = $setting['show_admin'];
                            $this->table('field')->update($gl['id'], [
                                'setting' => dr_array2string($gl_setting),
                            ]);
                        }
                    }
                }
            }
        }

        // 自定义属性不变
        if (isset($_data['setting']['diy'])) {
            $data['setting']['diy'] = $_data['setting']['diy'];
        }

        $data['setting'] = dr_array2string($data['setting']);
        $data['issearch'] = (int)$data['issearch'];
        $data['ismember'] = (int)$data['ismember'];
        $data['disabled'] = (int)$data['disabled'];

        // 更新字段表
        return $this->table('field')->update($_data['id'], $data);
    }

    /**
     * 判断表字段否存在
     */
    public function _field_exitsts($id, $name, $table, $siteid = 0) {

        if (!$table)	{
            return 0;
        }

        return $this->db->fieldExists($name, $table);
    }

    //--------------------------------------------------------------------
    
    /**
     * 分别更新各站点的表结构
     *
     * @param	string	$sql		执行该操作的sql语句
     * @param	intval	$ismain		是否主表
     * @return	void
     */
    public function update_table($sql, $ismain) {

        if (!$sql || !$this->func) {
            return null;
        }

        if (method_exists($this, '_sql_'.$this->func)) {
            return call_user_func_array(array($this, '_sql_'.$this->func), array($sql, $ismain));
        } elseif (function_exists('myfield_sql_'.$this->func)) {
            $rt = call_user_func_array('myfield_sql_'.$this->func, array($sql, $ismain, $this->data));
            if ($rt) {
                $this->_table_field = array_merge($this->_table_field, $rt);
            }
        }
    }
    
    /**
     * 判断同表字段否存在
     *
     * @param	string	$name	字段名称
     * @param	intval	$int	字段id
     * @return	int
     */
    public function exitsts($name) {

        if (!$name)	{
            return 1;
        }

        if (method_exists($this, '_field_'.$this->func)) {
            return call_user_func_array(array($this, '_field_'.$this->func), array($name));
        } elseif (function_exists('myfield_field_'.$this->func)) {
            return call_user_func_array('myfield_field_'.$this->func, array($name, $this->data));
        }

        return 1;
    }

    //--------------------------------------------------------------------

    // 栏目模型字段
    protected function _sql_category_data($sql, $ismain) {
        $table = $this->dbprefix(dr_module_table_prefix($this->data['dirname']).'_category_data'); // 主表名称
        if (!$this->db->tableExists($table)) {
            return;
        }
        // 更新主表 格式: 站点id_名称
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_category_data($name) {
        // 模块主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data['dirname']));
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        // 模块附表
        $rt = $this->_field_exitsts('id', $name, $table.'_data_0', SITE_ID);
        if ($rt) {
            return 1;
        }
        // 栏目表
        $rt = $this->_field_exitsts('id', $name, $table.'_category', SITE_ID);
        if ($rt) {
            return 1;
        }
        // 栏目模型主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data['dirname']).'_category_data');
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------


    //--------------------------------------------------------------------

    // 评论自定义字段
    protected function _sql_comment($sql, $ismain) {
        // 更新站点模块
        foreach (\Phpcmf\Service::C()->site_info as $sid => $v) {
            $table = $this->dbprefix($sid.'_'.$this->data.'_comment');
            if (!$this->db->tableExists($table)) {
                return;
            }
            $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
            $this->_table_field[] = $table;
        }
    }
    // 字段是否存在
    protected function _field_comment($name) {
        // 主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data).'_comment');
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------

    // 栏目字段
    protected function _sql_category($sql, $ismain) {
        // 更新站点模块
        foreach (\Phpcmf\Service::C()->site_info as $sid => $v) {
            $table = $this->dbprefix($sid.'_'.$this->data.'_category');
            if (!$this->db->tableExists($table)) {
                return;
            }
            $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
            $this->_table_field[] = $table;
        }
    }
    // 字段是否存在
    protected function _field_category($name) {
        // 主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data).'_category');
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------
    //--------------------------------------------------------------------

    // 会员字段
    protected function _sql_member($sql, $ismain) {
        $this->db->simpleQuery(str_replace('{tablename}', $this->dbprefix('member_data'), $sql));
        $this->_table_field[] = $this->dbprefix('member_data');
    }
    // 字段是否存在
    protected function _field_member($name) {
        // 保留
        if (in_array($name, ['role', 'uid', 'authid', 'adminid', 'tableid', 'group', 'groupid', 'levelid'])) {
            return 1;
        }
        // 主表
        $table = $this->dbprefix('member_data');
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------

    // 网站表单字段
    protected function _sql_form($sql, $ismain) {
        $table = $this->dbprefix(SITE_ID.'_form_'.$this->data['table']); // 主表名称
        if (!$this->db->tableExists($table)) {
            return;
        }
        if ($ismain) {
            // 更新主表 格式: 站点id_名称
            $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
            $this->_table_field[] = $table;
        } else {
            for ($i = 0; $i < 200; $i ++) {
                if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                    break;
                }
                $this->db->simpleQuery(str_replace('{tablename}', $table.'_data_'.$i, $sql)); //执行更新语句
                $this->_table_field[] = $table.'_data_'.$i;
            }
        }
    }
    // 字段是否存在
    protected function _field_form($name) {
        // 主表
        $table = $this->dbprefix(SITE_ID.'_form_'.$this->data['table']);
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        // 附表
        $table.'_data_0';
        $rt = $this->_field_exitsts('id', $name, $table.'_data_0', SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------

    // 联动字段
    protected function _sql_linkage($sql, $ismain) {
        $table = $this->dbprefix('linkage_data_'.$this->relatedid);
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_linkage($name) {
        // 主表
        $table = $this->dbprefix('linkage_data_'.$this->relatedid);
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------

    // Tag字段
    protected function _sql_tag($sql, $ismain) {
        $table = $this->dbprefix($this->relatedid.'_tag');
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_tag($name) {
        // 主表
        $table = $this->dbprefix($this->relatedid.'_tag');
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------//--------------------------------------------------------------------

    // navigator字段
    protected function _sql_navigator($sql, $ismain) {
        $table = $this->dbprefix($this->relatedid.'_navigator');
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_navigator($name) {
        // 主表
        $table = $this->dbprefix($this->relatedid.'_navigator');
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------

    // 订单插件字段
    protected function _sql_order($sql, $ismain) {
        $table = $this->dbprefix($this->relatedid.'_order');
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_order($name) {
        // 主表
        $table = $this->dbprefix($this->relatedid.'_order');
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }


    //--------------------------------------------------------------------

    // 单页字段
    protected function _sql_page($sql, $ismain) {
        $table = $this->dbprefix($this->relatedid.'_page');
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_page($name) {
        // 主表
        $table = $this->dbprefix($this->relatedid.'_page');
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------



    //--------------------------------------------------------------------

    // 模块字段
    protected function _sql_module($sql, $ismain) {
        // 更新站点模块
        foreach (\Phpcmf\Service::C()->site_info as $sid => $v) {
            $table = $this->dbprefix($sid.'_'.$this->data['dirname']); // 主表名称
            if (!$this->db->tableExists($table)) {
                continue;
            }
            if ($ismain) {
                // 更新主表 格式: 站点id_名称
                $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
                $this->_table_field[] = $table;
            } else {
                // 更新副表 格式: 名称_站点id_data_副表id
                for ($i = 0; $i < 200; $i ++) {
                    if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                        break;
                    }
                    $this->db->simpleQuery(str_replace('{tablename}', $table.'_data_'.$i, $sql)); //执行更新语句
                    $this->_table_field[] = $table.'_data_'.$i;
                }
            }
        }
    }
    // 字段是否存在
    protected function _field_module($name) {
        // 保留字段
        if (in_array($name, [
            'cat', 'category', 'name',
            'top', 'content_page',
            'pageid', 'params', 'page', 'pages',
            'parent', 'urlrule', 'member',
            'tags', 'tag', 'prev_page',
            'next_page', 'fstatus', 'old', 'mid', 'groupid', 'related', 'kws', 'more'
        ])) {
            return 1;
        }
        // 主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data['dirname']));
        $rt = $this->_field_exitsts('id', $name, $table, SITE_ID);
        if ($rt) {
            return 1;
        }
        // 附表
        $rt = $this->_field_exitsts('id', $name, $table.'_data_0', SITE_ID);
        if ($rt) {
            return 1;
        }
        // 栏目表
        $rt = $this->_field_exitsts('id', $name, $table.'_category', SITE_ID);
        if ($rt) {
            return 1;
        }
        // 栏目模型表
        $rt = $this->_field_exitsts('id', $name, $table.'_category_data', SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------


    //--------------------------------------------------------------------

    // 模块表单字段
    protected function _sql_mform($sql, $ismain) {

        // 更新站点模块
        foreach (\Phpcmf\Service::C()->site_info as $sid => $v) {
            $table = $this->dbprefix($sid.'_'.$this->data['module'].'_form_'.$this->data['table']); // 主表名称
            if (!$this->db->tableExists($table)) {
                continue;
            }
            if ($ismain) {
                // 更新主表 格式: 站点id_名称
                $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
                $this->_table_field[] = $table;
            } else {
                // 更新副表 格式: 名称_站点id_data_副表id
                for ($i = 0; $i < 200; $i ++) {
                    if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                        break;
                    }
                    $this->db->simpleQuery(str_replace('{tablename}', $table.'_data_'.$i, $sql)); //执行更新语句
                    $this->_table_field[] = $table.'_data_'.$i;
                }
            }
        }

    }
    // 字段是否存在
    protected function _field_mform($name) {
        // 主表
        $table = $this->dbprefix(dr_module_table_prefix($this->data['module']).'_form_'.$this->data['table']);
        $rt = $this->_field_exitsts('id', $name, $table, $this->data['module'] == 'space' ? 0 : SITE_ID);
        if ($rt) {
            return 1;
        }
        // 附表
        $rt = $this->_field_exitsts('id', $name, $table.'_data_0', $this->data['module'] == 'space' ? 0 : SITE_ID);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------


    //--------------------------------------------------------------------

    // 任意表
    protected function _sql_table($sql, $ismain) {
        $table = $this->dbprefix($this->data);
        if (!$this->db->tableExists($table)) {
            return;
        }
        $this->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        $this->_table_field[] = $table;
    }
    // 字段是否存在
    protected function _field_table($name) {
        // 主表
        $table = $this->dbprefix($this->data);
        if (!$this->db->tableExists($table)) {
            return;
        }
        $rt = $this->_field_exitsts('id', $name, $table, $this->relatedid);
        if ($rt) {
            return 1;
        }
        return 0;
    }

    //--------------------------------------------------------------------


    //--------------------------------------------------------------------

    // 网站信息表
    protected function _sql_site($sql, $ismain) {
        return '';
    }
    // 网站信息表
    protected function _field_site($name) {
        // 保留字段
        if (in_array($name, ['logo'])) {
            return 1;
        }
        if ($this->table('field')
            ->where('fieldname', $name)
            ->where('relatedid', $this->relatedid)
            ->where('relatedname', $this->relatedname)->counts()) {
            return 1;
        }

        return 0;
    }

    //--------------------------------------------------------------------

}