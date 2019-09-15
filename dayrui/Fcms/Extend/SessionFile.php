<?php namespace Phpcmf\Extend;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 重写session文件存储类
 */

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Exceptions\SessionException;

/**
 * Session 文件存储类文件
 */
class SessionFile extends \CodeIgniter\Session\Handlers\BaseHandler implements \SessionHandlerInterface
{

    /**
     * 存储路径
     *
     * @var string
     */
    protected $savePath;

    /**
     * 文件路径
     *
     * @var resource
     */
    protected $filePath;

    //--------------------------------------------------------------------

    /**
     * 初始化
     *
     * @param BaseConfig $config
     * @param string     $ipAddress
     */
    public function __construct($config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        if (! empty($config->sessionSavePath))
        {
            $this->savePath = rtrim($config->sessionSavePath, '/\\');
            ini_set('session.save_path', $config->sessionSavePath);
        }
        else
        {
            $sessionPath = rtrim(ini_get('session.save_path'), '/\\');

            if (! $sessionPath)
            {
                $sessionPath = WRITEPATH . 'session';
            }

            $this->savePath = $sessionPath;
        }

        $this->configureSessionIDRegex();
    }

    //--------------------------------------------------------------------

    /**
     * 打开session
     *
     * 清除保存路径目录
     *
     * @param string $savePath Path to session files' directory
     * @param string $name     Session cookie name
     *
     * @return boolean
     * @throws \Exception
     */
    public function open($savePath, $name): bool
    {
        if (! is_dir($savePath))
        {
            if (! mkdir($savePath, 0700, true))
            {
                throw SessionException::forInvalidSavePath($this->savePath);
            }
        }
        elseif (! is_writable($savePath))
        {
            throw SessionException::forWriteProtectedSavePath($this->savePath);
        }

        $this->savePath = $savePath;
        $this->filePath = $this->savePath . '/ci' . $name; // ($this->matchIP ? md5($this->ipAddress) : '')

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * 读取值
     *
     * 读取会话数据并获取锁
     *
     * @param string $sessionID Session ID
     *
     * @return string    Serialized session data
     */
    public function read($sessionID): string
    {
        $session_data = '';
        if (is_file($this->filePath . $sessionID)) {
            $session_data = file_get_contents($this->filePath . $sessionID);
        }

        return $session_data;
    }

    //--------------------------------------------------------------------

    /**
     * 写入值
     *
     * Writes (create / update) session data
     *
     * @param string $sessionID   Session ID
     * @param string $sessionData Serialized session data
     *
     * @return boolean
     */
    public function write($sessionID, $sessionData): bool
    {
        // If the two IDs don't match, we have a session_regenerate_id() call
        // and we need to close the old handle and open a new one
        if ($sessionID !== $this->sessionID && (! $this->close() || $this->read($sessionID) === false))
        {
            return false;
        }

        file_put_contents($this->filePath . $sessionID, $sessionData, LOCK_EX);

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * 重置变量
     *
     * @return boolean
     */
    public function close(): bool
    {
        return true;
    }

    //--------------------------------------------------------------------

    /**
     * 销毁当前session
     *
     * @param string $session_id Session ID
     *
     * @return boolean
     */
    public function destroy($session_id): bool
    {
        if (is_file($this->filePath . $session_id)) {
            @unlink($this->filePath . $session_id);
        }

        $this->destroyCookie();
        clearstatcache();

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * 处理垃圾
     *
     * 删除过期的session文件
     *
     * @param integer $maxlifetime Maximum lifetime of sessions
     *
     * @return boolean
     */
    public function gc($maxlifetime): bool
    {
        if (! is_dir($this->savePath) || ($directory = opendir($this->savePath)) === false)
        {
            $this->logger->debug("Session: Garbage collector couldn't list files under directory '" . $this->savePath . "'.");

            return false;
        }

        $ts = time() - $maxlifetime;

        $pattern = sprintf(
            '#\A%s' . $this->sessionIDRegex . '\z#',
            preg_quote($this->cookieName)
        );

        while (($file = readdir($directory)) !== false)
        {
            // If the filename doesn't match this pattern, it's either not a session file or is not ours
            if (! preg_match($pattern, $file)
                || ! is_file($this->savePath . DIRECTORY_SEPARATOR . $file)
                || ($mtime = filemtime($this->savePath . DIRECTORY_SEPARATOR . $file)) === false
                || $mtime > $ts
            )
            {
                continue;
            }

            unlink($this->savePath . DIRECTORY_SEPARATOR . $file);
        }

        closedir($directory);

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * session ID 生成 正则表达式
     */
    protected function configureSessionIDRegex()
    {
        $bitsPerCharacter = (int)ini_get('session.sid_bits_per_character');
        $SIDLength        = (int)ini_get('session.sid_length');

        if (($bits = $SIDLength * $bitsPerCharacter) < 160)
        {
            // Add as many more characters as necessary to reach at least 160 bits
            $SIDLength += (int)ceil((160 % $bits) / $bitsPerCharacter);
            ini_set('session.sid_length', $SIDLength);
        }

        // Yes, 4,5,6 are the only known possible values as of 2016-10-27
        switch ($bitsPerCharacter)
        {
            case 4:
                $this->sessionIDRegex = '[0-9a-f]';
                break;
            case 5:
                $this->sessionIDRegex = '[0-9a-v]';
                break;
            case 6:
                $this->sessionIDRegex = '[0-9a-zA-Z,-]';
                break;
        }

        $this->sessionIDRegex .= '{' . $SIDLength . '}';
    }
}
