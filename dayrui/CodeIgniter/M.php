<?php namespace Frame;

// 数据库引导类
class Model {

    public $db;
    public $prefix;

    public function _load_db() {
        // 数据库
        $this->db = \Config\Database::connect('default');
        $this->prefix = $this->db->DBPrefix;
    }

}