<?php namespace Frame;


// 控制器引导类
abstract class Controller  {

}

use think\facade\Cookie;

function set_cookie($name, $value = '', $expire = '') {
    Cookie::set($name, $value, $expire);
}

function get_cookie($name) {
    return Cookie::get($name);
}