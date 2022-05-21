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