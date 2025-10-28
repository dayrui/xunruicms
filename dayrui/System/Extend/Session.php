<?php namespace Frame;

class Session
{
    private $sessionStarted = false;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // 配置可按需调整
            if (!headers_sent()) {
                // 避免多处输出后再启动报错
                ini_set('session.use_strict_mode', '1');
                ini_set('session.cookie_httponly', '1');
                // 如需跨域或HTTPS，可在外部统一设置
            }
            @session_start();
        }
        $this->sessionStarted = (session_status() === PHP_SESSION_ACTIVE);

        if (!isset($_SESSION['_tempdata']) || !is_array($_SESSION['_tempdata'])) {
            $_SESSION['_tempdata'] = [];
        }

        $this->cleanupExpiredTempdata();
    }

    // 设置常规会话数据（支持数组批量，或单个键值）
    public function set($key, $value = null)
    {
        if (!$this->sessionStarted) {
            return false;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return true;
        }

        $_SESSION[$key] = $value;
        return true;
    }

    // 设置临时数据（带过期时间，单位秒）
    public function setTempdata($key, $value, $time)
    {
        if (!$this->sessionStarted) {
            return false;
        }

        $expire = time() + max(0, (int) $time);
        $_SESSION['_tempdata'][$key] = [
            'value' => $value,
            'expire' => $expire,
        ];
        return true;
    }

    // 获取临时数据（$key 为 null 返回所有未过期临时数据）
    // 注意：读取不会删除；如需一次性使用，可调用 getTempdataOnce
    public function getTempdata($key = null)
    {
        if (!$this->sessionStarted) {
            return null;
        }

        $this->cleanupExpiredTempdata();

        if ($key === null) {
            $result = [];
            foreach ($_SESSION['_tempdata'] as $k => $item) {
                $result[$k] = $item['value'];
            }
            return $result;
        }

        return isset($_SESSION['_tempdata'][$key]) ? $_SESSION['_tempdata'][$key]['value'] : null;
    }

    // 一次性获取临时数据（读取后即删除）
    public function getTempdataOnce($key)
    {
        if (!$this->sessionStarted) {
            return null;
        }

        $this->cleanupExpiredTempdata();

        if (!isset($_SESSION['_tempdata'][$key])) {
            return null;
        }

        $value = $_SESSION['_tempdata'][$key]['value'];
        unset($_SESSION['_tempdata'][$key]);
        return $value;
    }

    // 获取常规会话数据（$key 为 null 返回所有）
    public function get($key = null)
    {
        if (!$this->sessionStarted) {
            return null;
        }

        if ($key === null) {
            $data = $_SESSION;
            unset($data['_tempdata']);
            return $data;
        }

        return $_SESSION[$key] ?? null;
    }

    // 移除常规会话数据（支持数组）
    public function remove($key)
    {
        if (!$this->sessionStarted) {
            return false;
        }

        if (is_array($key)) {
            foreach ($key as $k) {
                if (array_key_exists($k, $_SESSION)) {
                    unset($_SESSION[$k]);
                }
            }
            return true;
        }

        if (array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
            return true;
        }

        return false;
    }

    // 销毁会话（可选）
    public function destroy()
    {
        if (!$this->sessionStarted) {
            return false;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->sessionStarted = false;
        return true;
    }

    // 获取 Session ID（可选）
    public function getId()
    {
        return $this->sessionStarted ? session_id() : null;
    }

    // 私有：清理过期临时数据
    private function cleanupExpiredTempdata()
    {
        if (empty($_SESSION['_tempdata']) || !is_array($_SESSION['_tempdata'])) {
            $_SESSION['_tempdata'] = [];
            return;
        }

        $now = time();
        foreach ($_SESSION['_tempdata'] as $k => $item) {
            if (!isset($item['expire']) || $item['expire'] <= $now) {
                unset($_SESSION['_tempdata'][$k]);
            }
        }
    }
}