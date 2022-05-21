<?php namespace Frame;

class Session {

    public function __construct() {

    }

    public function set($key, $value = null) {
        session(SYS_KEY.$key, $value);
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