<?php namespace Phpcmf\Library;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



// 路由及url处理类

class Router
{

    public $class;
    public $method;

    private $_uri;
    private $_temp;

    public function __construct(...$params)
    {

        $routes = \Config\Services::router(null, null, true);

        // 获取路由信息
        $this->class = strtolower(substr(strrchr($routes->controllerName(), '\\'), 1));
        $this->method = strtolower($routes->methodName());
    }

    // 获取用户中心,当前页面的URI
    public function member_uri()
    {
        $u = (APP_DIR ? APP_DIR . '/' : '') . $this->class . '/';
        return [trim($u . $this->method, '/'), trim($u . 'index', '/')];
    }

    // 获取当前页面的URI
    public function uri($m = '')
    {
        if (!$m && $this->_uri) {
            return $this->_uri;
        }

        $m = $m ? $m : $this->method;
        if (IS_MEMBER) {
            $_uri = 'member/' . trim((APP_DIR && APP_DIR != 'member' ? APP_DIR . '/' : '') . $this->class . '/' . $m, '/');
        } else {
            $_uri = trim((APP_DIR ? APP_DIR . '/' : '') . $this->class . '/' . $m, '/');
        }

        if ($m) {
            return $_uri;
        }

        $this->_uri = $_uri;

        return $this->_uri;
    }

    // 跳转到登录页面
    public function go_member_login($url) {

        $url = \Phpcmf\Service::L('Router')->member_url('login/index', ['back' => urlencode($url)]);
        dr_redirect($url);
        exit;
    }


    // 获取返回时的URL
    public function get_back($uri, $param = [])
    {
        $name = md5($_SERVER['HTTP_USER_AGENT'] . SELF . $uri . \Phpcmf\Service::C()->uid . SITE_ID . \Phpcmf\Service::L('input')->ip_address());
        $value = \Phpcmf\Service::L('cache')->get_data($name);
        if ($value) {
            $uri = $value[0];
            $param = dr_array22array($param, $value[1]);
        }
        return IS_ADMIN ? $this->url($uri, $param) : $this->member_url($uri, $param);
    }

    // 设置返回时的URL, uri页面标识,param参数,nuri当前页优先
    public function set_back($uri, $param = [], $nuri = '')
    {
        $name = md5($_SERVER['HTTP_USER_AGENT'] . SELF . $uri . \Phpcmf\Service::C()->uid . SITE_ID . \Phpcmf\Service::L('input')->ip_address());
        $param['page'] = $_GET['page'];
        \Phpcmf\Service::L('cache')->set_data(
            $name,
            [$nuri ? $nuri : $uri, $param],
            3600
        );
    }

    // 判断满足定向跳转的条件 page单页, indexc首页, indexm模块首页, category栏目页, show内容
    function is_redirect($type, $url)
    {

        // 不调整的条件
        if (defined('IS_NOT_301')) {
            return;
        } elseif (!$url || strpos($url, 'http') === FALSE) {
            return; // 为空时排除
        } elseif (IS_API) {
            return; // 排除接口
        } elseif (IS_ADMIN) {
            return; // 排除后台
        } elseif (\Phpcmf\Service::IS_MOBILE()) {
            return; // 排除移动端
        } elseif (\Phpcmf\Service::_is_mobile()) {
            return; // 排除移动端
        } elseif (defined('SC_HTML_FILE')) {
            return; // 排除生成
        } elseif (intval($_GET['page']) > 1) {
            return; // 排除分页
        } elseif (IS_CLIENT) {
            return; // 排除终端
        }

        // 跳转
        $url != dr_now_url() && dr_redirect($url, 'location', '301');
    }

    // 判断满足定向跳转的条件
    function is_redirect_url($url)
    {

        // 不调整的条件
        if (defined('IS_NOT_301')) {
            return;
        } elseif (!$url || strpos($url, 'http') === FALSE) {
            return; // 为空时排除
        } elseif (IS_API || IS_API_HTTP) {
            return; // 排除接口
        } elseif (IS_ADMIN) {
            return; // 排除后台
        } elseif (\Phpcmf\Service::IS_MOBILE()) {
            return; // 排除移动端
        } elseif (defined('SC_HTML_FILE')) {
            return; // 排除生成
        } elseif (intval($_GET['page']) > 1) {
            return; // 排除分页
        } elseif (IS_CLIENT) {
            return; // 排除终端
        }

        // 跳转
        if ($url != dr_now_url()) {
            if (IS_DEV) {
                \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：<br>当前URL['.dr_now_url().']<br>与其本身地址['.$url.']不符<br>正在自动跳转本身地址（关闭开发者模式时即可自动跳转）', $url, 9);
            } else {
                dr_redirect($url, 'location', '301');
            }
        }
    }


    /**
     * url函数
     *
     * @param    string $url URL规则，如home/index
     * @param    array $query 相关参数
     * @return    string    项目入口文件.php?参数
     */
    function url($url, $query = [], $self = SELF)
    {

        if (!$url) {
            return $self;
        }

        if (!$query && strpos($url, ':') !== false) {
            list($a, $b) = explode(':', $url);
            $url = $a;
            $query = $this->url2array($b);
        }

        // 当是分站且没有绑定域名自动加上参数
        #SITE_FID && !isset($query['fid']) && defined('SITE_BRANCH_DOMAIN') && !SITE_BRANCH_DOMAIN && $query['fid'] = SITE_FID;

        $url = strpos($url, 'admin') === 0 ? substr($url, 5) : $url;
        $url = trim($url, '/');

        // 判断是否后台首页
        if ($self != 'index.php' && ($url == 'home/index' || $url == 'home/home')) {
            return SELF;
        }

        if (!IS_ADMIN) {
            // 非后台统一index.php入口
            $self = '/index.php';
        }

        $url = explode('/', $url);
        $uri = array();

        switch (dr_count($url)) {
            case 1:
                $uri['c'] = 'home';
                $uri['m'] = $url[0];
                break;
            case 2:
                $uri['c'] = $url[0];
                $uri['m'] = $url[1];
                break;
            case 3:
                $uri['s'] = $url[0];
                // 非后台且非会员中心的模块地址
                if (is_dir(APPSPATH . ucfirst($uri['s'])) && trim($self, '/') == 'index.php' && !IS_MEMBER) {
                    $mod = \Phpcmf\Service::C()->get_cache('module-' . SITE_ID . '-' . $uri['s']);
                    if ($mod['domain']) {
                        unset($uri['s']);
                        $self = (\Phpcmf\Service::IS_PC() ? $mod['url'] : dr_mobile_url($mod['url'])) . 'index.php';
                    } else {
                        $self = (\Phpcmf\Service::IS_PC() ? SITE_URL : SITE_MURL) . 'index.php';
                    }
                }
                $uri['c'] = $url[1];
                $uri['m'] = $url[2];
                break;
        }

        $query && $uri = @array_merge($uri, $query);

        return $self . '?' . @http_build_query($uri);
    }

    /**
     * 会员url函数
     *
     * @param    string $url URL规则，如home/index
     * @param    array $query 相关参数
     * @return    string    地址
     */
    function member_url($url = '', $query = [], $null = '')
    {

        if (!$url || $url == 'home/index' || $url == '/') {
            return MEMBER_URL;
        }

        $self = 'index.php';

        if (!$query && strpos($url, ':') !== false) {
            list($a, $b) = explode(':', $url);
            $url = $a;
            $query = $this->url2array($b);
        }

        $url = trim(str_replace('member/', '', $url), '/');
        $url = explode('/', $url);
        $uri = ['s' => 'member'];

        switch (dr_count($url)) {
            case 1:
                $uri['c'] = 'home';
                $uri['m'] = $url[0];
                break;
            case 2:
                $uri['c'] = $url[0];
                $uri['m'] = $url[1];
                break;
            case 3:
                // 当存在三个参数时,表示模块或应用的会员中心
                $url[0] != 'member' && is_dir(dr_get_app_dir($url[0])) && $uri['app'] = $url[0];
                $uri['c'] = $url[1];
                $uri['m'] = $url[2];
                break;
        }

        $query && $uri = @array_merge($uri, $query);

        // 未绑定域名的情况下
        return (IS_CLIENT ? CLIENT_URL : (\Phpcmf\Service::IS_PC() ? SITE_URL : SITE_MURL)) . $self . '?' . @http_build_query($uri);
    }

    /**
     * 模块栏目URL地址
     *
     * @param    array $mod
     * @param    array $data
     * @param    intval $page
     * @return    string
     */
    function category_url($mod, $data, $page = 0, $fid = 0)
    {

        if (!$mod || !$data) {
            return '栏目URL参数不完整';
        }

        // 是否分页
        $page && $data['page'] = $page = is_numeric($page) ? max((int)$page, 1) : $page;
        $page == 1 && $page = 0;

        // 获取自定义URL
        $rule = isset($data['setting']['urlrule']) ? \Phpcmf\Service::L('cache')->get('urlrule', (int)$data['setting']['urlrule'], 'value') : 0;
        if ($rule && $rule['list']) {
            // URL模式为自定义，且已经设置规则
            $data['fid'] = $fid;
            $data['modname'] = $mod['share'] ? '共享栏目不能使用modname标签' : $mod['dirname'];
            $data['pdirname'].= $data['dirname'];
            $data['pdirname'] = str_replace('/', $rule['catjoin'], $data['pdirname']);
            $rep = new \php5replace($data);
            $url = ltrim($page ? $rule['list_page'] : $rule['list'], '/');
            $url = preg_replace_callback("#{([a-z_0-9]+)}#Ui", array($rep, 'php55_replace_data'), $url);
            $url = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $url);
            $url = str_replace('//', '/', $url);
            return $this->url_prefix('rewrite', $mod, $data, $fid) . $url;
        }

        return $this->url_prefix('module_php', $mod, $data, $fid) . 'c=category&id=' . (isset($data['id']) ? $data['id'] : 0) . ($page ? '&page=' . $page : '');
    }

    /**
     * 模块内容URL地址
     *
     * @param    array $mod
     * @param    array $data
     * @param    mod $page
     * @return    string
     */
    function show_url($mod, $data, $page = 0)
    {

        if (!$mod || !$data) {
            return '内容URL参数不完整';
        }

        $cat = $mod['category'][$data['catid']];

        $page && $data['page'] = $page = is_numeric($page) ? max((int)$page, 1) : $page;
        $page == 1 && $page = 0;

        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$cat['setting']['urlrule'], 'value');
        if ($rule && $rule['show']) {
            // URL模式为自定义，且已经设置规则
            $data['modname'] = $mod['dirname'];
            $cat['pdirname'] .= $cat['dirname'];
            $data['dirname'] = $cat['dirname'];
            $inputtime = isset($data['_inputtime']) ? $data['_inputtime'] : $data['inputtime'];
            $data['y'] = date('Y', $inputtime);
            $data['m'] = date('m', $inputtime);
            $data['d'] = date('d', $inputtime);
            //$data['fid'] = defined('IS_PLUS_FENZHAN') ? dr_get_show_fid($data) : 0;
            $data['pdirname'] = str_replace('/', $rule['catjoin'], $cat['pdirname']);
            $url = ltrim($page ? $rule['show_page'] : $rule['show'], '/');
            $rep = new \php5replace($data);
            $url = preg_replace_callback("#{([a-z_0-9]+)}#Ui", array($rep, 'php55_replace_data'), $url);
            $url = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $url);
            return $this->url_prefix('rewrite', $mod, $cat) . $url;
        }

        return $this->url_prefix('module_php', $mod, $cat) . 'c=show&id=' . $data['id'] . ($page ? '&page=' . $page : '');
    }

    /*
     * 单页URL地址
     *
     * @param	array	$data
     * @param	intval	$page
     * @return	string
     */
    function page_url($data, $page = 0)
    {

        if (!$data) {
            return '自定义页面数据不存在';
        }

        $page && $data['page'] = $page = is_numeric($page) ? max((int)$page, 1) : $page;
        $page == 1 && $page = 0;

        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$data['setting']['urlrule'], 'value');
        if ($rule && $rule['page']) {
            // URL模式为自定义，且已经设置规则
            $data['pdirname'] == '/' && $data['pdirname'] = '';
            $data['dirname'] == '/' && $data['dirname'] = '';
            $data['pdirname'] .= $data['dirname'];
            $data['pdirname'] = str_replace('/', $rule['catjoin'], $data['pdirname']);
            $url = $page ? $rule['page_page'] : $rule['page'];
            $rep = new \php5replace($data);
            $url = preg_replace_callback("#{([a-z_0-9]+)}#Ui", array($rep, 'php55_replace_data'), $url);
            $url = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $url);
            $url = '/' . $url;
            $url = str_replace('//', '/', $url);
            return $url;
        }

        return $this->url_prefix('php') . 's=page&id=' . $data['id'] . ($page ? '&page=' . $page : '');
    }


    /**
     * tag的url
     */
    function tag_url($name)
    {

        if (!$name) {
            return 'TagURL name参数为空';
        } elseif (!dr_is_app('tag')) {
            return '关键词库应用没有安装';
        }

        // PC端
        $cfg = \Phpcmf\Service::M('app')->get_config('tag');
        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$cfg['urlrule'], 'value');;
        if ($rule && $rule['tag']) {
            $data['tag'] = $name;
            $data['tag'] = str_replace('/', $rule['catjoin'], $data['tag']);
            $url = ltrim($rule['tag'], '/');
            $rep = new \php5replace($data);
            $url = preg_replace_callback("#{([a-z_0-9]+)}#Ui", array($rep, 'php55_replace_data'), $url);
            $url = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $url);
            $url = str_replace('//', '/', $url);
            unset($rep);
            return $this->url_prefix('rewrite', [], [], SITE_FID) . $url;
        } else {
            return $this->url_prefix('php', [], [], SITE_FID) . 's=tag&name=' . $name;
        }
    }

    // 缓存读取url
    function get_tag_url($name, $mid = '') {

        if (!$name) {
            return '/#无name参数';
        }

        // 读缓存
        $file = WRITEPATH.'tags/'.md5(SITE_ID.'-'.$name);
        if ($file) {
            $url = file_get_contents($file);
            if ($url) {
                if (!dr_is_app('tag')) {
                    return '关键词库应用没有安装';
                }
                return $url;
            }
        }

        if ($mid) {
            return $this->search_url([], 'keyword', $name, $mid);
        }

        return '/';
    }

    // 模块URL
    function module_url($mod, $sid)
    {

        // 绑定域名的情况下
        if ($mod['site'][$sid]['domain']) {
            return dr_http_prefix($mod['site'][$sid]['domain']) . '/';
        }

        // 自定义规则的情况下
        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$mod['urlrule'], 'value', 'module');
        if ($rule) {
            return '/' . str_replace('{modname}', $mod['dirname'], $rule);
        }

        return '/index.php?s=' . $mod['dirname'];
    }

    /**
     * 搜索url组合
     *
     * @param    array $params 搜索参数数组
     * @param    string|array $name 当前参数名称
     * @param    string|array $value 当前参数值
     * @param    string $mid 强制定位到模块
     * @param    string $fid 指定fid
     * @return    string
     */
    function search_url($params = [], $name = '', $value = '', $mid = '', $fid = SITE_FID)
    {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        $mod = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);
        if (!$mod) {
            return '模块[' . $dir . ']缓存不存在';
        }

        if ($name) {
            if (is_array($name)) {
                foreach ($name as $i => $_name) {
                    if (isset($value[$i]) && strlen($value[$i])) {
                        $params[$_name] = $value[$i];
                    } else {
                        unset($params[$_name]);
                    }
                }
            } else {
                if (strlen($value)) {
                    $params[$name] = $value;
                } else {
                    unset($params[$name]);
                }
            }

        }

        if (is_array($params)) {
            foreach ($params as $i => $t) {
                if (strlen($t) == 0) {
                    unset($params[$i]);
                }
            }
        }

        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$mod['urlrule'], 'value');
        if ($rule && $rule['search']) {
            //$fid && $data['fid'] = $fid;
            $data['modname'] = $mod['dirname'];
            $data['param'] = dr_search_rewrite_encode($params, $mod['setting']['search']);
            if ($params && !$data['param']) {
                log_message('error', '模块['.$mod['dirname'].']无法通过[搜索参数字符串规则]获得参数');
            }
            $url = ltrim($data['param'] ? $rule['search_page'] : $rule['search'], '/');
            $rep = new \php5replace($data);
            $url = preg_replace_callback("#{([a-z_0-9]+)}#Ui", array($rep, 'php55_replace_data'), $url);
            $url = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $url);
            return str_replace('//', '/', $this->url_prefix('rewrite', $mod) . $url);
        } else {
            return $this->url_prefix('php', $mod, array(), $fid) . trim('c=search&' . @http_build_query($params), '&');
        }
    }

    // 评论地址
    function comment_url($id, $moddir = '')
    {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $moddir && $dir = $moddir;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, array(), SITE_FID) . 'c=comment&id=' . $id;
    }

    // 打赏
    function donation_url($id, $moddir = '')
    {

        if (!dr_is_app('shang')) {
            return '没有安装【打赏】应用';
        }

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $moddir && $dir = $moddir;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, array(), SITE_FID) . 'c=donation&id=' . $id;
    }

    // 模块表单内容地址
    function mform_show_url($form, $id, $moddir = '', $page = 0)
    {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $moddir && $dir = $moddir;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=show&id=' . $id . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 模块表单提交地址
    function mform_post_url($form, $cid, $moddir = '')
    {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $moddir && $dir = $moddir;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=post&cid=' . $cid;
    }

    // 模块表单列表地址
    function mform_list_url($form, $cid, $moddir = '', $page = 0)
    {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $moddir && $dir = $moddir;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=index&cid=' . $cid . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 网站表单内容地址
    function form_show_url($form, $id, $page = 0)
    {

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=show&id=' . $id . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 网站表单提交地址
    function form_post_url($form)
    {

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=post';
    }

    // 网站表单列表地址
    function form_list_url($form, $page = 0)
    {

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 快捷登录地址
    function oauth_url($name, $type, $gourl = '')
    {
        return ROOT_URL . 'index.php?s=api&c=oauth&m=index&name=' . $name . '&type=' . $type.'&back='.urlencode($gourl);
    }


    // 地址前缀部分
    function url_prefix($type, $mod = [], $cat = [], $fid = 0)
    {

        $dir = isset($mod['dirname']) ? $mod['dirname'] : '';
        $domain = isset($mod['domain']) ? $mod['domain'] : '';

        if ($cat) {
            $dir = isset($cat['mid']) ? $cat['mid'] : $dir;
            //$domain = isset($cat['domain']) ? $cat['domain'] : $domain;
        }

        // 默认主网站的地址
        $site_url = '/';

        switch ($type) {

            // 动态模式
            case 'php':
                $url = $site_url . 'index.php?' . (!$mod || $domain ? '' : 's=' . $dir . '&') . (!$fid ? '' : 'fid=' . $fid . '&');
                break;

            // 模块动态模式
            case 'module_php':
                $url = $site_url . 'index.php?' . ($mod['share'] || $domain ? '' : 's=' . $dir . '&') . (!$fid ? '' : 'fid=' . $fid . '&');
                break;

            // 自定义url模式
            case 'rewrite':
                $url = $site_url;
                break;

        }

        return $url;
    }

    // 分站url
    public function furl($fid)
    {

        if (!$fid) {
            return IS_CLIENT ? CLIENT_URL : SITE_URL;
        }

        // 自定义规则
        $rule = \Phpcmf\Service::L('cache')->get('urlrule', SITE_REWRITE, 'value');
        if ($rule && $rule['findex']) {
            return (IS_CLIENT ? CLIENT_URL : SITE_URL) . str_replace('{fid}', $fid, ltrim($rule['findex'], '/'));
        } else {
            return $this->url_prefix('php', [], [], $fid);
        }

    }

    // 去除url中的域名
    public function remove_domain($url, $doamin = '')
    {

        if (!$this->_temp['domain']) {
            foreach (\Phpcmf\Service::C()->site_domain as $u => $i) {
                $this->_temp['domain'][] = 'http://' . $u . '/';
                $this->_temp['domain'][] = 'https://' . $u . '/';
            }
        }

        if ($doamin) {
            $this->_temp['domain'][] = $doamin;
        }

        return str_replace($this->_temp['domain'], '', $url);
    }

    /**
     * url参数转化成数组
     */
    public function url2array($query) {

        if (!$query) {
            return [];
        }

        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;

    }


    // 生成伪静态解析代码
    public function get_rewrite_code() {

        $data = \Phpcmf\Service::M()->table('urlrule')->getAll();
        if (!$data) {
            return dr_return_data(0, dr_lang('你没有设置URL规则'));
        }

        $code = '';
        $error = '';
        $write = []; // 防止重复
        foreach ($data as $r) {
            $value = dr_string2array($r['value']);
            if ($r['type'] == 1) {
                // 独立模块
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL.PHP_EOL;
                if ($value['module']) {
                    $rule = $value['module'];
                    $cname = "【".$r['name']."】模块首页（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['list_page']) {
                    $rule = $value['list_page'];
                    $cname = "【".$r['name']."】模块栏目列表(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{page}'])) {
                        $error.= "<p>".$cname."缺少{page}标签</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&dir=$'.$rname['{dirname}'].'&page=$'.$rname['{page}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&dir=$'.$rname['{pdirname}'].'&page=$'.$rname['{page}'];
                        } else {
                            // id模式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&id=$'.$rname['{id}'].'&page=$'.$rname['{page}'];
                        }
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['list']) {
                    $rule = $value['list'];
                    $cname = "【".$r['name']."】模块栏目列表（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&dir=$'.$rname['{dirname}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&dir=$'.$rname['{pdirname}'];
                        } else {
                            // id模式
                            $rule = 'index.php?s=$'.$rname['{modname}'].'&c=category&id=$'.$rname['{id}'];
                        }
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['show_page']) {
                    $rule = $value['show_page'];
                    $cname = "【".$r['name']."】模块内容页(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } elseif (!isset($rname['{id}'])) {
                        $error.= "<p>".$cname."缺少{id}标签</p>";
                    } elseif (!isset($rname['{page}'])) {
                        $error.= "<p>".$cname."缺少{page}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=show&id=$'.$rname['{id}'].'&page=$'.$rname['{page}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['show']) {
                    $rule = $value['show'];
                    $cname = "【".$r['name']."】模块内容页（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{id}'])) {
                        $error.= "<p>".$cname."缺少{id}标签</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=show&id=$'.$rname['{id}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['search_page']) {
                    $rule = $value['search_page'];
                    $cname = "【".$r['name']."】模块搜索页(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } elseif (!isset($rname['{param}'])) {
                        $error.= "<p>".$cname."缺少{param}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=search&rewrite=$'.$rname['{param}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['search']) {
                    $rule = $value['search'];
                    $cname = "【".$r['name']."】模块搜索页（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=search';
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }

                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL.PHP_EOL;
            } elseif ($r['type'] == 3 ) {
                // 共享栏目
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL.PHP_EOL;
                if ($value['list_page']) {
                    $rule = $value['list_page'];
                    $cname = "【".$r['name']."】模块栏目列表(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{page}'])) {
                        $error.= "<p>".$cname."缺少{page}标签</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?c=category&dir=$'.$rname['{dirname}'].'&page=$'.$rname['{page}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?c=category&dir=$'.$rname['{pdirname}'].'&page=$'.$rname['{page}'];
                        } else {
                            // id模式
                            $rule = 'index.php?c=category&id=$'.$rname['{id}'].'&page=$'.$rname['{page}'];
                        }
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['list']) {
                    $rule = $value['list'];
                    $cname = "【".$r['name']."】模块栏目列表（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?c=category&dir=$'.$rname['{dirname}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?c=category&dir=$'.$rname['{pdirname}'];
                        } else {
                            // id模式
                            $rule = 'index.php?c=category&id=$'.$rname['{id}'];
                        }
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['show_page']) {
                    $rule = $value['show_page'];
                    $cname = "【".$r['name']."】模块内容页(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{page}'])) {
                        $error.= "<p>".$cname."缺少{page}标签</p>";
                    } elseif (!isset($rname['{id}'])) {
                        $error.= "<p>".$cname."缺少{id}标签</p>";
                    } else {
                        $rule = 'index.php?c=show&id=$'.$rname['{id}'].'&page=$'.$rname['{page}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['show']) {
                    $rule = $value['show'];
                    $cname = "【".$r['name']."】模块内容页（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{id}'])) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } else {
                        $rule = 'index.php?c=show&id=$'.$rname['{id}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL.PHP_EOL;
            } elseif ($r['type'] == 2 ) {
                // 共享模块
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL.PHP_EOL;
                if ($value['search_page']) {
                    $rule = $value['search_page'];
                    $cname = "【".$r['name']."】模块搜索页(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } elseif (!isset($rname['{param}'])) {
                        $error.= "<p>".$cname."缺少{param}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=search&rewrite=$'.$rname['{param}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['search']) {
                    $rule = $value['search'];
                    $cname = "【".$r['name']."】模块搜索页（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{modname}'])) {
                        $error.= "<p>".$cname."缺少{modname}标签</p>";
                    } else {
                        $rule = 'index.php?s=$'.$rname['{modname}'].'&c=search';
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL.PHP_EOL;
            } elseif ($r['type'] == 4 ) {
                // 关键词库插件
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL.PHP_EOL;
                if ($value['tag']) {
                    $rule = $value['tag'];
                    $cname = "【".$r['name']."】TagURL（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{tag}'])) {
                        $error.= "<p>".$cname."缺少{tag}标签</p>";
                    } else {
                        $rule = 'index.php?s=tag&name=$'.$rname['{tag}'];
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }

                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL.PHP_EOL;
            } elseif ($r['type'] == 0 ) {
                // 自定义页面插件
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL.PHP_EOL;
                if ($value['page_page']) {
                    $rule = $value['page_page'];
                    $cname = "【".$r['name']."】自定义页面(分页)（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{page}'])) {
                        $error.= "<p>".$cname."缺少{page}标签</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?s=page&dir=$'.$rname['{dirname}'].'&page=$'.$rname['{page}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?s=page&dir=$'.$rname['{pdirname}'].'&page=$'.$rname['{page}'];
                        } else {
                            // id模式
                            $rule = 'index.php?s=page&id=$'.$rname['{id}'].'&page=$'.$rname['{page}'];
                        }

                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                if ($value['page']) {
                    $rule = $value['page'];
                    $cname = "【".$r['name']."】自定义页面（{$rule}）";
                    list($preg, $rname) = $this->_rule_preg_value($rule);
                    if (!$preg || !$rname) {
                        $error.= "<p>".$cname."格式不正确</p>";
                    } elseif (!isset($rname['{dirname}']) && !isset($rname['{id}']) && !isset($rname['{pdirname}'])) {
                        $error.= "<p>".$cname."缺少{dirname}或{id}或{pdirname}标签</p>";
                    } else {
                        if (isset($rname['{dirname}'])) {
                            // 目录格式
                            $rule = 'index.php?s=page&dir=$'.$rname['{dirname}'];
                        } elseif (isset($rname['{pdirname}'])) {
                            // 层次目录格式
                            $rule = 'index.php?s=page&dir=$'.$rname['{pdirname}'];
                        } else {
                            // id模式
                            $rule = 'index.php?s=page&id=$'.$rname['{id}'];
                        }
                        if (isset($write[$preg])) {
                            $error.= "<p>".$cname."与".$write[$preg]."规则存在冲突</p>";
                        } else {
                            $write[$preg] = $cname;
                            $code.= '   "'.$preg.'" => "'.$rule.'",  //'.$cname.PHP_EOL;
                        }
                    }
                }
                $code.= PHP_EOL.PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL.PHP_EOL;
            }
        }

        return dr_return_data(1, dr_lang('生成成功'), [
            'code' => $code,
            'error' => $error,
        ]);
    }


    // 正则解析
    private function _rule_preg_value($rule) {

        $rule = trim(trim($rule, '/'));

        if (preg_match_all('/\{(.*)\}/U', $rule, $match)) {

            $value = array();
            foreach ($match[0] as $k => $v) {
                $value[$v] = ($k + 1);
            }

            $preg = preg_replace(
                array(
                    '#\{id\}#U',
                    '#\{uid\}#U',
                    '#\{mid\}#U',
                    '#\{fid\}#U',
                    '#\{page\}#U',

                    '#\{pdirname\}#Ui',
                    '#\{dirname\}#Ui',
                    '#\{modname\}#Ui',
                    '#\{name\}#Ui',

                    '#\{tag\}#U',
                    '#\{param\}#U',

                    '#\{y\}#U',
                    '#\{m\}#U',
                    '#\{d\}#U',

                    '#\{.+}#U',
                    '#/#'
                ),
                array(
                    '([0-9]+)',
                    '([0-9]+)',
                    '(\d+)',
                    '(\w+)',
                    '([0-9]+)',

                    '([\w\/]+)',
                    '([a-z0-9]+)',
                    '([a-z]+)',
                    '([a-z]+)',

                    '(.+)',
                    '(.+)',

                    '([0-9]+)',
                    '([0-9]+)',
                    '([0-9]+)',

                    '(.+)',
                    '\/'
                ),
                $rule
            );

            // 替换特殊的结果
            $preg = str_replace(
                array('(.+))}-'),
                array('(.+)-'),
                $preg
            );

            return array($preg, $value);
        }

        return array($rule, array());
    }

    /**
     * 补空格
     *
     * @param	string	$name	变量名称
     * @return	string
     */
    private function _space($name) {
        $len = strlen($name) + 2;
        $cha = 60 - $len;
        $str = '';
        for ($i = 0; $i < $cha; $i ++) $str .= ' ';
        return $str;
    }
}