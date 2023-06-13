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
        session(SYS_KEY.$key, (SYS_TIME+$time).'{xunruicms}'.$value);
        \think\facade\Session::save();
    }

    public function getTempdata($key = null)
    {
        $value = session(SYS_KEY.$key);
        if ($value) {
            list($time, $value) = explode('{xunruicms}', $value);
            if (SYS_TIME > $time) {
                $this->remove($key);
                return NULL;
            }
        }
        return $value;
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