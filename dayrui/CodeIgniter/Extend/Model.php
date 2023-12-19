<?php namespace Frame;

// 数据库引导类
class Model {

    static private $db;
    static private $dbs = [];

    static function _load_db() {

        // 数据库
        if (self::$db) {
            return [self::$db, self::$db->DBPrefix];
        }

        self::$db = \Config\Database::connect('default');

        return [self::$db, self::$db->DBPrefix];
    }

    static function _load_db_source($name) {

        // 数据库
        if (isset(self::$dbs[$name]) && self::$dbs[$name]) {
            return [self::$dbs[$name], self::$dbs[$name]->DBPrefix];
        }

        $dbConfig = config(\Config\Database::class);
        $dbName = $dbConfig->get_group($name);
        if ($dbName != $name) {
            log_message('error', '数据源（'.$name.'）加载失败');
        }
        self::$dbs[$name] = \Config\Database::connect($dbName,  false);

        return [self::$dbs[$name], self::$dbs[$name]->DBPrefix];
    }

}