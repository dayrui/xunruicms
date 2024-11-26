<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Check extends \Phpcmf\Common
{

    private $_list = [

        '01' => '文件上传检测',
        '02' => 'PHP环境检测',
        '03' => '目录权限检测',
        '15' => '服务器环境检测',
        '04' => '后台入口名称检测',
        '05' => '数据库权限检测',
        '06' => '模板完整性检测',
        //'07' => '数据库表结构检测',
        //'08' => '程序兼容性检测',
        '09' => '项目安全性检测',
        '10' => '数据负载优化检测',
        '12' => 'HTTPS检测',
        '13' => '应用插件兼容性检测',
        '16' => '自动任务配置检测',

    ];

    public function index() {

        if (IS_USE_MODULE) {
            $this->_list['14'] = '移动端检测';
            $this->_list['11'] = '移动端检测';
        }

        if (is_file(WRITEPATH.'install.info')) {
            unlink(WRITEPATH.'install.info');
            unlink(WRITEPATH.'install.error');
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统体检' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                    '系统更新' => ['cache/index', 'fa fa-refresh'],
                    'PHP环境' => [\Phpcmf\Service::L('Router')->class.'/php_index', 'fa fa-code'],
                    'SERVER变量' => [\Phpcmf\Service::L('Router')->class.'/server_index', 'fa fa-cog'],
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

                $post = intval(ini_get("post_max_size"));
                $file = intval(ini_get("upload_max_filesize"));

                if ($file > $post) {
                    $this->_json(0,dr_lang('系统配置不合理，post_max_size值(%s)必须大于upload_max_filesize值(%s)', $post, $file));
                } elseif ($file < 10) {
                    $this->_json(1,dr_lang('系统环境只允许上传%sMB文件，可以设置upload_max_filesize值提升上传大小', $file));
                } elseif ($post < 10) {
                    $this->_json(1,dr_lang('系统环境要求每次发布内容不能超过%sMB（含文件），可以设置post_max_size值提升发布大小', $post));
                }

                break;

            case '02':

                $rt = [];
                if (!function_exists('mb_substr')) {
                    $rt[] = dr_lang('PHP不支持mbstring扩展，必须开启');
                }
                if (!function_exists('imagettftext')) {
                    $rt[] = dr_lang('PHP扩展库：GD库未安装或GD库版本太低，可能无法正常显示验证码和图片缩略图');
                }
                if (!function_exists('curl_init')) {
                    $rt[] = dr_lang('PHP不支持CURL扩展，必须开启');
                }
                if (!function_exists('mb_convert_encoding')) {
                    $rt[] = dr_lang('PHP的mb函数不支持，无法使用百度关键词接口');
                }
                if (!function_exists('imagecreatetruecolor')) {
                    $rt[] = dr_lang('PHP的GD库版本太低，无法支持验证码图片');
                }
                if (!function_exists('ini_get')) {
                    $rt[] = dr_lang('系统函数ini_get未启用，将无法获取到系统环境参数');
                }
                if (!function_exists('gzopen')) {
                    $rt[] = dr_lang('zlib扩展未启用，您将无法进行在线升级、无法下载应用插件等');
                }
                if (!function_exists('gzinflate')) {
                    $rt[] = dr_lang('函数gzinflate未启用，您将无法进行在线升级、无法下载应用插件等');
                }
                if (!function_exists('fsockopen')) {
                    $rt[] = dr_lang('PHP不支持fsockopen，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
                }
                if (!function_exists('openssl_open')) {
                    $rt[] = dr_lang('PHP不支持openssl，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
                }
                if (!ini_get('allow_url_fopen')) {
                    $rt[] = dr_lang('allow_url_fopen未启用，远程图片无法保存、网络图片无法上传、可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
                }
                if (!class_exists('ZipArchive')) {
                    $rt[] = dr_lang('php_zip扩展未开启，无法使用应用市场功能');
                }
                $url = 'https://www.xunruicms.com/';
                if ($this->cmf_license['cloud']) {
                    $url = $this->cmf_license['cloud'];
                }
                if (!fopen($url, "rb")) {
                    $rt[] = dr_lang('fopen无法获取远程数据，无法使用在线下载插件和在线升级');
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                break;

            case '03':

                list($thumb_path) = dr_thumb_path();
                list($avatar_path) = dr_avatar_path();

                $rt = [];
                $dir = [
                    WRITEPATH => '无法生成系统缓存文件',
                    $avatar_path => '无法上传头像',
                    WRITEPATH.'cloud/' => '无法下载应用插件',
                    WRITEPATH.'data/' => '无法生成系统配置文件，会导致系统配置无效',
                    WRITEPATH.'file/' => '无法生成系统缓存文件，会导致系统无法运行',
                    $thumb_path => '无法生成缩略图缓存文件',
                    SYS_UPLOAD_PATH => '无法上传附件',
                    APPSPATH => '无法创建模块、创建表单、下载应用插件',
                    TPLPATH => '无法创建模块模板和应用插件模板',
                ];

                foreach ($dir as $path => $note) {
                    if (!is_dir($path)) {
                       dr_mkdirs($path);
                    }
                    if (!dr_check_put_path($path)) {
                        $rt[] = dr_lang($note).'【'.(IS_DEV ? $path : dr_safe_replace_path($path)).'】';
                    }
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                break;

            case '04':
                if (SELF == 'admin.php') {
                    $this->halt(dr_lang('为了系统安全，请修改根目录admin.php的文件名'), 0);
                }
                break;

            case '05':

                $field = \Phpcmf\Service::M('table')->show_full_colunms(\Phpcmf\Service::M()->dbprefix('admin'));
                if (!$field) {
                    $this->halt(dr_lang("无法通获取到数据表字段结构，需要为Mysql账号开启SHOW FULL COLUMNS权限"), 0);
                }

                break;

            case '06':

                // 语言文件兼容处理
                if (is_dir(CONFIGPATH.'language/') && !is_dir(CONFIGPATH.'language/')) {
                    \Phpcmf\Service::L('file')->copy_dir(CONFIGPATH.'language/', CONFIGPATH.'language/', ROOTPATH.'api/language/');
                }

                $rt = [];

                // 语言文件
                $lang = dr_catcher_data(LANG_PATH.'lang.js', 5, false);
                if ($lang && strlen($lang) < 10) {
                    $rt[] = dr_lang('语言JS文件异常：%s', LANG_PATH.'lang.js');
                } elseif ($lang && strpos($lang, 'finecms_datepicker_lang') === false) {
                    $rt[] = dr_lang('语言JS文件异常：%s', LANG_PATH.'lang.js');
                }

                // 模板文件
                if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/index.html')) {
                    $rt[] = dr_lang('前端模板【电脑版】不存在：%s', 'TPLPATH/pc/'.SITE_TEMPLATE.'/home/index.html');
                }
                if (IS_USE_MEMBER) {
                    if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/index.html')) {
                        $rt[] = dr_lang('用户中心模板【电脑版】不存在：%s', 'TPLPATH/pc/'.SITE_TEMPLATE.'/member/index.html');
                    } elseif (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/msg.html')) {
                        $rt[] = dr_lang('用户中心模板【电脑版】不存在：%s', 'TPLPATH/pc/'.SITE_TEMPLATE.'/member/msg.html');
                    }
                }
                // 必备模板检测
                foreach (['msg.html', '404.html'] as $tt) {
                    if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/'.$tt)) {
                        $rt[] = dr_lang('前端模板【电脑版】不存在：%s', 'TPLPATH/pc/'.SITE_TEMPLATE.'/home/'.$tt);
                    }
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                // 移动端模板检测
                if (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt(dr_lang('前端模板【手机版】不存在：%s', 'TPLPATH/mobile/'.SITE_TEMPLATE.'/home/index.html'), 1);
                }

                if (IS_USE_MEMBER) {
                    if (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/index.html')) {
                        $this->halt(dr_lang('用户中心模板【手机版】不存在：%s', 'TPLPATH/mobile/'.SITE_TEMPLATE.'/member/index.html'), 1);
                    } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/msg.html')) {
                        $this->halt(dr_lang('用户中心模板【手机版】不存在：%s', 'TPLPATH/mobile/'.SITE_TEMPLATE.'/member/msg.html'), 1);
                    }
                }

                break;

            case '07':


                break;

            case '08':
                // 程序兼容性

                break;

            case '09':
				$rt = [];
                // 搜索根目录
                $local = dr_file_map(WEBPATH, 1); // 搜索根目录
                foreach ($local as $file) {
                    if (in_array(strtolower(substr(strrchr($file, '.'), 1)), ['zip', 'rar', 'sql'])) {
                        $rt[] = dr_lang('文件不安全【%s】请及时清理', '/'.$file.'');
                    }
                    $str = file_get_contents(WEBPATH.$file, 0, null, 0, 9286630);
                    if ($str && strlen($str) >= 9286630) {
                        $rt[] = dr_lang('存在大文件文件【%s】请及时清理', '/'.$file.'');
                    }
                }

				if (is_file(WEBPATH.'cache/api.php')) {
					$code = dr_catcher_data(SITE_URL.'cache/api.php/test', 5, false);
					if (strpos($code, 'phpcmf') !== false) {
						$rt[] = '<a href="javascript:dr_help(1005);">'.dr_lang('目录[cache]需要设置禁止访问');
					}
				}

                $code = file_get_contents(WEBPATH.'index.php');
                if ($code && substr_count($code, '<?php') > 1) {
                    $rt[] = dr_lang('首页入口文件index.php疑似被篡改');
                }

                if (function_exists('ini_get')) {
                    $pfile = ini_get('auto_prepend_file');
                    if ($pfile) {
                        $rt[] = '<font color="#ff7f50">'.dr_lang('php.ini中auto_prepend_file参数疑似可疑代码：%s', dr_strcut($pfile, 20));
                    }
                    $afile = ini_get('auto_append_file');
                    if ($afile) {
                        $rt[] = '<font color="#ff7f50">'.dr_lang('php.ini中auto_append_file参数疑似可疑代码：%s', dr_strcut($afile, 20));
                    }
                }

                if (!dr_is_app('safe')) {
                    $rt[] = '<font color="green">'.dr_lang('安装「系统安全加固」插件可以大大提高安全等级');
                }
				
				if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }
				
                $this->_json(1,dr_lang('完成'));
                break;

            case '10':
                // 数据负载
                $rt = [];
                // 任务队列
                $cron = \Phpcmf\Service::M()->table('cron')->counts();
                if ($cron > 10) {
                    $rt[] = '<font color="red">'.dr_lang('【任务队列】含有大量未执行的任务，会影响加载速度，建议删除不需要的任务').'</font>';
                }
                // 模块数据检测
                if (IS_USE_MODULE) {
                    $module = \Phpcmf\Service::M()->table('module')->getAll();
                    if ($module) {
                        foreach ($module as $m) {
                            $site = dr_string2array($m['site']);
                            $mform = \Phpcmf\Service::M()->is_table_exists('module_form') ? \Phpcmf\Service::M()->table('module_form')->where('module', $m['dirname'])->getAll() : [];
                            foreach ($this->site_info as $siteid => $s) {
                                if (isset($site[$siteid]) && $site[$siteid]) {
                                    $r = $this->_check_table_counts($siteid . '_' . $m['dirname'], $m['dirname'] . dr_lang('模块主表'));
                                    $r && $rt[] = $r;
                                    if ($mform) {
                                        foreach ($mform as $mm) {
                                            $r = $this->_check_table_counts(
                                                $siteid . '_' . $m['dirname'] . '_form_' . $mm['table'],
                                                $m['dirname'] . dr_lang('模块') . $mm['name'] . dr_lang('表')
                                            );
                                            $r && $rt[] = $r;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                $this->_json(1,dr_lang('正常'));
                break;

            case '11':

                // 域名检测
                if (!function_exists('stream_context_create')) {
                    $this->halt(dr_lang('函数没有被启用：%s', 'stream_context_create'), 0);
                }

                $error = $tips = [];
                list($a, $data) = \Phpcmf\Service::M('Site')->domain();
                if ($data) {
                    foreach ($data as $name => $domain) {
                        $url = '';
                        $cname = '';
                        if ($name == 'mobile_domain') {
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            } else {
                                $tips[] = dr_lang('当前站点没有绑定手机域名，可能无法使用移动端界面');
                            }
                            $cname = '移动端';
                        } elseif (strpos($name, 'module_') === 0) {
                            // 模块
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                            $cname = '模块';
                        } elseif (strpos($name, 'client_') === 0) {
                            // 终端
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                            $cname = '终端';
                        }

                        if ($url && $cname) {
                            $code = dr_catcher_data($url, 5, false);
                            if ($code != 'phpcmf ok') {
                                $error[] = dr_lang('[%s]域名绑定异常，无法访问：%s，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功', dr_lang($cname), $url);
                            }
                        }
                    }
                }

                // 验证附件域名
                list($a, $b) = dr_thumb_path();
                list($c, $d) = dr_avatar_path();
                $domain = [
                    ['name' => dr_lang('附件域名'), 'path' => SYS_UPLOAD_PATH, 'url' => SYS_UPLOAD_URL],
                    ['name' => dr_lang('缩略图域名'), 'path' => $a, 'url' => $b],
                    ['name' => dr_lang('头像域名'), 'path' => $c, 'url' => $d],
                ];
                foreach ($domain as $t) {
                    if (!file_put_contents($t['path'].'api.html', 'phpcmf ok')) {
                        $this->_json(0, (IS_DEV ? $t['path'] : dr_safe_replace_path($t['path'])).' '.dr_lang('无法写入文件'));
                    }
                    $code = dr_catcher_data($t['url'].'api.html', 5, false);
                    if ($code != 'phpcmf ok') {
                        $error[] = dr_lang('[%s]异常，无法访问：%s，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功', $t['name'], $t['url'].'api.html');
                    }
                }

                // 重复验证
                if (is_file(WRITEPATH.'config/domain_sso.php')) {
                    $domains = require WRITEPATH.'config/domain_sso.php';
                    if ($domains) {
                        // 获取去掉重复数据的数组
                        $unique_arr = array_unique ( $domains );
                        // 获取重复数据的数组
                        $repeat_arr = array_diff_assoc ( $domains, $unique_arr );
                        if ($repeat_arr) {
                            foreach ($repeat_arr as $t) {
                                $error[] = dr_lang('域名【%s】被多处重复配置，可能会影响到此域名的作用域或访问异常', $t);
                            }
                        }
                    }
                }


                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                } elseif ($tips) {
                    $this->_json(1, implode('<br>', $tips));
                } else {
                    $this->_json(1, dr_lang(dr_lang('完成')));
                }

                break;

            case '12':
                // https
                if (SYS_HTTPS) {
                    if (strpos(FC_NOW_URL, 'https://') !== false) {
                        $this->_json(1,dr_lang('正常'));
                    } else {
                        $this->_json(0,'<a href="javascript:dr_help(751);">'.dr_lang('服务器无法识别HTTPS证书，查看解决方案').'</a>');
                    }
                } else {
                    $this->_json(0,'<a href="javascript:dr_help(668);">'.dr_lang('系统没有开启HTTPS服务，查看解决方案').'</a>');
                }

                break;

            case '13':
                // 应用插件
                $func = [];
                $local = \Phpcmf\Service::Apps();
                $custom = file_get_contents(CONFIGPATH.'custom.php');
                foreach ($local as $dir => $path) {
                    if (is_file($path.'Config/App.php')) {
                        // 变量重定义
                        if (is_file($path.'Config/Init.php')) {
                            $code = file_get_contents($path.'Config/Init.php');
                            if (preg_match_all("/\s+function (.+)\(/", $code, $arr)) {
                                foreach ($arr[1] as $a) {
                                    $name = trim($a);
                                    if (strpos($name, "'") !== false) {
                                        continue;
                                    }
                                    if (isset($func[$name]) && $func[$name]) {
                                        $this->_json(0,dr_lang('应用[%s]中的函数[%s]存在于%s之中，不能被重复定义', $dir, $name, $func[$name]));
                                    }
                                    $func[$name] = $dir;
                                    if (function_exists($name)) {
                                        if (preg_match("/\s+function ".$name."\(/", $custom)) {
                                            // 存在于自定义函数库中
                                        } else {
                                            $this->_json(0, dr_lang('应用[%s]中的函数[%s]是系统函数，不能定义', $dir, $name));
                                        }
                                    }
                                }
                            }
                        }
                        //
                    }
                }

                if (!is_file(ROOTPATH.'static/assets/js/my.js') && is_dir(ROOTPATH.'static/assets/js/')) {
                    @file_put_contents(ROOTPATH.'static/assets/js/my.js', '');
                }

                $this->_json(1, dr_lang(dr_lang('完成')));

                break;

            case '14':
                // 移动端检测

                $error = [];

                // 开起自动识别，又开启了首页静态
                if ($this->site_info[SITE_ID]['SITE_AUTO'] && $this->site_info[SITE_ID]['SITE_INDEX_HTML']) {
                    $error[] = '<a href="javascript:dr_help(664);">'.dr_lang('当前站点已经开启[首页静态]模式，将无法实现移动端自动跳转功能，查看解决方案').'</a>';
                }

                $config = $this->get_cache('site', SITE_ID, 'mobile');
                if (!$this->site_info[SITE_ID]['SITE_IS_MOBILE'] && $config && $config['tohtml']) {
                    $error[] = '<a href="javascript:dr_help(506);">'.dr_lang('当前站点没有绑定移动端域名，将无法实现移动端静态页面功能，查看解决方案').'</a>';
                }

                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                }

                // 单独域名判断
                if (!$this->site_info[SITE_ID]['SITE_IS_MOBILE']) {
                    $this->_json(1,dr_lang('当前项目没有绑定移动端域名'));
                }

                $this->_json(1, dr_lang(dr_lang('完成')));
                break;

            case '15':
                // 服务器环境
                if (is_file(ROOTPATH.'test.php')) {
                    $error[] = dr_lang('当项目正式上线后，根目录的test.php建议删除');
                }
                if (IS_DEV) {
                    $error[] = dr_lang('当项目正式上线后，根目录的index.php中的开发者默认是参数，建议关闭');
                }

                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                }

                $this->_json(1, dr_lang(dr_lang('完成')));
                break;

            case '16':
                // 自动任务检测
                if (is_file(WRITEPATH.'config/run_time.php')) {
                    $time = file_get_contents(WRITEPATH.'config/run_time.php');
                    $this->_json(1, dr_lang('最近执行时间：%s', $time));
                }

                $this->_json(0, '<a href="javascript:dr_help(353);">'.dr_lang('没有配置自动任务功能，无法自动清理缓存和更新缓存，查看解决方案').'</a>');
                break;

            case '99':

                break;

        }

        $this->_json(1, dr_lang(dr_lang('完成')));
    }

    public function php_index() {
        phpinfo();
    }

    public function server_index() {
        echo '<pre style="background: #f1f5f8;padding: 10px">';
        print_r($_SERVER);
    }

    private function halt($msg, $code) {
        $this->_json($code, $msg);
    }

    private function _check_table_counts($table, $name) {

        $ptable = \Phpcmf\Service::M()->dbprefix($table);
        if (!\Phpcmf\Service::M()->db->tableExists($ptable)) {
            return dr_lang('数据表【%s】不存在，请创建', $name.'/'.$ptable);
        }

        $counts = \Phpcmf\Service::M()->table($table)->counts();
        if ($counts > 1000000) {
            return '<font color="green">'.dr_lang('数据表【%s】数据量超过100万，会影响加载速度，建议对其进行数据优化', $name.'/'.$ptable).'</font>';
        }
    }

}
