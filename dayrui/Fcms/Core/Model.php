<?php namespace Phpcmf;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 模型类
class Model {

    public $db;
    public $id;
    public $key;
    public $table;
    public $prefix;

    public $uid;
    public $admin;
    public $member;

    protected $date_field;
    protected $param;
    protected $init;

    public function __construct(...$params) {
        // 数据库
        $this->db = \Config\Database::connect('default');
        $this->prefix = $this->db->DBPrefix;
        $this->key = $this->id = 'id';
        $this->uid = \Phpcmf\Service::C()->uid;
        $this->site = \Phpcmf\Service::C()->site;
        $this->admin = \Phpcmf\Service::C()->admin;
        $this->member = \Phpcmf\Service::C()->member;
    }

    // 设置初始化查询条件
    public function init($data) {
        
        isset($data['id']) && $this->id = $this->key = $data['id'];
        isset($data['table']) && $this->table = $data['table'];
        isset($data['field']) && $this->field = $data['field'];
        isset($data['date_field']) && $this->date_field = $data['date_field'];
        
        isset($data['order_by']) && $this->param['order_list'] = $data['order_by'];
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

    // 设置操作表
    public function table($name) {
        $this->table = $name;
        return $this;
    }

    // 设置操作站点的表
    public function table_site($name, $site = SITE_ID) {
        $this->table = $site.'_'.$name;
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
            log_message('error', $sql.': '.$error['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($error['message']);
        }

        return dr_return_data(1);
    }
    
    // 附表不存在时创建附表
    public function is_data_table($table, $tid) {
        if ($tid > 0 && !$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($table.$tid)."'")->getRowArray()) {
            // 附表不存在时创建附表
            $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix($table)."0`")->getRowArray();
            $this->db->query(str_replace(
                array($sql['Table'], 'CREATE TABLE '),
                array($this->dbprefix($table.$tid), 'CREATE TABLE IF NOT EXISTS '),
                $sql['Create Table']
            ));
        }
    }

    // 表是否存在
    public function is_table_exists($table) {

        if (!$table) {
            return 0;
        }

        $table = strpos($table, $this->prefix) === 0 ? $table : $this->dbprefix($table);
        return $this->db->tableExists($table) ? 1 : 0;
    }

    // 表字段是否存在
    public function is_field_exists($table, $name) {

        if (!$table || !$name) {
            return 0;
        }

        $table = strpos($table, $this->prefix) === 0 ? $table : $this->dbprefix($table);
        return $this->db->fieldExists($name, $table) ? 1 : 0;
    }

    // 字段值是否存在
    public function is_exists($id, $name, $value) {

        $builder = $this->db->table($this->table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v);
            }
        }
        
        $rt = $builder->where($name, $value)->where($this->key.'<>', $id)->countAllResults();

        $this->_clear();
        
        return $rt;
    }
    
    
    // 统计数量
    public function counts($table = '', $where = '') {

        $builder = $this->db->table(!$table ? $this->table : $table);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v);
            }
        }
        
        $where && $builder->where($where);
        
        $this->_clear();
        
        return $builder->countAllResults();
    }
    
    // 插入数据
    public function insert($data) {

        $this->db->table($this->table)->insert($data);
        $rt = $this->db->error();
        if ($rt['code']) {
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $id = $this->db->insertID();
        !$id && $id = intval($data[$this->key]);

        if (!$id) {
            log_message('error', $this->table.': 主键获取失败<br>'.FC_NOW_URL);
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
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }

        $id = $this->db->insertID();
        !$id && $id = intval($data[$this->key]);

        if (!$id) {
            log_message('error', $this->table.': 主键获取失败<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': 主键获取失败');
        }

        $this->_clear();

        return dr_return_data($id);
    }
    
    // 更新数据
    public function update($id, $data, $where = '') {

        $db = $this->db->table($this->table);
        $db->where($this->key, (int)$id);

        $where && $db->where($where);
        $db->update($data);

        $rt = $this->db->error();
        if ($rt['code']) {
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

        $where && $db->where($where);
        $id && $db->where($this->key, (int)$id);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $db->where($v[0], $v[1]) : $db->where($v);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $db->whereIn($v[0], $v[1]) : $db->whereIn($v);
            }
        }

        // 执行删除
        $db->delete();

        $rt = $this->db->error();
        if ($rt['code']) {
            log_message('error', $this->table.': '.$rt['message'].'<br>'.FC_NOW_URL);
            return $this->_return_error($this->table.': '.$rt['message']);
        }


        $this->_clear();

        return dr_return_data($id);
    }
    
    // 批量删除数据
    /*
    * 主键数组
    * */
    public function deleteAll($ids, $where = '') {

        $db = $this->db->table($this->table);
        $where && $db->where($where);

        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $db->where($v[0], $v[1]) : $db->where($v);
            }
        }

        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $db->whereIn($v[0], $v[1]) : $db->whereIn($v);
            }
        }
        
        $db->whereIn($this->key, (array)$ids)->delete();

        $rt = $this->db->error();
        if ($rt['code']) {
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
        $where && $db->where($where);
        $db->where($this->key, (int)$id)->update([$name => $value]);

        $rt = $this->db->error();
        if ($rt['code']) {
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
    public function getAll($num = 0, $key = '') {

        $builder = $this->db->table($this->table);
        
        // 条件
        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v);
            }
        }
        
        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v);
            }
        }
        
        // 排序
        $this->param['order'] && $builder->orderBy($this->param['order']);

        // 数量控制
        if ($this->param['limit']) {
            $builder->limit($this->param['limit']);
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
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->where($v);
            }
        }
        
        // in条件
        if ($this->param['where_in']) {
            foreach ($this->param['where_in'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereIn($v);
            }
        }

        if (!$builder) {
            return [];
        }
        
        // 排序
        $this->param['order'] && $builder->orderBy($this->param['order']);

        $rt = $builder->get();

        $data = [];
        $rt && $data = $rt->getRowArray();

        $this->_clear();

        return $data;
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
            return $value;
        }

        return -1;
    }

    // 条件组合
    protected function _where($table, $name, $value, $field) {

        if (!$value && strlen($value) == 0) {
            return ''; //空值
        }

        $name = dr_safe_replace($name, ['\\', '/']);
        if ((isset($field['fieldtype']) && $field['fieldtype'] == 'Date') || in_array($name, ['inputtime', 'updatetime'])) {
            // 匹配时间字段
            list($s, $e) = explode(',', $value);
            $s = (int)strtotime($s);
            $e = (int)strtotime($e);
            if (!$e) {
                return '`'.$table.'`.`'.$name.'` > '.$s;
            } else {
                return '`'.$table.'`.`'.$name.'` BETWEEN '.$s.' AND '.$e;
            }
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Baidumap') {
            // 百度地图
            list($a, $km) = explode('|', $value);
            list($lng, $lat) = explode(',', $a);
            if ($lat && $lng) {
                // 获取Nkm内的数据
                $squares = dr_square_point($lng, $lat, $km);
                return "(`".$table."`.`".$name."_lat` between {$squares['right-bottom']['lat']} and {$squares['left-top']['lat']}) and (`".$table."`.`".$name."_lng` between {$squares['left-top']['lng']} and {$squares['right-bottom']['lng']})";
            } else {
                //\Phpcmf\Service::C()->goto_404_page(dr_lang('没有定位到您的坐标'));
            }
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkage') {
            // 联动菜单字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                $data = dr_linkage($field['setting']['option']['linkage'], $value);
                if ($data) {
                    if ($data['child']) {
                        $where[] = '`'.$table.'`.`'.$name.'` IN ('.$data['childids'].')';
                    } else {
                        $where[] = '`'.$table.'`.`'.$name.'`='.intval($data['ii']);
                    }
                }
            }
            return $where ? '('.implode(' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkages') {
            // 联动菜单多选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                $data = dr_linkage($field['setting']['option']['linkage'], $value);
                if ($data) {
                    if ($data['child']) {
                        $ids = explode(',', $data['childids']);
                        foreach ($ids as $id) {
                            if ($id) {
                                if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                                    // 兼容写法
                                    $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.intval($id).'\"%"';
                                } else {
                                    // 高版本写法
                                    $where[] = "(`{$table}`.`{$name}`<>'' AND JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".intval($id)."\"', '$'))";
                                }
                            }
                        }
                    } else {
                        if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                            // 兼容写法
                            $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.intval($data['ii']).'\"%"';
                        } else {
                            // 高版本写法
                            $where[] = "(`{$table}`.`{$name}`<>'' AND  JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".intval($data['ii'])."\"', '$'))";
                        }
                    }
                }
            }
            return $where ? '('.implode(' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Checkbox') {
            // 复选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                if ($value) {
                    if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                        // 兼容写法
                        $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.$this->db->escapeString($value, true).'\"%"';
                    } else {
                        // 高版本写法
                        $where[] = "(`{$table}`.`{$name}`<>'' AND  JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".$this->db->escapeString($value, true)."\"', '$'))";
                    }
                }
            }
            return $where ? '('.implode(' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (isset($field['fieldtype']) && in_array($field['fieldtype'], ['Radio', 'Select'])) {
            // 单选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                if (is_numeric($value)) {
                    $where[] = '`'.$table.'`.`'.$name.'`='.$value;
                } else {
                    $where[] = '`'.$table.'`.`'.$name.'`="'.dr_safe_replace($value, ['\\', '/']).'"';
                }
            }
            return $where ? '('.implode(' OR ', $where).')' : '`'.$table.'`.`id` = 0';
        } elseif (strpos($value, '%') === 0 && strrchr($value, '%') === '%') {
            // like 条件
            return '`'.$table.'`.`'.$name.'` LIKE "%'.trim($this->db->escapeString($value, true), '%').'%"';
        } elseif (preg_match('/[0-9]+,[0-9]+/', $value)) {
            // BETWEEN 条件
            list($s, $e) = explode(',', $value);
            if (!$e) {
                return '`'.$table.'`.`'.$name.'` > '.$s;
            } else {
                return '`'.$table.'`.`'.$name.'` BETWEEN '.$s.' AND '.$e;
            }
        } elseif (is_numeric($value)) {
            return '`'.$table.'`.`'.$name.'`='.$value;
        } else {
            return '`'.$table.'`.`'.$name.'`="'.dr_safe_replace($value, ['\\', '/']).'"';
        }
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
                dr_count($v) == 2 ? $select->where($v[0], $v[1]) : $select->where($v);
            }
        }

        // 条件搜索
        if ($param) {
            $field = $this->field;
            $field[$this->id] = $this->id;
            // 关键字 + 自定义字段搜索
            if (isset($param['keyword']) && $param['keyword'] != '' && isset($field[$param['field']])) {
                if ($param['field'] == $this->id) {
                    // 按id查询
                    $id = [];
                    $ids = explode(',', $param['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    dr_count($id) == 1 ? $select->where($this->id, (int)$id[0]) : $select->whereIn($this->id, $id);
                } elseif ($field[$param['field']]['fieldtype'] == 'Linkage'
                    && $field[$param['field']]['setting']['option']['linkage']) {
                    // 联动菜单搜索
                    if (is_numeric($param['keyword'])) {
                        // 联动菜单id查询
                        $link = dr_linkage($field[$param['field']]['setting']['option']['linkage'], (int)$param['keyword'], 0, 'childids');
                        $link && $select->where($param['field'].' IN ('.$link.')');
                    } else {
                        // 联动菜单名称查询
                        $id = (int)\Phpcmf\Service::C()->get_cache('linkid-'.SITE_ID, $field[$param['field']]['setting']['option']['linkage']);
                        $id && $select->where($param['field'].' IN (select id from `'.$this->dbprefix('linkage_data_'.$id).'` where `name` like "%'.$param['keyword'].'%")');
                    }
                } elseif (in_array($field[$param['field']]['fieldtype'], ['INT'])) {
                    // 数字类型
                    $select->where($param['field'], intval($param['keyword']));
                } elseif ($field[$param['field']]['isemoji']) {
                    // 表情符号查询
                    $key = $param['keyword'];
                    $key2 = str_replace ( '\u', '\\\\\\\\u', trim ( dr_emoji2html ($key, 0 ), '"' ) );
                    // 搜索用户表
                    $select->where("(nickname LIKE '%$key%' OR nickname LIKE '%$key2%')");
                } elseif ($field[$param['field']]['isint']) {
                    // 整数绝对匹配
                    $select->where($param['field'], intval($param['keyword']));
                } else {
                    $select->like($param['field'], urldecode($param['keyword']));
                }
            }
            // 时间搜索
            if ($this->date_field) {
                if (isset($param['date_form']) && $param['date_form']) {
                    $select->where($this->date_field.' BETWEEN ' . max((int)strtotime($param['date_form'].' 00:00:00'), 1) . ' AND ' . ($param['date_to'] ? (int)strtotime($param['date_to'].' 23:59:59') : SYS_TIME));
                } elseif (isset($param['date_to']) && $param['date_to']) {
                    $select->where($this->date_field.' BETWEEN 1 AND ' . (int)strtotime($param['date_to'].' 23:59:59'));
                }
            }
            // 栏目查询
            if (isset($param['catid']) && $param['catid']) {
                $cat = \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-'.MOD_DIR, 'category', $param['catid']);
                $cat['child'] ? $select->whereIn('catid', explode(',', $cat['childids'])) : $select->where('catid', (int)$param['catid']);
            }
            // 其他自定义字段查询
            if (isset($this->param['is_diy_where_list']) && $this->param['is_diy_where_list']) {
                $where = [];
                foreach ($param as $i => $v) {
                    if (!in_array($i, ['id', 'keyword', 'catid', 'date_form', 'date_to', 'field', 'total']) && isset($field[$i]) && $field[$i]['ismain'] && strlen($v)) {
                        $where[] = str_replace('`{finecms_table}`.', '', $this->_where('{finecms_table}', $i, $v, $field));
                    }
                }
                $where && $select->where(implode(' AND ', $where));
            }
        }

        return $param;
    }

    // 分页
    public function limit_page($size = SYS_ADMIN_PAGESIZE) {

        $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        $param = \Phpcmf\Service::L('input')->get();
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);
        if (isset($param['keyword']) && $param['keyword']) {
            $param['keyword'] = trim(urldecode($param['keyword']));
        }

        if ($size > 0 && !$total) {
            $select	= $this->db->table($this->table)->select('count(*) as total');
            $param = $this->_limit_page_where($select, $param);
            $query = $select->get();
            if (!$query) {
                log_message('error', '数据查询失败：'.$this->table);
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
        $order = dr_get_order_string(dr_safe_replace($param['order']), $this->param['order_list']);
        $param = $this->_limit_page_where($select, $param);
        $size > 0 && $select->limit($size, $size * ($page - 1));
        $query = $select->orderBy($order)->get();
        if (!$query) {
            log_message('error', '数据查询失败：'.$this->table);
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
    public function where($name, $value = '') {

        if (!$name) {
            return $this;
        }

        $this->param['where'][] = strlen($value) ? [$name, $value] : $name;
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

    /**
     * 指定查询数量
     * @access public
     * @param int $offset 起始位置
     * @param int $length 查询数量
     * @return $this
     */
    public function limit(int $offset, int $length = null)
    {

        $this->param['limit'] = $offset . ($length ? ',' . $length : '');

        return $this;
    }

    // 排序
    public function order_by($value) {
        $this->param['order'] = $value;
        return $this;
    }

    // 运行SQL
    public function query_sql($sql, $more = 0) {

        $sql = str_replace('{dbprefix}', $this->prefix, $sql);
        $query = $this->db->query($sql);
        if (!$query) {
            return [];
        }

        return $more ? $query->getResultArray() : $query->getRowArray();
    }
    
    // 批量执行
    public function query_all($sql) {

        if (!$sql) {
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
            if (!$this->db->simpleQuery(dr_format_create_sql($ret))) {
                $rt = $this->db->error();
                return $ret.': '.$rt['message'];
            }
        }
        
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
        if (!method_exists($my, 'getQuery')) {
            return '';
        }

        return str_replace(PHP_EOL, ' ', $my->getQuery());
    }
    
    private function _clear() {
        $this->key = $this->id;
        $this->date_field = 'inputtime';
        $this->field = [];
        $this->param = [];
    }

    // 显示数据库错误
    private function _return_error($msg) {
        return IS_ADMIN || IS_DEV ? dr_return_data(0, $msg) : dr_return_data(0, dr_lang('系统错误'));
    }
}
