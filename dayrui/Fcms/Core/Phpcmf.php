<?php namespace Phpcmf;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

require FRAMEPATH.'Extend/Controller.php';

// 公共类
abstract class Common extends \Frame\Controller {

    private static $instance;

    private $load_init = [];
    private $is_load_init_run = false;

    public $uid; // 登录uid
    public $admin; // 管理员属性
    public $member; // 用户属性
    public $module; // 模块属性
    public $weixin; // 微信属性
    public $loadjs; // 预定义变量
    public $member_cache; // 用户配置缓存

    public $site; // 网站id信息
    public $site_info; // 网站配置信息
    public $site_domain; // 全部站点域名
    public $is_hcategory; // 模块不使用栏目

    public $session; // 网站session对象
    public $is_mobile; // 是否移动端
    public $temp = []; // 临时数据存储

    public $content_model; // 模块内容对象

    protected $is_module_init; // 防止模块重复初始化
    protected $cmf_version; // 版本信息
    protected $cmf_license; // 版本信息

    /**
     * 兼容动态属性
     *
    private array $properties = [];
    public function __set(string $name, mixed $value) {
        $this->properties[$name] = $value;
    }
    public function __get(string $name)
    {
        return $this->properties[$name];
    }*/

    /**
     * 初始化共享控制器
     */
    public function __construct()
    {
        //parent::initController(...$params);

        // 部分虚拟主机会报500错误
        //\Config\Services::response()->removeHeader('Content-Type');

        self::$instance =& $this;

        if (defined('IS_INSTALL')) {
            // 安装程序不执行他
            return;
        } elseif (defined('IS_COMMON')) {
            // 防止重复实例化
            return $this;
        }

        define('IS_COMMON', 1);

        // 站点配置
        if (is_file(WRITEPATH.'config/site.php')) {
            $this->site_info = require WRITEPATH.'config/site.php';
            foreach ($this->site_info as $id => $t) {
                !$t['SITE_DOMAIN'] && $t['SITE_DOMAIN'] = DOMAIN_NAME;
                $this->site[$id] = $id;
                $this->site_info[$id] = $t;
                $this->site_info[$id]['SITE_ID'] = $id;
                $this->site_info[$id]['SITE_URL'] = dr_http_prefix($t['SITE_DOMAIN'].'/');
                $this->site_info[$id]['SITE_MURL'] = dr_http_prefix(($t['SITE_MOBILE'] ? $t['SITE_MOBILE'] : $t['SITE_DOMAIN']).'/');
                $this->site_info[$id]['SITE_IS_MOBILE'] = $t['SITE_MOBILE'] ? (strpos($t['SITE_MOBILE'], '/') ? 2 : 1) : 0;
            }
            define('IS_SITES', count($this->site_info) > 1 ? 1 : 0);
        } else {
            $this->site_info[1] = [
                'SITE_ID' => 1,
                'SITE_URL' => dr_http_prefix(DOMAIN_NAME.'/'),
                'SITE_MURL' => dr_http_prefix(DOMAIN_NAME.'/'),
            ];
            define('IS_SITES', 0);
        }

        // 版本
        if (!is_file(MYPATH.'Config/Version.php')) {
            $this->cmf_version = [
                'id' => 8,
                'name' => 'XunRuiCMS',
                'version' => 'dev',
                'downtime' => SYS_TIME,
                'updatetime' => '--',
            ];
        } else {
            $this->cmf_version = require MYPATH.'Config/Version.php';
        }
        define('CMF_NAME', $this->cmf_version['name']);
        define('CMF_VERSION', $this->cmf_version['version']);

        // 版本更新时间字符串
        define('CMF_UPDATE_TIME', IS_XRDEV ? SYS_TIME : str_replace(['-', ' ', ':'], '', file_get_contents(WRITEPATH.'install.lock')));

        // 站点id
        !defined('SITE_ID') && define('SITE_ID', 1);

        // 项目站点不存在
        if (!isset($this->site_info[SITE_ID]) || !$this->site_info[SITE_ID]) {
            if (IS_DEV) {
                dr_show_error(dr_lang('项目【%s】配置文件不存在，请检查cache/config/site.php文件数据是否完整', SITE_ID));
            } else {
                dr_show_error(dr_lang('项目配置文件不存在'));
            }
        }

        // 站点共享变量
        define('SITE_URL', $this->site_info[SITE_ID]['SITE_URL']);
        define('SITE_MURL', $this->site_info[SITE_ID]['SITE_MURL']);
        define('SITE_NAME', $this->site_info[SITE_ID]['SITE_NAME']);
        define('SITE_LOGO', $this->site_info[SITE_ID]['SITE_LOGO']);
        define('SITE_IS_MOBILE', $this->site_info[SITE_ID]['SITE_IS_MOBILE']); // 是否存在移动端
        define('SITE_IS_MOBILE_HTML', (int)$this->site_info[SITE_ID]['SITE_IS_MOBILE_HTML']);
        define('SITE_MOBILE_DIR', $this->site_info[SITE_ID]['SITE_MOBILE_DIR']); // 移动端目录
        define('SITE_MOBILE_NOT_PAD', (int)$this->site_info[SITE_ID]['SITE_MOBILE_NOT_PAD']); // pad不归类为移动端
        define('SITE_THUMB_WATERMARK', $this->site_info[SITE_ID]['SITE_THUMB_WATERMARK']);
        define('SITE_THEME', dr_strlen($this->site_info[SITE_ID]['SITE_THEME']) ? $this->site_info[SITE_ID]['SITE_THEME'] : 'default');
        define('SITE_SEOJOIN', dr_strlen($this->site_info[SITE_ID]['SITE_SEOJOIN']) ? $this->site_info[SITE_ID]['SITE_SEOJOIN'] : '_');
        define('SITE_REWRITE', (int)$this->site_info[SITE_ID]['SITE_REWRITE']);
        define('SITE_TEMPLATE', dr_strlen($this->site_info[SITE_ID]['SITE_TEMPLATE']) ? $this->site_info[SITE_ID]['SITE_TEMPLATE'] : 'default');
        define('SITE_LANGUAGE', dr_strlen($this->site_info[SITE_ID]['SITE_LANGUAGE']) && is_file(ROOTPATH.'api/language/'.$this->site_info[SITE_ID]['SITE_LANGUAGE'].'/lang.php') ? $this->site_info[SITE_ID]['SITE_LANGUAGE'] : (is_file(WRITEPATH.'lang.default') ? file_get_contents(WRITEPATH.'lang.default') : 'zh-cn'));
        define('SITE_TIME_FORMAT', dr_strlen($this->site_info[SITE_ID]['SITE_TIME_FORMAT']) ? $this->site_info[SITE_ID]['SITE_TIME_FORMAT'] : 'Y-m-d H:i:s');

        // 客户端识别
        $this->is_mobile = defined('IS_MOBILE') ? 1 : (IS_ADMIN ? 0 : \Phpcmf\Service::IS_MOBILE_USER());

        // 后台域名
        !defined('ADMIN_URL') && define('ADMIN_URL', dr_http_prefix(DOMAIN_NAME.'/'));

        // 设置时区
        if (dr_strlen($this->site_info[SITE_ID]['SITE_TIMEZONE']) > 0) {
            date_default_timezone_set('Etc/GMT'.($this->site_info[SITE_ID]['SITE_TIMEZONE'] > 0 ? '-' : '+').abs($this->site_info[SITE_ID]['SITE_TIMEZONE'])); // 设置时区
        }

        // 全局URL
        define('PAY_URL', $this->is_mobile ? SITE_MURL : SITE_URL); // 付款URL
        define('ROOT_URL', $this->site_info[1]['SITE_URL']); // 主站URL
        define('OAUTH_URL', PAY_URL); // 第三方登录URL

        if (!defined('THEME_PATH')) {
            // 系统风格
            if (dr_is_root_path() && (IS_ADMIN || (defined('SYS_THEME_ROOT_PATH') && SYS_THEME_ROOT_PATH))) {
                define('THEME_PATH', '/static/');
                define('LANG_PATH', '/api/language/'.SITE_LANGUAGE.'/');
                define('ROOT_THEME_PATH', '/static/');
            } else {
                define('THEME_PATH', ROOT_URL.'static/');
            }
        }

        !defined('LANG_PATH') && define('LANG_PATH', ROOT_URL.'api/language/'.SITE_LANGUAGE.'/'); // 语言包
        !defined('ROOT_THEME_PATH') && define('ROOT_THEME_PATH', ROOT_URL.'static/'); // 系统风格绝对路径

        if (strpos(SITE_THEME, '/') !== false) {
            // 远程资源
            define('HOME_THEME_PATH', SITE_THEME); // 站点风格
            define('MOBILE_THEME_PATH', SITE_THEME); // 移动端站点风格
        } else {
            // 本地资源
            define('HOME_THEME_PATH', trim(THEME_PATH == '/static/' ? '/' : ROOT_URL).'static/'.SITE_THEME.'/'); // 站点风格
            if (!defined('IS_MOBILE')
                && \Phpcmf\Service::IS_MOBILE_USER()
                && $this->site_info[SITE_ID]['SITE_AUTO']
                && SITE_URL == SITE_MURL) {
                // 当开启自适应移动端，没有绑定域名时
                define('MOBILE_THEME_PATH', SITE_URL.SITE_MOBILE_DIR.'/static/'.SITE_THEME.'/'); // 移动端站点风格
            } else {
                define('MOBILE_THEME_PATH', SITE_MURL.'static/'.SITE_THEME.'/'); // 移动端站点风格
            }
        }

        // 本地附件上传目录和地址
        if (SYS_ATTACHMENT_PATH
            && (strpos(SYS_ATTACHMENT_PATH, '/') === 0 || strpos(SYS_ATTACHMENT_PATH, ':') !== false)
            && is_dir(SYS_ATTACHMENT_PATH)) {
            // 相对于根目录
            // 附件上传目录
            define('SYS_UPLOAD_PATH', rtrim(SYS_ATTACHMENT_PATH, DIRECTORY_SEPARATOR).'/');
            // 附件访问URL
            define('SYS_UPLOAD_URL', trim(SYS_ATTACHMENT_URL, '/').'/');
        } else {
            // 在当前网站目录
            $path = trim(SYS_ATTACHMENT_PATH ? SYS_ATTACHMENT_PATH : 'uploadfile', '/');
            // 附件上传目录
            define('SYS_UPLOAD_PATH', ROOTPATH.$path.'/');
            // 附件访问URL
            define('SYS_UPLOAD_URL', (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).$path.'/');
        }


        // 设置终端模板
        $is_auto_mobile_page = 0;
        if (defined('IS_CLIENT')) {
            // 存在自定义终端
            !defined('CLIENT_URL') && define('CLIENT_URL', dr_http_prefix($this->get_cache('site', SITE_ID, 'client', IS_CLIENT)) . '/');
            \Phpcmf\Service::V()->init(defined('IS_CLIENT_TPL') && IS_CLIENT_TPL ? IS_CLIENT_TPL : IS_CLIENT);
            define('CLIENT_NAME', IS_CLIENT);
        } elseif (defined('IS_MOBILE') || (\Phpcmf\Service::IS_MOBILE_USER() && $this->site_info[SITE_ID]['SITE_AUTO'])) {
            // 移动端模板 // 开启自动识别移动端
            \Phpcmf\Service::V()->init('mobile');
            $is_auto_mobile_page = 1;
            define('CLIENT_URL', SITE_MURL);
            define('CLIENT_NAME', 'mobile');
        } else {
            // 默认情况下pc模板
            define('CLIENT_URL', SITE_URL);
            \Phpcmf\Service::V()->init('pc');
            define('CLIENT_NAME', 'pc');
        }
        !defined('IS_CLIENT') && define('IS_CLIENT', '');

        // 用户系统
        $this->member_cache = $this->get_cache('member');
        if (IS_CLIENT) {
            define('MEMBER_URL', CLIENT_URL.(defined('MEMBER_PAGE') && MEMBER_PAGE ? MEMBER_PAGE : 'index.php?s=member'));
        } else {
            // 默认域名
            define('MEMBER_URL', (!$is_auto_mobile_page ? SITE_URL : SITE_MURL).(defined('MEMBER_PAGE') && MEMBER_PAGE ? MEMBER_PAGE : 'index.php?s=member'));
        }

        // 预览开发的id
        !defined('SITE_FID') && define('SITE_FID', 0);

        // 姓名字段
        define('MEMBER_CNAME', dr_lang($this->member_cache['config']['cname'] ? $this->member_cache['config']['cname'] : '姓名'));

        // 网站常量
        define('SITE_ICP', $this->get_cache('site', SITE_ID, 'config', 'SITE_ICP'));
        define('SITE_TONGJI', $this->get_cache('site', SITE_ID, 'config', 'SITE_TONGJI'));

        // 默认登录时间
        define('SITE_LOGIN_TIME', $this->member_cache['config']['logintime'] ? max(intval($this->member_cache['config']['logintime']), 500) : 36000);

        // 定义交易变量
        define('SITE_SCORE', dr_lang($this->member_cache['pay']['score'] ? $this->member_cache['pay']['score'] : '金币'));
        define('SITE_EXPERIENCE', dr_lang($this->member_cache['pay']['experience'] ? $this->member_cache['pay']['experience'] : '经验'));

        // 验证api提交认证
        if (\Phpcmf\Service::L('input')->request('api_token')) {
            define('IS_API_HTTP', 1);
            if (!defined('SYS_API_TOKEN') || !SYS_API_TOKEN) {
                $this->_json(0, dr_lang('API_TOKEN未启用'));
            }
            $token = \Phpcmf\Service::L('input')->request('api_token');
            if (SYS_API_TOKEN != $token) {
                $this->_json(0, dr_lang('API_TOKEN不正确'));
            }
            if (IS_POST && !$_POST) {
                $param = file_get_contents('php://input');
                if ($param) {
                    $_POST = json_decode($param, true);
                }
            }
            define('IS_API_HTTP_CODE', $token);
            // 验证账号授权并登录
            $auth = \Phpcmf\Service::L('input')->request('api_auth_code');  // 获取当前的登录记录
            if ($auth) {
                // 通过接口的post认证
                $uid = (int)\Phpcmf\Service::L('input')->get('api_auth_uid');
                if ($uid) {
                    $member = \Phpcmf\Service::M('member')->get_member($uid);
                    // 表示登录成功
                    if (!$member) {
                        // 不存在的账号
                        $this->_json(0, dr_lang('api_auth_uid 账号不存在'));
                    } elseif (md5($member['password'].$member['salt']) != $auth) {
                        $this->_json(0, dr_lang('登录超时，请重新登录'));
                    }
                    $this->uid = $uid;
                    $this->member = $member;
                    if (IS_ADMIN) {
                        // 开启session
                        $this->session();
                        \Phpcmf\Service::M('auth')->login_session($member);
                    }
                }
            }
        } elseif (dr_is_app('httpapi') && \Phpcmf\Service::L('input')->request('appid')) {
            define('IS_API_HTTP', 1);
            \Phpcmf\Service::M('http', 'httpapi')->check_auth();
        } else {
            define('IS_API_HTTP', 0);
            $this->uid = (int)\Phpcmf\Service::M('member')->member_uid();
            $this->member = \Phpcmf\Service::M('member')->get_member($this->uid);
            if (!$this->member) {
                $this->uid = 0;
            }
            // 验证账号cookie的有效性
            if ($this->member && !\Phpcmf\Service::M('member')->check_member_cookie($this->member)) {
                $this->uid = 0;
                $this->member = [];
            }
        }

        // 访客唯一标识
        if (defined('IS_API_HTTP_CODE') && IS_API_HTTP_CODE) {
            define('USER_HTTP_CODE', IS_API_HTTP_CODE);
        } else {
            !defined('USER_HTTP_CODE') && define('USER_HTTP_CODE', md5($this->uid.\Phpcmf\Service::L('input')->ip_address().\Phpcmf\Service::L('input')->get_user_agent()));
        }

        if (IS_USE_MODULE && is_file(IS_USE_MODULE.'Config/Run.php')) {
            require IS_USE_MODULE.'Config/Run.php';
        }

        // 判断是否存在授权登录
        if (!IS_ADMIN && $code = \Phpcmf\Service::L('input')->get_cookie('admin_login_member')) {
            list($uid, $adminid) = explode('-', $code);
            $uid = (int)$uid;
            if ($this->uid != $uid) {
                $admin = \Phpcmf\Service::M()->table('member')->get((int)$adminid);
                if ($this->session()->get('admin_login_member_code') == md5($uid.$admin['id'].$admin['password'])) {
                    $this->uid = $uid;
                    $this->member = \Phpcmf\Service::M('member')->get_member($this->uid);
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'member' => $this->member,
        ]);


        if (IS_ADMIN) {
            // 开启session
            $this->session();
            // 版本
            if (!is_file(MYPATH.'Config/License.php')) {
                define('IS_OEM_CMS', '');
                $this->cmf_license = [];
            } else {
                $this->cmf_license = \Phpcmf\Service::R(MYPATH.'Config/License.php');
                define('IS_OEM_CMS', $this->cmf_license['oem'] ? $this->cmf_license['name'] : '');
            }
            // 后台登录判断
            $this->admin = \Phpcmf\Service::M('auth')->is_admin_login($this->member);
            \Phpcmf\Service::V()->admin();
            \Phpcmf\Service::V()->assign([
                'admin' => $this->admin,
                'is_ajax' => \Phpcmf\Service::L('input')->get('is_ajax'),
                'is_mobile' => \Phpcmf\Service::IS_MOBILE_USER() ? 1 : 0,
            ]);
            // 权限判断
            $uri = \Phpcmf\Service::L('Router')->uri();
            if (!$this->_is_admin_auth($uri)) {
                // 无权限操作
                list($a, $action) = explode('_',\Phpcmf\Service::L('Router')->method);
                !$action && $action = $a;
                // 获取操作名称
                switch ($action) {
                    case 'add':
                        $name = dr_lang('【增】');
                        break;
                    case 'edit':
                        $name = dr_lang('【改】');
                        break;
                    case 'del':
                        $name = dr_lang('【删】');
                        break;
                    default:
                        $name = dr_lang('【使用】');
                        break;
                }
                $cname = \Phpcmf\Service::M('auth')->get_auth_name();
                if (!$cname) {
                    $cname = '#'.$uri;
                }
                if (IS_DEV) {
                    if (\Phpcmf\Service::M()->table('admin_menu')->where('mark', $uri)->getRow()) {
                        $this->_admin_msg(0, dr_lang('%s：没有%s权限', $cname, $name). '<br>请在角色管理-操作权限-勾选');
                    } else {
                        $this->_admin_msg(0, dr_lang('%s：没有%s权限', $cname, $name). '<br>权限-后台菜单-没有找到'.$uri);
                    }
                }
                $this->_admin_msg(0, dr_lang('%s：没有%s权限', $cname, $name));
            }
        }

        if (IS_MEMBER && IS_USE_MEMBER) {
            \Phpcmf\Service::L('member', 'member')->init($this);
        }

        // 初始化处理
        \Phpcmf\Service::M('member')->init_member($this->member);

        if (!IS_ADMIN && !IS_API  && !in_array(\Phpcmf\Service::L('Router')->class, ['register', 'login', 'api'])) {
            // 判断网站访问权限
            if (!defined('SC_HTML_FILE') && !IS_MEMBER && IS_USE_MEMBER
                && \Phpcmf\Service::L('member_auth', 'member')->home_auth('show', $this->member)) {
                $this->_msg(0, dr_lang('您的用户组无权限访问'));
            }
            // 账户被锁定
            if ($this->member && $this->member['is_lock']) {
                if (dr_is_app('login') && $this->member['is_lock'] == 2) {
                    // 被插件锁定
                    if (APP_DIR != 'login') {
                        $this->_msg(0, dr_lang('账号被锁定'), dr_url('login/home/index'));
                    }
                } else {
                    $this->_msg(0, dr_lang('账号被锁定'));
                }
            }
        }

        // 加载初始化文件
        $this->_init_run();
    }

    /**
     * 加载初始化文件
     */
    private function _init_run() {

        if ($this->is_load_init_run) {
            return;
        }

        $this->is_load_init_run = true;

        // 附加程序初始化文件
        if (is_file(MYPATH.'Init.php')) {
            require MYPATH.'Init.php';
        }

        // 用户系统初始化
        (IS_MEMBER || APP_DIR == 'member') && $this->init_file('member');

        // 插件目录初始化(排除用户系统重复触发)
        APP_DIR && APP_DIR != 'member' && $this->init_file(APP_DIR);

        // 挂钩点 程序初始化之后
        \Phpcmf\Hooks::trigger('cms_init');
    }

    /**
     * 插件目录初始化文件
     */
    public function init_file($namespace) {

        if (!$namespace) {
            return;
        }

        $file = dr_get_app_dir($namespace).'Config/Init.php';
        if (dr_in_array($file, $this->load_init)) {
            return;
        }

        if (is_file($file)) {
            $this->load_init[] = $file;
            require_once $file;
        }
    }

    /**
     * 开启session
     */
    public function session() {

        if ($this->session) {
            return $this->session;
        }

        require_once FRAMEPATH.'Extend/Session.php';

        $this->session = new \Frame\Session();

        return $this->session;
    }

    /**
     * 缓存页面
     */
    protected function cachePage(int $time) {
        return;// 暂时不使用页面缓存
    }

    /**
     * 读取缓存
     */
    public function get_cache(...$params) {
        return \Phpcmf\Service::L('cache')->get(...$params);
    }

    /**
     * 附件信息
     */
    public function get_attachment($id, $update = 0) {

        if (!$id) {
            return null;
        }

        if (!$update) {
            $data = \Phpcmf\Service::L('cache')->get_file('attach-info-'.$id, 'attach');
            if ($data) {
                $data['url'] = dr_get_file_url($data);
                $data['iscache'] = 1;
                return $data;
            }
        }

        $id = (int)$id;
        $data = \Phpcmf\Service::M()->db->table('attachment')->where('id', $id)->get()->getRowArray();
        if (!$data) {
            return null;
        } elseif ($data['related']) {
            $info = \Phpcmf\Service::M()->db->table('attachment_data')->where('id', $id)->get()->getRowArray();
        } else {
            $info = \Phpcmf\Service::M()->db->table('attachment_unused')->where('id', $id)->get()->getRowArray();
        }

        if (!$info) {
            if ($data['related']) {
                $info = \Phpcmf\Service::M()->db->table('attachment_unused')->where('id', $id)->get()->getRowArray();
            }
            if (!$info) {
                return null;
            }
        }

        // 合并变量
        $info = $data + $info;
        $info['file'] = SYS_UPLOAD_PATH.$info['attachment'];

        // 文件真实地址
        if ($info['remote']) {
            $remote = $this->get_cache('attachment', $info['remote']);
            if (!$remote) {
                // 远程地址无效
                $info['url'] = $info['file'] = '自定义附件（'.$info['remote'].'）的配置已经不存在';
                return $info;
            } else {
                $info['file'] = $remote['value']['path'].$info['attachment'];
            }
        }

        // 附件属性信息
        $info['attachinfo'] = dr_string2array($info['attachinfo']);

        $info['url'] = dr_get_file_url($info);

        \Phpcmf\Service::L('cache')->set_file('attach-info-'.$id, $info, 'attach');

        return $info;
    }

    // 初始化模块 $rt 是否返回
    public function _module_init($dirname = '', $siteid = SITE_ID, $rt = 0) {

        if (is_file(IS_USE_MODULE.'Config/Module_init.php')) {
            require_once IS_USE_MODULE.'Config/Module_init.php';
        } else {
            $this->_msg(0, '请升级建站系统插件');
        }

        return 1;
    }

    /**
     * 统一返回json格式并退出程序
     */
    public function _json($code, $msg, $data = [], $return = false, $extend = []){

        // 强制显示提交信息而不采用ajax返回
        if (isset($_GET['is_show_msg']) && $_GET['is_show_msg']) {
            $url = '';
            if ($code) {
                $url = dr_redirect_safe_check(isset($data['url']) ? $data['url'] : '');
            }
            $this->_msg($code, $msg, $url);
        }

        // 如果是来自api判断回调
        if (IS_API_HTTP) {
            $call = \Phpcmf\Service::L('input')->request('api_call_function');
            if ($call) {
                $data = \Phpcmf\Service::M('http', 'httpapi')->json(dr_safe_replace($call), $code, $msg, $data);
            }
        }

        // 返回的钩子
        $rt = dr_return_data($code, $msg, $data, $extend);

        if (SYS_CSRF && IS_POST) {
            $rt['token'] = [
                'name' => csrf_token(),
                'value' => csrf_hash()
            ];
        }

        // 按格式返回数据
        if (isset($_GET['format']) && $_GET['format']) {
            switch ($_GET['format']) {
                case 'jsonp':
                    $this->_jsonp(1, $msg, $data, $return, $extend);
                    break;
                case 'text':
                    \Phpcmf\Hooks::trigger('cms_end', $rt);
                    echo $msg;exit;
                    break;
            }
        }

        \Phpcmf\Hooks::trigger('cms_end', $rt);
        header('Content-type: application/json');

        echo dr_array2string($rt);
        if (!$return or IS_API_HTTP) {
            exit;
        }
    }

    /**
     * 统一返回jsonp格式并退出程序
     */
    public function _jsonp($code, $msg, $data = [], $return = false, $extend = []){

        $callback = dr_safe_replace(\Phpcmf\Service::L('input')->get('callback'));
        !$callback && $callback = 'callback';

        if (IS_API_HTTP) {
            $this->_json($code, $msg, $data, $return, $extend);
        } else {
            // 返回的钩子
            $rt = dr_return_data($code, $msg, $data, $extend);
            \Phpcmf\Hooks::trigger('cms_end', $rt);
            echo $callback.'('.dr_array2string($rt).')';
            if (!$return) {
                exit;
            }
        }
    }

    /**
     * 加载数组配置文件
     */
    public function _require_array($file) {

        if (!is_file($file)) {
            return [];
        }

        $array = require $file;

        return $array;
    }

    /**
     * 后台提示信息
     */
    public function _admin_msg($code, $msg, $url = '', $time = 3, $return = false) {

        if (\Phpcmf\Service::L('input')->get('callback')) {
            return $this->_jsonp($code, $msg, $url, $return);
        } elseif ((\Phpcmf\Service::L('input')->get('is_ajax') || IS_API_HTTP || IS_AJAX)) {
            return $this->_json($code, $msg, $url, $return);
        }

        $url = dr_safe_url($url, true);
        $backurl = $url ? $url : dr_safe_url($_SERVER['HTTP_REFERER'], true);

        if ($backurl) {
            strpos(dr_now_url(), $backurl) === 0 && $backurl = '';
        } else {
            $backurl = 'javascript:history.go(-1);';
        }

        // 加载初始化文件
        $this->_init_run();

        // 不存在URL时进入提示页面
        \Phpcmf\Service::V()->assign([
            'msg' => $msg,
            'url' => $url,
            'time' => $time,
            'mark' => $code,
            'backurl' => $backurl,
            'meta_title' => dr_clearhtml($msg),
            'is_msg_page' => 1,
        ]);

        \Phpcmf\Service::V()->display('msg.html', 'admin');
        if ($return) {
            return;
        }
        !defined('SC_HTML_FILE') && exit();
    }

    /**
     * 前台提示信息
     */
    public function _msg($code, $msg, $url = '', $time = 3, $return = false) {

        if (isset($_GET['is_show_msg'])) {
            // 强制显示提交信息而不采用ajax返回
        } else {
            if (\Phpcmf\Service::L('input')->get('callback')) {
                $this->_jsonp($code, $msg, $url);
            } elseif ((\Phpcmf\Service::L('input')->get('is_ajax') || IS_API_HTTP || IS_AJAX)) {
                $this->_json($code, $msg, $url);
            }
        }

        if (!$url) {
            $backurl = dr_safe_url($_SERVER['HTTP_REFERER'], true);
            (!$backurl || $backurl == dr_now_url() ) && $backurl = SITE_URL;
        } else {
            $backurl = dr_safe_url($url, true);
        }

        // 加载初始化文件
        $this->_init_run();

        // 返回的钩子
        $rt = [
            'msg' => $msg,
            'url' => $url,
            'time' => $time,
            'mark' => $code,
            'code' => $code,
            'backurl' => $backurl,
            'meta_title' => SITE_NAME
        ];
        \Phpcmf\Hooks::trigger('cms_end', $rt);

        \Phpcmf\Service::V()->assign($rt);
        \Phpcmf\Service::V()->display('msg.html');
        if ($return) {
            return;
        }
        !defined('SC_HTML_FILE') && exit();
    }

    /**
     * 引用404页面
     */
    public function goto_404_page($msg = '') {

        \Phpcmf\Hooks::trigger('cms_404', $msg);

        if (IS_API_HTTP) {
            $this->_json(0, $msg);
        } elseif (defined('SC_HTML_FILE')) {
            if (isset($_GET['iframe'])) {
                return $msg;
            }
            $this->_json(0, $msg);
        }

        // 调试模式下不进行404状态码
        if (!CI_DEBUG && !defined('SC_HTML_FILE')) {
            http_response_code(404);
        }

        // 开启跳转404页面功能
        if (defined('SYS_GO_404') && SYS_GO_404) {
            if (CMSURI != '404.html') {
                if (IS_DEV) {
                    $msg.= '（开发者模式下不跳转到404.html页面）';
                } else {
                    dr_redirect('/404.html');
                }
            } else {
                $msg = dr_lang('你访问的页面不存在');
            }
        }

        \Phpcmf\Service::V()->assign([
            'msg' => $msg,
            'meta_title' => dr_lang('你访问的页面不存在')
        ]);
        \Phpcmf\Service::V()->display('404.html');
        !defined('SC_HTML_FILE') && exit();
    }

    /**
     * 生成静态时的跳转提示
     */
    public function _html_msg($code, $msg, $url = '', $note = '') {

        if (\Phpcmf\Service::L('input')->get('is_ajax')) {
            $this->_json($code, $msg, $url);
        }

        \Phpcmf\Service::V()->assign([
            'msg' => $msg,
            'url' => $url,
            'note' => $note,
            'mark' => $code,
            'meta_title' => dr_lang('操作进度')
        ]);
        \Phpcmf\Service::V()->display('html_msg.html', 'admin');exit;
    }

    /**
     * 后台登录判断
     */
    protected function _is_admin_login() {
        return \Phpcmf\Service::M('auth')->_is_admin_login();
    }

    /**
     * 登录判断
     */
    public function _member_option($call = 1) {
        if (IS_USE_MEMBER) {
            \Phpcmf\Service::L('member', 'member')->member_option($this);
        }
    }

    /**
     * 判断模块栏目是否具有用户操作权限
     */
    public function _get_module_member_category($module, $name) {

        if (!$module) {
            return [];
        }

        if (isset($this->temp['_get_module_member_category'][$module['dirname'].$name]) && $this->temp['_get_module_member_category'][$module['dirname'].$name]) {
            return $this->temp['_get_module_member_category'][$module['dirname'].$name];
        }

        // 重新获取栏目
        if (isset($module['category'][0]) && dr_is_module($module['category'][0])) {
            $module['category'] = \Phpcmf\Service::L('category', 'module')->get_category($module['dirname']);
        }

        $category = $module['category'];
        foreach ($category as $id => $t) {
            // 筛选可发布的栏目权限
            if (!is_array($t)) {
                continue;
            }
            if (!$t['child']) {
                if ($t['mid'] != $module['dirname']) {
                    // 模块不符合 排除
                    unset($category[$id]);
                } elseif (IS_USE_MEMBER && !\Phpcmf\Service::L('member_auth', 'member')->category_auth($module, $id, $name, $this->member)) {
                    // 用户的的权限判断
                    unset($category[$id]);
                }
            }
        }

        $this->temp['_get_module_member_category'][$module['dirname'].$name] = $category;
        return $category;
    }

    /**
     * 判断后台uri是否具有操作权限
     */
    public function _is_admin_auth($uri = '') {
        return \Phpcmf\Service::M('auth')->_is_admin_auth($uri);
    }

    /**
     * 是否移动端访问访问
     */
    public function _is_mobile() {
        return dr_is_mobile();
    }

    /**
     * 插件的clink值
     */
    protected function _app_clink($type = '', $data = [])
    {
        return $this->_app_click('link', $type, $data);
    }

    /**
     * 插件的cbottom值
     */
    protected function _app_cbottom($type = '', $data = [])
    {
        return $this->_app_click('bottom', $type, $data);
    }

    private function _app_click($pos, $type, $row) {

        if (!$type) {
            // 表示模块部分
            $endfix = '';
        } else {
            $endfix = '_'.$type;
        }

        // 加载全部插件的
        $local = \Phpcmf\Service::Apps(true);

        // 加载模块自身的
        if (APP_DIR) {
            if (is_file(APPPATH.'Config/C'.$pos.$endfix.'.php')) {
                $local[APP_DIR] = [APPPATH];
            } else {
                // 排除模块自身
                if (isset($local[APP_DIR])) {
                    unset($local[APP_DIR]);
                }
            }
        }

        $data = [];
        if ($pos == 'bottom') {
            $data = $row; // 底部自定义菜单写入
        }
        foreach ($local as $dir => $path) {
            $ck = 0;
            // 判断插件目录
            if (is_array($path)) {
                $ck = 1;
                $path = array_shift($path);
            } elseif (is_file($path.'Config/C'.$pos.$endfix.'.php')
                && is_file($path.'Config/App.php')) {
                $cfg = require $path.'Config/App.php';
                if ($cfg['type'] == 'app' && !$cfg['ftype']) {
                    // 表示插件非模块
                    $ck = 1;
                }
            }
            if ($ck) {
                $_clink = require $path.'Config/C'.$pos.$endfix.'.php';
                if ($_clink) {
                    if (is_file($path.'Models/Auth'.$endfix.'.php')) {
                        $obj = \Phpcmf\Service::M('auth'.$endfix, $dir);
                        foreach ($_clink as $k => $v) {
                            if (defined('IS_MODULE_VERIFY')
                                && (!isset($v['is_verify']) || !$v['is_verify'])) {
                                // 审核界面
                                unset($_clink[$k]);
                                continue;
                            }
                            // 动态名称
                            if (strpos($v['name'], '_') === 0
                                && method_exists($obj, substr($v['name'], 1))) {
                                $_clink[$k]['name'] = call_user_func(array($obj, substr($v['name'], 1)), APP_DIR);
                            }
                            // check权限验证
                            if (isset($v['check']) && $v['check'] && method_exists($obj, $v['check'])
                                && !call_user_func(array($obj, $v['check']), APP_DIR, $row)) {
                                unset($_clink[$k]);
                                continue;
                            }
                            // 对象存储不返回出去了
                            $_clink[$k]['model'] = NULL;
                        }
                        // 权限验证
                        if ($pos == 'link' && method_exists($obj, 'is_link_auth') && $obj->is_link_auth(APP_DIR)) {
                            $data = array_merge($data, $_clink);
                        } elseif ($pos == 'bottom' && method_exists($obj, 'is_bottom_auth') && $obj->is_bottom_auth(APP_DIR)) {
                            $data = array_merge($data , $_clink) ;
                        } else {
                            CI_DEBUG && log_message('debug', 'Auth类（'.$path.'Models/Auth'.$endfix.'.php'.'）没有定义is_'.$pos.'_auth或者is_'.$pos.'_auth验证失败');
                        }
                    } else {
                        $data = array_merge($data , $_clink) ;
                        CI_DEBUG && log_message('debug', '配置文件（'.$path.'Config/C'.$pos.$endfix.'.php'.'）没有定义权限验证类（'.$path.'Models/Auth'.$endfix.'.php'.'）');
                    }
                }
            }
        }

        if ($data) {
            foreach ($data as $i => $t) {
                $data[$i]['displayorder'] = $i + ($t['displayorder'] ? (int)$t['displayorder'] : 0);
                if (IS_ADMIN) {
                    if (!$t['url']) {
                        unset($data[$i]); // 没有url
                        CI_DEBUG && !$t['murl'] && log_message('error', 'C'.$pos.'（'.$t['name'].'）没有设置url参数');
                        continue;
                    } elseif ($t['uri'] && !$this->_is_admin_auth($t['uri'])) {
                        unset($data[$i]); // 无权限的不要
                        continue;
                    }
                    $data[$i]['url'] = urldecode($data[$i]['url']);
                } else {
                    if (!$t['murl']) {
                        unset($data[$i]); // 非后台必须验证murl
                        CI_DEBUG && !$t['url'] && log_message('error', 'C'.$pos.'（'.$t['name'].'）没有设置murl参数');
                        continue;
                    }
                    $data[$i]['url'] = urldecode($data[$i]['murl']);
                }
            }
            uasort($data, function($a, $b){
                if($a['displayorder'] == $b['displayorder']){
                    return 0;
                }
                return($a['displayorder']<$b['displayorder']) ? -1 : 1;
            });
        }

        return $data;
    }

    /**
     * 获取可用后table区域
     */
    protected function _main_table()
    {
        // 默认的
        $data = [
            'couts' => dr_lang('数据统计'),
            'notice' => dr_lang('通知提醒'),
            'mylink' => dr_lang('快捷链接'),
        ];

        if (is_file(MYPATH.'/Config/Main.php')) {
            $_data = require MYPATH.'/Config/Main.php';
            $_data && $data = dr_array22array($data, $_data);
        }

        // 执行插件自己的缓存程序
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock')
                && is_file($path.'Config/Main.php')) {
                $_data = require $path.'Config/Main.php';
                if ($_data) {
                    foreach ($_data as $key => $name) {
                        $data[strtolower($dir).'-'.$key] = $name;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 官方短信接口查询
     */
    protected function _api_sms_info() {

        $uid = (int)\Phpcmf\Service::L('input')->get('uid');
        $key = dr_safe_replace(\Phpcmf\Service::L('input')->get('key'));
        if (!$uid || !$key) {
            $this->_json(0, dr_lang('uid或者key不能为空'));
        }

        $url = "https://www.xunruicms.com/index.php?s=vip&c=check&uid={$uid}&key={$key}";
        $data = dr_catcher_data($url);

        $this->_json(1, $data);
    }

    // 版本检查
    protected function _api_version_cmf() {
		
		if (defined('SYS_NOT_UPDATE') && SYS_NOT_UPDATE) {
            $this->_json(1, '');
        }
		
        exit(dr_catcher_data('https://www.xunruicms.com/version.php?action=new&php='.PHP_VERSION.'&id=1&time='.strtotime($this->cmf_version['downtime']).'&v='.$this->cmf_version['version']));
    }

    // 版本检查
    protected function _api_version_cms() {
        $this->_api_version_cmf();
    }

    // 搜索帮助
    protected function _api_search_help() {

        $kw = dr_safe_replace(\Phpcmf\Service::L('input')->get('kw'));
        $url = 'https://www.xunruicms.com/index.php?s=doc&c=search&keyword='.$kw.'&is_phpcmf=cms';
        \Phpcmf\Service::V()->assign([
            'url' => $url,
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');
    }

    /**
     * (废弃)
     */
    public function _member_auth_value($authid, $name) {
        return 0;
    }
    /**
     * (废弃)
     */
    public function _member_value($authid, $value)
    {
        return 0;
    }
    /**
     * (废弃)
     */
    public function _module_member_value($catid, $dir, $auth, $authid = 0) {
        return 0;
    }
    /**
     * (废弃)
     */
    public function _module_member_category($category, $dir, $auth) {

        if (!$category) {
            return [];
        }

        foreach ($category as $id => $t) {
            // 筛选可发布的栏目权限
            if (!$t['child']) {
                if ($t['mid'] != $dir) {
                    // 模块不符合 排除
                    unset($category[$id]);
                }
            }
        }

        return $category;
    }

    /**
     * Get the CI singleton
     */
    public static function &get_instance()
    {
        return self::$instance;
    }

}