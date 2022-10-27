<?php namespace Config;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array
     */
    public $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array
     */
    public $globals = [
        'before' => [
            // 'honeypot',
            // 'csrf',
        ],
        'after'  => [
            'toolbar',
            // 'honeypot',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['csrf', 'throttle']
     *
     * @var array
     */
    public $methods = [
        'post' => []
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array
     */
    public $filters = [];

    public function __construct()
    {
        parent::__construct();
        if (defined('SYS_CSRF') && SYS_CSRF) {
            if (SYS_CSRF == 2) {
                $this->methods['post'] = ['csrf'];
            } elseif (SYS_CSRF == 1 && !IS_ADMIN) {
                $this->methods['post'] = ['csrf'];
            }
            // 后台登录关闭跨站验证
            if (IS_ADMIN && in_array(\Phpcmf\Service::L('router')->uri(), ['login/index'])) {
                $this->methods['post'] = [];
            } elseif (IS_ADMIN && \Phpcmf\Service::L('router')->class == 'cloud') {
                $this->methods['post'] = [];
            }
        }

        if (IS_ADMIN && (IS_DEV || CI_DEBUG)) {
            // 调试模式下关闭
            $this->methods['post'] = [];
        } elseif (defined('IS_API') && IS_API) {
            $this->methods['post'] = [];
        } elseif (isset($_GET['appid']) && is_file(dr_get_app_dir('httpapi').'/install.lock')) {
            $this->methods['post'] = [];
        } elseif (APP_DIR == 'weixin') {
            $this->methods['post'] = [];
        } elseif (defined('IS_INSTALL') || defined('IS_NOT_CSRF')) {
            $this->methods['post'] = [];
        }
    }
}
