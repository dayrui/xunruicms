<?php namespace Frame;

use Illuminate\Support\Facades\Cache as Fcache;

class Cache {

    public function save($key, $value, $time = 3600) {
        Fcache::put(SYS_KEY.$key, $value, $time);
    }

    public function get($key)
    {
        return Fcache::get(SYS_KEY.$key);
    }

    public function delete($key)
    {
        Fcache::forget(SYS_KEY.$key);
    }

    public function clean()
    {
        Fcache::flush();
    }
}