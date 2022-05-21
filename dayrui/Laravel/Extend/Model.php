<?php namespace Frame;

// 数据库引导类
class Model {

    public $db;
    public $prefix;

    public function _load_db() {
        // 数据库
        $this->db = new db_mysql();
        $this->db->prefix = $this->prefix = defined('XR_DB_PREFIX') ? XR_DB_PREFIX : '';
    }

}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class db_mysql {

    public $query_sql;
    public $param = [];
    public $prefix;
    public $likeEscapeChar = '!';

    public function query($sql) {
        $this->_clear();
        $this->param['result'] = DB::select($sql);
        return $this;
    }

    public function simpleQuery($sql) {
        $this->_clear();
        DB::statement($sql);
        return $this;
    }

    public function error() {

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
            $builder->select(DB::raw($this->param['select']));
        }

        if ($this->param['where']) {
            foreach ($this->param['where'] as $v) {
                dr_count($v) == 2 ? $builder->where($v[0], $v[1]) : $builder->whereRaw($v);
            }
        }

        if ($this->param['whereIn']) {
            foreach ($this->param['whereIn'] as $v) {
                dr_count($v) == 2 ? $builder->whereIn($v[0], $v[1]) : $builder->whereRaw($v);
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

    public function getRowArray() {

        $rt = [];
        if ($this->param['result']) {
            $rt = dr_object2array($this->param['result']);
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

    public function whereIn($where, $value = '') {

        if (dr_strlen($value)) {
            $this->param['whereIn'][] = [$where, $value];
        } else {
            $this->param['whereIn'][] = $where;
        }

        return $this;
    }

    public function table($name) {
        $this->_clear();
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

    public function insert($data) {
        DB::table($this->param['table'])->insert($data);
        $this->_clear();
    }

    public function replace($data) {
        DB::table($this->param['table'])->upsert($data, 'id');
        $this->_clear();
    }

    public function set($key, $value) {
        $this->param['update'][$key] = $value;
        return $this;
    }

    public function update($data = []) {

        if (!$this->param['is_get']) {
            $this->get();
        }

        if ($this->param['builder']) {
            if ($this->param['update']) {
                foreach ($this->param['update'] as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data && $this->param['builder']->update($data);
        }

        $this->_clear();
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

    public function updateBatch($values, $index) {

        $ids   = [];
        $final = [];

        foreach ($values as $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                if ($field !== $index) {
                    $final[$field][] = 'WHEN ' . $index . ' = ' . $val[$index] . ' THEN "' . $val[$field] . '"';
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

        DB::statement($sql);
    }

    public function delete() {

        if (!$this->param['is_get']) {
            $this->get();
        }

        if ($this->param['builder']) {
            $this->param['builder']->delete();
        }

        $this->_clear();
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

        return Schema::hasTable($table);
    }

    public function fieldExists($name, $table) {

        if (!$table or !$name) {
            return false;
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
            'order' => '',
            'group' => '',
            'where' => [],
            'update' => [],
            'whereIn' => [],
            'builder' => null,
            'result' => null,
            'is_get' => false,
        ];
    }

}