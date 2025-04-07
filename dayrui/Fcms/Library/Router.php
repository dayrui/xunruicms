<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 路由及url处理类

class Router {

    public $class;
    public $method;

    protected $_uri;
    protected $_temp;

    public function __construct() {
        // 获取路由信息
        $this->class = strtolower(isset($_GET['c']) ? dr_safe_filename($_GET['c']) : 'home');
        $this->method = strtolower(isset($_GET['m']) ? dr_safe_filename($_GET['m']) : 'index');
    }

    // 获取用户中心,当前页面的URI
    public function member_uri() {
        $u = (APP_DIR && APP_DIR != 'member' ? APP_DIR . '/' : '') . $this->class . '/';
        return [trim($u . $this->method, '/'), trim($u . 'index', '/')];
    }

    // 获取当前页面的URI
    public function uri($m = '') {
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

        $url = $this->member_url('login/index', ['back' => urlencode($url)]);
        if (IS_DEV) {
            \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：登录超时<br>正在自动跳转到登录页面（关闭开发者模式时即可自动跳转）', $url, 9);
        } else {
            dr_redirect($url);
        }
        exit;
    }

    // 获取返回时的URL
    public function get_back($uri, $param = [], $remove_total = false) {

        $name = md5((string)$_SERVER['HTTP_USER_AGENT'] . SELF . $uri . \Phpcmf\Service::C()->uid . SITE_ID . \Phpcmf\Service::L('input')->ip_address());
        $value = \Phpcmf\Service::L('cache')->get_data($name);
        if ($value) {
            $uri = $value[0];
            $param = dr_array22array($param, $value[1]);
        }
        // 移除统计参数
        if ($remove_total && isset($param['total'])) {
            unset($param['total']);
        }

        return IS_ADMIN ? $this->url($uri, $param) : $this->member_url($uri, $param);
    }

    // 设置返回时的URL, uri页面标识,param参数,nuri当前页优先
    public function set_back($uri, $param = [], $nuri = '') {

        $name = md5((string)$_SERVER['HTTP_USER_AGENT'] . SELF . $uri . \Phpcmf\Service::C()->uid . SITE_ID . \Phpcmf\Service::L('input')->ip_address());
        $param['page'] = $_GET['page'];
        \Phpcmf\Service::L('cache')->set_data(
            $name,
            [$nuri ? $nuri : $uri, $param],
            3600
        );
    }

    // 清理返回url
    public function clear_back($uri) {
        $name = md5((string)$_SERVER['HTTP_USER_AGENT'] . SELF . $uri . \Phpcmf\Service::C()->uid . SITE_ID . \Phpcmf\Service::L('input')->ip_address());
        \Phpcmf\Service::L('cache')->del_data($name);
    }

    // 自动识别的跳转动作
    public function auto_redirect($url) {

        if (isset($_GET['not301']) && intval($_GET['not301']) > 1) {
            return; // 排除自定义参数
        } elseif (isset($_GET['page']) && intval($_GET['page']) > 1) {
            return; // 排除分页
        }

        // 跳转
        $this->redirect($url, true);
    }

    // 执行跳转动作
    public function redirect($url, $auto = false) {

        // 跳转
        if ($url != FC_NOW_URL) {
            if (!$auto) {
                if (IS_DEV) {
                    if (defined('SYS_URL_ONLY') && SYS_URL_ONLY) {
                        \Phpcmf\Service::C()->_admin_msg(0, '当前URL['.dr_now_url().']<br>与其本身地址['.$url.']不符<br>关闭开发者模式时本页面将成为404');
                    }
                    \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：<br>当前URL['.dr_now_url().']<br>与其本身地址['.$url.']不符<br>正在自动跳转本身地址（关闭开发者模式时即可自动跳转）', $url, 9);
                } elseif (defined('SYS_URL_ONLY') && SYS_URL_ONLY) {
                    \Phpcmf\Service::C()->goto_404_page('匹配地址与实际地址不符');
                }
            } elseif (IS_DEV) {
                // 自动识别
                \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：<br>当前URL['.dr_now_url().']<br>自动识别为['.$url.']<br>若不需要识别功能可在后台-设置-手机设置-关闭自动识别（如果开启了CDN请关闭自动识别）<br>正在自动跳转本身地址（关闭开发者模式时即可自动跳转）', $url, 9);
            }
            dr_redirect($url, 'location', '301');
        }
    }

    // 判断满足定向跳转的条件
    public function is_redirect_url($url, $is_mobile = 0, $is_page = 0) {

        // 不跳转的条件
        if (!dr_is_sys_301()) {
            return; // 系统关闭301
        } elseif (!$url || strpos($url, 'http') === FALSE) {
            return; // 为空时排除
        } elseif (IS_API || IS_API_HTTP) {
            return; // 排除接口
        } elseif (IS_ADMIN) {
            return; // 排除后台
        } elseif (!$is_mobile && \Phpcmf\Service::IS_MOBILE()) {
            return; // 排除移动端,移动端不跳转开关
        } elseif (defined('SC_HTML_FILE')) {
            return; // 排除生成
        } elseif (isset($_GET['not301']) && intval($_GET['not301']) > 1) {
            return; // 排除自定义参数
        } elseif (!$is_page && isset($_GET['page']) && intval($_GET['page']) > 1) {
            return; // 排除分页
        } elseif (IS_CLIENT) {
            return; // 排除终端
        }

        $this->redirect($url);
    }

    /**
     * url函数
     *
     * @param    string $url URL规则，如home/index
     * @param    array $query 相关参数
     * @return    string    项目入口文件.php?参数
     */
    public function url($url, $query = [], $self = SELF) {

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
            if ($self == '/index.php') {
                // 表示相对路径
                $self = dr_web_prefix('index.php');
            } else {
                if (defined('IS_CLIENT')) {
                    // 终端前缀
                    $self = CLIENT_URL.'index.php';
                } elseif (\Phpcmf\Service::IS_MOBILE_TPL()){
                    // 移动端模板 域名
                    $self = SITE_MURL.'index.php';
                } else {
                    $self = SITE_URL.'index.php';
                }
            }
        }
        
        $uri = [];
        $url = explode('/', $url);

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
                        $self = (!\Phpcmf\Service::IS_MOBILE_TPL() ? $mod['url'] : dr_mobile_url($mod['url'])) . 'index.php';
                    } else {
                        $self = (!\Phpcmf\Service::IS_MOBILE_TPL() ? SITE_URL : SITE_MURL) . 'index.php';
                    }
                }
                $uri['c'] = $url[1];
                $uri['m'] = $url[2];
                break;
        }

        $query && $uri = array_merge($uri, $query);

        return $self . '?' . http_build_query($uri);
    }

    /**
     * 会员url函数
     *
     * @param    string $url URL规则，如home/index
     * @param    array $query 相关参数
     * @return    string    地址
     */
    public function member_url($url = '', $query = [], $null = '') {

        if (!$url || $url == 'member/home/index' || $url == 'home/index' || $url == '/') {
            return MEMBER_URL;
        }

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

        $query && $uri = array_merge($uri, $query);

        // 未绑定域名的情况下
        return (IS_CLIENT ? CLIENT_URL : (\Phpcmf\Service::IS_MOBILE_TPL() ? SITE_MURL : SITE_URL)).'index.php?' . http_build_query($uri);
    }

    /**
     * 模块栏目URL地址
     *
     * @param    array $mod
     * @param    array $data
     * @param    intval $page
     * @return    string
     */
    public function category_url($mod, $data, $page = 0, $fid = 0) {
        if (function_exists('dr_module_category_url')) {
            return dr_module_category_url($mod, $data, $page, $fid);
        } else {
            return '需要升级建站系统插件';
        }
    }

    /**
     * 模块内容URL地址
     *
     * @param    array $mod
     * @param    array $data
     * @param    mod $page
     * @return    string
     */
    public function show_url($mod, $data, $page = 0) {
        if (function_exists('dr_module_show_url')) {
            return dr_module_show_url($mod, $data, $page);
        } else {
            return '需要升级建站系统插件';
        }
    }

    /*
     * 单页URL地址 后期废弃
     *
     * @param	array	$data
     * @param	intval	$page
     * @return	string
     */
    public function page_url($data, $page = 0) {
        return '请升级自定义页面插件';
    }

    /**
     * tag的url
     */
    public function tag_url($name) {

        if (!$name) {
            return 'TagURL name参数为空';
        } elseif (!dr_is_app('tag')) {
            return '关键词库应用没有安装';
        }

        $obj = \Phpcmf\Service::M('tag', 'tag');
        if (method_exists($obj, 'tag_url')) {
            return $obj->tag_url($name);
        }

        return '关键词库插件未升级';
    }

    // 缓存读取url
    public function get_tag_url($name, $mid = '') {

        if (!$name) {
            return '/#无name参数';
        }

        if (dr_is_app('tag')) {
            $obj = \Phpcmf\Service::M('tag', 'tag');
            if (method_exists($obj, 'get_tag_url')) {
                $url = $obj->get_tag_url($name);
                if ($url) {
                    return $url;
                }
            }
        }

        if ($mid) {
            return $this->search_url([], 'keyword', $name, $mid);
        }

        return WEB_DIR;
    }

    // 模块URL
    public function module_url($mod, $sid) {

        if (function_exists('dr_module_index_url')) {
            return dr_module_index_url($mod, $sid);
        } else {
            return '需要升级建站系统插件';
        }
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
    public function search_url($params = [], $name = '', $value = '', $mid = '', $fid = SITE_FID) {

        if (function_exists('dr_module_search_url')) {
            return dr_module_search_url($params, $name, $value, $mid, $fid);
        } else {
            return '需要升级建站系统插件';
        }
    }

    // 伪静态替换
    public function get_url_value($data, $rule, $prefix) {
        $rep = new \php5replace($data);
        $url = (string)$rep->replace($rule);
        if (dr_is_url($url)) {
            return $url; // 带域名的url直接返回
        }
        $url = ltrim(str_replace('//', '/', $url), '/');
        if (IS_DEV && strpos($url, '?') !== false) {
            log_message('debug', '开发者模式提醒：自定义URL规则['.$rule.']不建议包含问号?');
        }
        return $prefix.$url;
    }

    // 快捷登录地址
    public function oauth_url($name, $type, $gourl = '') {
        return OAUTH_URL . 'index.php?s=api&c=oauth&m=index&name=' . $name . '&type=' . $type.'&back='.urlencode((string)$gourl);
    }

    // 地址前缀部分
    public function url_prefix($type, $mod = [], $cat = [], $fid = 0) {

        $dir = isset($mod['dirname']) ? $mod['dirname'] : '';
        if (\Phpcmf\Service::IS_MOBILE_TPL()) {
            $domain = isset($mod['domain_mobile']) ? $mod['domain_mobile'] : '';
        } else {
            $domain = isset($mod['domain']) ? $mod['domain'] : '';
        }

        if ($cat) {
            $dir = isset($cat['mid']) ? $cat['mid'] : $dir;
        }

        // 默认主网站的地址
        $url = '/';
        switch ($type) {

            // 动态模式
            case 'php':
                $url = '/index.php?' . (!$mod || $domain ? '' : 's=' . $dir . '&') . (!$fid ? '' : 'fid=' . $fid . '&');
                break;

            // 模块动态模式
            case 'module_php':
                $url = '/index.php?' . ($mod['share'] || $domain ? '' : 's=' . $dir . '&') . (!$fid ? '' : 'fid=' . $fid . '&');
                break;

            // 自定义url模式
            case 'rewrite':
                $url = '/';
                break;
        }

        return trim($url);
    }

    // 废弃
    public function furl($fid) {

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
    public function remove_domain($url, $doamin = '') {

        if (!$this->_temp['domain']) {
            $site_domian = \Phpcmf\Service::R(WRITEPATH.'config/domain_site.php');
            foreach ($site_domian as $u => $i) {
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
     * 伪静态设置代码
     */
    public function rewrite_code() {

        $root = '';
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        if (strpos($server, 'apache') !== FALSE) {
            $name = 'Apache';
            $note = '<font color=red><b>'.dr_lang('将以下内容保存为.htaccess文件，放到每个域名所绑定的根目录').'</b></font>';
            $code = '';
            $code.= 'RewriteEngine On'.PHP_EOL.PHP_EOL;
            $code.= 'RewriteBase '.$root.'/'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL
                .'RewriteRule !.(js|ico|gif|jpe?g|bmp|png|css)$ '.$root.'/index.php [NC,L]'.PHP_EOL.PHP_EOL;
        } elseif (strpos($server, 'nginx') !== FALSE) {
            $name = $server;
            $note = '<font color=red><b>'.dr_lang('将以下代码放到Nginx配置文件中去（如果是绑定了域名，所绑定目录也要配置下面的代码）').'</b></font>';
            // 子目录
            $code = '###当存在多个子目录格式的域名时，需要多写几组location标签：location /目录/ '.PHP_EOL;
            // 主目录
            $code.= 'location '.$root.'/ { '.PHP_EOL
                .'    if (-f $request_filename) {'.PHP_EOL
                .'           break;'.PHP_EOL
                .'    }'.PHP_EOL
                .'    if ($request_filename ~* "\.(js|ico|gif|jpe?g|bmp|png|css)$") {'.PHP_EOL
                .'        break;'.PHP_EOL
                .'    }'.PHP_EOL
                .'    if (!-e $request_filename) {'.PHP_EOL
                .'        rewrite . '.$root.'/index.php last;'.PHP_EOL
                .'    }'.PHP_EOL
                .'}'.PHP_EOL;
        } else {
            $name = $server;
            $note = '<font color=red><b>'.dr_lang('无法为此服务器提供伪静态规则，建议让运营商帮你把下面的Apache规则做转换').'</b></font>';
            $code = 'RewriteEngine On'.PHP_EOL
                .'RewriteBase /'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL
                .'RewriteRule !.(js|ico|gif|jpe?g|bmp|png|css)$ /index.php [NC,L]';
        }

        return [$name, $note, $code];
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


    /**
     * 补空格
     *
     * @param	string	$name	变量名称
     * @return	string
     */
    protected function _space($name) {

        $str = '';
        $len = strlen($name) + 2;
        $cha = 60 - $len;

        for ($i = 0; $i < $cha; $i ++) {
            $str .= ' ';
        }

        return $str;
    }


    //////////////////////////////////////////////////


    // 评论地址
    public function comment_url($id, $mid = '') {

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $obj = \Phpcmf\Service::M('comment', 'comment');
        if (method_exists($obj, 'comment_url')) {
            return $obj->comment_url($id, $mid);
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return dr_url_prefix($this->url_prefix('php', $module, [], SITE_FID) . 'c=comment&id=' . $id);
    }

    // 打赏
    public function donation_url($id, $mid = '') {

        if (!dr_is_app('shang')) {
            return '没有安装【打赏】应用';
        }

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $obj = \Phpcmf\Service::M('buy', 'shang');
        if (method_exists($obj, 'donation_url')) {
            return $obj->donation_url($id, $mid);
        }

        return $this->url_prefix('php', [], [], SITE_FID) . 's=shang&mid='.$dir.'&id=' . $id;
    }

    // 模块表单内容地址
    public function mform_show_url($form, $id, $mid = '', $page = 0) {

        if (!dr_is_app('mform')) {
            return '没有安装【模块表单】应用';
        }

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $obj = \Phpcmf\Service::M('mform', 'mform');
        if (method_exists($obj, 'show_url')) {
            return $obj->show_url($form, $id, $mid, $page);
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=show&id=' . $id . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 模块表单提交地址
    public function mform_post_url($form, $cid, $mid = '') {

        if (!dr_is_app('mform')) {
            return '没有安装【模块表单】应用';
        }

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $obj = \Phpcmf\Service::M('mform', 'mform');
        if (method_exists($obj, 'post_url')) {
            return $obj->post_url($form, $cid, $mid);
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=post&cid=' . $cid;
    }

    // 模块表单列表地址
    public function mform_list_url($form, $cid, $mid = '', $page = 0) {

        if (!dr_is_app('mform')) {
            return '没有安装【模块表单】应用';
        }

        // 模块目录识别
        defined('MOD_DIR') && MOD_DIR && $dir = MOD_DIR;
        $mid && $dir = $mid;

        if (!dr_is_module($dir)) {
            return '没有安装【'.$dir.'】模块';
        }

        $obj = \Phpcmf\Service::M('mform', 'mform');
        if (method_exists($obj, 'list_url')) {
            return $obj->list_url($form, $cid, $mid);
        }

        $module = \Phpcmf\Service::L('cache')->get('module-' . SITE_ID . '-' . $dir);

        return $this->url_prefix('php', $module, [], SITE_FID) . 'c=' . $form . '&m=index&cid=' . $cid . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 网站表单内容地址
    public function form_show_url($form, $id, $page = 0) {

        if (!dr_is_app('form')) {
            return '没有安装【全局网站表单】应用';
        }

        $obj = \Phpcmf\Service::M('form', 'form');
        if (method_exists($obj, 'show_url')) {
            return $obj->show_url($form, $id, $page);
        }

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=show&id=' . $id . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 网站表单提交地址
    public function form_post_url($form) {

        if (!dr_is_app('form')) {
            return '没有安装【全局网站表单】应用';
        }

        $obj = \Phpcmf\Service::M('form', 'form');
        if (method_exists($obj, 'show_url')) {
            return $obj->post_url($form);
        }

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=post';
    }

    // 网站表单列表地址
    public function form_list_url($form, $page = 0) {

        if (!dr_is_app('form')) {
            return '没有安装【全局网站表单】应用';
        }

        $obj = \Phpcmf\Service::M('form', 'form');
        if (method_exists($obj, 'show_url')) {
            return $obj->list_url($form, $page);
        }

        return $this->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    public function get_rewrite_code() {
        return dr_return_data(0, '请升级建站系统插件');
    }
}