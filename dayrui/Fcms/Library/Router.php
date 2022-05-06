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

    public function __construct(...$params) {

        $routes = \Config\Services::router(null, null, true);
        // 获取路由信息
        $this->class = strtolower(strpos($routes->controllerName(), '\\') !== false ? substr(strrchr($routes->controllerName(), '\\'), 1) : $routes->controllerName());
        $this->method = strtolower($routes->methodName());
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
        dr_redirect($url);
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

        if (isset($_GET['page']) && intval($_GET['page']) > 1) {
            return; // 排除分页
        } elseif (isset($_GET['not301']) && intval($_GET['not301']) > 1) {
            return; // 排除自定义参数
        }

        // 跳转
        $this->redirect($url);
    }

    // 执行跳转动作
    public function redirect($url) {

        // 跳转
        if ($url != FC_NOW_URL) {
            if (IS_DEV) {
                \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：<br>当前URL['.dr_now_url().']<br>与其本身地址['.$url.']不符<br>正在自动跳转本身地址（关闭开发者模式时即可自动跳转）', $url, 9);
            } elseif (defined('SYS_URL_ONLY') && SYS_URL_ONLY) {
                \Phpcmf\Service::C()->goto_404_page('匹配地址与实际地址不符');
            } else {
                dr_redirect($url, 'location', '301');
            }
        }
    }

    // 判断满足定向跳转的条件
    public function is_redirect_url($url, $is_mobile = 0) {

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
        } elseif (isset($_GET['page']) && intval($_GET['page']) > 1) {
            return; // 排除分页
        } elseif (isset($_GET['not301']) && intval($_GET['not301']) > 1) {
            return; // 排除自定义参数
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

        if (!$mod || !$data) {
            return '栏目URL参数不完整';
        }

        // 是否分页
        $page && $data['page'] = $page = is_numeric($page) ? max((int)$page, 1) : $page;
        !$page && $page = 1;

        // 获取自定义URL
        $rule = isset($data['setting']['urlrule']) ? \Phpcmf\Service::L('cache')->get('urlrule', (int)$data['setting']['urlrule'], 'value') : 0;
        if ($page > 1) {
            if (isset($data['myurl_page']) && $data['myurl_page']) {
                $url = ltrim($data['myurl_page'], '/');
                return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $data, $fid));
            } elseif ($rule && $rule['list_page']) {
                $url = ltrim($rule['list_page'], '/');
            }
        } else {
            if (isset($data['myurl']) && $data['myurl']) {
                $url = ltrim($data['myurl'], '/');
                return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $data, $fid));
            } elseif ($rule && $rule['list']) {
                $url = ltrim($rule['list'], '/');
            }
        }

        if ($url) {
            // URL模式为自定义，且已经设置规则
            $data['fid'] = $fid;
            $data['modname'] = $mod['share'] ? '共享栏目不能使用modname标签' : $mod['dirname'];
            $data['pdirname'].= $data['dirname'];
            $data['pdirname'] = str_replace('/', $rule['catjoin'], $data['pdirname']);
            $data['opdirname'] = $data['pid'] && isset($mod['category'][$data['pid']]) ? $mod['category'][$data['pid']]['dirname'] : $data['dirname'];
            $data['otdirname'] = $data['topid'] && isset($mod['category'][$data['topid']]) ? $mod['category'][$data['topid']]['dirname'] : $data['dirname'];
            return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $data, $fid));
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
    public function show_url($mod, $data, $page = 0) {

        if (!$mod || !$data) {
            return '内容URL参数不完整';
        }

        $cat = $mod['category'][$data['catid']];

        $page && $data['page'] = $page = is_numeric($page) ? max((int)$page, 1) : $page;
        !$page && $page = 1;

        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$cat['setting']['urlrule'], 'value');
        if ($page > 1) {
            if (isset($data['myurl_page']) && $data['myurl_page']) {
                $url = ltrim($data['myurl_page'], '/');
                return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $cat));
            } elseif ($rule && $rule['show_page']) {
                $url = ltrim($rule['show_page'], '/');
            }
        } else {
            if (isset($data['myurl']) && $data['myurl']) {
                $url = ltrim($data['myurl'], '/');
                return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $cat));
            } elseif ($rule && $rule['show']) {
                $url = ltrim($rule['show'], '/');
            }
        }

        if ($url) {
            // URL模式为自定义，且已经设置规则
            $data['cat'] = $cat;
            $data['modname'] = $mod['dirname'];
            $cat['pdirname'].= $cat['dirname'];
            $data['dirname'] = $cat['dirname'];
            $inputtime = isset($data['_inputtime']) ? $data['_inputtime'] : $data['inputtime'];
            $data['y'] = date('Y', $inputtime);
            $data['yy'] = date('y', $inputtime);
            $data['m'] = date('m', $inputtime);
            $data['d'] = date('d', $inputtime);
            $data['pdirname'] = str_replace('/', $rule['catjoin'], $cat['pdirname']);
            $data['opdirname'] = $cat['pid'] && isset($mod['category'][$cat['pid']]) ? $mod['category'][$cat['pid']]['dirname'] : $data['dirname'];
            $data['otdirname'] = $cat['topid'] && isset($mod['category'][$cat['topid']]) ? $mod['category'][$cat['topid']]['dirname'] : $data['dirname'];
            return $this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod, $cat));
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
    public function page_url($data, $page = 0) {

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
            return $this->get_url_value($data, $url, '/');
        }

        return $this->url_prefix('php') . 's=page&id=' . $data['id'] . ($page ? '&page=' . $page : '');
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

        // 绑定域名的情况下
        if ($mod['site'][$sid]['domain']) {
            return dr_http_prefix($mod['site'][$sid]['domain']) . '/';
        }

        // 自定义规则的情况下
        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$mod['urlrule'], 'value', 'module');
        if ($rule) {
            return dr_web_prefix(str_replace('{modname}', $mod['dirname'], $rule));
        }

        return dr_web_prefix('index.php?s=' . $mod['dirname']);
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
                    if (isset($value[$i]) && strlen((string)$value[$i])) {
                        $params[$_name] = $value[$i];
                    } else {
                        unset($params[$_name]);
                    }
                }
            } else {
                if (strlen((string)$value)) {
                    $params[$name] = $value;
                } else {
                    unset($params[$name]);
                }
            }
        }

        if (is_array($params)) {
            foreach ($params as $i => $t) {
                if (strlen((string)$t) == 0) {
                    unset($params[$i]);
                }
            }
        }

        $rule = \Phpcmf\Service::L('cache')->get('urlrule', (int)$mod['urlrule'], 'value');
        if ($rule && $rule['search']) {
            $fid && $data['fid'] = $fid;
            $data['modname'] = $mod['dirname'];
            $data['param'] = dr_search_rewrite_encode($params, $mod['setting']['search']);
            if ($params && !$data['param']) {
                log_message('debug', '模块['.$mod['dirname'].']无法通过[搜索参数字符串规则]获得参数');
            }
            $url = ltrim($data['param'] ? $rule['search_page'] : $rule['search'], '/');
            return dr_url_prefix($this->get_url_value($data, $url, $this->url_prefix('rewrite', $mod)), $mod['dirname']);
        } else {
            return dr_url_prefix($this->url_prefix('php', $mod, [], $fid) . trim('c=search&' . (is_array($params) ? http_build_query($params) : ''), '&'), $mod['dirname']);
        }
    }

    // 伪静态替换
    public function get_url_value($data, $rule, $prefix) {
        $rep = new \php5replace($data);
        $url = $rep->replace($rule);
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
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL;
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname.'</textarea>';
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
                            $code.= '<textarea class="form-control" rows="1">   "'.$preg.'" => "'.$rule.'",  //'.$cname.'</textarea>';
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
                        }
                    }
                }

                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL;
            } elseif ($r['type'] == 3 ) {
                // 共享栏目
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL;
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
                        }
                    }
                }
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL;
            } elseif ($r['type'] == 2 ) {
                // 共享模块
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL;
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
                        }
                    }
                }
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL;
            } elseif ($r['type'] == 4 ) {
                // 关键词库插件
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL;
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
                        }
                    }
                }

                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL;
            } elseif ($r['type'] == 0 ) {
                // 自定义页面插件
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----开始'.PHP_EOL;
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
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
                            $code.= '<textarea class="form-control" rows="1">    "'.$preg.'" => "'.$rule.'",  //'.$cname."</textarea>";
                        }
                    }
                }
                $code.= PHP_EOL.'	// '.$r['name'].'---解析规则----结束'.PHP_EOL;
            }
        }

        return dr_return_data(1, dr_lang('生成成功'), [
            'code' => nl2br($code),
            'error' => $error,
        ]);
    }


    // 正则解析
    protected function _rule_preg_value($rule) {

        $rule = trim(trim($rule, '/'));

        if (preg_match_all('/\{(.*)\}/U', $rule, $match)) {

            $value = [];
            foreach ($match[0] as $k => $v) {
                $value[$v] = ($k + 1);
            }

            $preg = preg_replace(
                [
                    '#\{id\}#U',
                    '#\{uid\}#U',
                    '#\{mid\}#U',
                    '#\{fid\}#U',
                    '#\{page\}#U',

                    '#\{pdirname\}#Ui',
                    '#\{dirname\}#Ui',
                    '#\{opdirname\}#Ui',
                    '#\{otdirname\}#Ui',
                    '#\{modname\}#Ui',
                    '#\{name\}#Ui',

                    '#\{tag\}#U',
                    '#\{param\}#U',

                    '#\{y\}#U',
                    '#\{m\}#U',
                    '#\{d\}#U',

                    '#\{.+}#U',
                    '#/#'
                ],
                [
                    '([0-9]+)',
                    '([0-9]+)',
                    '(\d+)',
                    '(\w+)',
                    '([0-9]+)',

                    '([\w\/]+)',
                    '([A-za-z0-9 \-\_]+)',
                    '([A-za-z0-9 \-\_]+)',
                    '([A-za-z0-9 \-\_]+)',
                    '([a-z]+)',
                    '([a-z]+)',

                    '(.+)',
                    '(.+)',

                    '([0-9]+)',
                    '([0-9]+)',
                    '([0-9]+)',

                    '(.+)',
                    '\/'
                ],
                $rule
            );

            // 替换特殊的结果
            $preg = str_replace(
                ['(.+))}-', '.html'],
                ['(.+)-', '\.html'],
                $preg
            );

            return [$preg, $value];
        }

        return [$rule, []];
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
}