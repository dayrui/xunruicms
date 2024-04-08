<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\FileHandler;

class App extends BaseConfig
{

	public string $baseURL = FC_NOW_HOST;
    public array $allowedHostnames = [];

	public string $indexPage = SELF;

	public string $uriProtocol = 'REQUEST_URI';

	public string $defaultLocale = 'zh-cn';

    public bool $negotiateLocale = false;

	public array $supportedLocales = ['en'];

	public string $appTimezone = 'PRC';

	public string $charset = 'UTF-8';

	public bool $forceGlobalSecureRequests = false;

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';
    public string $sessionDriver = FileHandler::class;

	public string $sessionCookieName = 'xunruicms';
    public int $sessionExpiration = 7200;
    public string $sessionSavePath = WRITEPATH . 'session';
    public bool $sessionMatchIP = false;
    public int $sessionTimeToUpdate = 300;
    public bool $sessionRegenerateDestroy = false;

    public ?string $sessionDBGroup = null;

    public string $cookiePrefix = '';
    public string $cookieDomain = '';
	public string $cookiePath     = '/';
	public bool $cookieSecure   = false;
	public bool $cookieHTTPOnly = true;
    public ?string $cookieSameSite = 'Lax';


    public array $proxyIPs = [];

    public string $CSRFTokenName = 'csrf_test_name';
	public string $CSRFCookieName = 'csrf_cookie_name';
	public string $CSRFHeaderName = 'X-CSRF-TOKEN';
	public int $CSRFExpire = 7200;
	public bool $CSRFRegenerate = false;
	public bool $CSRFRedirect   = true;
    public string $CSRFSameSite = 'Lax';

	public bool $CSPEnabled = false;

	public $salt = SYS_KEY;

	//--------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->cookieSecure = strpos(FC_NOW_URL, 'https://') === 0 ? true : false;
        $this->sessionCookieName = 'xunruicms_'.md5(SYS_KEY);
    }
}
