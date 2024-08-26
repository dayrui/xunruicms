<?php namespace Frame;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

// 数据库引导类
class Model {

    static private $db;
    static private $dbs;

    static function _load_db() {

        // 数据库
        if (self::$db) {
            return [self::$db, self::$db->prefix];
        }

        self::$db = new db_mysql();
        self::$db->DBPrefix = self::$db->prefix = Config::get('database.connections.mysql.prefix');

        return [self::$db, self::$db->prefix];
    }


    static function _load_db_source($name) {

        // 数据库
        if (isset(self::$dbs[$name]) && self::$dbs[$name]) {
            return [self::$dbs[$name], self::$dbs[$name]->prefix];
        }

        self::$dbs[$name] = new db_mysql($name);
        self::$dbs[$name]->DBPrefix = self::$dbs[$name]->prefix = config('database.connections.'.$name.'.prefix');

        return [self::$dbs[$name], self::$dbs[$name]->prefix];
    }

}

class db_mysql {

    public $query_sql;
    public $param = [];
    public $prefix;
    public $DBPrefix;
    public $likeEscapeChar = '!';
    public $affectedRows = 0;
    public $db_source = '';

    public function __construct($name = '') {
        if ($name) {
            $this->db_source = $name;
        }
    }

    public function query($sql) {
        $this->_clear();
        if ($this->db_source) {
            $this->param['result'] = DB::Connection($this->db_source)->select($sql);
        } else {
            $this->param['result'] = DB::select($sql);
        }
        return $this;
    }

    public function simpleQuery($sql) {
        $this->_clear();
        DB::statement($sql);
        return $this;
    }

    public function connect($name = '') {
        return true;
    }

    public function resetDataCache() {

    }

    public function error() {

    }


    public function transBegin() {
        Db::beginTransaction();
    }

    public function transRollback() {
        Db::rollBack();
    }

    public function transStatus() {
        return true;
    }

    public function transCommit() {
        Db::commit();
    }

    public function getVersion() {
        $v = "version()";
        return DB::select("select version()")[0]->$v;
    }

    public function get() {

        if (!$this->param['table']) {
            return $this;
        }

        $builder = DB::table($this->param['table']);

        if ($this->param['select']) {
            if ($this->param['select_sum']) {
                $builder->select(DB::raw($this->param['select']), DB::raw('SUM('.$this->param['select_sum'].') as '.$this->param['select_sum']));
            } else {
                $builder->select(DB::raw($this->param['select']));
            }
        }

        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                if (dr_count($v) == 2) {
                    if (strpos($v[0], $this->prefix) === 0) {
                        $v[0] = substr($v[0], strlen($this->prefix));
                    }
                    $builder->where($v[0], $v[1]);
                } else {
                    $builder->whereRaw($v);
                }
            }
        }

        if ($this->param['whereIn']) {
            foreach ($this->param['whereIn'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereRaw($v);
            }
        }

        if ($this->param['join']) {
            foreach ($this->param['join'] as $table => $v) {
                list($where, $type) = $v;
                list($a, $b) = explode('=', $where);
                switch ($type) {
                    case 'left':
                        $builder->leftJoin($table, $a, '=', $b);
                        break;
                    case 'right':
                        $builder->rightJoin($table, $a, '=', $b);
                        break;
                    default:
                        $builder->join($table, $a, '=', $b);
                        break;
                }
                if ($this->param['order'] && strpos($this->param['order'], '.') !== false) {
                    $this->param['order'] = str_replace($this->param['table'].'.', ''.$this->prefix.$this->param['table'].'.', $this->param['order']);
                    $this->param['order'] = str_replace($table.'.', ''.$this->prefix.$table.'.', $this->param['order']);
                }
            }
        }

        if ($this->param['order']) {
            $builder->orderByRaw($this->param['order']);
        }

        if ($this->param['group']) {
            $builder->groupByRaw($this->param['group']);
        }

        if ($this->param['limit']) {
            list($a, $b) = explode(',', $this->param['limit']);
            if ($b) {
                $builder->limit($a);
                $builder->offset($b);
            } else {
                $builder->limit($a);
            }
        }

        $this->param['is_get'] = true;
        $this->param['builder'] = $builder;

        return $this;
    }

    public function limit($limit, $b = 0) {

        $this->param['limit'] = $b ? $limit.','.$b : $limit;

        return $this;
    }

    public function join($table, $where, $type = 'left') {

        $this->param['join'][$table] = [$where, $type];

        return $this;
    }

    public function getRowArray() {

        $rt = [];
        if ($this->param['result']) {
            $rt = dr_object2array($this->param['result']);
            $rt = array_shift($rt);
        } elseif ($this->param['builder']) {
            $a = $this->param['builder']->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
            $rt = array_shift($a);
        }

        $this->_clear();

        return $rt;
    }

    public function getResultArray() {

        $rt = [];
        if ($this->param['result']) {
            $rt = dr_object2array($this->param['result']);
        } elseif ($this->param['builder']) {
            $rt = $this->param['builder']->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        }

        $this->_clear();

        return $rt;
    }

    public function countAllResults() {

        $rt = 0;

        if (!$this->param['is_get']) {
            $this->get();
        }

        if ($this->param['builder']) {
            $rt = $this->param['builder']->count();
        }

        $this->_clear();

        return $rt;
    }

    public function where($where, $value = '', $test = '') {

        if (dr_strlen($value)) {
            $this->param['where'][] = [$where, $value];
        } else {
            $this->param['where'][] = $where;
        }

        return $this;
    }

    public function selectSum($field) {

        $this->param['select_sum'] = $field;

        return $this;
    }

    public function whereIn($where, $value = '') {

        if (dr_strlen($value)) {
            $this->param['whereIn'][] = [$where, $value];
        } else {
            $this->param['whereIn'][] = $where;
        }

        return $this;
    }

    public function like($where, $value = '') {

        if (dr_strlen($value)) {
            $this->param['where'][] = $where." LIKE '%".$this->escapeString($value, 1)."%'";
        } else {
            $this->param['where'][] = $where;
        }

        return $this;
    }

    public function table($name) {
        $this->_clear();
        if (strpos($name, $this->prefix) === 0) {
            $name = substr($name, strlen($this->prefix));
        }
        $this->param['table'] = $name;
        return $this;
    }

    public function select($name) {
        $this->param['select'] = $name;
        return $this;
    }

    public function orderBy($name) {
        $this->param['order'] = $name;
        return $this;
    }

    public function groupBy($name) {
        $this->param['group'] = $name;
        return $this;
    }

    public function insertID() {
        return DB::getPdo()->lastInsertId();
    }

    // 执行”写入”类型的语句（insert，update等）时返回有多少行受影响
    public function affectedRows() {
        return $this->affectedRows;
    }

    public function insert($data) {
        $rt = DB::table($this->param['table'])->insert($data);
        $this->affectedRows = $rt ? 1 : 0;
        $this->_clear();
    }

    public function replace($data) {
        $rt = $this->affectedRows = DB::table($this->param['table'])->upsert($data, 'id');
        $this->affectedRows = $rt ? 1 : 0;
        $this->_clear();
    }
    public function set($key, $value, $escape = true) {

        if ($escape) {
            $this->param['update'][$key] = $value;
        } else {
            $this->param['update'][$key] = DB::raw($value);

        }

        return $this;
    }

    //+
    public function increment($key, $value) {
        $this->param['update_inc'][$key] = $value;
        return $this->update();
    }

    //-
    public function decrement($key, $value) {
        $this->param['update_dec'][$key] = $value;
        return $this->update();
    }

    public function update($data = []) {

        $this->affectedRows = 0;

        if (!$this->param['is_get']) {
            $this->get();
        }

        if ($this->param['builder']) {
            if ($this->param['update']) {
                foreach ($this->param['update'] as $key => $value) {
                    $data[$key] = $value;
                }
            }
            if ($this->param['update_dec']) {
                foreach ($this->param['update_dec'] as $key => $value) {
                    $data[$key] = DB::raw($key.'-'.$value);
                }
            }
            if ($this->param['update_inc']) {
                foreach ($this->param['update_inc'] as $key => $value) {
                    $data[$key] = DB::raw($key.'+'.$value);
                }
            }
            $this->affectedRows = $this->param['builder']->update($data);
        }

        $this->_clear();
        return $this->affectedRows;
    }

    public function escapeString($str, bool $like = false) {

        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escapeString($val, $like);
            }

            return $str;
        }

        $str = str_replace("'", "''", remove_invisible_characters($str, false));

        // escape LIKE condition wildcards
        if ($like === true) {
            return str_replace(
                [
                    $this->likeEscapeChar,
                    '%',
                    '_',
                ],
                [
                    $this->likeEscapeChar . $this->likeEscapeChar,
                    $this->likeEscapeChar . '%',
                    $this->likeEscapeChar . '_',
                ],
                $str
            );
        }

        return $str;
    }


    public function escape($str)
    {

        if (is_array($str))
        {
            return array_map([&$this, 'escape'], $str);
        }

        if (is_string($str) || (is_object($str) && method_exists($str, '__toString')))
        {
            return "'" . $this->escapeString($str) . "'";
        }

        if (is_bool($str))
        {
            return ($str === false) ? 0 : 1;
        }

        if (is_numeric($str) && $str < 0)
        {
            return "'{$str}'";
        }

        if ($str === null)
        {
            return 'NULL';
        }

        return $str;
    }

    public function insertBatch($values) {

        if (!$values) {
            return;
        }

        $this->affectedRows = $rt = DB::table($this->param['table'])->insertOrIgnore($values);

        $this->_clear();

        return $rt;
    }

    public function updateBatch($values, $index) {

        $ids   = [];
        $final = [];

        foreach ($values as $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                if ($field !== $index) {
                    $final[$field][] = 'WHEN ' . $index . ' = ' . $val[$index] . ' THEN "' . addslashes($val[$field]) . '"';
                }
            }
        }

        $cases = '';

        foreach ($final as $k => $v) {
            $cases .= $k . " = CASE \n"
                . implode("\n", $v) . "\n"
                . 'ELSE ' . $k . ' END, ';
        }

        $where = ' WHERE ' .$index . ' IN(' . implode(',', $ids) . ')';

        $sql = 'UPDATE `'.$this->prefix.$this->param['table'].'` SET ' . substr($cases, 0, -2) . $where;

        $this->_clear();

        $this->affectedRows = DB::affectingStatement($sql);
    }

    public function delete() {

        if (!$this->param['is_get']) {
            $this->get();
        }

        if ($this->param['builder']) {
            $this->affectedRows = $this->param['builder']->delete();
        }

        $this->_clear();
    }

    public function emptyTable() {
        $this->delete();
    }

    public function setLastQuery($sql) {
        $this->query_sql = $sql;
    }

    public function getLastQuery() {
        return $this->query_sql;
    }

    public function truncate() {
        DB::table($this->param['table'])->truncate();
        $this->_clear();
    }

    public function tableExists($table) {

        if (!$table) {
            return false;
        }

        // 去掉多余前缀
        if ($this->prefix && strpos($table, $this->prefix) === 0) {
            $table = substr($table, strlen($this->prefix));
        }

        return Schema::hasTable($table);
    }

    public function fieldExists($name, $table) {

        if (!$table or !$name) {
            return false;
        }

        // 去掉多余前缀
        if ($this->prefix && strpos($table, $this->prefix) === 0) {
            $table = substr($table, strlen($this->prefix));
        }

        return Schema::hasColumn($table, $name);
    }

    public function getFieldNames($table) {

        if (!$table) {
            return [];
        }

        // 去掉多余前缀
        if ($this->prefix && strpos($table, $this->prefix) === 0) {
            $table = substr($table, strlen($this->prefix));
        }

        return Schema::getColumnListing($table);
    }

    private function _clear() {
        $this->param = [
            'table' => '',
            'select' => '',
            'select_sum' => '',
            'order' => '',
            'group' => '',
            'where' => [],
            'join' => [],
            'update' => [],
            'update_dec' => [],
            'update_inc' => [],
            'whereIn' => [],
            'builder' => null,
            'result' => null,
            'is_get' => false,
        ];
    }

}