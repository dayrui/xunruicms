<?php namespace Frame;


// 控制器引导类
abstract class Controller  {

}

use think\facade\Cookie;

function set_cookie($name, $value = '', $expire = '') {
    Cookie::set(SYS_KEY.$name, $value, $expire);
    Cookie::save();
}

function get_cookie($name) {
    return Cookie::get(SYS_KEY.$name);
}