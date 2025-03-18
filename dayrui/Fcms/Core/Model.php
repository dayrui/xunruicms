<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

if (is_file(MYPATH.'Extend/Model.php')) {
    require MYPATH.'Extend/Model.php';
} else {
    require FRAMEPATH.'Extend/Model.php';
}

// 模型类
class Model {

    public $db;
    public $prefix;

    public $id = 'id';
    public $key = 'id';

    public $site;

    public $field;
    public $siteid;
    public $table;
    public $mytable;
    public $stable; // join关联表
    public $sfield; // join关联表的字段
    public $db_temp; // 备份默认数据库

    public $uid;
    public $admin;
    public $member;

    protected $date_field;
    protected $param;
    protected $init;

    public function __construct() {

        list($this->db, $this->prefix) = \Frame\Model::_load_db();

        $this->uid = \Phpcmf\Service::C()->uid;
        $this->site = \Phpcmf\Service::C()->site;
        $this->admin = \Phpcmf\Service::C()->admin;
        $this->member = \Phpcmf\Service::C()->member;
        $this->siteid = defined('SITE_ID') ? SITE_ID : 0;
    }

    // 设置初始化查询条件
    public function init($data) {

        isset($data['id']) && $this->id = $this->key = $data['id'];
        isset($data['table']) && $this->table = $data['table'];
        isset($data['stable']) && $this->stable = $data['stable'];
        isset($data['sfield']) && $this->sfield = $data['sfield'];
        isset($data['field']) && $this->field = $data['field'];
        isset($data['date_field']) && $this->date_field = $data['date_field'];

        isset($data['order_by']) && $this->param['order_list'] = $data['order_by'];
        isset($data['group_by']) && $this->param['group_list'] = $data['group_by'];
        isset($data['order_list']) && $this->param['order_list'] = $data['order_list'];
        isset($data['where_list']) && $this->param['where_list'] = $data['where_list'];
        isset($data['is_diy_where_list']) && $this->param['is_diy_where_list'] = $data['is_diy_where_list'];
        isset($data['join_list']) && $this->param['join_list'] = $data['join_list'];
        isset($data['select_list']) && $this->param['select_list'] = $data['select_list'];

        $this->init = $data; // 方便调用

        return $this;
    }

    // 设置列表搜索条件
    public function set_where_list($where) {
        $this->param['where_list'] = $where;
    }

    // 追加列表搜索条件
    public function add_where_list($where) {
        $this->param['where'][] = $where;
    }

    // 设置操作主键
    public function id($id = '') {
        if ($id) {
            $this->key = $id;
        } else {
            $this->key = $this->id;
        }
        return $this;
    }

    // 设置数据源
    public function db_source($name = '') {
        if ($name) {
            $this->db_temp = $this->db;
            list($this->db, $this->prefix) = \Frame\Model::_load_db_source($name);
        }
        return $this;
    }

    // 设置操作表
    public function table($name) {
        $this->table = $name;
        return $this;
    }

    // 设置操作站点的表
    public function table_site($name, $site = 0) {
        if (!$site) {
            $site = $this->siteid ? $this->siteid : SITE_ID;
        }
        $this->table = dr_site_table_prefix($name, $site);
        return $this;
    }

    // 获取表前缀
    public function dbprefix($name = '') {
        return $this->prefix.$name;
    }

    // 执行sql
    public function query($sql) {

        if (!$this->db->simpleQuery($sql)) {
            $error = $this->db->error();
            $this->_clear();
            log_message('error', $sql.': '.$error['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($error['message']);
        }

        $this->_clear();
        return dr_return_data(1);
    }

    // 附表不存在时创建附表
    public function is_data_table($table, $tid) {
        if ($tid > 0 && !$this->is_table_exists($this->dbprefix($table.$tid))) {
            // 附表不存在时创建附表
            list($a, $sql, $name) = \Phpcmf\Service::M('table')->create_table_sql($this->dbprefix($table).'0');
            $this->db->query(str_replace(
                array($name, 'CREATE TABLE '),
                array($this->dbprefix($table.$tid), 'CREATE TABLE IF NOT EXISTS '),
                $a
            ));
        }
        $this->_clear();
    }

    // 表是否存在
    public function is_table_exists($table) {

        if (!$table) {
            $this->_clear();
            return 0;
        }

        $table = strpos($table, $this->prefix) === 0 ? $table : $this->dbprefix($table);
        $rt = $this->db->tableExists($table) ? 1 : 0;

        $this->_clear();

        return $rt;
    }

    // 表字段是否存在
    public function is_field_exists($table, $name) {

        if (!$table || !$name) {
            $this->_clear();
            return 0;
        }

        $table = strpos($table, $this->prefix) === 0 ? $table : $this->dbprefix($table);
        $rt = $this->db->fieldExists($name, $table) ? 1 : 0;
        $this->_clear();

        return $rt;
    }

    // 字段值是否存在
    public function is_exists($id, $name, $value) {

        $builder = $this->db->table($this->table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v, null, false);
            }
        }

        $rt = $builder->where($name, $value)->where($this->key.'<>'. $id)->countAllResults();

        $this->_clear();

        return $rt;
    }


    // 统计数量
    public function counts($table = '', $where = '') {

        $table = !$table ? $this->table : $table;
        if (!$table) {
            return 0;
        }

        $builder = $this->db->table($table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v, null, false);
            }
        }

        // 分组
        $this->param['group'] && $builder->groupBy($this->param['group']);

        $where && $builder->where($where);

        $this->_clear();

        return $builder->countAllResults();
    }

    // 插入数据
    public function insert($data) {

        $this->db->table($this->table)->insert($data);
        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $id = $this->db->insertID();
        !$id && $id = intval($data[$this->key]);

        if (!$id) {
            $this->_clear();
            log_message('debug', $this->table.': 主键获取失败<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': 主键获取失败');
        }

        $this->_clear();

        return dr_return_data($id);
    }

    // 插入数据
    public function replace($data) {

        $this->db->table($this->table)->replace($data);
        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $id = $this->db->insertID();
        !$id && $id = intval($data[$this->key]);

        if (!$id) {
            $this->_clear();
            log_message('debug', $this->table.': 主键获取失败<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': 主键获取失败');
        }

        $this->_clear();

        return dr_return_data($id);
    }

    // 批量插入
    public function insert_batch($data) {

        if (!$this->table || !$data) {
            return;
        }

        $rt = $this->db->table($this->table)->insertBatch($data);

        $this->_clear();

        return $rt;
    }
	
	// 批量更新
	public function update_batch($data, $key = 'id') {

		if (!$this->table || !$data) {
			return;
		}
		
		$this->db->table($this->table)->updateBatch($data, $key ? $key : $this->key);
		
        $this->_clear();
	}

    // 更新数据
    public function update($id, $data, $where = '') {

        if (!$data) {
            $this->_clear();
            return $this->_return_error($this->table.': update() data值为空');
        }

        $db = $this->db->table($this->table);
        $id && $db->where($this->key, (int)$id);

        $where && $db->where($where, null, false);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $db->where($v[0], $v[1]) : $db->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $db->whereIn($v[0], $v[1]) : $db->whereIn($v, null, false);
            }
        }

        $db->update($data);

        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $this->_clear();

        return dr_return_data($id);
    }

    // 删除数据
    /*
    * 主键
    * */
    public function delete($id = 0 , $where = '') {

        $db = $this->db->table($this->table);

        $where && $db->where($where, null, false);
        $id && $db->where($this->key, (int)$id);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $db->where($v[0], $v[1]) : $db->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $db->whereIn($v[0], $v[1]) : $db->whereIn($v, null, false);
            }
        }

        // 执行删除
        $db->delete();

        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $this->_clear();

        return dr_return_data(1);
    }

    // 执行”写入”类型的语句（insert，update等）时返回有多少行受影响
    public function affected_rows() {
        return $this->db->affectedRows();
    }

    // 启动事务
    public function trans_start(){
        $this->db->transBegin();
    }

    // 回滚事务
    public function trans_rollback(){
        $this->db->transRollback();
    }

    // 执行事务提交
    public function trans_comment(){
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback();
            return false;
        } else  {
            $this->db->transCommit();
            return true;
        }
    }

    // 删除全部内容
    public function clear_all() {
        return $this->db->table($this->table)->truncate();
    }

    // 批量删除数据
    /*
    * 主键数组
    * */
    public function delete_all($ids, $where = '') {
        $this->deleteAll($ids, $where);
    }
    public function deleteAll($ids, $where = '') {

        $db = $this->db->table($this->table);
        $where && $db->where($where, null, false);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $db->where($v[0], $v[1]) : $db->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $db->whereIn($v[0], $v[1]) : $db->whereIn($v, null, false);
            }
        }

        $db->whereIn($this->key, (array)$ids)->delete();

        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }


        $this->_clear();

        return dr_return_data(1);
    }

    /*
     * 保存单个数据
     * 主键
     * 字段名
     * 字段值
     * */
    public function save($id, $name, $value, $where = '') {

        $db = $this->db->table($this->table);
        $where && $db->where($where, null, false);
        $db->where($this->key, (int)$id)->update([$name => $value]);

        $rt = $this->db->error();
        if ($rt['code']) {
            $this->_clear();
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $this->_clear();

        return dr_return_data($id);
    }

    /*
     * 获取单个数据
     * 主键
     * */
    public function get($id) {

        $query = $this->db->table($this->table)->where($this->key, (int)$id)->get();
        if (!$query) {
            $this->_clear();
            return [];
        }

        $rt = $query->getRowArray();
        $this->_clear();

        return $rt;
    }

    /*
     * 获取全部数据
     * 指定数量
     * 数组主键id
     * */
    public function get_all($num = 0, $key = '') {
        return $this->getAll($num, $key);
    }
    public function getAll($num = 0, $key = '') {

        $builder = $this->db->table($this->table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v, null, false);
            }
        }

        // select字段
        if ($this->param['select']) {
            $builder->select(implode(',', $this->param['select']));
        }

        // 排序
        $this->param['order'] && $builder->orderBy($this->param['order']);

        // 分组
        $this->param['group'] && $builder->groupBy($this->param['group']);

        // 数量控制
        if ($this->param['limit']) {
            list($a, $b) = explode(',', $this->param['limit']);
            if ($b) {
                $builder->limit($a, $b);
            } else {
                $builder->limit($a);
            }
        } elseif ($num) {
            $builder->limit($num);
        }

        $query = $builder->get();
        if (!$query) {
            $this->_clear();
            return [];
        }

        $rt = $query->getResultArray();
        if ($rt && $key) {
            $rt2 = $rt;
            $rt = [];
            foreach ($rt2 as $i => $t) {
                $rt[(isset($t[$key]) ? $t[$key] : $i)] = $t;
            }
        }

        $this->_clear();

        return $rt;
    }

    /*
     * 获取单个数据
     * */
    public function getRow() {

        $builder = $this->db->table($this->table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v, null, false);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v, null, false);
            }
        }

        // select字段
        if ($this->param['select']) {
            $builder->select(implode(',', $this->param['select']));
        }

        if (!$builder) {
            $this->_clear();
            return [];
        }

        // 排序
        $this->param['order'] && $builder->orderBy($this->param['order']);

        $builder->limit(1);

        $rt = $builder->get();

        $data = [];
        $rt && $data = $rt->getRowArray();

        $this->_clear();

        return $data;
    }

    /*
     * 获取单个字段值
     * */
    public function getField($name) {
        $data = $this->getRow();
        return isset($data[$name]) ? $data[$name] : '';
    }

    /*
     * 操作数据
     * 数据不存在-1, 变更值0, 变更至1
     * */
    public function used($id, $name) {

        $data = $this->db->table($this->table)->select($name)->where('id', (int)$id)->get()->getRowArray();

        if ($data) {
            $value = $data[$name] ? 0 : 1;
            // 更新
            $this->db->table($this->table)->where('id', $id)->update([$name => $value]);
            $this->_clear();
            return $value;
        }

        $this->_clear();
        return -1;
    }

    // 条件组合
    public function _where($table, $name, $value, $field, $is_like = false) {

        if (!$value && dr_strlen($value) == 0) {
            return ''; //空值
        }

        $name = dr_safe_replace($name, ['\\', '/']);
        if ((isset($field['fieldtype']) && $field['fieldtype'] == 'Date') || in_array($name, ['inputtime', 'updatetime'])) {
            // 匹配时间字段
            list($s, $e) = explode(',', $value);
            $s = (int)strtotime((string)$s);
            $e = (int)strtotime((string)$e);
            if ($s == $e && $s == 0) {
                return '';
            }
            if (!$e) {
                return '`'.$table.'`.`'.$name.'` > '.$s;
            } else {
                return '`'.$table.'`.`'.$name.'` BETWEEN '.$s.' AND '.$e;
            }
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'File'
            && $field['fieldname'] == 'thumb' && $value == 1) {
            return '`'.$table.'`.`'.$name.'` <> \'\'';
        } elseif (isset($field['fieldtype']) && strpos($field['fieldtype'], 'map') !== false) {
            // 地图
            list($a, $km) = explode('|', $value);
            list($lng, $lat) = explode(',', $a);
            if ($km && $lat && $lng) {
                // 获取Nkm内的数据
                $squares = dr_square_point($lng, $lat, $km);
                return "(`".$table."`.`".$name."_lat` between {$squares['right-bottom']['lat']} and {$squares['left-top']['lat']}) and (`".$table."`.`".$name."_lng` between {$squares['left-top']['lng']} and {$squares['right-bottom']['lng']})";
            } else {
                return '1=1';
                //\Phpcmf\Service::C()->goto_404_page(dr_lang('没有定位到您的坐标'));
            }
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Diy'
            && function_exists('dr_diy_field_'.substr($field['setting']['option']['file'], 0, -4).'_search')) {
            // DIY字段
            return call_user_func(
                'dr_diy_field_'.substr($field['setting']['option']['file'], 0, -4).'_search',
                $table, $name, $value, $field
            );
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkage') {
            // 联动菜单字段
            $arr = explode('|', $value);
            $where = [];
            if ($is_like && $value) {
                $key = \Phpcmf\Service::L('cache')->get_file('key', 'linkage/'.SITE_ID.'_'.$field['setting']['option']['linkage'].'/');
                $row = $this->db->query('select * from `'.$this->dbprefix('linkage_data_'.$key).'` where `name` LIKE "%'.$value.'%"')->getRowArray();
                if ($row) {
                    $arr[] = $row['cname'];
                }
            }
            foreach ($arr as $val) {
                $data = dr_linkage($field['setting']['option']['linkage'], $val);
                if ($data) {
                    if ($data['child']) {
                        $where[] = '`'.$table.'`.`'.$name.'` IN ('.$data['childids'].')';
                    } else {
                        $where[] = '`'.$table.'`.`'.$name.'`='.intval($data['ii']);
                    }
                }
            }
            return $where ? '('.implode(strpos($value,  '||') !== false ? ' AND ' : ' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkages') {
            // 联动菜单多选字段
            $arr = explode('|', $value);
            $where = [];
            if ($is_like && $value) {
                $key = \Phpcmf\Service::L('cache')->get_file('key', 'linkage/'.SITE_ID.'_'.$field['setting']['option']['linkage'].'/');
                if ($key) {
                    $row = $this->db->query('select * from `'.$this->dbprefix('linkage_data_'.$key).'` where `name` LIKE "%'.$value.'%"')->getRowArray();
                    if ($row) {
                        $arr[] = $row['cname'];
                    }
                }
            }
            foreach ($arr as $val) {
                $data = dr_linkage($field['setting']['option']['linkage'], $val);
                if ($data) {
                    if ($data['child']) {
                        $ids = explode(',', $data['childids']);
                        foreach ($ids as $id) {
                            if ($id) {
                                $where[] = $this->where_json($table, $name, $id);
                            }
                        }
                    } else {
                        $where[] = $this->where_json($table, $name, intval($data['ii']));
                    }
                }
            }
            //if (dr_count($where) > 20) {
                //$where = array_slice($where, 0, 20);
            //}
            return $where ? '('.implode(strpos($value,  '||') !== false ? ' AND ' : ' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && in_array($field['fieldtype'], ['Selects' , 'Checkbox', 'Cats'])) {
            // 复选字段
            $arr = explode('|', $value);
            $where = [];
            if ($is_like && $value) {
                $option = dr_format_option_array($field['setting']['option']['options']);
                if ($option) {
                    $new = [];
                    foreach ($option as $k => $v) {
                        if (strpos($v, (string)$value) !== false) {
                            $new[] = $k;
                        }
                    }
                    if ($new) {
                        $arr = $new;
                    }
                }
            }
            foreach ($arr as $val) {
                if ($val) {
                    $where[] = $this->where_json($table, $name, $this->db->escapeString(dr_safe_replace($val), true));
                }
            }
            return $where ? '('.implode(strpos($value,  '||') !== false ? ' AND ' : ' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && in_array($field['fieldtype'], ['Members', 'Related'])) {
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $val) {
                if (is_numeric($val)) {
                    $where[] = ' FIND_IN_SET ('.intval($value).',`'.$table.'`.`'.$name.'`)';
                } else {
                    $where[] = ' FIND_IN_SET ("'.dr_safe_replace($value).'",`'.$table.'`.`'.$name.'`)';
                }
            }
            return $where ? '('.implode(strpos($value,  '||') !== false ? ' AND ' : ' OR ', $where).')' : '`'.$table.'`.`id` = 0';

        } elseif (isset($field['fieldtype']) && in_array($field['fieldtype'], ['Radio', 'Select'])) {
            // 单选字段
            $arr = explode('|', $value);
            if ($is_like && $value) {
                $option = dr_format_option_array($field['setting']['option']['options']);
                if ($option) {
                    $new = [];
                    foreach ($option as $k => $v) {
                        if (strpos($v, $value) !== false) {
                            $new[] = $k;
                        }
                    }
                    if ($new) {
                        $arr = $new;
                    }
                }
            }
            $where = [];
            foreach ($arr as $val) {
                if (is_numeric($val)) {
                    $where[] = '`'.$table.'`.`'.$name.'`='.$val;
                } else {
                    $where[] = '`'.$table.'`.`'.$name.'`=\''.dr_safe_replace($val, ['\\', '/']).'\'';
                }
            }
            return $where ? '('.implode(strpos($value,  '||') !== false ? ' AND ' : ' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (substr_count($value, ',') == 1 && preg_match('/[\+\-0-9\.]+,[\+\-0-9\.]+/', $value)) {
            // BETWEEN 条件
            list($s, $e) = explode(',', $value);
            $s = floatval($s);
            $e = floatval($e);
            if ($s == $e && $s == 0) {
                return '`'.$table.'`.`'.$name.'` = 0';
            }
            if (!$e && $s > 0) {
                return '`'.$table.'`.`'.$name.'` > '.$s;
            } else {
                return '`'.$table.'`.`'.$name.'` BETWEEN '.$s.' AND '.$e;
            }
        } elseif ($is_like || strpos($value, '%') !== false || strpos($value, ' ') !== false) {
            // like 条件
            $arr = explode('%', str_replace(' ', '%', $value));
            if (count($arr) == 1) {
                return '`'.$table.'`.`'.$name.'` LIKE \'%'.trim($this->db->escapeString($value, true), '%').'%\'';
            } else {
                $wh = [];
                foreach ($arr as $c) {
                    $c && $wh[] = '`'.$table.'`.`'.$name.'` LIKE \'%'.trim($this->db->escapeString($c, true)).'%\'';
                }
                return $wh ? ('('.implode(strpos($value,  '%%') !== false ? ' AND ' : ' OR ', $wh).')') : '';
            }
        } elseif (is_numeric($value)) {
            return '`'.$table.'`.`'.$name.'`='.$value;
        } else {
            return '`'.$table.'`.`'.$name.'`=\''.dr_safe_replace($value, ['\\', '/']).'\'';
        }
    }

    protected function _limit_where(&$select, $param, $field, $table) {

        $table = $this->dbprefix($table);
        if (isset($param['keyword']) && $param['keyword']) {
            $param['keyword'] = htmlspecialchars(urldecode($param['keyword']));
        }
        if ($param['field'] == $this->id) {
            // 按id查询
            $id = [];
            $ids = explode(',', $param['keyword']);
            foreach ($ids as $i) {
                $id[] = (int)$i;
            }
            dr_count($id) == 1 ? $select->where($table.'.'.$this->id, (int)$id[0]) : $select->whereIn($this->id, $id);
            $param['keyword'] = $param['keyword'];
        } elseif (isset($field[$param['field']]['myfunc']) && $field[$param['field']]['myfunc']) {
            // 自定义的匹配模式
            if (function_exists($field[$param['field']]['myfunc'])) {
                $rt = call_user_func_array($field[$param['field']]['myfunc'], [$param]);
                if ($rt) {
                    $select->where($rt);
                }
            } else {
                CI_DEBUG && log_message('debug', '字段myfunc参数中的函数（'.$field[$param['field']]['myfunc'].'）未定义');
            }
        } elseif ($param['field'] == 'uid' || $field[$param['field']]['fieldtype'] == 'Uid') {
            // 数字查询作为账号id
            $uid = is_numeric($param['keyword']) ? intval($param['keyword']) : 0;
            if ($uid && $this->db->table('member')->where('id', $uid)->countAllResults()) {
                $select->where($table.'.`'.$param['field'].'` = '.intval($param['keyword']));
            } else {
                // uid 非数字查询 账户查询
                $select->where($table.'.`'.$param['field'].'` in (select id from '.$this->dbprefix('member').' where username LIKE "%'.$this->db->escapeString($param['keyword'], true).'%")');
            }
        } elseif (in_array($field[$param['field']]['fieldtype'], ['INT'])) {
            // 数字类型
            $select->where($param['field'], intval($param['keyword']));
        } elseif (isset($field[$param['field']]['isemoji']) && $field[$param['field']]['isemoji']) {
            // 表情符号查询
            $key = addslashes($param['keyword']);
            $key2 = addslashes(str_replace ( '\u', '\\\\\\\\u', trim ( str_replace('\\', '|', json_encode($key)), '"' ) ));
            // 搜索用户表
            $select->where("(".$table.".`".$param['field']."` LIKE '%$key%' OR ".$param['field']." LIKE '%$key2%')");
        } elseif (isset($field[$param['field']]['isint']) && $field[$param['field']]['isint']) {
            // 整数绝对匹配
            $select->where($param['field'], intval($param['keyword']));
        } elseif (isset($field[$param['field']]['iswhere']) && $field[$param['field']]['iswhere']) {
            // 准确匹配模式
            $select->where($param['field'], $param['keyword']);
        } else {
            $where = $this->_where(
                $table,
                $param['field'],
                $param['keyword'],
                $field[$param['field']],
                true
            );
            if ($where) {
                $select->where($where, null, false);
            }
        }
        return $select;
    }

    /**
     * 条件查询
     *
     * @param	object	$select	查询对象
     * @param	intval	$where	是否搜索
     * @return	intval
     */
    protected function _limit_page_where(&$select, $param) {

        // 默认搜索条件
        $this->param['where_list'] && $select->where($this->param['where_list']);

        // 默认搜索条件 关联查询
        $this->param['join_list'] && $select->join(
            $this->param['join_list'][0],
            $this->param['join_list'][1],
            $this->param['join_list'][2]
        );

        // 定义的条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $select->where($v[0], $v[1]) : $select->where($v, null, false);
            }
        }

        // 条件搜索
        if ($param) {
            $field = $this->field;
            $field[$this->id] = $this->id;
            // 关键字 + 自定义字段搜索
            if (isset($param['keyword']) && $param['keyword'] != '') {
                if (isset($this->init['is_swhere']) && $this->init['is_swhere']) {
                    if (isset($this->sfield[$param['field']]) && $this->stable) {
                        $select = $this->_limit_where($select, $param, $this->sfield, $this->stable);
                    } elseif (isset($field[$param['field']])) {
                        $select = $this->_limit_where($select, $param, $field, $this->table);
                    }
                } else {
                    if (isset($field[$param['field']])) {
                        $select = $this->_limit_where($select, $param, $field, $this->table);
                    } elseif (isset($this->sfield[$param['field']]) && $this->stable) {
                        $select = $this->_limit_where($select, $param, $this->sfield, $this->stable);
                    }
                }
            }
            // 时间搜索
            if ($this->date_field) {
                if (isset($param['date_form']) && $param['date_form']) {
                    $select->where($this->date_field.' BETWEEN ' . max((int)strtotime(strpos($param['date_form'], ' ') ? $param['date_form'] : $param['date_form'].' 00:00:00'), 1) . ' AND ' . ($param['date_to'] ? (int)strtotime(strpos($param['date_to'], ' ') ? $param['date_to'] : $param['date_to'].' 23:59:59') : SYS_TIME));
                } elseif (isset($param['date_to']) && $param['date_to']) {
                    $select->where($this->date_field.' BETWEEN 1 AND ' . (int)strtotime(strpos($param['date_to'], ' ') ? $param['date_to'] : $param['date_to'].' 23:59:59'));
                }
            }
            // 栏目查询
            if (isset($param['catid']) && $param['catid']) {
                $mid = defined('MOD_DIR') ? MOD_DIR : (APP_DIR ? APP_DIR : 'share');
                $cat = dr_cat_value($mid, $param['catid']);
                $cat && $cat['child'] ? $select->whereIn('catid', explode(',', $cat['childids'])) : $select->where('catid', (int)$param['catid']);
            }
            // 其他自定义字段查询
            if (isset($this->param['is_diy_where_list']) && $this->param['is_diy_where_list']) {
                $where = [];
                foreach ($param as $i => $v) {
                    if (!in_array($i, ['id', 'keyword', 'catid', 'date_form', 'date_to', 'field', 'total']) && isset($field[$i]) && $field[$i]['ismain'] && strlen($v)) {
                        $where[] = str_replace('`{finecms_table}`.', '', $this->_where('{finecms_table}', $i, $v, $field));
                    }
                }
                $where && $select->where(implode(' AND ', $where), null, false);
            }
        }

        return $param;
    }

    // 分页
    public function limit_page($size = SYS_ADMIN_PAGESIZE, $where = '') {

        $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        $param = \Phpcmf\Service::L('input')->get();
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);
        if (isset($param['keyword']) && $param['keyword']) {
            $param['keyword'] = trim(urldecode($param['keyword']));
        }

        if ($size > 0 && !$total) {
            $select	= $this->db->table($this->table);
            if ($this->param['group_list']) {
                $select->select('count(DISTINCT '.$this->param['group_list'].') as total');
            } else {
                $select->select('count(*) as total');
            }
            // 自定义查询闭包函数
            if (isset($this->init['select_function'])) {
                $this->init['select_function']($select);
            }
            $where && $select->where($where);
            $param = $this->_limit_page_where($select, $param);
            $query = $select->get();
            if (!$query) {
                log_message('debug', '数据查询失败：'.$this->table);
                $this->_clear();
                return [[], $total, $param];
            }
            $data = $query->getRowArray();
            $total = (int)$data['total'];
            $param['total'] = $total;
            unset($select);
            if (!$total) {
                $this->_clear();
                return [[], $total, $param];
            }
        }

        $select	= $this->db->table($this->table);
        $this->param['select_list'] && $select->select($this->param['select_list']);
        // 自定义查询闭包函数
        if (isset($this->init['select_function'])) {
            $this->init['select_function']($select);
        }
        $where && $select->where($where);
        $param = $this->_limit_page_where($select, $param);
        if ($size > 0) {
            $select->limit($size, intval($size * ($page - 1)));
        }

        $this->param['group_list'] && $select->groupBy($this->param['group_list']);

        //分析参数合法性
        $order = isset($param['order']) && $param['order'] ? urldecode($param['order']) : ''; // 获取的排序参数
        $order_str = dr_safe_replace($this->param['order_list']);
        if ($order) {
            $arr = explode(',', $order);
            $order_arr = [];
            foreach ($arr as $t) {
                list($order_field, $b) = explode(' ', $t);
                if ($this->is_field_exists($this->table, $order_field)) {
                    if ($this->stable && $this->is_field_exists($this->stable, $order_field)) {
                        // 两个表都有这个字段
                        $order_arr[] = $this->table.'.'.$order_field.' '.($b && $b=='asc' ? 'asc' : 'desc');
                    } else {
                        $order_arr[] = $order_field.' '.($b && $b=='asc' ? 'asc' : 'desc');
                    }
                } elseif ($this->stable && $this->is_field_exists($this->stable, $order_field)) {
                    $order_arr[] = $this->stable.'.'.$order_field.' '.($b && $b=='asc' ? 'asc' : 'desc');
                }
            }
            if ($order_arr) {
                $order_str = implode(',', $order_arr);
            }
        }
        $query = $select->orderBy($order_str ? $order_str : 'id desc')->get();
        if (!$query) {
            log_message('debug', '数据查询失败：'.$this->table);
            $this->_clear();
            return [[], $total, $param];
        }
        $data = $query->getResultArray();

        $param['order'] = $order;
        $param['total'] = $total;

        // 收尾工作
        $this->_clear();

        return [$data, $total, $param];

    }

    // 条件
    public function field($field) {
        return $this->select($field);
    }
    public function select($field) {

        if (!$field) {
            return $this;
        }

        $this->param['select'][] = $field;

        return $this;
    }

    // 条件
    public function where($name, $value = '') {

        if (!$name) {
            return $this;
        }

        if (is_array($name)) {
            foreach ($name as $f => $v) {
                $this->param['where'][] = !is_numeric($f) ? [$f, $v] : $v;
            }
        } else {
            $this->param['where'][] = dr_strlen($value) ? [$name, $value] : $name;
        }

        return $this;
    }

    // 条件
    public function like($name, $value = '') {

        if (!$name) {
            return $this;
        }

        $this->param['where'][] = $name.' LIKE "%'.$value.'%"';

        return $this;
    }

    // json
    public function where_json($table, $name, $value) {

        if (method_exists($this->db, 'whereJson')) {
            return $this->db->whereJson($table, $name, $value);
        }

        if (strpos($name, '`') === false) {
            $name = $table ? '`'.$table.'`.`'.$name.'`' : '`'.$name.'`';
        }

        if (version_compare($this->db->getVersion(), '5.7.0') < 0) {
            // 兼容写法
            return $name.' LIKE \'%"'.$value.'"%\'';
        } else {
            // 高版本写法
            return "(CASE WHEN JSON_VALID({$name}) THEN JSON_CONTAINS ({$name}->'$[*]', '\"".$value."\"', '$') ELSE null END)";
        }
    }

    // in条件
    public function where_in($name, $value) {

        if (!$name) {
            return $this;
        }

        if (is_array($value) && $value) {
            $this->param['where_in'][] = [$name, $value];
        }

        return $this;
    }

    public function where_date($name, $value) {

        if (!$name) {
            return $this;
        }

        //$where = 'DATEDIFF(from_unixtime('.$name.'),now())='.$value;
        $where = '';
        if (!$value) {
            // 今天
            $stime = strtotime(date('Y-m-d', SYS_TIME).' 00:00:00');
            $etime = strtotime(date('Y-m-d 23:59:59', $stime));
            $where = $name." BETWEEN ".$stime." AND ".$etime;
        }

        $this->param['where'][] = $where;

        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param int $offset 起始位置
     * @param int $length 查询数量
     * @return $this
     */
    public function limit($offset, $length = 0) {

        $this->param['limit'] = $offset . ($length ? ',' . $length : '');

        return $this;
    }

    // 排序
    public function order_by($value, $value2 = null) {

        $this->param['order'] = $value2 ? $value.' '.$value2 : $value;

        return $this;
    }

    public function group_by($value) {

        $this->param['group'] = $value;

        return $this;
    }

    // 运行SQL
    public function query_sql($sql, $more = 0) {

        $sql = str_replace('{dbprefix}', $this->prefix, $sql);
        $query = $this->db->query($sql);
        if (!$query || !is_object($query)) {
            $this->_clear();
            return [];
        }

        $rt = $more ? $query->getResultArray() : $query->getRowArray();
        $this->_clear();

        return $rt;
    }

    // 批量执行
    public function query_all($sql) {

        if (!$sql) {
            $this->_clear();
            return '';
        }

        $sql = str_replace('{dbprefix}', $this->prefix, $sql);
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $sql)));

        foreach($sql_data as $query){
            if (!$query) {
                continue;
            }
            $ret = '';
            $queries = explode('SQL_FINECMS_EOL', trim($query));
            foreach($queries as $query) {
                $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
            }
            if (!$ret) {
                continue;
            }
            if (strpos($ret, '/*') === 0 && preg_match('/\/\*(.+)\*\//U', $ret)) {
                continue;
            }
            if (!$this->db->simpleQuery(dr_format_create_sql($ret))) {
                $rt = $this->db->error();
                $this->_clear();
                return $ret.': '.$rt['message'];
            }
        }

        $this->_clear();

        return '';
    }

    // 获取当前执行后的sql语句
    public function get_sql_query() {

        if (!$this->db) {
            return '';
        } elseif (!method_exists($this->db, 'getLastQuery')) {
            return '';
        }

        $my = $this->db->getLastQuery();
        if (!$my) {
            $this->_clear();
            return '';
        }

        if ($my && !method_exists($my, 'getQuery')) {
            $this->_clear();
            return (string)$my;
        }

        $rt = str_replace(PHP_EOL, ' ', $my->getQuery());
        $this->_clear();

        return $rt;
    }

    // 关闭数据库
    public function close() {
        if (method_exists($this->db, 'close')) {
            $this->db->close();
        }
    }

    private function _clear() {
        $this->key = $this->id;
        $this->date_field = 'inputtime';
        $this->field = [];
        $this->param = [];
        if ($this->db_temp) {
            // 还原默认库
            $this->db = $this->db_temp;
            $this->prefix = $this->db_temp->DBPrefix;
            $this->db_temp = NULL;
        }
    }

    // 附表分表规则
    public function get_table_id($id) {
        return floor($id / 100000);
    }

    // 显示数据库错误
    private function _return_error($msg) {
        return IS_ADMIN || IS_DEV ? dr_return_data(0, $msg) : dr_return_data(0, dr_lang('系统错误'));
    }
}
