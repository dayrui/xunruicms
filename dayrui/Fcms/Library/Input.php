<?php namespace Phpcmf\Library;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Input {

    protected $ip_address;

    // get post解析
    public function request($name, $xss = true) {
        $value = isset($_REQUEST[$name]) ? $_REQUEST[$name] : (isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : false));
        return $xss ? $this->xss_clean($value) : $value;
    }
    
    // post解析
    public function post($name, $xss = true) {
        $value = isset($_POST[$name]) ? $_POST[$name] : false;
        return $xss ? $this->xss_clean($value, true) : $value;
    }

    // get解析
    public function get($name = '', $xss = true) {
        $value = !$name ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : false);
        return $xss ? $this->xss_clean($value, true) : $value;
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
        return \Frame\set_cookie($name, $value, $expire);
    }
    
    public function get_cookie($name) {
        return \Frame\get_cookie($name);
    }

    // inputip存储信息
    public function ip_info() {
        return $this->ip_address().'-'.(int)$_SERVER['REMOTE_PORT'];
    }

    // 获取访客ip地址
    public function ip_address() {

        if ($this->ip_address) {
            return $this->ip_address;
        }

        if (defined('IS_CDN_IP') && IS_CDN_IP && getenv(IS_CDN_IP)) {
            $client_ip = getenv(IS_CDN_IP);
        } elseif (getenv('HTTP_TRUE_CLIENT_IP')) {
            $client_ip = getenv('HTTP_TRUE_CLIENT_IP');
        } elseif (getenv('HTTP_ALI_CDN_REAL_IP')) {
            $client_ip = getenv('HTTP_ALI_CDN_REAL_IP');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR', true)) {
            $client_ip = getenv('REMOTE_ADDR', true);
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }

        if ($client_ip && strpos($client_ip, ',') !== false) {
            $client_ip = trim(explode(',', $client_ip)[0]);
        }

        // 验证规范
        if (!$this->is_ip($client_ip)) {
            $client_ip = '';
        }

        $this->ip_address = (string)$client_ip;
        $this->ip_address = str_replace([",", '(', ')', ',', chr(13), PHP_EOL], '', $this->ip_address);
        $this->ip_address = trim($this->ip_address);

        return $this->ip_address;
    }

    /**
     * 检测是否是合法的IP地址
     */
    public function is_ip($ip, $type = '') {

        switch (strtolower($type)) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = 0;
                break;
        }

        return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
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
		return dr_safe_replace(str_replace(['"', "'", '<', '>'], '', \Phpcmf\Service::L('Security')->xss_clean((string)$_SERVER['HTTP_USER_AGENT'], true)));
	}

    /**
     * 系统错误日志
     */
    public function log($level, $message, $context = []) {

        $message = $this->interpolate($message, $context);

        if (! is_string($message)) {
            $message = print_r($message, true);
        }

        $message = strtoupper($level) . ' - '.date('Y-m-d H:i:s'). ' --> '.$message;

        $file = WRITEPATH . 'error/log-'.date('Y-m-d').'.php';
        if (!is_file($file)) {
            file_put_contents($file, "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');?>".PHP_EOL.$message);
        } else {
            file_put_contents($file, $message.PHP_EOL, FILE_APPEND);
        }

        return true;
    }
    /**
     * Replaces any placeholders in the message with variables
     * from the context, as well as a few special items like:
     *
     * {session_vars}
     * {post_vars}
     * {get_vars}
     * {env}
     * {env:foo}
     * {file}
     * {line}
     *
     * @param mixed $message
     *
     * @return mixed
     */
    protected function interpolate($message, array $context = [])
    {
        if (! is_string($message)) {
            return $message;
        }

        // build a replacement array with braces around the context keys
        $replace = [];

        foreach ($context as $key => $val) {
            // Verify that the 'exception' key is actually an exception
            // or error, both of which implement the 'Throwable' interface.
            if ($key === 'exception' && $val instanceof Throwable) {
                $val = $val->getMessage() . ' ' . $val->getFile() . ':' . $val->getLine();
            }

            // todo - sanitize input before writing to file?
            $replace['{' . $key . '}'] = $val;
        }

        // Add special placeholders
        $replace['{post_vars}'] = '$_POST: ' . print_r($_POST, true);
        $replace['{get_vars}']  = '$_GET: ' . print_r($_GET, true);

        // Allow us to log the file/line that we are logging from
        if (strpos($message, '{file}') !== false) {
            [$file, $line] = $this->determineFile();

            $replace['{file}'] = $file;
            $replace['{line}'] = $line;
        }

        // Match up environment variables in {env:foo} tags.
        if (strpos($message, 'env:') !== false) {
            preg_match('/env:[^}]+/', $message, $matches);

            if ($matches) {
                foreach ($matches as $str) {
                    $key                 = str_replace('env:', '', $str);
                    $replace["{{$str}}"] = $_ENV[$key] ?? 'n/a';
                }
            }
        }

        if (isset($_SESSION)) {
            $replace['{session_vars}'] = '$_SESSION: ' . print_r($_SESSION, true);
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Determines the file and line that the logging call
     * was made from by analyzing the backtrace.
     * Find the earliest stack frame that is part of our logging system.
     */
    public function determineFile(): array
    {
        $logFunctions = [
            'log_message',
            'log',
            'error',
            'debug',
            'info',
            'warning',
            'critical',
            'emergency',
            'alert',
            'notice',
        ];

        // Generate Backtrace info
        $trace = \debug_backtrace(0);

        // So we search from the bottom (earliest) of the stack frames
        $stackFrames = \array_reverse($trace);

        // Find the first reference to a Logger class method
        foreach ($stackFrames as $frame) {
            if (\in_array($frame['function'], $logFunctions, true)) {
                $file = isset($frame['file']) ? ($frame['file']) : 'unknown';
                $line = $frame['line'] ?? 'unknown';

                return [
                    $file,
                    $line,
                ];
            }
        }

        return [
            'unknown',
            'unknown',
        ];
    }

    /**
     * 后台日志
     */
    public function system_log($action, $insert = 0, $param = [], $username = '') {

        if (!$insert && (!SYS_ADMIN_LOG || !IS_ADMIN)) {
            // 是否开启日志
            return NULL;
        }

        $data = [
            'ip' => $this->ip_address(),
            'uid' => (int)\Phpcmf\Service::C()->admin['uid'],
            'url' => dr_safe_url(FC_NOW_URL),
            'time' => SYS_TIME,
            'param' => $param,
            'action' => addslashes(dr_safe_replace($action)),
            'username' => $username ? $username : (\Phpcmf\Service::C()->admin['username'] ? \Phpcmf\Service::C()->admin['username'] : '未登录'),
        ];

        $path = WRITEPATH.'log/'.date('Ym', SYS_TIME).'/';
        $file = $path.date('d', SYS_TIME).'.php';
        if (!is_dir($path)) {
            dr_mkdirs($path);
        }

        if (!is_file($file)) {
            file_put_contents($file, "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');?>".PHP_EOL.dr_array2string($data));
        } else {
            file_put_contents($file, PHP_EOL.dr_array2string($data), FILE_APPEND);
        }
    }

    /**
     * 密码错误日志
     */
    public function password_log($post) {

        $data = [
            'ip' => $this->ip_address(),
            'time' => SYS_TIME,
            'username' => $post['username'],
        ];

        $file = WRITEPATH.'password_log.php';

        if (!is_file($file)) {
            file_put_contents($file, "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');?>".PHP_EOL.dr_array2string($data));
        } else {
            file_put_contents($file, PHP_EOL.dr_array2string($data), FILE_APPEND);
        }
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
    public function xss_clean($str, $is = FALSE) {
        return \Phpcmf\Service::L('Security')->xss_clean($str, $is);
    }

}