<?php namespace Frame;

class Session {

    public function __construct() {

    }

    public function set($key, $value = null) {
        session(SYS_KEY.$key, $value);
        \think\facade\Session::save();
    }

    public function setTempdata($key, $value, $time)
    {
        session(SYS_KEY.$key, $value);
    }

    public function getTempdata($key = null)
    {
        return session(SYS_KEY.$key);
    }

    public function get($key = null)
    {
        return session(SYS_KEY.$key);
    }

    public function remove($key)
    {
        session(SYS_KEY.$key, null);
    }
}