<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Check extends \Phpcmf\Common
{

    private $_list = [

        '01' => '文件上传检测',
        '02' => 'PHP环境检测',
        '03' => '目录权限检测',
        '04' => '后台入口名称检测',
        '05' => '数据库权限检测',
        '06' => '模板完整性检测',
        '07' => '数据库表结构检测',
        '08' => '程序兼容性检测',
        '09' => '网站安全性检测',
        '10' => '数据负载优化检测',
        '11' => '域名绑定检测',
        '12' => 'HTTPS检测',
        '13' => '应用插件兼容性检测',
        '14' => '服务器环境检测',

    ];

    public function index() {

        if (is_file(WRITEPATH.'install.info')) {
            @unlink(WRITEPATH.'install.info');
            @unlink(WRITEPATH.'install.error');
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统体检' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                    '系统更新' => ['cache/index', 'fa fa-refresh'],
                    'PHP环境' => [\Phpcmf\Service::L('Router')->class.'/php_index', 'fa fa-code'],
                ]
            ),
            'list' => $this->_list,
        ]);
        \Phpcmf\Service::V()->display('check_index.html');
    }

    public function do_index() {

        $id = $_GET['id'];

        switch ($id) {

            case '01':

                $post = intval(@ini_get("post_max_size"));
                $file = intval(@ini_get("upload_max_filesize"));

                if ($file > $post) {
                    $this->_json(0,'系统配置不合理，post_max_size值('.$post.')必须大于upload_max_filesize值('.$file.')');
                } elseif ($file < 10) {
                    $this->_json(1,'系统环境只允许上传'.$file.'MB文件，可以设置upload_max_filesize值提升上传大小');
                } elseif ($post < 10) {
                    $this->_json(1,'系统环境要求每次发布内容不能超过'.$post.'MB（含文件），可以设置post_max_size值提升发布大小');
                }

                break;


            case '02':

                if (!function_exists('mb_substr')) {
                    $this->_json(0, 'PHP不支持mbstring扩展，必须开启');
                } elseif (!function_exists('curl_init')) {
                    $this->halt('PHP不支持CURL扩展，必须开启', 0);
                } elseif (!function_exists('mb_convert_encoding')) {
                    $this->halt('PHP的mb函数不支持，无法使用百度关键词接口', 0);
                } elseif (!function_exists('imagecreatetruecolor')) {
                    $this->halt('PHP的GD库版本太低，无法支持验证码图片', 0);
                } elseif (!function_exists('ini_get')) {
                    $this->_json(0, '系统函数ini_get未启用，将无法获取到系统环境参数');
                } elseif (!function_exists('gzopen')) {
                    $this->halt('zlib扩展未启用，您将无法进行在线升级、无法下载应用插件等', 0);
                } elseif (!function_exists('gzinflate')) {
                    $this->halt('函数gzinflate未启用，您将无法进行在线升级、无法下载应用插件等', 0);
                } elseif (!function_exists('fsockopen')) {
                    $this->halt('PHP不支持fsockopen，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                } elseif (!function_exists('openssl_open')) {
                    $this->halt('PHP不支持openssl，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                } elseif (!ini_get('allow_url_fopen')) {
                    $this->halt('allow_url_fopen未启用，远程图片无法保存、网络图片无法上传、可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                } elseif (!class_exists('ZipArchive')) {
                    $this->halt('php_zip扩展未开启，无法使用应用市场功能', 0);
                }
                break;

            case '03':

                list($thumb_path) = dr_thumb_path();
                list($avatar_path) = dr_avatar_path();

                $dir = array(
                    WRITEPATH => '无法生成系统缓存文件',
                    $avatar_path => '无法上传头像',
                    WRITEPATH.'data/' => '无法生成系统配置文件，会导致系统配置无效',
                    WRITEPATH.'caching/' => '无法生成系统缓存文件，会导致系统无法运行',
                    $thumb_path => '无法生成缩略图缓存文件',
                    SYS_UPLOAD_PATH => '无法上传附件',
                    APPSPATH => '无法创建模块、创建表单、下载应用插件',
                    TPLPATH => '无法创建模块模板和应用插件模板',
                );

                foreach ($dir as $path => $note) {
                    if (!dr_check_put_path($path)) {
                        $this->_json(0, $note.'【'.$path.'】');
                    }
                }

                if (!is_dir(WEBPATH.'api/ueditor/')) {
                    $this->halt('百度编辑器目录不存在：'.WEBPATH.'api/ueditor/', 0);
                }

                break;

            case '04':
                if (SELF == 'admin.php') {
                    $this->halt('为了系统安全，请修改根目录admin.php的文件名', 0);
                }
                break;

            case '05':

                $list = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();
                if (!$list) {
                    $this->halt("无法获取到数据表结构，需要为Mysql账号开启SHOW TABLE STATUS权限", 0);
                }

                $field = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.\Phpcmf\Service::M()->dbprefix('admin').'`')->getResultArray();
                if (!$field) {
                    $this->halt("无法通获取到数据表字段结构，需要为Mysql账号开启SHOW FULL COLUMNS权限", 0);
                }

                break;

            case '06':

                // 语言文件兼容处理
                if (is_dir(ROOTPATH.'config/language/') && !is_dir(ROOTPATH.'api/language/')) {
                    \Phpcmf\Service::L('file')->copy_dir(ROOTPATH.'config/language/', ROOTPATH.'config/language/', ROOTPATH.'api/language/');
                }

                // 语言文件
                $lang = file_get_contents(LANG_PATH.'lang.js');
                if (strlen($lang) < 10) {
                    $this->halt('网站语言JS文件异常：'.LANG_PATH.'lang.js', 0);
                } elseif (strpos($lang, 'finecms_datepicker_lang') === false) {
                    $this->halt('网站语言JS文件异常：'.LANG_PATH.'lang.js', 0);
                }

                $lang = file_get_contents(LANG_PATH.'ueditor.js');
                if (strlen($lang) < 10) {
                    $this->halt('百度编辑器语言JS文件异常：'.SITE_LANGUAGE.'ueditor.js', 0);
                } elseif (strpos($lang, 'UE.I18N[\''.SITE_LANGUAGE.'\']') === false) {
                    $this->halt('百度编辑器语言JS文件异常：'.LANG_PATH.'ueditor.js', 0);
                }

                // 模板文件
                if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt('网站前端模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/home/index.html', 0);
                } elseif (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/index.html')) {
                    $this->halt('用户中心模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/member/index.html', 0);
                } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt('网站前端模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/home/index.html', 1);
                } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/index.html')) {
                    $this->halt('用户中心模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/member/index.html', 1);
                }
                break;

            case '07':

                // 模块
                $module = \Phpcmf\Service::M()->table('module')->order_by('displayorder ASC,id ASC')->getAll();

                // 站点
                $prefix = \Phpcmf\Service::M()->prefix;
                foreach ($this->site as $siteid) {
                    // 升级资料库
                    $table = $prefix.$siteid.'_block';
                    if (\Phpcmf\Service::M()->db->tableExists($table)) {
                        // 创建code字段 代码
                        if (!\Phpcmf\Service::M()->db->fieldExists('code', $table)) {
                            \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `code` VARCHAR(100) NOT NULL');
                        }
                    }
                    if ($module) {
                        foreach ($module as $m) {
                            $table = $prefix.$siteid.'_'.$m['dirname'].'_recycle';
                            if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                // 创建字段 删除理由
                                if (!\Phpcmf\Service::M()->db->fieldExists('result', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `result` Text NOT NULL');
                                }
                            }
                            $table = $prefix.$siteid.'_'.$m['dirname'].'_support';
                            if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                // 创建字段 游客点赞
                                if (!\Phpcmf\Service::M()->db->fieldExists('agent', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `agent` VARCHAR(200) DEFAULT NULL');
                                }
                            }
                            $table = $prefix.$siteid.'_'.$m['dirname'].'_oppose';
                            if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                // 创建字段 游客点赞
                                if (!\Phpcmf\Service::M()->db->fieldExists('agent', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `agent` VARCHAR(200) DEFAULT NULL');
                                }
                            }
                        }
                    }
                }

                $table = $prefix.'cron';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                }

                $table = $prefix.'member_paylog';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                }

                $table = $prefix.'member_group_verify';
                if (!\Phpcmf\Service::M()->db->fieldExists('price', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `price` decimal(10,2) DEFAULT NULL COMMENT \'已费用\'');
                }

                $table = $prefix.'member_scorelog';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_explog';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_oauth';
                if (!\Phpcmf\Service::M()->db->fieldExists('unionid', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `unionid` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_menu';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT NOT NULL');
                }

                $table = $prefix.'admin_setting';
                if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                      `name` varchar(50) NOT NULL,
                      `value` mediumtext NOT NULL,
                      PRIMARY KEY (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'系统属性参数表\';');
                }
                /*
                                $table = $prefix.'email';
                                if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                                    \Phpcmf\Service::M()->query('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                  `value` text NOT NULL COMMENT \'配置信息\',
                  `displayorder` smallint(5) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY (`displayorder`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT=\'邮件账户表\';');
                                }*/

                /*
                // 创建member_notice username字段
                $table = $prefix.'member_notice';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) NOT NULL');
                }*/
                break;

            case '08':
                // 程序兼容性
                $local = dr_dir_map(APPSPATH, 1); // 搜索本地模块
                foreach ($local as $dir) {
                    if (is_file(APPSPATH.$dir.'/Config/App.php')) {
                        $key = strtolower($dir);
                        $file =  APPSPATH.$dir.'/Controllers/Search.php';
                        if (is_file($file)) {
                            // 替换搜索控制器
                            $code = file_get_contents($file);
                            if (strpos($code, '\Phpcmf\Home\Search') !== false) {
                                file_put_contents($file, str_replace(
                                    ['\Phpcmf\Home\Search', '_Module_Search'],
                                    ['\Phpcmf\Home\Module', '_Search'],
                                    $code
                                ));
                            }
                        }
                    }
                }
                break;

            case '09':
                //
                $local = dr_file_map(WEBPATH, 1); // 搜索根目录
                foreach ($local as $file) {
                    $ext = strtolower(substr(strrchr($file, '.'), 1));
                    if (in_array($ext, ['zip', 'rar', 'sql'])) {
                        $this->halt('文件不安全【/'.$file.'】请及时清理', 0);
                    }
                    $size = file_get_contents(WEBPATH.$file, 0, null, 0, 9286630);
                    if (strlen($size) >= 9286630) {
                        $this->halt('存在大文件文件【/'.$file.'】请及时清理', 0);
                    }
                }
                $this->_json(1,'通过');
                break;

            case '10':

                // 模块数据检测
                $rt = [];
                $module = \Phpcmf\Service::M()->table('module')->getAll();
                if ($module) {
                    foreach ($module as $m) {
                        $site = dr_string2array($m['site']);
                        $mform = \Phpcmf\Service::M()->table('module_form')->where('module', $m['dirname'])->getAll();
                        foreach ($this->site_info as $siteid => $s) {
                            if (isset($site[$siteid]) && $site[$siteid]) {
                                $r = $this->_check_table_counts($siteid . '_' . $m['dirname'], $m['dirname'] . '模块主表');
                                $r && $rt[] = $r;
                                if ($mform) {
                                    foreach ($mform as $mm) {
                                        $r = $this->_check_table_counts($siteid . '_' . $m['dirname'] . '_form_' . $mm['table'], $m['dirname'] . '模块' . $mm['name'] . '表');
                                        $r && $rt[] = $r;
                                    }
                                }
                                if (\Phpcmf\Service::M()->table($siteid.'_'.$m['dirname'].'_category')->counts() > 200) {
                                    $rt[] = '<font color="green">模块【'.$m['name'].'/'.$m['dirname'].'】的栏目数据量超过200个，会影响加载速度，建议对其进行数据优化</font>';
                                }
                            }
                        }
                    }
                }
                if (\Phpcmf\Service::M()->table(SITE_ID.'_share_category')->counts() > 200) {
                    $rt[] = '<font color="green">共享栏目数据量超过200个，会影响加载速度，建议对其进行数据优化</font>';
                }
                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                $this->_json(1,'正常');
                break;

            case '11':

                // 域名检测
                if (!function_exists('stream_context_create')) {
                    $this->halt('函数没有被启用：stream_context_create', 0);
                }

                $error = $tips = [];
                list($module, $data) = \Phpcmf\Service::M('Site')->domain();
                if ($data) {
                    foreach ($data as $name => $domain) {
                        $url = '';
                        if ($name == 'mobile_domain') {
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            } else {
                                $tips[] = '当前站点没有绑定手机域名，可能无法使用移动端界面';
                            }
                        } elseif (strpos($name, 'module_') === 0) {
                            // 模块
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                        } elseif (strpos($name, 'client_') === 0) {
                            // 终端
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                        }

                        if ($url) {
                            $code = dr_catcher_data($url, 5);
                            if ($code != 'phpcmf ok') {
                                $error[] = '域名绑定异常，无法访问：' . $url . '，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功，<a href="'.dr_url('site_domain/index').'">查看详情</a>';
                            }
                        }
                    }
                }

                // 验证附件域名
                list($a, $b) = dr_thumb_path();
                list($c, $d) = dr_avatar_path();
                $domain = [
                    ['name' => '附件域名', 'path' => SYS_UPLOAD_PATH, 'url' => SYS_UPLOAD_URL],
                    ['name' => '缩略图域名', 'path' => $a, 'url' => $b],
                    ['name' => '头像域名', 'path' => $c, 'url' => $d],
                ];
                foreach ($domain as $t) {
                    if (!file_put_contents($t['path'].'api.html', 'phpcmf ok')) {
                        $this->_json(0, $t['path'].' 无法写入文件');
                    }
                    $code = dr_catcher_data($t['url'].'api.html', 5);
                    if ($code != 'phpcmf ok') {
                        $error[] = '['.$t['name'].']异常，无法访问：' . $t['url'] . 'api.html，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功';
                    }
                }


                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                } elseif ($tips) {
                    $this->_json(1, implode('<br>', $tips));
                } else {
                    $this->_json(1, '通过');
                }

                break;

            case '12':
                // https
                if (SYS_HTTPS) {
                    if (strpos(FC_NOW_URL, 'https://') !== false) {
                        $this->_json(1,'正常');
                    } else {
                        $this->_json(0,'服务器无法识别HTTPS证书，<a href="javascript:dr_help(751);">查看解决方案</a>');
                    }
                } else {
                    $this->_json(0,'系统没有开启HTTPS服务');
                }

                break;

            case '13':
                // 应用插件
                $func = [];
                $local = dr_dir_map(dr_get_app_list(), 1);
                $custom = file_get_contents(ROOTPATH.'config/custom.php');
                foreach ($local as $dir) {
                    $path = dr_get_app_dir($dir);
                    if (is_file($path.'Config/App.php') && is_file($path.'Config/Init.php')) {
                        $code = file_get_contents($path.'Config/Init.php');
                        if (preg_match_all("/\s+function (.+)\(/", $code, $arr)) {
                            foreach ($arr[1] as $a) {
                                $name = trim($a);
                                if (strpos($name, "'") !== false) {
                                    continue;
                                }
                                if (isset($func[$name]) && $func[$name]) {
                                    $this->_json(0,'应用['.$dir.']中的函数['.$name.']存在于'.$func[$name].'之中，不能被重复定义');
                                }
                                $func[$name] = $dir;
                                if (function_exists($name)) {
                                    if (preg_match("/\s+function ".$name."\(/", $custom)) {
                                        // 存在于自定义函数库中
                                    } else {
                                        $this->_json(0,'应用['.$dir.']中的函数['.$name.']是系统函数，不能定义');
                                    }
                                }
                            }
                        }
                    }
                }
                $this->_json(1, '通过');

                break;

            case '14':
                // 服务器环境

                $this->_json(1, '通过');
                break;

            case '99':

                break;

        }

        $this->_json(1,'完成');
    }

    public function php_index() {
        phpinfo();
    }


    private function halt($msg, $code) {
        $this->_json($code, $msg);
    }

    private function _check_table_counts($table, $name) {

        $ptable = \Phpcmf\Service::M()->dbprefix($table);
        if (!\Phpcmf\Service::M()->db->tableExists($ptable)) {
            return '数据表【'.$name.'/'.$ptable.'】不存在，请创建';
        }
        $counts = \Phpcmf\Service::M()->table($table)->counts();
        if ($counts > 100000) {
            return '<font color="green">数据表【'.$name.'/'.$ptable.'】数据量超过10万，会影响加载速度，建议对其进行数据优化</font>';
        }
    }

}
