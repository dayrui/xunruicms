<?php namespace Frame;


// 控制器引导类
abstract class Controller  {

}



function set_cookie($name, $value = '', $expire = '')
{
    if ($name === '' || headers_sent()) {
        return false;
    }

    // 计算过期时间戳
    if ($expire === '' || $expire === null) {
        $ts = 0; // 会话期
    } elseif (is_numeric($expire)) {
        $expire = (int) $expire;
        // <= 10 年视为相对秒，否则视为绝对时间戳
        $ts = ($expire > 0 && $expire <= 315576000) ? time() + $expire : $expire;
    } elseif ($expire instanceof DateTimeInterface) {
        $ts = $expire->getTimestamp();
    } else {
        $ts = strtotime((string) $expire) ?: 0;
    }

    // 使用原生 setcookie（PHP 7.3+ 支持 options 数组）
    return setcookie($name, (string) $value, [
        'expires'  => $ts,
        'path'     => '/',
        'domain'   => '',         // 需要时可填写你的域名
        'secure'   => false,      // HTTPS 环境可设为 true
        'httponly' => true,       // 防止 JS 读取
        'samesite' => 'Lax',      // 可改为 'Strict' 或 'None'（None 需 secure=true）
    ]);
}

function get_cookie($name)
{
    if ($name === '') {
        return null;
    }
    return array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : null;
}