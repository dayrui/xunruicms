<?php namespace Frame;

use think\facade\Cache as Fcache;

class Cache {

    public function save($key, $value, $time = 3600) {
        Fcache::set(SYS_KEY.$key, $value, $time);
    }

    public function get($key)
    {
        return Fcache::get(SYS_KEY.$key);
    }

    public function delete($key)
    {
        Fcache::delete(SYS_KEY.$key);
    }

    public function clean()
    {
        Fcache::clear();
    }
}