<?php namespace Phpcmf\Library;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Input {

    private $ip_address;

    // get post解析
    public function request($name, $xss = true) {
        $value = isset($_REQUEST[$name]) ? $_REQUEST[$name] : (isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : false));
        return $xss ? $this->xss_clean($value) : $value;
    }
    
    // post解析
    public function post($name, $xss = true) {
        $value = isset($_POST[$name]) ? $_POST[$name] : false;
        return $xss ? $this->xss_clean($value) : $value;
    }

    // get解析
    public function get($name = '', $xss = true) {
        $value = !$name ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : false);
        return $xss ? $this->xss_clean($value) : $value;
    }

    // 通过post格式化ids
    public function get_post_ids($name = 'ids') {

        $in = [];
        $ids = self::post($name);
        if (!$ids) {
            return $in;
        }

        foreach ($ids as $i) {
            $i && $in[] = (int)$i;
        }

        return $in;
    }
    
    public function set_cookie($name, $value = '', $expire = '') {
        // 部分虚拟主机会报500错误
        \Config\Services::response()->removeHeader('Content-Type');
        \Config\Services::response()->setcookie(md5(SYS_KEY).'_'.dr_safe_replace($name), $value, $expire)->send();
    }
    
    public function get_cookie($name) {
        $name = md5(SYS_KEY).'_'.dr_safe_replace($name);
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    // 获取访客ip地址
    public function ip_address() {

        if ($this->ip_address) {
            return $this->ip_address;
        }

        if (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR', true)) {
            $client_ip = getenv('REMOTE_ADDR', true);
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // 验证规范
        if (!preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $client_ip)) {
            $client_ip = '';
        }

        $this->ip_address = $client_ip ? $client_ip : \Config\Services::request(null, true)->getIPAddress();
        $this->ip_address = str_replace([",", '(', ')', ',', chr(13), PHP_EOL], '', $this->ip_address);
        $this->ip_address = trim($this->ip_address);

        return $this->ip_address;
    }
    
    // ip转为实际地址
    public function ip2address($ip) {
        return \Phpcmf\Service::L('ip')->address($ip);
    }

    // 当前ip实际地址
    public function ip_address_info() {
       return \Phpcmf\Service::L('ip')->address($this->ip_address());
    }
	
	// 安全过滤
	public function get_user_agent() {
		return \Phpcmf\Service::L('Security')->xss_clean($_SERVER['HTTP_USER_AGENT']);
	}

    /**
     * 后台日志
     */
    public function system_log($action, $insert = 0) {

        if (!$insert && (!SYS_ADMIN_LOG || !IS_ADMIN)) {
            // 是否开启日志
            return NULL;
        }

        $data = [
            'ip' => $this->ip_address(),
            'uid' => (int)\Phpcmf\Service::C()->admin['uid'],
            'time' => SYS_TIME,
            'action' => addslashes(dr_safe_replace($action)),
            'username' => \Phpcmf\Service::C()->admin['username'] ? \Phpcmf\Service::C()->admin['username'] : '未登录',
        ];

        $path = WRITEPATH.'log/'.date('Ym', SYS_TIME).'/';
        $file = $path.date('d', SYS_TIME).'.php';
        if (!is_dir($path)) {
            dr_mkdirs($path);
        }

        file_put_contents($file, PHP_EOL.dr_array2string($data), FILE_APPEND);
    }

    // 服务器ip地址
    public function server_ip() {

        if (isset($_SERVER['SERVER_ADDR'])
            && $_SERVER['SERVER_ADDR']
            && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }

        return gethostbyname($_SERVER['HTTP_HOST']);
    }

    // 后台分页
    public function page($url, $total, $dir = '') {

        $config = require CMSPATH.'Config/Apage.php';

        $config['base_url'] = $url.'&page={page}';
        $config['per_page'] = SYS_ADMIN_PAGESIZE;
        $config['total_rows'] = $total;

        return \Phpcmf\Service::L('page')->initialize($config)->create_links();
    }

    // Ftable分页
    public function table_page($url, $total, $config, $size) {

        $config['base_url'] = $url;
        $config['per_page'] = $size;
        $config['total_rows'] = $total;

        return \Phpcmf\Service::L('page')->initialize($config)->create_links();
    }


    /**
     * XSS Clean
     */
    public function xss_clean($str, $is_image = FALSE) {
        return \Phpcmf\Service::L('Security')->xss_clean($str, $is_image);
    }

}