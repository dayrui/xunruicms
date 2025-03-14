<?php namespace Phpcmf;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * CMS模板标签解析
 */
class View {

    private $_is_admin; // 是否后台模板
    private $_is_return; // 是否返回模板名称不输出模板
    private $_disp_dir; // display传人的目录参数

    private $_dir; // 模板目录
    private $_tname; // 判断是否是手机端目录

    private $_cache; // 模板缓存目录

    private $_root; // 默认前端项目模板目录
    private $_mroot; // 默认会员项目模板目录

    private $_aroot; // 默认后台项目模板目录
    private $_froot; // 默认后台项目的修正模板目录
    private $_load_file_tips; // 模板引用提示

    private $_options; // 模板变量
    private $_filename; // 主模板名称
    private $_include_file; // 引用计数
    private $_return_sql; // 是否返回sql语句用于运算

    private $_view_time; // 模板的运行时间
    private $_view_files; // 累计模板的引用文件
    private $_view_file; // 当前模板的引用文件

    private $pos_map; // 地图定位坐标
    private $pos_order; // 是否包含有地图定位的排序

    private $action; // 指定action

    public $_is_mobile; // 是否是移动端模板
    private $_is_pc; // 是否是pc模板
    private $_sql; // 最近运行的sql语句
    private $_sql_time; // 最近运行的sql语句的时间

    private $performanceData = []; // 用于调试栏数据
    private $loadjs = []; // 加载的js

    private $_page_config = []; // 分页参数
    private $_page_config_file = ''; // 分页配置文件
    private $_page_urlrule = ''; // 分页地址参数
    private $_page_used = 0; // 是否开启分页

    private $_list_tag = ''; // 循环体标签
    private $_list_where = []; // 循环体解析的条件数组
    private $_list_error = []; // 循环标签遇到的错误
    private $_is_list_search = 0; // 搜索标签
    private $_page_value = 0; // 页码变量
    private $_sum_field; // 求和时的字段

    private $_code;
    private $_mdir;

    private $_select_rt_name = '_XUNRUICMS_RT_'; // select替换字符

    public $call_value; // 动态模板返回调用


    /**
     * 初始化环境
     */
    public function init($name = 'pc') {

        $this->_is_pc = $name == 'pc'; // 标记为pc端模板
        $this->_is_mobile = $name == 'mobile'; // 标记为移动端模板

        // 模板缓存目录
        $this->_cache = WRITEPATH.'template/';
        $this->_tname = $this->_is_mobile ? MOBILE_TPL_DIR : ($name ? $name : 'pc');
        $this->_aroot = $this->_froot = COREPATH.'View/';
        $this->_mroot = $this->get_client_member_path($this->_tname);
        // 当前项目模板目录
        if (IS_ADMIN) {
            // 后台
            $this->admin();
        } elseif (IS_MEMBER) {
            // 会员
            //$this->_is_mobile && !is_dir(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/') && $this->_tname = 'pc';
            $this->_root = $this->get_client_home_path($this->_tname);
            $this->_dir = $this->_mroot;
            // 当用户中心没有这个模板目录时我们就调用default的用户中心模板
            !is_dir($this->_mroot) && $this->_mroot = str_replace('/'.SITE_TEMPLATE.'/', '/default/', $this->_mroot);
            // 项目的会员中心
            APP_DIR != 'member' && $this->_dir = $this->get_client_member_path($this->_tname, APP_DIR);
        } elseif (IS_API || (APP_DIR && dr_is_app_dir(APP_DIR))) {
            // 插件模块或者api
            $dir = IS_API ? 'api' : APP_DIR;
            //$this->_is_mobile && !is_dir(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/') && $this->_tname = 'pc';
            $this->_root = $this->get_client_home_path($this->_tname);
            $this->_dir = $this->get_client_home_path($this->_tname, $dir);
        } else {
            // 首页前端页面
            //$this->_is_mobile && !is_dir(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/') && $this->_tname = 'pc';
            $this->_dir = $this->_root = $this->get_client_home_path($this->_tname);
        }

        // 系统action
        $this->action = [
            'category', 'module', 'content', 'related', 'share', 'table', 'form', 'mform', 'member', 'page',
            'tag', 'hits', 'search', 'category_search_field', 'linkage', 'sql', 'function', 'comment',
            'cache', 'navigator'
            ,'modules'
        ];
    }

    // 判断是pc端模板
    public function is_pc() {
        return $this->_is_pc;
    }

    // 判断是移动端模板
    public function is_mobile() {
        return $this->_is_mobile;
    }

    // 终端路径
    private function get_client_home_path($name, $mid = '') {
        return dr_get_app_tpl($mid).$name.'/'.SITE_TEMPLATE.'/home/'.($mid ? $mid.'/' : '');
    }

    // 终端路径
    private function get_client_member_path($name, $mid = '') {
        return dr_get_app_tpl($mid).$name.'/'.SITE_TEMPLATE.'/member/'.($mid ? $mid.'/' : '');
    }

    /**
     * 强制设置模块模板目录
     *
     * @param   string  $dir    模板名称
     * @param   string  $isweb  强制前台
     */
    public function module($module, $isweb = 0) {

        if ($isweb == 0 && (IS_ADMIN || IS_MEMBER)) {
            return;
        }

        // 默认模板目录
        $this->_is_admin = 0;
        $this->_root = $this->get_client_home_path($this->_tname);
        $this->_dir = $this->get_client_home_path($this->_tname, $module);
    }

    // 外部获取当前应用的模板路径
    public function get_dir() {
        return $this->_dir;
    }

    /**
     * 强制设置为当前默认的模板目录(一般用于api外部接入)
     */
    public function dir($dir) {
        $this->_dir = $this->_mdir = TPLPATH.$dir;
    }

    /**
     * 强制设置为当前默认的模板目录
     */
    public function set_dir($path) {
        $this->_dir = $this->_mdir = $path;
    }

    /**
     * 设置是否返回模板名称不显示
     */
    public function set_return($is) {
        $this->_is_return = $is;
    }

    /**
     * 强制设置为后台模板目录
     */
    public function admin($path = '', $fix_path = '') {
        $this->_aroot = COREPATH.'View/';
        $this->_dir = $path ? $path : (APP_DIR ? APPPATH.'Views/' : $this->_aroot);
        $this->_froot = $fix_path ? $fix_path : (APP_DIR ? APPPATH.'Views/' : $this->_aroot);
        $this->_is_admin = 1;
    }

    /**
     * 当前模板对应的URL地址
     */
    public function now_php_url() {

        if (isset($this->_options['my_php_url']) && $this->_options['my_php_url']) {
            return $this->_options['my_php_url'];
        } elseif (isset($this->_options['my_web_url']) && $this->_options['my_web_url']) {
            return $this->_options['my_web_url'];
        }

        return FC_NOW_URL;
    }

    /**
     * 输出模板
     *
     * @param   string  $_name      模板文件名称（含扩展名）
     * @param   string  $_dir       模块名称
     * @param   boll  $is_destruction   是否销毁变量
     * @return  void
     */
    public function display($phpcmf_name, $phpcmf_dir = '', $is_destruction = true) {

        if ($this->_is_return) {
            return $phpcmf_name;
        }

        $phpcmf_start = microtime(true);

        // 定义当前模板的url地址
        if (!isset($this->_options['my_web_url']) or !$this->_options['my_web_url']) {
            $this->_options['my_web_url'] = isset($this->_options['fix_html_now_url']) && $this->_options['fix_html_now_url'] ? $this->_options['fix_html_now_url'] : dr_now_url();
        }

        // 定义当前url参数值
        if (!isset($this->_options['get'])) {
            $this->_options['get'] = \Phpcmf\Service::L('input')->xss_clean($_GET);
        }

        // 如果是来自api就不解析模板，直接输出变量
        if (IS_API_HTTP) {
            \Phpcmf\Service::C()->_json(1, '当前是get请求方式，返回当前模板可用的变量', $this->_options);
        }

        // 生成静态时退出账号记录
        if (defined('SC_HTML_FILE')) {
            $this->_options['member'] = [];
        }

        // 挂钩点 模板加载之前
        \Phpcmf\Hooks::trigger('cms_view_display', $this->_options, $phpcmf_name, $phpcmf_dir);

        extract($this->_options, EXTR_SKIP);

        $ci = \Phpcmf\Service::C(); // 控制器对象简写
        $phpcmf_name && $this->_filename = str_replace('..', '[removed]', $phpcmf_name);

        // 加载编译后的缓存文件
        $this->_disp_dir = $phpcmf_dir;
        $this->_view_file = $_view_file = $this->get_file_name($this->_filename, $phpcmf_dir);

        $is_dev = 0;
        if ((IS_DEV || (IS_ADMIN && SYS_DEBUG))
            && !isset($_GET['callback']) && !isset($_GET['is_ajax'])
            && !IS_API_HTTP && !IS_AJAX) {
            $is_dev = 1;
            echo "<!--当前页面的模板文件是：$_view_file （本代码只在开发者模式下显示）-->".PHP_EOL;
        }

        // 兼容php8
        !defined('IS_SHARE') && define('IS_SHARE', 1);
        !defined('IS_OEM_CMS') && define('IS_OEM_CMS', 0);
        !defined('MOD_DIR') && define('MOD_DIR', '');
        !defined('MODULE_NAME') && define('MODULE_NAME', '');
        !defined('MODULE_URL') && define('MODULE_URL', '');
        !defined('SITE_TITLE') && define('SITE_TITLE', SITE_NAME);

        !defined('IS_PC') && define('IS_PC', \Phpcmf\Service::IS_PC_USER());
        !defined('IS_MOBILE') && define('IS_MOBILE', \Phpcmf\Service::IS_MOBILE_USER());
        !defined('IS_MOBILE_USER') && define('IS_MOBILE_USER', \Phpcmf\Service::IS_MOBILE_USER());
        !defined('IS_COMMENT') && define('IS_COMMENT', dr_is_app('comment'));
        !defined('IS_CLIENT') && define('IS_CLIENT', '');

        $LANG_PATH = LANG_PATH;
        $THEME_PATH = THEME_PATH;

        $_temp_file = $this->load_view_file($_view_file);

        // 挂钩点 模板加载之后
        \Phpcmf\Hooks::trigger('cms_view', $this->_options, $_temp_file);

        include $_temp_file;

        // 挂钩点 模板结束之后
        \Phpcmf\Hooks::trigger('cms_view_end');

        $this->_view_time = round(microtime(true) - $phpcmf_start, 2);

        // 消毁变量
        if ($is_destruction) {
            unset($this->loadjs);
            unset($this->_include_file);
            if (!$is_dev) {
                unset($this->_options);
            }
            if (CI_DEBUG || !defined('SC_HTML_FILE')) {
                \Phpcmf\Service::M()->close();
            }
        }
    }

    // 动态加载js
    public function load_js($js) {
        if (isset($this->loadjs[$js])) {
            return '';
        } else {
            $this->loadjs[$js] = 1;
            return '<script type=\'text/javascript\' src=\''.$js.'\'></script>';
        }
    }

    /**
     * 设置模块/应用的模板目录
     *
     * @param   string  $file       文件名
     * @param   string  $dir        模块/应用名称
     * @param   string  $include    是否使用的是include标签
     */
    public function get_file_name($file, $dir = null, $include = FALSE) {

        if (!$file) {
            $this->show_error('模板文件没有设置');
        }

        $dir = $dir ? $dir : $this->_disp_dir;
        $file = str_replace('..', '', (string)$file); // 安全规范化模板名称引入

        if ($dir == 'admin' || $this->_is_admin) {
            // 后台操作时，不需要加载风格目录，如果文件不存在可以尝试调用主项目模板

            if (APP_DIR && is_file(MYPATH.'View/'.APP_DIR.'/'.$file)) {
                return MYPATH . 'View/' . APP_DIR . '/' . $file;
            } elseif (is_file(MYPATH.'View/'.$file) && is_file(COREPATH.'View/'.$file)) {
                if (APP_DIR && is_file($this->_dir.$file)) {
                    // 防止app目录加载主目录的模板
                    return $this->_dir.$file;
                }
                return MYPATH.'View/'.$file;
            } elseif ((!APP_DIR or $dir == 'admin') && is_file(MYPATH.'View/'.$file)) {
                // 强制定位admin目录时验证my目录文件
                return MYPATH.'View/'.$file;
            } elseif (is_file($this->_dir.$file)) {
                return $this->_dir.$file; // 调用当前后台的模板
            } elseif (is_file($this->_froot.$file)) {
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用主目录的模板['.$this->_froot.$file.']！';
                return $this->_froot.$file; // 当前项目目录模板不存在时调用主项目的
            } elseif (is_file($this->_aroot.$file)) {
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用主目录的模板['.$this->_aroot.$file.']';
                return $this->_aroot.$file; // 当前项目目录模板不存在时调用主项目的
            } elseif (APP_DIR && is_file(MYPATH.'View/'.$file)) {
                return MYPATH.'View/'.$file; // 当前项目目录模板不存在时调用主项目的My目录
            } elseif ($dir != 'admin' && is_file(APPSPATH.ucfirst($dir).'/Views/'.$file)) {
                return APPSPATH.ucfirst($dir).'/Views/'.$file; // 指定模块时调用模块下的文件
            }
            $error = $this->_dir.$file;
        } elseif (IS_MEMBER || $dir == 'member') {
            // 会员操作时，需要加载风格目录，如果文件不存在可以尝试调用主项目模板
            if ($dir === '/' && is_file($this->_root.$file)) {
                return $this->_root.$file;
            } elseif (is_file($this->_dir.$file)) {
                // 调用当前的会员模块目录
                return $this->_dir.$file;
            } elseif (is_file($this->_mroot.$file)) {
                // 调用默认的会员模块目录
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用会员模块目录的模板['.$this->_mroot.$file.']';
                return $this->_mroot.$file;
            } elseif (is_file($this->_root.$file)) {
                // 调用网站主站模块目录
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用主目录的模板['.$this->_root.$file.']';
                return $this->_root.$file;
            }
            $error = $dir === '/' ? $this->_root.$file : $this->_dir.$file;
        } elseif ($file == 'goto_url') {
            // 转向字段模板
            return COREPATH.'View/go.html';
        } else {
            if ($dir === '/' && is_file($this->_root.$file)) {
                // 强制主目录
                return $this->_root.$file;
            } elseif (is_file($this->_dir.$file)) {
                // 调用本目录
                return $this->_dir.$file;
            } elseif (is_file($this->_root.$file)) {
                // 再次调用主程序下的文件
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用主目录的模板['.$this->_root.$file.']';
                return $this->_root.$file;
            }
            // 尝试判断主defualt目录
            $default_file = TPLPATH.$this->_tname.'/default/home/'.$file;
            if (is_file($default_file)) {
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用default目录的模板['.$default_file.']';
                return $default_file;
            }
            $error = $dir === '/' ? $this->_root.$file : $this->_dir.$file;
            $default_file = str_replace($this->_root, TPLPATH.$this->_tname.'/default/home/', $error);
            if (is_file($default_file)) {
                $this->_load_file_tips[$file] = '由于模板文件['.$this->_dir.$file.']不存在，因此本页面引用default目录的模板['.$default_file.']';
                return $default_file;
            }
        }

        /*
        // 如果移动端模板不存在就调用主网站风格
        if ($this->_is_mobile && is_file(str_replace('/mobile/', '/pc/', $error))) {
            return str_replace('/mobile/', '/pc/', $error);
        } elseif ($this->_is_mobile && is_file(str_replace('/mobile/', '/pc/', $this->_root.$file))) {
            return str_replace('/mobile/', '/pc/', $this->_root.$file);
        }*/
        if ($file == 'msg.html' && is_file(TPLPATH.'pc/default/home/'.$file)) {
            return TPLPATH.'pc/default/home/'.$file;
        }

        if (CI_DEBUG) {
            log_message('error', '模板文件['.$error.']不存在');
            if ($file == 'msg.html' || $file == '404.html') {
                return COREPATH.'View/api_msg.html';
            }
            if ($this->_is_mobile && $default_file) {
                $pc = str_replace('/mobile/', '/pc/', (string)$default_file);
                if (is_file($pc)) {
                    $this->show_error('移动端模板文件不存在', $error, $pc);exit;
                }
            }
        }

        $this->show_error('模板文件不存在', $error);
    }

    /**
     * 设置模板变量
     */
    public function assign($key, $value = '') {

        if (!$key) {
            return FALSE;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_options[$k] = $v;
            }
        } else {
            $this->_options[$key] = $value;
        }
    }

    /**
     * 获取模板变量
     */
    public function get_value($key) {

        if (!$key) {
            return '';
        }

        return $this->_options[$key];
    }

    /**
     * 重新赋值模板变量
     */
    public function set_value($key, $value = '', $replace = '') {

        if (!$key) {
            return '';
        }

        $this->_options[$key] = $replace ? str_replace($replace, $value, $this->_options[$key]) : $value;
    }

    /**
     * 模板标签include/template
     *
     * @param   string  $name   模板文件
     * @param   string  $dir    应用、模块目录
     * @return  bool
     */
    public function _include($name, $dir = '') {

        $dir = $dir == 'MOD_DIR' ? MOD_DIR : $dir;
        $file = $this->get_file_name($name, $dir, TRUE);

        $fname = md5($file);
        isset($this->_include_file[$fname]) ? $this->_include_file[$fname] ++ : $this->_include_file[$fname] = 0;

        if ($this->_include_file[$fname] > 500) {
            $this->show_error('模板文件标签template引用文件目录结构错误', $file);
        }

        return $this->load_view_file($file);
    }

    /**
     * 模板标签load
     *
     * @param   string  $file   模板文件
     * @return  bool
     */
    public function _load($file) {

        $fname = md5($file);
        $this->_include_file[$fname] ++;

        if ($this->_include_file[$fname] > 500) {
            $this->show_error('模板文件标签load引用文件目录结构错误', $file);
        }

        return $this->load_view_file($file);
    }

    /**
     * 加载缓存文件
     *
     * @param   string
     * @return  string
     */
    public function load_view_file($name) {

        $cache_file = $this->_cache.str_replace([
                WEBPATH, '/', '\\', DIRECTORY_SEPARATOR, ':', '?', '*', '|', '<', '>'
            ], [
                '', '_DS_', '_DS_', '_DS_', '', '', '', '', '', ''
            ], $name).'.cache.php';

        $this->_view_files[$name] = [
            'name' => pathinfo($name, PATHINFO_BASENAME),
            'path' => $name,
        ];

        // 当缓存文件不存在时或者缓存文件创建时间少于了模板文件时,再重新生成缓存文件
        // 开发者模式下关闭缓存
        if (IS_DEV || !is_file($cache_file) || (is_file($cache_file) && is_file($name) && filemtime($cache_file) < filemtime($name))) {
            // 防止目录未创建的情况
            $path = dirname($cache_file);
            if (!is_dir($path)) {
                dr_mkdirs($path);
            }
            // 写入新文件
            $content = $this->handle_view_file(file_get_contents($name));
            if (file_put_contents($cache_file, $content, LOCK_EX) === FALSE) {
                if (IS_DEV) {
                    $this->show_error('模板缓存文件 ('.$cache_file.') 创建失败，请将cache目录权限设为777');
                } else {
                    $this->show_error('请将模板缓存目录 ('.$path.') 权限设为777');
                }
            }
        }

        return $cache_file;
    }

    // 将模板代码转化为php
    public function code2php($code, $time = 0, $include = 1) {

        $file = md5($code).$time.'.code.php';
        if (!$include) {
            $code = preg_replace([
                '#{template.*}#Uis',
                '#{load.*}#Uis'
            ], [
                '--',
                '--',
            ], $code);
        }
        if (!is_file($this->_cache.$file)) {
            file_put_contents($this->_cache.$file, str_replace('$this->', '\Phpcmf\Service::V()->', $this->handle_view_file($code)));
        }

        return $this->_cache.$file;
    }

    /**
     * 解析模板文件
     *
     * @param   string
     * @param   string
     * @return  string
     */
    public function handle_view_file($view_content) {

        if (!$view_content) {
            return '';
        }

        if (function_exists('my_parser_view_rule')) {
            $view_content = my_parser_view_rule($view_content);
        }

        // 正则表达式匹配的模板标签
        $regex_array = [
            // 3维数组变量
            '#{\$(\w+?)\.(\w+?)\.(\w+?)\.(\w+?)}#i',
            // 2维数组变量
            '#{\$(\w+?)\.(\w+?)\.(\w+?)}#i',
            // 1维数组变量
            '#{\$(\w+?)\.(\w+?)}#i',
            // 3维数组变量
            '#\$(\w+?)\.(\w+?)\.(\w+?)\.(\w+?)#Ui',
            // 2维数组变量
            '#\$(\w+?)\.(\w+?)\.(\w+?)#Ui',
            // 1维数组变量
            '#\$(\w+?)\.(\w+?)#Ui',
            // PHP函数
            '#{([a-z_0-9]+)\((.*)\)}#Ui',
            // PHP常量
            '#{([A-Z_0-9]+)}#',
            // PHP变量
            '#{\$(.+?)}#i',
            // 类库函数
            '#{([A-Za-z_]+)::(.+)\((.*)\)}#Ui',
            // 引入模板
            '#{\s*template\s+"([\$\-_\/\w\.]+)",\s*"(.+)"\s*}#Uis',
            '#{\s*template\s+"([\$\-_\/\w\.]+)",\s*MOD_DIR\s*}#Uis',
            '#{\s*template\s+"([\$\-_\/\w\.]+)"\s*}#Uis',
            '#{\s*template\s+([\$\-_\/\w\.]+)\s*}#Uis',
            // 加载指定文件到模板
            '#{\s*load\s+"([\$\-_\/\w\.]+)"\s*}#Uis',
            '#{\s*load\s+([\$\-_\/\w\.]+)\s*}#Uis',
            // php标签
            '#{php\s+(.+?)}#is',
            // list标签
            '#{list\s+(.+?)return=([\w]+)}#i', //(.+?)\s?
            '#{list\s+(.+?)\s?}#i',
            '#{\s?\/list\s?}#i',
            // if判断语句
            '#{\s?if\s+(.+?)\s?}#i',
            '#{\s?else\sif\s+(.+?)\s?}#i',
            '#{\s?elseif\s+(.+?)\s?}#i',
            '#{\s?else\s?}#i',
            '#{\s?\/if\s?}#i',
            // 循环语句
            '#{\s?loop\s+\$(.+?)\s+\$(\w+?)\s?\$(\w+?)\s?}#i',
            '#{\s?loop\s+\$(.+?)\s+\$(\w+?)\s?}#i',
            '#{\s?loop\s+\$(.+?)\s+\$(\w+?)\s?=>\s?\$(\w+?)\s?}#i',
            '#{\s?\/loop\s?}#i',
            // for
            '#{for\s+(.+?)\s+(.+?)\s+(.+?)\s?}#i',
            '#{\s?\/for\s?}#i',
            // 结束标记
            '#{\s?php\s?}#i',
            '#{\s?\/php\s?}#i',
            '#\?\>\s*\<\?php\s#s',
            // 结果为空时
            '#{\s?empty\s?}#i',
            '#{\s?\/empty\s?}#i',
        ];

        // 替换直接变量输出
        $replace_array = [
            "<?php echo \$\\1['\\2']['\\3']['\\4']; ?>",
            "<?php echo \$\\1['\\2']['\\3']; ?>",
            "<?php echo \$\\1['\\2']; ?>",
            "\$\\1['\\2']['\\3']['\\4']",
            "\$\\1['\\2']['\\3']",
            "\$\\1['\\2']",
            "<?php echo \\1(\\2); ?>",
            "<?php echo \\1; ?>",
            "<?php echo \$\\1; ?>",
            "<?php echo \\Phpcmf\\Service::L('\\1')->\\2(\\3); ?>",
            //"<?php echo \$this->library_method(\"\\1\",\"\\2\", \$this->_get_method(\\3));,
            "<?php if (\$fn_include = \$this->_include(\"\\1\", \"\\2\")) include(\$fn_include); ?>",
            "<?php if (\$fn_include = \$this->_include(\"\\1\", \"MOD_DIR\")) include(\$fn_include); ?>",
            "<?php if (\$fn_include = \$this->_include(\"\\1\")) include(\$fn_include); ?>",
            "<?php if (\$fn_include = \$this->_include(\"\\1\")) include(\$fn_include); ?>",
            "<?php if (\$fn_include = \$this->_load(\"\\1\")) include(\$fn_include); ?>",
            "<?php if (\$fn_include = \$this->_load(\"\\1\")) include(\$fn_include); ?>",
            "<?php \\1 ?>",
            "<?php \$return_\\2 = [];\$list_return_\\2 = \$this->list_tag(\"\\1 return=\\2\"); if (\$list_return_\\2) { extract(\$list_return_\\2, EXTR_OVERWRITE); \$count_\\2=dr_count(\$return_\\2);} if (is_array(\$return_\\2) && \$return_\\2) { \$key_\\2=-1;foreach (\$return_\\2 as \$\\2) { \$key_\\2++; \$is_first=\$key_\\2==0 ? 1 : 0;\$is_last=\$count_\\2==\$key_\\2+1 ? 1 : 0;  ?>",
            "<?php \$return = [];\$list_return = \$this->list_tag(\"\\1\"); if (\$list_return) { extract(\$list_return, EXTR_OVERWRITE); \$count=dr_count(\$return);} if (is_array(\$return) && \$return) { \$key=-1; foreach (\$return as \$t) { \$key++; \$is_first=\$key==0 ? 1 : 0;\$is_last=\$count==\$key+1 ? 1 : 0; ?>",
            "<?php } } ?>",
            "<?php if (\\1) { ?>",
            "<?php } else if (\\1) { ?>",
            "<?php } else if (\\1) { ?>",
            "<?php } else { ?>",
            "<?php } ?>",
            "<?php if (isset(\$\\1) && is_array(\$\\1) && \$\\1) { \$key_\\3=-1;\$count_\\3=dr_count(\$\\1);foreach (\$\\1 as \$\\2=>\$\\3) { \$key_\\3++; \$is_first=\$key_\\3==0 ? 1 : 0;\$is_last=\$count_\\3==\$key_\\3+1 ? 1 : 0; ?>",
            "<?php if (isset(\$\\1) && is_array(\$\\1) && \$\\1) { \$key_\\2=-1;\$count_\\2=dr_count(\$\\1);foreach (\$\\1 as \$\\2) { \$key_\\2++; \$is_first=\$key_\\2==0 ? 1 : 0;\$is_last=\$count_\\2==\$key_\\2+1 ? 1 : 0;?>",
            "<?php if (isset(\$\\1) && is_array(\$\\1) && \$\\1) { \$key_\\3=-1;\$count_\\3=dr_count(\$\\1);foreach (\$\\1 as \$\\2=>\$\\3) { \$key_\\3++; \$is_first=\$key_\\3==0 ? 1 : 0;\$is_last=\$count_\\3==\$key_\\3+1 ? 1 : 0; ?>",
            "<?php } } ?>",
            "<?php for (\\1 ; \\2 ; \\3) { ?>",
            "<?php }  ?>",
            "<?php ",
            " ?>",
            " ",
            "<?php } } else { ?>",
            "<?php } ?>",
        ];

        // 统计和求和
        foreach (['sum', 'count'] as $name) {
            // 正则表达式匹配的模板标签
            $regex_array[] = '#{'.$name.'\s+(.+?)return=([\w]+)}#i';// 去掉\s，win平台
            $regex_array[] = '#{'.$name.'\s+(.+?)\s?}#i';
            // 替换直接变量输出
            $replace_array[] = "<?php \$return_".$name."_\\2 = [];\$list_return_".$name."_\\2 = \$this->list_tag(\"".$name." \\1 return=\\2\"); if (\$list_return_".$name."_\\2 && is_array(\$list_return_".$name."_\\2)) { extract(\$list_return_".$name."_\\2, EXTR_OVERWRITE);   \$\\2_".$name."=(\$return_".$name." ? \$return_".$name."[0]['ct'] : 0); } else { \$\\2_".$name."= 0; } ?>";
            $replace_array[] = "<?php \$return_".$name." = [];\$list_return_".$name." = \$this->list_tag(\"".$name." \\1\"); if (\$list_return_".$name." && is_array(\$list_return_".$name.")) { extract(\$list_return_".$name.", EXTR_OVERWRITE); echo (\$return_".$name." ? \$return_".$name."[0]['ct'] : 0); } else { echo 0; }   ?>";
        }

        // list标签别名
        foreach ($this->action as $name) {
            // 正则表达式匹配的模板标签
            $regex_array[] = '#{'.$name.'\s+(.+?)return=([\w]+)}#i';// 去掉\s，win平台
            $regex_array[] = '#{'.$name.'\s+(.+?)\s?}#i';
            $regex_array[] = '#{\s?\/'.$name.'\s?}#i';
            // 替换直接变量输出
            $replace_array[] = "<?php \$list_return_\\2 = \$this->list_tag(\"action=".$name." \\1 return=\\2\"); if (\$list_return_\\2 && is_array(\$list_return_\\2)) extract(\$list_return_\\2, EXTR_OVERWRITE); \$count_\\2=dr_count(\$return_\\2); if (is_array(\$return_\\2) && \$return_\\2) { \$key_\\2=-1;  foreach (\$return_\\2 as \$\\2) { \$key_\\2++; \$is_first=\$key_\\2==0 ? 1 : 0;\$is_last=\$count_\\2==\$key_\\2+1 ? 1 : 0; ?>";
            $replace_array[] = "<?php \$list_return = \$this->list_tag(\"action=".$name." \\1\"); if (\$list_return && is_array(\$list_return)) extract(\$list_return, EXTR_OVERWRITE); \$count=dr_count(\$return); if (is_array(\$return) && \$return) { \$key=-1; foreach (\$return as \$t) { \$key++; \$is_first=\$key==0 ? 1 : 0;\$is_last=\$count==\$key+1 ? 1 : 0; ?>";
            $replace_array[] = "<?php } } ?>";
        }

        // 注释内容
        $view_content = preg_replace('#{note}(.+){/note}#Us', '', $view_content);

        // 保护代码
        $this->_code = [];
        $view_content = preg_replace_callback('#{code}(.+){/code}#Us', function ($match) {
            $key = count($this->_code);
            $this->_code[$key] = $match[1];
            return '<!--phpcmf'.$key.'-->';
        }, $view_content);

        $view_content = preg_replace($regex_array, $replace_array, $view_content);

        $view_content = preg_replace_callback("/_get_var\('(.*)'\)/Ui", function ($match) {
            return "_get_var('".preg_replace('#\[\'(\w+)\'\]#Ui', '.\\1', $match[1])."')";
        }, $view_content);

        $view_content = preg_replace_callback("/list_tag\(\"(.*)\"\)/Ui", function ($match) {
            return "list_tag(\"".preg_replace('#\[\'(\w+)\'\]#Ui', '[\\1]', $match[1])."\")";
        }, $view_content);

        // 恢复代码
        if ($this->_code) {
            foreach ($this->_code as $key => $code) {
                $view_content = str_replace('<!--phpcmf'.$key.'-->', $code, $view_content);
            }
        }

        return $view_content;
    }

    // 方法类解析
    public function library_method($class, $method, $param) {

        return call_user_func_array(
            [\Phpcmf\Service::L($class), $method],
            $param
        );
    }

    // 替换方法变量
    public function _get_method(...$params) {

        if (!$params) {
            return [];
        }

        $value = [];
        foreach ($params as $var) {
            if ($var && strpos($var, '$') === 0) {
                $value[] = preg_replace('/\[(.+)\]/U', '[\'\\1\']', $var);
            } else {
                $value[] = $var;
            }
        }

        return $value;
    }

    // 存储缓存
    private function _save_cache_data($cache_name, $data, $time) {
        \Phpcmf\Service::L('cache')->set_data($cache_name, $data, $time);
    }

    // list 标签解析
    public function list_tag($_params) {

        $param = $where = [];

        $system = [
            'db' => '', // 数据源
            'app' => '', // 指定插件时
            'num' => '', // 显示数量
            'limit' => '', // 显示数量
            'maxlimit' => '', // 显示数量
            'sum' => '', // 求和字段
            'form' => '', // 表单
            'page' => '', // 是否分页
            'site' => '', // 站点id
            'flag' => '', // 推荐位id
            'not_flag' => '', // 排除推荐位id
            'show_flag' => '', // 显示推荐位名称
            'more' => '', // 是否显示栏目模型表
            'catid' => '', // 栏目id，支持多id
            'field' => '', // 显示字段
            'order' => '', // 排序
            'space' => '', // 空间uid
            'table' => '', // 表名变量
            'table_site' => '', // 当前站点表名变量
            'total' => '', // 分页总数据
            'join' => '', // 关联表名
            'on' => '', // 关联表条件
            'cache' => SYS_CACHE ? SYS_CACHE_LIST * 3600 : 0, // 默认缓存时间
            'action' => '', // 动作标识
            'return' => '', // 返回变量
            'sbpage' => '', // 不按默认分页
            'module' => '', // 模块名称
            'urlrule' => '', // 自定义分页规则
            'firsturl' => '', // 分页是第一页的固定地址
            'pagesize' => '', // 自定义分页数量
            'pagefile' => '', // 自定义分页配置文件
        ];
        if (!$system['pagefile'] && $this->_is_admin) {
            $system['pagefile'] = 'admin';
        }

        $_params = trim($_params);
        $this->_list_tag = '{list '.$_params.'}';

        // 过滤掉自定义where语句
        if (preg_match('/where=\'(.+)\'/sU', $_params, $match)) {
            $param['where'] = $match[1];
            $_params = str_replace($match[0], '', $_params);
        }

        // 判断是否是统计
        if (stripos($_params, 'count') === 0) {
            $_params = trim(substr($_params, 5));
            $this->_return_sql = 'count';
        } elseif (stripos($_params, 'sum') === 0) {
            $_params = trim(substr($_params, 3));
            $this->_return_sql = 'sum';
        } else {
            $this->_return_sql = '';
        }

        $params = explode(' ', $_params);
        if (in_array($params[0], $this->action)) {
            $params[0] = 'action='.$params[0];
        }

        $sysadj = [
            'IN', 'BEWTEEN', 'BETWEEN', 'LIKE', 'NOT', 'BW',
            'NOTLIKE', 'NOTJSON', 'NOTFIND', 'NOTIN', 'NOTNULL', 'NULL',
            'GT', 'EGT', 'LT', 'ELT',
            'DAY', 'MONTH', 'MAP', 'YEAR', 'SEASON', 'WEEK',
            'JSON', 'FIND'
        ];
        foreach ($params as $t) {
            $var = substr($t, 0, strpos($t, '='));
            $val = substr($t, strpos($t, '=') + 1);
            if (!$var) {
                continue;
            }
            $val = defined($val) ? constant($val) : dr_rp_view($val, true);
            if ($var == 'fid' && !$val) {
                continue;
            }
            if (isset($system[$var])) { // 系统参数，只能出现一次，不能添加修饰符
                if ($var != 'cache' && $system[$var]) {
                    continue; // 防止重复记录
                }
                $system[$var] = in_array($var, ['urlrule']) ? $val : dr_safe_replace($val);
            } else {
                if (preg_match('/^([A-Z_]+)(.+)/', $var, $match)) { // 筛选修饰符参数
                    $_adj = '';
                    $_pre = explode('_', $match[1]);
                    foreach ($_pre as $p) {
                        if (in_array($p, $sysadj)) {
                            $_adj = $p;
                        }
                    }
                    $where[$var] = [
                        'adj' => $_adj,
                        'name' => $match[2],
                        'value' => $val
                    ];
                } else {
                    $where[$var] = [
                        'adj' => '',
                        'name' => $var,
                        'value' => $val
                    ];
                }
                $param[$var] = $val; // 用于特殊action
            }
        }

        // return位置判断
        if ($system['return'] && strpos(end($params), 'return=') === false) {
            return $this->_return($system['return'], $this->_return_sql.'标签中的return参数只能放在标签的尾部');
        }

        // 替换order中的非法字符
        isset($system['order']) && $system['order'] && $system['order'] = str_ireplace(
            ['"', "'", ';', 'select', 'insert'], //, '`', ')', '('
            '',
            $system['order']
        );

        // limit别名映射num
        if (isset($system['limit']) && !isset($system['num'])) {
            $system['num'] = $system['limit'];
        }

        // 开发模式下显示求和字段
        if (CI_DEBUG) {
            $this->_sum_field = $system['sum'];
        }

        $action = $system['action'];
        // 当hits动作时，定位到moule动作
        $system['action'] == 'hits' && $system['action'] = 'module';
        // 默认站点参数
        $system['site'] = !$system['site'] ? SITE_ID : $system['site'];
        // 默认模块参数
        $system['module'] = $dirname = strtolower($system['module'] ? (string)$system['module'] : (string)\Phpcmf\Service::C()->module['dirname']);
        // 格式化field
        $system['field'] && $system['field'] = urldecode($system['field']);
        // 分页页码变量
        $this->_page_value = $system['page'];

        if (in_array(strtoupper($system['order']), ['RAND()', 'RAND'])) {
            $cache_name = 'view-'.$this->_return_sql.md5($_params.dr_now_url().$this->_get_page_id($system['page']).$this->_is_mobile);
        } else {
            $cache_name = 'view-'.$this->_return_sql.($system['page'] ? $this->_get_page_id($system['page']) : 'one').md5($_params.$this->_is_mobile);
        }

        if (!CI_DEBUG && SYS_CACHE && $system['cache']) {
            $cache_data = \Phpcmf\Service::L('cache')->get_data($cache_name);
            if ($cache_data) {
                $this->_page_used = $cache_data['page_used'];
                $this->_page_urlrule = $cache_data['page_urlrule'];
                return $this->_return($system['return'], $cache_data['data'], $cache_data['sql'], $cache_data['total'], $cache_data['pages'], $cache_data['pagesize'], $system['cache']);
            }
        }

        // 运算排除
        if ($this->_return_sql && !in_array($action,
                ['sql', 'module', 'member', 'form', 'mform', 'comment', 'table', 'tag', 'related'])) {
            return $this->_return($system['return'], $this->_return_sql.'标签不支持[acntion='.$action.']的运算');
        }

        // 判断标签是否是搜索标签
        $this->_is_list_search = 0;

        // action
        switch ($system['action']) {

            case 'function': //执行函数

                if (!isset($param['name'])) {
                    return $this->_return($system['return'], 'name参数不存在');
                } elseif (strpos($param['name'], 'my_') !== 0) {
                    return $this->_return($system['return'], '自定义函数['.$param['name'].']必须以my_开头');
                } elseif (!function_exists($param['name'])) {
                    return $this->_return($system['return'], '自定义函数['.$param['name'].']未定义');
                }

                $name = 'function-'.md5(dr_array2string($param));
                $cache = \Phpcmf\Service::L('cache')->get_data($name);
                if (!$cache) {
                    $p = [];
                    foreach ($param as $var => $t) {
                        if (strpos($var, 'param') === 0) {
                            $n = intval(substr($var, 5));
                            $p[$n] = $t;
                        }
                    }
                    if ($p) {
                        $rt = call_user_func_array($param['name'], $p);
                    } else {
                        $rt = call_user_func($param['name']);
                    }

                    $cache = [
                        $rt
                    ];
                    $system['cache'] && \Phpcmf\Service::L('cache')->set_data($name, $cache, $system['cache']);
                }

                return $this->_return($system['return'], $cache, '');
                break;

            case 'cache': // 系统缓存数据

                if (!isset($param['name'])) {
                    return $this->_return($system['return'], 'name参数不存在');
                }

                $pos = strpos($param['name'], '.');
                if ($pos !== FALSE) {
                    $_name = substr($param['name'], 0, $pos);
                    $_param = substr($param['name'], $pos + 1);
                } else {
                    $_name = $param['name'];
                    $_param = NULL;
                }

                $cache = $this->_cache_var($_name, $system['site']);
                if (!$cache) {
                    return $this->_return($system['return'], "缓存({$_name})不存在，请在后台更新缓存");
                } elseif ($_name == 'module-content') {
                    // 指定模块
                    if ($system['module'] && $system['module'] != 'all') {
                        $mid = explode(',', $system['module']);
                        foreach ($cache as $i => $t) {
                            if (!in_array($t['dirname'], $mid)) {
                                unset($cache[$i]);
                            }
                        }
                        if (!$cache) {
                            return $this->_return($system['return'], '指定的模块('.$system['module'].')都不存在');
                        }
                    }
                    // 读取内容模块更多信息
                    if ($system['more']) {
                        foreach ($cache as $i => $t) {
                            $cache[$i] = \Phpcmf\Service::L('cache')->get('module-'.$system['site'].'-'.$t['dirname']);
                        }
                    }

                }

                if ($_param) {
                    $data = dr_get_param_var($cache, $_param);
                    //eval('$data=$cache'.$this->_get_var($_param).';');
                    if (!$data) {
                        return $this->_return($system['return'], "缓存({$_name})参数不存在!!");
                    }
                } else {
                    $data = $cache;
                }

                return $this->_return($system['return'], $data, '');
                break;

            case 'linkage': // 联动菜单

                if (isset($param['pid']) && $param['pid']) {
                    $link = dr_linkage($param['code'],  $param['pid']);
                    if (!$link) {
                        return $this->_return($system['return'], "联动菜单{$param['code']}不存在，请在后台一键生成数据");
                    }
                    if (!$link['child']) {
                        // 表示本身就是子菜单，我们需要调用父级作为同级显示
                        $param['pid'] =  $link['pid'];
                    }
                }

                $linkage = dr_linkage_list($param['code'], isset($param['pid']) && $param['pid'] ? $param['pid'] : 0);
                if (!$linkage) {
                    return $this->_return($system['return'], "联动菜单{$param['code']}不存在，请在后台一键生成数据");
                }

                $i = 0;
                $return = [];
                foreach ($linkage as $t) {
                    if ($system['num'] && $i >= $system['num']) {
                        break;
                    } elseif (isset($param['id']) && !dr_in_array($t['id'], explode(',', $param['id']))) {
                        continue;
                    } elseif (isset($param['ii']) && !dr_in_array($t['ii'], explode(',', $param['ii']))) {
                        continue;
                    }
                    $return[] = $t;
                    $i ++;
                }

                $data = isset($param['call']) && $param['call'] ? array_reverse($return) : $return;

                // 存储缓存
                $system['cache'] && $data && $this->_save_cache_data($cache_name, [
                    'data' => $data,
                    'sql' => '',
                    'total' => '',
                    'pages' => '',
                    'pagesize' => '',
                    'page_used' => $this->_page_used,
                    'page_urlrule' => $this->_page_urlrule,
                ], $system['cache']);


                return $this->_return($system['return'], $data, '');
                break;

            case 'sql': // 直接sql查询

                if (preg_match('/sql=\'(.+)\'/sU', $_params, $sql)) {

                    // 替换前缀
                    $sql = str_replace(
                        ['@#S', 'S@#', '@#'],
                        [\Phpcmf\Service::M()->dbprefix($system['site']), \Phpcmf\Service::M()->dbprefix($system['site']), \Phpcmf\Service::M()->dbprefix()],
                        trim(urldecode($sql[1]))
                    );

                    if (stripos($sql, 'SELECT') !== 0) {
                        return $this->_return($system['return'], 'SQL语句只能是SELECT查询语句');
                    } elseif (preg_match('/select(.*)into outfile(.*)/i', $sql)) {
                        return $this->_return($system['return'], 'SQL语句只能是SELECT查询语句');
                    } elseif (preg_match('/select(.*)into dumpfile(.*)/i', $sql)) {
                        return $this->_return($system['return'], 'SQL语句只能是SELECT查询语句');
                    }

                    $total = 0;
                    $pages = '';

                    // 统计标签
                    if ($this->_return_sql) {
                        $sql = preg_replace('/select .* from /iUs', 'SELECT '.$this->_select_rt_name.' FROM ', $sql, 1);
                    } else {
                        // 如存在分页条件才进行分页查询
                        if ($system['page']) {
                            $page = $this->_get_page_id($system['page']);
                            $row = $this->_query(preg_replace('/select .* from /iUs', 'SELECT count(*) as c FROM ', $sql, 1), $system, FALSE);
                            $total = (int)$row['c'];
                            $pagesize = $system['pagesize'] ? $system['pagesize'] : 10;
                            // 没有数据时返回空
                            if (!$total) {
                                return $this->_return($system['return'], '没有查询到内容', $sql, 0);
                            }
                            $sql.= ' LIMIT '.intval($pagesize * ($page - 1)).','.$pagesize;
                            $pages = $this->_new_pagination($system, $pagesize, $total);
                        }
                    }

                    $data = $this->_query($sql, $system);

                    // 存储缓存
                    $system['cache'] && $data && $this->_save_cache_data($cache_name, [
                        'data' => $data,
                        'sql' => $sql,
                        'total' => $total,
                        'pages' => $pages,
                        'pagesize' => $pagesize,
                        'page_used' => $this->_page_used,
                        'page_urlrule' => $this->_page_urlrule,
                    ], $system['cache']);

                    return $this->_return($system['return'], $data, $sql, $total, $pages, $pagesize);
                } else {
                    return $this->_return($system['return'], '参数不正确，SQL语句必须用单引号包起来'); // 没有查询到内容
                }
                break;

            case 'table': // 表名查询

                if (!$system['table'] && !$system['table_site']) {
                    return $this->_return($system['return'], 'table参数不存在');
                }

                // 填充当前站点id的写法
                if ($system['table_site']) {
                    $system['table'] = dr_site_table_prefix($system['table_site'], SITE_ID);
                }

                $tableinfo = \Phpcmf\Service::L('cache')->get_data('table-'.$system['table']);
                if (!$tableinfo) {
                    $tableinfo = \Phpcmf\Service::M('Table')->get_field($system['table']);
                    \Phpcmf\Service::L('cache')->set_data('table-'.$system['table'], $tableinfo, 36000);
                }
                if (!$tableinfo) {
                    return $this->_return($system['return'], '表('.$system['table'].')结构不存在');
                }

                // 是否操作自定义where
                if ($param['where']) {
                    $where[] = [
                        'adj' => 'SQL',
                        'value' => urldecode($param['where'])
                    ];
                    unset($param['where']);
                }

                $table = \Phpcmf\Service::M()->dbprefix($system['table']);
                $where = $this->_set_where_field_prefix($where, $tableinfo, $table); // 给条件字段加上表前缀
                $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo, $table); // 给显示字段加上表前缀

                $_order = [];
                $_order[$table] = $tableinfo;

                $total = 0;
                $sql_from = $table; // sql的from子句

                // 关联表
                if ($system['join'] && $system['on']) {
                    $rt = $this->_join_table($table, $system, $where, $_order, $sql_from);
                    if (!$rt['code']) {
                        return $this->_return($system['return'], $rt['msg']);
                    }
                    list($system, $where, $_order, $sql_from) = $rt['data'];
                }

                $sql_limit = $pages = '';
                $sql_where = $this->_get_where($where); // sql的where子句

                // 统计标签
                if ($this->_return_sql) {
                    $sql = "SELECT ".$this->_select_rt_name." FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY NULL";
                } else {
                    if ($system['page']) {
                        $page = $this->_get_page_id($system['page']);
                        $pagesize = (int) $system['pagesize'];
                        $pagesize = $pagesize ? $pagesize : 10;
                        $sql = "SELECT count(*) as c FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY NULL";
                        $row = $this->_query($sql, $system, FALSE);
                        $total = (int)$row['c'];
                        if ($system['maxlimit'] && $total > $system['maxlimit']) {
                            $total = $system['maxlimit']; // 最大限制
                            if ($page * $pagesize > $total) {
                                $return_data = $this->_return($system['return'], 'maxlimit设置最大显示'.$system['maxlimit'].'条，当前（'.$total.'）已超出', $sql, 0);
                                return;
                            }
                        }
                        // 没有数据时返回空
                        if (!$total) {
                            return $this->_return($system['return'], '没有查询到内容', $sql, 0);
                        }
                        $sql_limit = 'LIMIT '.intval($pagesize * ($page - 1)).','.$pagesize;
                        $pages = $this->_new_pagination($system, $pagesize, $total);
                    } elseif ($system['num']) {
                        $sql_limit = "LIMIT {$system['num']}";
                    }
                    $system['order'] = $this->_set_orders_field_prefix($system['order'], $_order); // 给排序字段加上表前缀
                    $sql = "SELECT ".$this->_get_select_field($system['field'] ? $system['field'] : "*")." FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ".($system['order'] ? "ORDER BY {$system['order']}" : "")." $sql_limit";
                }

                $data = $this->_query($sql, $system);

                if (is_array($data) && $data) {
                    // 表的系统字段
                    $myfield = \Phpcmf\Service::M('field')->get_mytable_field($system['table'], 0);
                    if (!$myfield) {
                        $myfield = \Phpcmf\Service::M('field')->get_mytable_field($system['table'], SITE_ID);
                    }
                    if ($myfield) {
                        $dfield = \Phpcmf\Service::L('Field')->app();
                        foreach ($data as $i => $t) {
                            $data[$i] = $dfield->format_value($myfield, $t, 1);
                        }
                    }
                    // 存储缓存
                    $system['cache'] && $data && $this->_save_cache_data($cache_name, [
                        'data' => $data,
                        'sql' => $sql,
                        'total' => $total,
                        'pages' => $pages,
                        'pagesize' => $pagesize,
                        'page_used' => $this->_page_used,
                        'page_urlrule' => $this->_page_urlrule,
                    ], $system['cache']);
                }

                return $this->_return($system['return'], $data, $sql, $total, $pages, $pagesize);
                break;

            default :

                // 插件自定义标签
                if (!$system['app']) {
                    if (in_array($system['action'], [
                        'member',
                        'page', 'navigator', 'tag',
                        'comment', 'form', 'mform'
                    ])) {
                        $system['app'] = $system['action'];
                    } elseif (in_array($system['action'], [
                        'category_search_field', 'content', 'category',
                        'related', 'search', 'modules', 'module'
                    ])) {
                        $system['app'] = 'module';
                    }
                }

                $return_data = [];

                // 当来自插件目录
                if ($system['app']) {
                    if (!dr_is_app($system['app'])) {
                        return $this->_return($system['return'], '本插件('.$system['app'].')没有安装');
                    }
                    $myfile = dr_get_app_dir($system['app']).'Action/'.dr_safe_filename(ucfirst($system['action'])).'.php';
                    if (is_file($myfile)) {
                        $rs = require $myfile;
                        if (!$return_data && is_array($rs)) {
                            return $rs;
                        }
                        return $return_data;
                    } else {
                        return $this->_return($system['return'], '本插件('.$system['app'].')没有('.$system['action'].')标签');
                    }
                } else {
                    // 识别自定义标签
                    $myfile = MYPATH.'Action/'.dr_safe_filename(ucfirst($system['action'])).'.php';
                    if (is_file($myfile)) {
                        $rs = require $myfile;
                        if (!$return_data && is_array($rs)) {
                            return $rs;
                        }
                        return $return_data;
                    } else {
                        return $this->_return($system['return'], '无此标签('.$system['action'].')');
                    }
                }
                break;
        }
    }

    /**
     * 查询缓存
     */
    public function _query($sql, $system, $all = TRUE) {

        $mysql = \Phpcmf\Service::M()->db;
        if (isset($system['db']) && $system['db']) {
            list($mysql) = \Frame\Model::_load_db_source($system['db']);
        }

        // 运算替换
        if ($this->_return_sql && strpos($sql, $this->_select_rt_name) !== false) {
            switch ($this->_return_sql) {
                case 'count':
                    $sql = str_replace($this->_select_rt_name, 'count(*) as ct', $sql);
                    break;
                case 'sum':
                    if (!$system['sum']) {
                        return '缺少参数sum，指定求和的字段名称';
                    }
                    $sql = str_replace($this->_select_rt_name, 'sum('.$system['sum'].') as ct', $sql);
                    break;
            }
        }

        // 执行SQL
        $t = microtime(TRUE);
        $query = $mysql->query($sql);
        $time = microtime(TRUE) - $t;

        // 记录慢日志
        if ($time > 1 && is_file(WRITEPATH.'database/sql.lock')) {
            $file = WRITEPATH.'database/Sql/sql.txt';
            $path = dirname($file);
            if (!is_dir($path)) {
                dr_mkdirs($path);
            }
            $size = filesize($file);
            $json = json_encode([SYS_TIME, $sql, $time, $this->_view_file, $this->_list_tag, SITE_URL.SELF.'?'.http_build_query($_GET)]);
            if ($size > 1024*1024*2) {
                copy($file, $path.SYS_TIME.'.txt');
                file_put_contents($file, $json.PHP_EOL, LOCK_EX);
            } else {
                file_put_contents($file, $json.PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        $this->_sql = $sql;
        $this->_sql_time = $time;

        // 挂钩点 模板中的sql语句
        \Phpcmf\Hooks::trigger('cms_view_sql', $sql, $time, $this->_view_file, $this->_list_tag);

        if (!$query) {
            return 'SQL查询解析不正确：'.$sql;
        }

        // 查询结果
        $data = $all ? $query->getResultArray() : $query->getRowArray();

        return $data;
    }

    // 获取当前执行后的sql语句
    public function get_sql_query() {
        return $this->_sql;
    }

    // 设置分页参数
    public function set_page_config($config) {
        $this->_page_config = $config;
    }

    /**
     * 新分页
     */
    public function _new_pagination($system, $pagesize, $total) {
        return $this->_get_pagination($system['urlrule'], $pagesize, $total, $system['pagefile'], $system['firsturl']);
    }

    /**
     * 分页
     */
    public function _get_pagination($url, $pagesize, $total, $name = 'page', $first_url = '') {

        $this->_page_used = 1;
        $this->_page_config_file = '';
        if ($name == 'admin') {
            // 使用后台分页规则
            $this->_page_config_file =  CMSPATH.'Config/Apage.php';
        } else {
            // 这里要支持移动端分页条件
            !$name && $name = 'page';
            $file = 'page/'.($this->_is_mobile ? 'mobile' : 'pc').'/'.(dr_safe_filename($name)).'.php';
            if (is_file(WEBPATH.'config/'.$file)) {
                $this->_page_config_file = WEBPATH.'config/'.$file;
            } elseif (is_file(CONFIGPATH.$file)) {
                $this->_page_config_file =  CONFIGPATH.$file;
            } else {
                exit('无法找到分页配置文件【'.$file.'】');
            }
        }

        $config = require $this->_page_config_file;
        if ($this->_page_config) {
            $config = dr_array22array($config, $this->_page_config);
        }

        !$url && $url = '此标签没有设置urlrule参数';

        $this->_page_urlrule = str_replace(['[page]', '%7Bpage%7D', '%5Bpage%5D', '%7bpage%7d', '%5bpage%5d'], '{page}', $url);
        $config['base_url'] = $this->_page_urlrule;
        $config['first_url'] = $first_url ? $first_url : '';
        $config['per_page'] = $pagesize;
        $config['page_name'] = $this->_page_value;
        $config['total_rows'] = $total;
        $config['use_page_numbers'] = TRUE;
        $config['query_string_segment'] = 'page';

        return \Phpcmf\Service::L('Page')->initialize($config)->create_links();
    }
    
    private function _get_where_in($str) {
        
        if (!$str) {
            return 0;
        }
        
        $arr = explode(',', $str);
        foreach ($arr as $i => $t) {
            $arr[$i] = intval($t);
        }
        
        return implode(',', $arr);
    }

    // 条件子句格式化
    public function _get_where($where) {

        if ($where) {
            $string = '';
            foreach ($where as $i => $t) {
                // 过滤字段
                if (isset($t['use']) && $t['use'] == 0 || !strlen($t['value'])) {
                    continue;
                }
                $join = $string ? ' AND' : '';
                // 条件组装
                switch ($t['adj']) {

                    case 'MAP':
                        // 地图
                        if ($t['value'] == '') {
                            $string.= $join." ".$t['name']." = ''";
                        } else {
                            list($a, $km) = explode('|', $t['value']);
                            list($lng, $lat) = explode(',', $a);
                            if ($lat && $lng) {
                                $this->pos_map = [
                                    'lng' => $lng,
                                    'lat' => $lat,
                                    'km' => $km,
                                ];
                                // 获取Nkm内的数据
                                $squares = dr_square_point($lng, $lat, $km);
                                $string.= $join." (".$t['prefix']."`".$t['name']."_lat` between {$squares['right-bottom']['lat']} and {$squares['left-top']['lat']}) and (".$t['prefix']."`".$t['name']."_lng` between {$squares['left-top']['lng']} and {$squares['right-bottom']['lng']})";
                            } else {
                                $string.= $join." ".$t['name']." = '没有定位到您的坐标'";
                            }
                        }

                        break;

                    case 'JSON':
                    case 'NOTJSON':
                        if ($t['value'] == '') {
                            $string.= $join." ".$t['name']." = ''";
                        } else {
                            $or_and = strpos($t['value'], ',');
                            if ($or_and) {
                                $arr = explode(',', $t['value']);
                            } elseif (strpos($t['value'], '|')) {
                                $arr = explode('|', $t['value']);
                            } else {
                                $arr = [$t['value']];
                            }
                            $vals = [];
                            if ($arr) {
                                foreach ($arr as $value) {
                                    if ($value) {
                                        $vals[] = \Phpcmf\Service::M()->where_json('', $t['name'], \Phpcmf\Service::M()->db->escapeString(dr_safe_replace($value), true));
                                    }
                                }
                            }
                            if ($t['adj'] == 'NOTJSON') {
                                $join.= ' NOT';
                            }
                            $string.= $vals ? $join.'('.$t['name'].'<>\'\' AND  ('.implode($or_and ? ' AND ' : ' OR ', $vals).'))' : '';
                        }

                        break;

                    case 'FIND':
                    case 'NOTFIND':
                        $vals = [];
                        $value = dr_safe_replace($t['value']);
                        if ($value) {
                            $arr = explode('|', $t['value']);
                            foreach ($arr as $v) {
                                if ($v) {
                                    if (!is_numeric($v)) {
                                        $v = "'".$v."'";
                                    }
                                    $vals[] = " FIND_IN_SET (".$v.", {$t['name']})";
                                }
                            }
                        }
                        if ($t['adj'] == 'NOTFIND') {
                            $join.= ' NOT';
                        }
                        $string.= $vals ? $join.' ('.implode(' OR ', $vals).')' : '';
                        break;

                    case 'LIKE':
                    case 'NOTLIKE':
                        $vals = [];
                        $value = dr_safe_replace($t['value']);
                        if ($value) {
                           $arr = explode('|', $t['value']);
                           foreach ($arr as $value) {
                               if ($value) {
                                   $ns = strpos($value, '%');
                                   if ($ns !== false && $ns !== 0) {
                                       $value = trim($value, '%').'%'; // 尾%查询
                                   } else {
                                       $value = '%'.trim($value, '%').'%'; // 首尾%查询
                                   }
                                   $vals[]= "{$t['name']} LIKE '".$value."'";
                               }
                           }
                        }
                        if ($t['adj'] == 'NOTLIKE') {
                            $join.= ' NOT';
                        }
                        $string.= $vals ? $join.' ('.implode(' OR ', $vals).')' : '';
                        break;

                    case 'IN':
                        $arr = explode(',', dr_safe_replace($t['value']));
                        $str = '';
                        foreach ($arr as $a) {
                            if (!is_numeric($a)) {
                                $str.= ',\''.$a.'\'';
                            } else {
                                $str.= ','.$a;
                            }
                        }
                        $string.= $join." {$t['name']} IN (".trim($str, ',').")";
                        break;

                    case 'NOTIN':
                        $arr = explode(',', dr_safe_replace($t['value']));
                        $str = '';
                        foreach ($arr as $a) {
                            if (!is_numeric($a)) {
                                $str.= ',\''.$a.'\'';
                            } else {
                                $str.= ','.$a;
                            }
                        }
                        $string.= $join." {$t['name']} NOT IN (".trim($str, ',').")";
                        break;

                    case 'NOT':
                        $string.= $join.(is_numeric($t['value']) ? " {$t['name']} <> ".$t['value'] : " {$t['name']} <> \"".($t['value'] == "''" ? '' : dr_safe_replace($t['value']))."\"");
                        break;

                    case 'BEWTEEN':
                        goto BETWEEN;
                        break;

                    case 'BW':
                        goto BETWEEN;
                        break;

                    case 'BETWEEN':
                        BETWEEN:
                        list($s, $e) = explode(',', $t['value']);
                        $string.= $join." {$t['name']} BETWEEN ".(int)$s." AND ".(int)$e;
                        break;

                    case 'GT':
                        $string.= $join." {$t['name']} > ".intval($t['value'])."";
                        break;

                    case 'EGT':
                        $string.= $join." {$t['name']} >= ".intval($t['value'])."";
                        break;

                    case 'LT':
                        $string.= $join." {$t['name']} < ".intval($t['value'])."";
                        break;

                    case 'ELT':
                        $string.= $join." {$t['name']} <= ".intval($t['value'])."";
                        break;

                    case 'SQL':
                        $string.= $join.' '.str_replace('@#', \Phpcmf\Service::M()->dbprefix(), $t['value']);
                        break;

                    case 'DAY':
                        if (substr($t['value'], 0, 1) == 'E') {
                            // 当天
                            $stime = strtotime('-'.intval(substr($t['value'], 1)).' day');
                            $stime = strtotime(date('Y-m-d', $stime).' 00:00:00');
                            $etime = strtotime(date('Y-m-d 23:59:59', $stime));
                        } elseif (strpos($t['value'], ',')) {
                            // 范围查询
                            list($s, $e) = explode(',', $t['value']);
                            $stime = strtotime($s.' 00:00:00');
                            $etime = strtotime(($e ? $e : date('Y-m-d')).' 23:59:59');
                        } else {
                            $time = strtotime('-'.intval($t['value']).' day');
                            $stime = strtotime(date('Y-m-d', $time).' 00:00:00');
                            $etime = SYS_TIME;
                        }
                        if ($stime > $etime) {
                            // 判断时间大小
                            $tp = $stime;
                            $stime = $etime;
                            $etime = $tp;
                        }
                        $string.= $join." ({$t['name']} BETWEEN ".$stime." AND ".$etime.")";
                        break;

                    case 'WEEK':
                        if (substr($t['value'], 0, 1) == 'E') {
                            $num = intval(substr($t['value'], 1));
                            if ($num) {
                                $stime = strtotime(date('Y-m-d', strtotime('-' . ($num+1) . ' monday')) . ' 00:00:00');
                                $etime = strtotime(date('Y-m-d 23:59:59', ($stime + (8 - (date('w') == 0 ? 8 : date('w'))) * 24 * 3600)));
                            } else {
                                // 本周
                                $stime = strtotime(date('Y-m-d 00:00:00', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
                                $etime = strtotime(date('Y-m-d 23:59:59', ($stime + (8 - (date('w') == 0 ? 8 : date('w'))) * 24 * 3600)));
                            }
                        } else {
                            $num = intval($t['value']);
                            if ($num) {
                                $stime = strtotime(date('Y-m-d', strtotime('-' . ($num+1) . ' monday')) . ' 00:00:00');
                            } else {
                                // 本周
                                $stime = strtotime(date('Y-m-d 00:00:00', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
                            }
                            $etime = SYS_TIME;
                        }
                        if ($stime > $etime) {
                            // 判断时间大小
                            $tp = $stime;
                            $stime = $etime;
                            $etime = $tp;
                        }
                        $string.= $join." ({$t['name']} BETWEEN ".$stime." AND ".$etime.")";
                        break;

                    case 'SEASON':
                        list($s, $y) = explode('-', $t['value']);
                        if (!$y) {
                            $y = date('Y');
                        }
                        if (!$s || !in_array($s, [1,2,3,4])) {
                            $s = ceil(date('n')/3);
                        }
                        $season = [
                            1 => ['-01-01 00:00:00', '-03-31 23:59:59'],
                            2 => ['-04-01 00:00:00', '-06-30 23:59:59'],
                            3 => ['-07-01 00:00:00', '-09-30 23:59:59'],
                            4 => ['-10-01 00:00:00', '-12-31 23:59:59'],
                        ];
                        $stime = strtotime($y.$season[$s][0]);
                        $etime = strtotime($y.$season[$s][1]);
                        if ($stime > $etime) {
                            // 判断时间大小
                            $tp = $stime;
                            $stime = $etime;
                            $etime = $tp;
                        }
                        $string.= $join." ({$t['name']} BETWEEN ".$stime." AND ".$etime.")";
                        break;

                    case 'MONTH':
                        if (substr($t['value'], 0, 1) == 'E') {
                            // 当月
                            $stime = strtotime(date('Y-m-01 00:00:00') . " - ".intval(substr($t['value'], 1))." month");
                            $etime = strtotime(date('Y-m', $stime).'-1  +1 month -1 day 23:59:59');
                        } elseif (strpos($t['value'], ',')) {
                            // 范围查询
                            list($s, $e) = explode(',', $t['value']);
                            if (!$e) {
                                $e = $s;
                            }
                            $stime = strtotime($s.'-01 00:00:00');
                            $etime = strtotime($e." +1 month -1 day 23:59:59");
                        } else {
                            $time = strtotime('-'.intval($t['value']).' month');
                            $stime = strtotime(date('Y-m', $time).'-01 00:00:00');;
                            $etime = SYS_TIME;
                        }
                        if ($stime > $etime) {
                            // 判断时间大小
                            $tp = $stime;
                            $stime = $etime;
                            $etime = $tp;
                        }
                        $string.= $join."  ({$t['name']}  BETWEEN ".$stime." AND ".$etime.")";
                        break;

                    case 'YEAR':
                        if (strlen($t['value']) == 4) {
                            // 按年份值
                            $stime = strtotime($t['value'].'-01-01 00:00:00');
                            $etime = strtotime($t['value'].'-12-31 23:59:59');
                        } elseif (substr($t['value'], 0, 1) == 'E') {
                            // 今年
                            $stime = strtotime(date('Y', strtotime('-'.intval(substr($t['value'], 1)).' year')).'-01-01 00:00:00');
                            $etime = strtotime(date('Y', $stime).'-12-31 23:59:59');
                        } elseif (strpos($t['value'], ',')) {
                            // 范围查询
                            list($s, $e) = explode(',', $t['value']);
                            $stime = strtotime(intval($s).'-01-01 00:00:00');
                            $etime = strtotime(intval($e ? $e : date('Y')).'-12-31 23:59:59');
                        } else {
                            $stime = strtotime(date('Y', strtotime('-'.intval($t['value']).' year')).'-01-01 00:00:00');
                            $etime = SYS_TIME;
                        }
                        if ($stime > $etime) {
                            // 判断时间大小
                            $tp = $stime;
                            $stime = $etime;
                            $etime = $tp;
                        }
                        $string.= $join." ({$t['name']} BETWEEN ".$stime." AND ".$etime.")";
                        break;

                    case 'NOTNULL':
                        $string.= $join." {$t['name']}<>''";
                        break;

                    case 'NULL':
                        $string.= $join." ({$t['name']}='' or {$t['name']} IS NULL)";
                        break;

                    default:
                        if ($t['name'] && strpos($t['name'], '`thumb`')) {
                            // 缩略图筛选
                            $t['value'] == 1 ? $string.= $join." {$t['name']}<>''" : $string.= $join." {$t['name']}=''";
                        } elseif (!$t['name'] && $t['value']) {
                            $string.= $join.' '.$t['value'];
                        } else {
                            $string.= $join.(is_numeric($t['value']) ? " {$t['name']} = ".$t['value'] : " {$t['name']} = \"".($t['value'] == "''" ? '' : dr_safe_replace($t['value']))."\"");
                        }
                        break;
                }
            }
            return trim($string);
        }

        return '';
    }

    // 给条件字段加上表前缀
    public function _set_where_field_prefix($where, $field, $prefix, $myfield = []) {

        if (!$where) {
            return [];
        }

        $this->_list_where = $where;

        foreach ($where as $i => $t) {
            if (dr_in_array($t['name'], $field)) {
                $where[$i]['use'] = 1;
                $where[$i]['name'] = "`$prefix`.`{$t['name']}`";
                if ($myfield && $t['value']) {
                    if ($myfield[$t['name']]['fieldtype'] == 'Linkages') {
                        // 联动多选
                        $arr = explode('|', $t['value']);
                        $link_where = [];
                        foreach ($arr as $value) {
                            $data = dr_linkage($myfield[$t['name']]['setting']['option']['linkage'], $value);
                            if ($data) {
                                if ($data['child']) {
                                    $link_where = dr_array2array($link_where, explode(',',  $data['childids']));
                                } else {
                                    $link_where[] = intval($data['ii']);
                                }
                            }
                        }
                        $where[$i]['adj'] = 'JSON';
                        if ($link_where) {
                            $where[$i]['value'] = implode('|', array_unique($link_where));
                        } else {
                            // 没有找到就当做普通数据库查询
                            $where[$i]['value'] = $t['value'];
                        }
                    } elseif (isset($myfield[$t['name']]['fieldtype']) && in_array($myfield[$t['name']]['fieldtype'], ['Members', 'Related'])) {
                        // 关联字段
                        if (!$where[$i]['adj']) {
                            $where[$i]['adj'] = 'FIND';
                            $where[$i]['value'] = intval($t['value']);
                        }
                    } elseif ($myfield[$t['name']]['fieldtype'] == 'Linkage') {
                        // 联动菜单
                        $arr = explode('|', $t['value']);
                        $link_where = [];
                        foreach ($arr as $value) {
                            $data = dr_linkage($myfield[$t['name']]['setting']['option']['linkage'], $value);
                            if ($data) {
                                if ($data['child']) {
                                    $link_where[] = $where[$i]['name'].' IN ('.$data['childids'].')';
                                } else {
                                    $link_where[] = $where[$i]['name'].' = '.intval($data['ii']);
                                }
                            }
                        }
                        if ($link_where) {
                            $where[$i]['adj'] = 'SQL';
                            $where[$i]['value'] = '('.implode(' OR ', array_unique($link_where)).')';
                        } else {
                            // 没有找到就当做普通数据库查询
                            $where[$i]['value'] = $t['value'];
                            //$where[$i]['value'] = '没有找到对应的联动菜单值['.$t['value'].']';
                            //$where[$i]['value'] = '联动单选字段('.$t['name'].')没有找到对应的联动菜单['.$myfield[$t['name']]['setting']['option']['linkage'].']的别名值['.$t['value'].']';
                        }
                    }
                }
            } elseif (!$t['name'] && $t['value']) {
                // 标示只有where的条件查询
                $where[$i]['use'] = 1;
            } elseif ($t['adj'] == 'MAP' && dr_in_array($t['name'].'_lat', $field) && dr_in_array($t['name'].'_lng', $field)) {
                $where[$i]['use'] = 1;
                $where[$i]['prefix'] = "`$prefix`.";
            } else {
                if (!$t['use'] && !in_array($t['name'], ['where'])) {
                    $this->_list_error[] = '在['.$prefix.']表中字段['.$t['name'].']不存在（可用字段：'.implode('、', $field).'）';
                }
                $where[$i]['use'] = $t['use'] ? 1 : 0;
            }
        }

        return $where;
    }

    // 给显示字段加上表前缀
    public function _set_select_field_prefix($select, $field, $prefix) {

        if ($select) {
            $array = explode(',', $select);
            foreach ($array as $i => $t) {

                $field_prefix = '';
                if (strpos($t, 'DISTINCT_') === 0) {
                    $t = str_replace('DISTINCT_', '', $t);
                    $field_prefix = 'DISTINCT ';
                }

                if (dr_in_array($t, $field)) {
                    $array[$i] = $field_prefix."`$prefix`.`$t`";
                } elseif (strpos($t, '.') !== false && strpos($t, '`') === false) {
                    list($a, $b) = explode('.', $t);
                    if (($prefix == $a || substr($prefix, strlen(\Phpcmf\Service::M()->dbprefix())) == $a)) {
                        if (strpos($b, ':') !== false) {
                            // 存在别名
                            list($b, $cname) = explode(':', $b);
                            if (dr_in_array($b, $field)) {
                                $array[$i] = $field_prefix."`$prefix`.`$b` as `$cname`";
                            }
                        } else {
                            if (dr_in_array($b, $field)) {
                                $array[$i] = $field_prefix."`$prefix`.`$b`";
                            }
                        }
                    }
                }
            }
            return implode(',', $array);
        }

        return $select;
    }

    // 获取分页页数
    private function _get_page_id($page) {

        if (is_numeric($page)) {
            return max(1, (int)$_GET['page']);
        } else {
            return max(1, isset($_GET[$page]) ? (int)$_GET[$page] : 0);
        }
    }

    // 格式化查询参数
    private function _get_select_field($field) {

        if ($field != '*') {
            $my = [];
            $array = explode(',', $field);
            foreach ($array as $t) {
                if (strpos($t, '`') !== false) {
                    $my[] = $t;
                    continue;
                }
            }
            if (!$my) {
                $field = '*';
            } else {
                $field = implode(',', $my);
            }
        }

        // 定位范围搜索
        if ($this->pos_order) {
            if ($this->pos_map && $this->pos_map['lat'] && $this->pos_map['lng']) {
                if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                    $field .= ',ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $this->pos_map['lat'] . '*PI()/180-' . $this->pos_order . '_lat*PI()/180)/2),2)+COS(' . $this->pos_map['lat'] . '*PI()/180)*COS(' . $this->pos_order . '_lat*PI()/180)*POW(SIN((' . $this->pos_map['lng'] . '*PI()/180-' . $this->pos_order . '_lng*PI()/180)/2),2)))*1000) AS ' . $this->pos_order . '_map';
                } else {
                    $field.= ',ST_Distance_Sphere(POINT('.$this->pos_order.'_lng, '.$this->pos_order.'_lat), POINT('.$this->pos_map['lng'].', '.$this->pos_map['lat'].')) AS '.$this->pos_order.'_map';
                }
            } else {
                $field.= '没有定位到您的坐标';
            }
        }

        return $field;
    }

    // join 联合查询表
    public function _join_table($main, $system, $where, $_order, $sql_from) {

        $table = \Phpcmf\Service::M()->dbprefix($system['join']); // 关联表
        $tableinfo = \Phpcmf\Service::L('cache')->get_data('table-join-'.$system['join']);
        if (!$tableinfo) {
            $tableinfo = \Phpcmf\Service::M('Table')->get_field($system['join']);
            if (!$tableinfo) {
                return dr_return_data(0, '关联数据表('.$system['join'].')结构不存在');
            }
            \Phpcmf\Service::L('cache')->set_data('table-join-'.$system['join'], $tableinfo, 36000);
        }

        list($a, $b) = explode(',', $system['on']);
        $b = $b ? $b : $a;
        $where = $this->_set_where_field_prefix($where, $tableinfo, $table); // 给条件字段加上表前缀
        $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo, $table); // 给显示字段加上表前缀
        $_order[$table] = $tableinfo;
        $sql_from.= ' LEFT JOIN `'.$table.'` ON `'.$main.'`.`'.$a.'`=`'.$table.'`.`'.$b.'`';

        return dr_return_data(1, 'ok', [$system, $where, $_order, $sql_from]);
    }

    // 给排序字段加上多表前缀
    public function _set_orders_field_prefix($order, $fields) {

        if (!$order) {
            return NULL;
        } elseif (strtoupper($order) == 'FIXNULL') {
            // NULL排序
            return 'NULL';
        } elseif (in_array(strtoupper($order), ['RAND()', 'RAND'])) {
            // 随机排序
            return 'RAND()';
        }

        $order = urldecode($order);
        if (strpos($order, '`') !== false) {
            return $order;
        }

        // 字段排序
        $my = [];
        $array = explode(',', $order);

        foreach ($array as $i => $t) {
            $a = explode('_', $t);
            $b = end($a);
            if (in_array(strtolower($b), ['desc', 'asc', 'instr', 'field'])) {
                $a = str_replace('_'.$b, '', $t);
            } else {
                $a = $t;
                $b = '';
            }
            $b = strtoupper($b);
            foreach ($fields as $prefix => $field) {
                if (is_array($field)) {
                    if (dr_in_array($a, $field)) {
                        if ($b == 'INSTR') {
                            if (isset($this->_list_where['IN_' . $a]) && $this->_list_where['IN_' . $a]) {
                                $my[$i] = "instr(\"" . $this->_list_where['IN_' . $a]['value'] . "\",`$prefix`.`$a`)";
                            } else {
                                $this->_list_error[] = '无法找到字段' . $a . '的IN通配符参数，order参数将会无效';
                            }
                        } elseif ($b == 'FIELD') {
                            if (isset($this->_list_where['IN_'.$a]) && $this->_list_where['IN_'.$a]) {
                                $my[$i] = "FIELD(`$prefix`.`$a`, ".$this->_list_where['IN_'.$a]['value'].")";
                            } else {
                                $this->_list_error[] = '无法找到字段'.$a.'的IN通配符参数，order参数将会无效';
                            }
                        } else {
                            $my[$i] = "`$prefix`.`$a` ".($b ? $b : "DESC");
                        }
                    } elseif (dr_in_array($a.'_lat', $field) && dr_in_array($a.'_lng', $field)) {
                        if ($this->pos_map) {
                            $my[$i] = `$prefix`.$a.'_map ASC';
                            $this->pos_order = $a;
                        } else {
                            $this->_list_error[] = '没有定位到您的坐标，order参数将会无效';
                        }
                    }
                }
            }

        }
        if ($my) {
            return implode(',', $my);
        }

        return NULL;
    }

    // 给排序字段加上表前缀
    public function _set_order_field_prefix($order, $field, $prefix) {
        return $this->_set_orders_field_prefix($order, [$prefix => $field]);
    }

    // list 返回
    public function _return($return, $data = [], $sql = '', $total = 0, $pages = '', $pagesize = 0, $is_cache = 0) {

        $this->pos_map = $this->pos_order = null;
        $total = isset($total) && $total ? $total : dr_count($data);
        $page = $this->_get_page_id($this->_page_value);
        $nums = $pagesize ? ceil($total/$pagesize) : 0;

        if (CI_DEBUG) {
            // 显示debug数据
            $debug = '<pre style="background-color: #f5f5f5; border: 1px solid #ccc;padding:10px; overflow: auto; text-align: left">';

            if ($this->_list_tag) {
                $debug.= '<p>标签解析：'.str_replace('{list action=', '{', $this->_list_tag).'</p>';
            }

            if ($this->_list_error) {
                $debug.= '<p>错误提示：'.implode('、', $this->_list_error).'</p>';
            }

            if (strpos($this->_list_tag, 'return=') !== false) {
                $arr = explode('return=', $this->_list_tag);
                if (strpos($arr[1], ' ') !== false) {
                    $debug.= '<p>错误提示：return参数必须放在最后，且后面不能带有空格符号</p>';
                }
            }

            if ($this->_is_list_search) {
                if (!$this->_options['is_search_page']) {
                    $debug.= '<p>使用范围：search标签只能用于搜索页面，当前页面不是搜索页面，可能会无效</p>';
                }
                $debug.= '<p>搜索解析：'.$this->_options['search_sql'].'</p>';
            } elseif ($this->_options['is_search_page'] && (strpos($this->_list_tag, 'page=1') || $this->_page_used)) {
                $debug.= '<p>标签提醒：当前页面是搜索页面，只有search标签才适用于搜索页面，可能会引起本标签分页无效</p>';
            }

            if ($sql) {
                // 运算替换
                if ($this->_return_sql && strpos($sql, $this->_select_rt_name) !== false) {
                    switch ($this->_return_sql) {
                        case 'count':
                            $sql = str_replace($this->_select_rt_name, 'count(*) as ct', $sql);
                            break;
                        case 'sum':
                            $sql = str_replace($this->_select_rt_name, 'sum('.$this->_sum_field.') as ct', $sql);
                            break;
                    }
                }
                $debug.= '<p>查询解析: '.$sql.'</p>';
                $debug.= '<p>查询耗时: '.$this->_sql_time.'s</p>';
            }

            $debug.= '<p>当前路由：'.\Phpcmf\Service::L('router')->uri().'</p>';
            CMSURI && $debug.= '<p>当前地址：'.CMSURI.'</p>';
            $debug.= '<p>动态地址：'.SELF.'?'.http_build_query($_GET).'</p>';

            if ($data && !is_array($data)) {
                $debug.= '<p>'.$data.'</p>';
                $data = [];
            }

            $debug.= '<p>变量前缀：'.($return ? $return : 't').'</p>';
        } else {
            $debug = 'debug诊断信息需要在index.php文件中开启开发者模式才能查看';
        }

        // 开始返回
        if ($this->_return_sql) {
            if (CI_DEBUG) {
                $debug .= '<p>运算数量：' . ($data[0]['ct']) . '</p>';
                $debug .= '<p>运算变量：' . ($return ? '{$' . $return . '_' . $this->_return_sql . '} 不输出，需要手动调用变量' : '自动输出') . '</p>';
                $debug .= '</pre>';
            }
            return [
                'debug_'.$this->_return_sql => $debug,
                'return_'.$this->_return_sql => $data,
            ];
        } else {
            if (CI_DEBUG) {
                $debug.= '<p>总记录数：'.$total.'</p>';
                if ($this->_page_used) {
                    $debug.= '<p>分页功能：已开启</p>';
                    $debug.= '<p>当前页码：'.$page.'</p>';
                    $debug.= '<p>总页数量：'.$nums. ($nums == 1 ? '（数据量未达到分页数据，因此只有一页）' : '').'</p>';
                    $debug.= '<p>每页数量：'.$pagesize.'</p>';
                    $debug.= '<p>分页地址：'.$this->_page_urlrule.'</p>';
                    $debug.= '<p>分页配置：'.$this->page_config_file.'</p>';
                } else {
                    $debug.= '<p>分页功能：未开启</p>';
                }

                isset($data[0]) && is_array($data[0]) && $debug.= '<p>可用字段：'.implode('、', array_keys($data[0])).'</p>';
                $debug.= '</pre>';
            }
            // 返回数据格式
            if ($return) {
                return [
                    'nums_'.$return => $nums,
                    'page_'.$return => $page,
                    'pages_'.$return => $pages,
                    'debug_'.$return => $debug,
                    'total_'.$return => $total,
                    'return_'.$return => $data,
                    'pagerule_'.$return => $this->_page_urlrule,
                    'pagesize_'.$return => $pagesize,
                ];
            } else {
                return [
                    'nums' => $nums,
                    'debug' => $debug,
                    'page' => $page,
                    'total' => $total,
                    'pages' => $pages,
                    'return' => $data,
                    'pagerule' => $this->_page_urlrule,
                    'pagesize' => $pagesize,
                ];
            }
        }
    }

    // 替换变量
    public function _get_var($param) {

        $array = explode('.', $param);
        if (!$array) {
            return '';
        }

        $string = '';
        foreach ($array as $var) {
            $var = dr_safe_replace($var);
            $string.= '[';
            if (strpos($var, '$') === 0) {
                $string.= preg_replace('/\[(.+)\]/U', '[\'\\1\']', $var);
            } elseif (preg_match('/[A-Z_]+/', $var)) {
                $string.= ''.$var.'';
            } else {
                $string.= '\''.$var.'\'';
            }
            $string.= ']';
        }

        return $string;
    }

    // 公共变量参数
    public function _cache_var($name, $siteid = 0) {

        $data = null;
        $name = strtolower($name);

        switch ($name) {
            case 'member':
                $data = \Phpcmf\Service::C()->member_cache;
                break;
            case 'member_group':
                $data = \Phpcmf\Service::C()->member_cache['group'];
                break;
            case 'site_info':
                $data = \Phpcmf\Service::C()->site_info;
                break;
            case 'urlrule':
                $data = \Phpcmf\Service::L('cache')->get('urlrule');
                break;
            case 'module-content':
                $data = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-content');
                break;
            case 'category':
                $data = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.MOD_DIR, 'category');
                break;
            case 'page':
                $data = \Phpcmf\Service::L('cache')->get('page-'.$siteid, 'data');
                break;
            default:
                if ($siteid) {
                    $data = \Phpcmf\Service::L('cache')->get($name.'-'.$siteid);
                } else {
                    $data = \Phpcmf\Service::L('cache')->get($name);
                }
                break;
        }

        return $data;
    }

    // 模板中的全部变量
    public function get_data() {
        return $this->_options;
    }

    // 模板中的全部变量
    public function getData() {
        return [];
    }

    // 模板中的文件数
    public function get_view_files() {
        return $this->_view_files;
    }

    // 模板中的文件引用提示
    public function add_load_tips($name, $msg) {
        if ($name) {
            $this->_load_file_tips[$name] = $msg;
        } else {
            $this->_load_file_tips[] = $msg;
        }
    }

    // 模板中的文件引用提示
    public function get_load_tips() {

        if (!$this->_load_file_tips) {
            return [
                [
                    'name' => $this->_filename,
                    'tips' => ''
                ]
            ];
        }

        $rt = [];
        foreach ($this->_load_file_tips as $i => $t) {
            $rt[] = [
                'name' => $i,
                'tips' => $t,
            ];
        }

        return $rt;
    }

    // 模板中的运行时间
    public function get_view_time() {
        return $this->_view_time;
    }

    /**
     * 主要用于调试工具栏
     *
     * @return array
     */
    public function getPerformanceData(): array
    {
        return $this->performanceData;
    }

    /**
     * 记录模板的性能数据
     *
     * @param float  $start
     * @param float  $end
     * @param string $view
     */
    protected function logPerformance(float $start, float $end, string $view)
    {
        if (!CI_DEBUG)
            return;

        $this->performanceData[] = [
            'start'  => $start,
            'end'    => $end,
            'view'   => $view
        ];
    }

    // 错误提示
    public function show_error($msg, $file = '', $fixfile = '') {

        if (CI_DEBUG || defined('SC_HTML_FILE')) {
            // 开发者模式下，静态生成模式下，显示详细错误
            if ($file) {
                $msg.= '（'.$file.'）';
                if ($fixfile) {
                    $msg.= '<br>你可以将PC模板（'.$fixfile.'）手动复制过来作为本模板';
                }
            }
            log_message('error', $this->_options['my_web_url'].'：'.$msg);
            if (defined('SC_HTML_FILE')) {
                if (isset($_GET['iframe'])) {
                    \Phpcmf\Service::C()->_html_msg(0, $this->_options['my_web_url'].'：'.$msg);
                } else {
                    \Phpcmf\Service::C()->_json(0, $this->_options['my_web_url'].'：'.$msg);
                }
            }
        }

        dr_show_error($msg);
    }

}