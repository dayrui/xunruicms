<?php namespace Frame;

class Session {

    private $session;

    public function __construct() {
        $this->session = app('session');
    }

    public function set($key, $value = null) {
        $this->session->put(SYS_KEY.$key, $value);
        $this->session->save();
    }

    public function setTempdata($key, $value, $time)
    {
        $this->set(SYS_KEY.$key, (SYS_TIME+$time).'{xunruicms}'.$value);
    }

    public function getTempdata($key = null)
    {
        $value = $this->session->get(SYS_KEY.$key);
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
        return $this->session->get(SYS_KEY.$key);
    }

    public function remove($key)
    {
        $this->session->forget(SYS_KEY.$key);
        $this->session->save();
    }
}