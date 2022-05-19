<?php namespace Frame;

// 公共类
abstract class Controller extends \CodeIgniter\Controller {

}


function set_cookie($name, $value = '', $expire = '') {
    // 部分虚拟主机会报500错误
    \Config\Services::response()->removeHeader('Content-Type');
    \Config\Services::response()->setcookie(md5(SYS_KEY).'_'.dr_safe_replace($name), (string)$value, $expire)->send();
}

function get_cookie($name) {
    $name = md5(SYS_KEY).'_'.dr_safe_replace($name);
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
}