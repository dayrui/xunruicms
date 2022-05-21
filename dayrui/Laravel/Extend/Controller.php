<?php namespace Frame;

// 控制器引导类
abstract class Controller extends \Illuminate\Routing\Controller {

}

function set_cookie($name, $value = '', $expire = '') {
    response('')->cookie(SYS_KEY.$name, $value, $expire)->sendHeaders();
}

function get_cookie($name) {
    return \Illuminate\Support\Facades\Cookie::get(SYS_KEY.$name);;
}