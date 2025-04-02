<?php namespace Phpcmf\Control;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

define('IS_INSTALL', 1);
set_time_limit(0);

// 安装程序
class Install extends \Phpcmf\Common {

    public $db;
    private $lock;

    /**
     * 初始化共享控制器
     */
    public function __construct()
    {
        parent::__construct();
        define('SITE_LANGUAGE', is_file(WRITEPATH.'lang.default') ? file_get_contents(WRITEPATH.'lang.default') : 'zh-cn');
        define('SITE_ID', 1);
        define('SITE_URL', '/');
        define('SITE_MURL', '/');
        define('IS_API_HTTP', 0);
        define('SITE_TEMPLATE', 'default');
        define('THEME_PATH', '/static/');
        define('ROOT_THEME_PATH', '/static/');
        define('LANG_PATH', '/api/language/'.SITE_LANGUAGE.'/'); // 语言包
        if (is_file(MYPATH.'Config/Version.php')) {
            $app = require MYPATH.'Config/Version.php';
        } else {
            $app = [
                'id' => 1,
                'name' => 'XunRuiCMS',
                'version' => 'dev',
            ];
        }
        define('DR_CMS', $app['id']);
        define('DR_NAME', $app['name']);
        define('DR_VERSION', $app['version']);
        define('SITE_NAME', $app['name']);
        define('CMF_UPDATE_TIME', SYS_TIME);
        \Phpcmf\Service::V()->init('pc');
        \Phpcmf\Service::V()->admin();
    }

    public function index() {

        $this->lock = WRITEPATH.'install.lock';
        if (!IS_XRDEV && is_file($this->lock)) {
            exit(dr_lang('安装程序已经被锁定，重新安装请删除%s', 'cache/install.lock'));
        }

        $data = [];
        $step = intval($_GET['step']);
        if (!$step && SITE_LANGUAGE != 'zh-cn') {
            $step = 1;
        }
        $error = '';
        $admin = 'admin';

        switch ($step) {

            case 0:
                $gsname = '四川迅睿云软件开发有限公司';
                if (is_file(MYPATH.'Config/License.php')) {
                    $ls = require MYPATH.'Config/License.php';
                    if (isset($ls['name']) && $ls['name']) {
                        $gsname = $ls['name'];
                    }
                }
                \Phpcmf\Service::V()->assign([
                    'gsname' => $gsname,
                ]);
                break;

            case 1:

                $error = 0;
                // 目录权限检查
                $dir = [
                    WRITEPATH,
                    WRITEPATH.'data/',
                    WRITEPATH.'file/',
                    WRITEPATH.'config/',
                    WRITEPATH.'session/',
                    WRITEPATH.'template/',
                    CONFIGPATH,
                    WEBPATH.'uploadfile/',
                ];
                $path = [];
                foreach ($dir as $t) {
                    $path[$t] = dr_check_put_path($t);
                    if (!$path[$t]) {
                        $error = 1;
                    }
                }
                $php = [
                    [
                        'name' => dr_lang('PHP扩展%s', 'mb string'),
                        'code' => function_exists('mb_substr'),
                        'help' => 'http://www.xunruicms.com/doc/742.html',
                    ],
                    [
                        'name' => dr_lang('PHP扩展%s', 'curl'),
                        'code' => function_exists('curl_init'),
                        'help' => 'http://www.xunruicms.com/doc/743.html',
                    ],
                ];

                \Phpcmf\Service::V()->assign([
                    'php' => $php,
                    'path' => $path,
                    'error' => $error,
                ]);
                break;

            case 2:
                // 安装信息填写

                $is_demo = is_file(MYPATH.'Config/demo.sql') && !is_file(MYPATH.'Config/Install.sql');

                if (IS_AJAX_POST) {

                    $data = $_POST['data'];
                    if (empty($data['name'])) {
                        $this->_json(0, dr_lang('项目名称不能为空'));
                    } elseif (empty($data['username'])) {
                        $this->_json(0, dr_lang('创始人账号不能为空'));
                    } elseif (empty($data['password'])) {
                        $this->_json(0, dr_lang('创始人密码不能为空'));
                    }  elseif (empty($data['email'])) {
                        $this->_json(0, dr_lang('创始人邮箱不能为空'));
                    } elseif (empty($data['db_host'])) {
                        $this->_json(0, dr_lang('数据库地址不能为空'));
                    } elseif (empty($data['db_user'])) {
                        $this->_json(0, dr_lang('数据库账号不能为空'));
                    } elseif (empty($data['db_name'])) {
                        $this->_json(0, dr_lang('数据库名称不能为空'));
                    } elseif (empty($data['db_pass'])) {
                        $this->_json(0, dr_lang('数据库密码不能为空'));
                    } elseif (empty($data['db_prefix'])) {
                        $this->_json(0, dr_lang('数据表前缀不能为空'));
                    }
                    if (is_numeric(substr($data['db_name'], 0, 1))) {
                        $this->_json(0, dr_lang('数据库名称（%s）不能是数字开头', $data['db_name']));
                    } elseif (strpos($data['db_name'], '.') !== false) {
                        $this->_json(0, dr_lang('数据库名（%s）不能存在.号', $data['db_name']));
                    }
                    $data['db_name'] = dr_safe_filename($data['db_name']);
                    $mysqli = function_exists('mysqli_init') ? mysqli_init() : 0;
                    if (!$mysqli) {
                        $this->_json(0, dr_lang('PHP环境必须启用Mysqli扩展'));
                    } elseif (!mysqli_real_connect($mysqli, $data['db_host'], $data['db_user'], $data['db_pass'])) {
                        if (SITE_LANGUAGE == 'zh-cn') {
                            $this->_json(0, '['.mysqli_connect_errno().'] - 无法连接到数据库服务器（'.$data['db_host'].'），请检查用户名（'.$data['db_user'].'）和密码（'.$data['db_pass'].'）是否正确');
                        } else {
                            $this->_json(0, mysqli_connect_errno() . mysqli_connect_error());
                        }
                    } elseif (!mysqli_select_db($mysqli, $data['db_name'])) {
                        if (!mysqli_query($mysqli, 'CREATE DATABASE '.$data['db_name'])) {
                            $this->_json(0, dr_lang('指定的数据库（%s）不存在，系统尝试创建失败，请通过其他方式建立数据库', $data['db_name']));
                        }
                    }

                    if (!mysqli_set_charset($mysqli, "utf8mb4")) {
                        $this->_json(0, dr_lang("当前MySQL不支持utf8mb4编码（%s）建议升级MySQL版本", mysqli_error($mysqli)));
                    }

                    $data['db_prefix'] = strtolower($data['db_prefix']);

                    // 存储缓存文件中
                    $size = file_put_contents(WRITEPATH.'install.info', dr_array2string($data));
                    if (!$size || $size < 10) {
                        $this->_json(0, dr_lang('临时数据存储失败，cahce目录无法写入'));
                    }

                    // 存储mysql
                    $database = '<?php

/**
 * 数据库配置文件
 */

$db[\'default\']	= [
    \'hostname\'	=> \''.$data['db_host'].'\',
    \'username\'	=> \''.$data['db_user'].'\',
    \'password\'	=> \''.$data['db_pass'].'\',
    \'database\'	=> \''.$data['db_name'].'\',
    \'DBPrefix\'	=> \''.dr_safe_filename($data['db_prefix']).'\',
];';
                    $size = file_put_contents(CONFIGPATH.'database.php', $database);
                    if (!$size || $size < 10) {
                        $this->_json(0, dr_lang('数据库配置文件创建失败，config目录无法写入'));
                    }

                    $this->_json(1, 'index.php?c=install&m=index&is_install_db='.(!$is_demo ? 0 : intval($_POST['is_install_db'])).'&step='.($step+1));
                }

                \Phpcmf\Service::V()->assign([
                    'do_url' => '/index.php?c=install&m=index&is_install_db='.(!$is_demo ? 0 : intval($_POST['is_install_db'])).'&step=2',
                    'is_demo' => $is_demo,
                ]);

                break;

            case 3:
                $page = intval($_GET['page']);
                $data = dr_string2array(file_get_contents(WRITEPATH.'install.info'));
                $error = '';
                file_put_contents(WRITEPATH.'install.error', '');
                if (empty($data)) {
                    $error = dr_lang('临时数据获取失败，请返回前一页重新执行');
                } else {
                    $this->db = \Phpcmf\Service::M()->db;
                    // 检查数据库是否存在
                    if (!$this->db->connect(false)) {
                        // 链接失败,尝试创建数据库
                        $error = dr_lang('数据库连接失败，请返回前一页重新执行');
                    } elseif (!isset($_GET['doin']) && $this->db->tableExists($data['db_prefix'].'cron')) {
                        // 判断是否安装过
                        $error = dr_lang('指定的数据库（%s）已经安装过表前缀（%s），你可以尝试修改数据库名或者数据表前缀', $data['db_name'], $data['db_prefix']);
                    } else {

                        // 导入数据结构
                        if ($page) {
                            if (is_file(MYPATH.'Config/Install.sql')) {
                                $sql = file_get_contents(MYPATH.'Config/Install.sql');
                                if (strlen($sql) < 100) {
                                    $sql = file_get_contents(CMSPATH.'Config/Install.sql');
                                }
                            } else {
                                $sql = file_get_contents(CMSPATH.'Config/Install.sql');
                            }
                            $sql = str_replace('{dbprefix}', $data['db_prefix'], $sql);
                            $rows = $this->query_rows($sql, 10);
                            $key = $page - 1;
                            if (isset($rows[$key]) && $rows[$key]) {
                                // 安装本次结构
                                foreach($rows[$key] as $query){
                                    if (!$query) {
                                        continue;
                                    }
                                    $ret = '';
                                    $queries = explode('SQL_FINECMS_EOL', trim($query));
                                    foreach($queries as $query) {
                                        $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                                    }
                                    if (!$ret) {
                                        continue;
                                    }
                                    if (!$this->db->simpleQuery($ret)) {
                                        $rt = $this->db->error();
                                        $error = '**************************************************************************'
                                            .PHP_EOL.$ret.PHP_EOL.$rt['message'].PHP_EOL;
                                        $error.= '**************************************************************************'.PHP_EOL;
                                        file_put_contents(WRITEPATH.'install.error', $error.PHP_EOL, FILE_APPEND);
                                        $this->_json(0, 'SQL执行错误：'.$rt['message']);
                                    }
                                }
                                $this->_json(1, dr_lang('正在执行：%s', dr_strcut($ret, 70)), ['page' => $page + 1]);

                            } else {
                                $this->_json(1, dr_lang('执行完成，即将安装数据信息...'), ['page' => 99]);
                            }
                        }

                    }
                }

                \Phpcmf\Service::V()->assign([
                    'do_url' => 'index.php?c=install&m=index&step=3&doin=1&is_install_db='.intval($_GET['is_install_db']),
                ]);
                break;

            case 4:

                $data = dr_string2array(file_get_contents(WRITEPATH.'install.info'));
                if (empty($data)) {
                    $error = dr_lang('临时数据获取失败，请返回前一页重新执行');
                } else {
                    $this->db = \Phpcmf\Service::M()->db;
                    // 检查数据库是否存在
                    if (!$this->db->connect(false)) {
                        // 链接失败,尝试创建数据库
                        $error = dr_lang('数据库连接失败，请返回前一页重新执行');
                    } else {
                        // 导入默认安装数据
                        $errorlog = file_get_contents(WRITEPATH.'install.error');
                        if ($errorlog && strlen($errorlog) > 10) {
                            // 出现错误了
                            $error = $errorlog;
                        } else {
                            if (!\Phpcmf\Service::M()->is_table_exists('member')) {
                                if (is_file(MYPATH.'Config/Install.sql')) {
                                    $sql = MYPATH.'Config/Install.sql';
                                } else {
                                    $sql = CMSPATH.'Config/Install.sql';
                                }
                                $error = dr_lang('SQL文件：%s', $sql);
                                $error.= '<br>'.dr_lang('关键数据表member结构不完整，检查安装包sql文件是否合理');
                            } else {
                                // 创建账号
                                $pwd = md5(dr_safe_password($data['password']));
                                $salt = substr(md5(rand(0, 999)), 0, 10);
                                $prefix = \Phpcmf\Service::M()->prefix;
                                if (\Phpcmf\Service::M()->table('member')->get(1)) {
                                    \Phpcmf\Service::M()->table('member')->update(1, [
                                        'email' => $data['email'],
                                        'username' => $data['username'],
                                        'password' => md5($pwd.$salt.$pwd),
                                        'salt' => $salt,
                                        'name' => dr_lang('创始人'),
                                        'phone' => '',
                                        'money' => 1000000,
                                        'freeze' => 0,
                                        'spend' => 0,
                                        'score' => 1000000,
                                        'experience' => 1000000,
                                        'regip' => '',
                                        'regtime' => SYS_TIME,
                                        'randcode' => 0,
                                    ]);
                                    $id = 1;
                                } else {
                                    $this->db->table('member')->insert([
                                        'email' => $data['email'],
                                        'username' => $data['username'],
                                        'password' => md5($pwd.$salt.$pwd),
                                        'salt' => $salt,
                                        'name' => dr_lang('创始人'),
                                        'phone' => '',
                                        'money' => 1000000,
                                        'freeze' => 0,
                                        'spend' => 0,
                                        'score' => 1000000,
                                        'experience' => 1000000,
                                        'regip' => '',
                                        'regtime' => SYS_TIME,
                                        'randcode' => 0,
                                    ]);
                                    $id = $this->db->insertID();
                                    $this->db->table('member_data')->insert([
                                        'id' => $id,
                                        'is_lock' => 0,
                                        'is_admin' => 1,
                                        'is_verify' => 1,
                                        'is_mobile' => 1,
                                        'is_complete' => 1,
                                    ]);
                                }

                                // 加入管理员表
                                if (!\Phpcmf\Service::M()->table('admin')->where('uid', $id)->counts()) {
                                    $this->db->table('admin')->insert([
                                        'uid' => $id,
                                        'setting' => '',
                                        'usermenu' => '',
                                    ]);
                                    // 加入角色表
                                    $this->db->table('admin_role_index')->insert([
                                        'uid' => $id,
                                        'roleid' => 1,
                                    ]);
                                }

                                // 创建站点
                                if (\Phpcmf\Service::M()->table('site')->get(1)) {
                                    \Phpcmf\Service::M()->table('site')->update(1, [
                                        'name' => $data['name'],
                                        'domain' => DOMAIN_NAME,
                                    ]);
                                } else {
                                    $this->db->table('site')->replace([
                                        'id' => 1,
                                        'name' => $data['name'],
                                        'domain' => DOMAIN_NAME,
                                        'setting' => '',
                                        'disabled' => 0,
                                        'displayorder' => 0,
                                    ]);
                                }

                                \Phpcmf\Service::M()->site = $this->site = [ 1 => 1 ];

                                $ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 1 : 0;;
                                if (isset($_GET['protocol']) && trim($_GET['protocol'], ':') == 'https') {
                                    $ssl = 1;
                                }

                                // 写配置文件
                                $sys = [
                                    'SYS_DEBUG'                     => '1',
                                    'SYS_ADMIN_CODE'                => '0',
                                    'SYS_ADMIN_LOG'                 => '0',
                                    'SYS_AUTO_FORM'                 => '0',
                                    'SYS_ADMIN_PAGESIZE'            => '10',
                                    'SYS_SMS_IMG_CODE'              => '0',
                                    'SYS_NOT_ADMIN_CACHE'           => '0',
                                    'SYS_URL_ONLY'                  => '0',
                                    'SYS_THEME_ROOT_PATH'           => '1',
                                    'SYS_URL_REL'                   => '1',
                                    'SYS_CRON_AUTH'                 => '0',
                                    'SYS_CSRF'                      => '0',
                                    'SYS_CSRF_TIME'                 => '0',
                                    'SYS_301'                       => '1',
                                    'SYS_NOT_UPDATE'                => '1',
                                    'SYS_KEY'                       => 'PHPCMF'.md5($data['name'].rand(1, 999999)), //安全密匙
                                    'SYS_HTTPS'                     => $ssl,
                                    'SYS_ATTACHMENT_DB'             => '',
                                    'SYS_ATTACHMENT_PATH'           => '',
                                    'SYS_ATTACHMENT_URL'            => '',
                                    'SYS_API_TOKEN'                 => '',
                                ];
                                if (is_file(MYPATH.'Config/License.php')) {
                                    $ls = require MYPATH.'Config/License.php';
                                    if (isset($ls['oem']) && $ls['oem']) {
                                        $sys['SYS_DEBUG'] = 0;
                                    }
                                }
                                \Phpcmf\Service::M('System')->save_config($sys, $sys);

                                // 删除app的install.lock
                                $local = \Phpcmf\Service::Apps();
                                foreach ($local as $dir => $path) {
                                    if (is_file($path.'install.lock')) {
                                        unlink($path.'install.lock');
                                    }
                                }

                                // 执行安装程序废除
                                /*
                                $sql = '';
                                if (is_file(MYPATH.'Config/Install.sql')) {
                                    $sql = file_get_contents(MYPATH.'Config/Install.sql');
                                    $sql = str_replace('{dbprefix}', $data['db_prefix'], $sql);
                                }

                                if (is_file(MYPATH.'Config/Install_site.sql')) {
                                    $s = file_get_contents(MYPATH.'Config/Install_site.sql');
                                    $sql.= PHP_EOL.str_replace('{dbprefix}', $data['db_prefix'].'1_', $s);
                                }
                                $this->query($sql);*/

                                // 运行自定义安装脚本
                                if (is_file(MYPATH.'Config/Install.php')) {
                                    require MYPATH.'Config/Install.php';
                                }

                                // 运行自定义安装脚本②
                                if (is_file(MYPATH.'Config/Install_tpl.php')) {
                                    require MYPATH.'Config/Install_tpl.php';
                                }

                                $errorlog = file_get_contents(WRITEPATH.'install.error');
                                if ($errorlog && strlen($errorlog) > 10) {
                                    // 出现错误了
                                    $error = $errorlog;
                                } else {
                                    // 安装完成
                                    file_put_contents($this->lock, time());
                                    file_put_contents(WRITEPATH.'update.txt', time());
                                    file_put_contents(WRITEPATH.'install.test', time());
                                    unlink(WRITEPATH.'install.info');
                                    unlink(WRITEPATH.'install.error');
                                    // 重命名后台入口
                                    $admin = 'admin'.substr(md5(SYS_TIME.rand(1, 999999)), 0, 12);
                                    $afile = WEBPATH.$admin.'.php';
                                    if (is_file(WEBPATH.'admin.php')) {
                                        copy(WEBPATH.'admin.php', $afile);
                                    }
                                    if (!is_file($afile)) {
                                        file_put_contents($afile, "<?php
define('IS_ADMIN', TRUE);
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
require('index.php');");
                                    }
                                    if (is_file($afile)) {
                                        unlink(WEBPATH.'admin.php');
                                    } else {
                                        $admin = 'admin';
                                    }
                                }
                            }
                        }
                    }
                }

                break;
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'step' => $step,
            'error' => $error,
            'admin' => $admin,
            'pre_url' => 'index.php?c=install&m=index&step='.($step-1).'&is_install_db='.intval($_GET['is_install_db']),
            'next_url' => 'index.php?c=install&m=index&step='.($step+1).'&is_install_db='.intval($_GET['is_install_db']),
        ]);
        \Phpcmf\Service::V()->display('install/'.$step.'.html');
        exit;
    }

    // 数据分组
    private function query_rows($sql, $num = 0) {

        if (!$sql) {
            return '';
        }

        $rt = [];
        $sql = dr_format_create_sql($sql);
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $sql)));

        foreach($sql_data as $query){
            if (!$query) {
                continue;
            }
            $ret = '';
            $queries = explode('SQL_FINECMS_EOL', trim($query));
            foreach($queries as $query) {
                $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
            }
            if (strpos($ret, '/*') === 0 && preg_match('/\/\*(.+)\*\//U', $ret)) {
                continue;
            }
            if (!$ret) {
                continue;
            }
            $rt[] = $ret;
        }

        return $num ? array_chunk($rt, $num) : $rt;
    }

    // 数据执行
    private function query($sql) {

        if (!$sql) {
            return '';
        }

        $sql = dr_format_create_sql($sql);
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $sql)));

        foreach($sql_data as $query){
            if (!$query) {
                continue;
            }
            $ret = '';
            $queries = explode('SQL_FINECMS_EOL', trim($query));
            foreach($queries as $query) {
                $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
            }
            if (!$ret) {
                continue;
            }
            if (strpos($ret, '/*') === 0 && preg_match('/\/\*(.+)\*\//U', $ret)) {
                continue;
            }
            if (!$this->db->simpleQuery($ret)) {
                $rt = $this->db->error();
                $error = '**************************************************************************'
                    .PHP_EOL.$ret.PHP_EOL.$rt['message'].PHP_EOL;
                $error.= '**************************************************************************'.PHP_EOL;
                file_put_contents(WRITEPATH.'install.error', $error.PHP_EOL, FILE_APPEND);
            }
        }
    }

}
