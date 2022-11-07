<?php namespace Frame;

class Session {

    private $session;

    public function __construct() {
        $this->session = \Config\Services::session();
    }

    public function set($key, $value = null) {
        $this->session->set(SYS_KEY.$key, $value);
    }

    public function setTempdata($key, $value, $time)
    {
        $this->session->setTempdata(SYS_KEY . $key, $value, $time);
    }

    public function getTempdata($key = null)
    {
        return $this->session->getTempdata(SYS_KEY . $key);
    }

    public function get($key = null)
    {
        return $this->session->get(SYS_KEY.$key);
    }

    public function remove($key)
    {
        $this->session->remove(SYS_KEY.$key);
    }
}