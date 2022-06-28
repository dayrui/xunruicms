<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{

	public $baseURL = FC_NOW_HOST;

	public $indexPage = SELF;

	public $uriProtocol = 'REQUEST_URI';

	public $defaultLocale = 'zh-cn';

	public $negotiateLocale = false;

	public $supportedLocales = ['en'];

	public $appTimezone = 'PRC';

	public $charset = 'UTF-8';

	public $forceGlobalSecureRequests = false;


	public $sessionDriver            = 'CodeIgniter\Session\Handlers\FileHandler';
	public $sessionCookieName        = 'xunruicms';
	public $sessionExpiration        = 7200;
	public $sessionSavePath          = WRITEPATH . 'session';
	public $sessionMatchIP           = false;
	public $sessionTimeToUpdate      = 300;
	public $sessionRegenerateDestroy = false;


	public $cookiePrefix   = '';
	public $cookieDomain   = '';
	public $cookiePath     = '/';
	public $cookieSecure   = false;
	public $cookieHTTPOnly = true;
    public $cookieSameSite = 'Lax';


	public $proxyIPs = '';

	public $CSRFTokenName  = 'csrf_test_name';
	public $CSRFCookieName = 'csrf_cookie_name';
	public $CSRFHeaderName = 'X-CSRF-TOKEN';
	public $CSRFExpire     = 7200;
	public $CSRFRegenerate = false;
	public $CSRFRedirect   = true;
    public $CSRFSameSite = 'Lax';

	public $CSPEnabled = false;


	public $salt = SYS_KEY;

	//--------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->cookieSecure = strpos(FC_NOW_URL, 'https://') === 0 ? true : false;
        $this->sessionCookieName = 'xunruicms_'.md5(SYS_KEY);
    }
}
