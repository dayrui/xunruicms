<?php namespace Frame;

// 数据库引导类
class Model {

    static private $db;

    static function _load_db() {

        // 数据库
        if (self::$db) {
            return [self::$db, self::$db->DBPrefix];
        }

        self::$db = \Config\Database::connect('default');

        return [self::$db, self::$db->DBPrefix];
    }

}