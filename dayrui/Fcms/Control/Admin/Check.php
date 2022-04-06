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
        '07' => '数据库表结构检测',
        '08' => '程序兼容性检测',
        '09' => '项目安全性检测',
        '10' => '数据负载优化检测',
        '11' => '域名绑定检测',
        '12' => 'HTTPS检测',
        '13' => '应用插件兼容性检测',
        '14' => '移动端检测',
        '16' => '自动任务配置检测',

    ];

    public function index() {

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
                    $this->_json(0,'系统配置不合理，post_max_size值('.$post.')必须大于upload_max_filesize值('.$file.')');
                } elseif ($file < 10) {
                    $this->_json(1,'系统环境只允许上传'.$file.'MB文件，可以设置upload_max_filesize值提升上传大小');
                } elseif ($post < 10) {
                    $this->_json(1,'系统环境要求每次发布内容不能超过'.$post.'MB（含文件），可以设置post_max_size值提升发布大小');
                }

                break;

            case '02':

                $rt = [];
                if (!function_exists('mb_substr')) {
                    $rt[] = 'PHP不支持mbstring扩展，必须开启';
                }
                if (!function_exists('imagettftext')) {
                    $rt[] = 'PHP扩展库：GD库未安装或GD库版本太低，可能无法正常显示验证码和图片缩略图';
                }
                if (!function_exists('curl_init')) {
                    $rt[] = 'PHP不支持CURL扩展，必须开启';
                }
                if (!function_exists('mb_convert_encoding')) {
                    $rt[] = 'PHP的mb函数不支持，无法使用百度关键词接口';
                }
                if (!function_exists('imagecreatetruecolor')) {
                    $rt[] = 'PHP的GD库版本太低，无法支持验证码图片';
                }
                if (!function_exists('ini_get')) {
                    $rt[] = '系统函数ini_get未启用，将无法获取到系统环境参数';
                }
                if (!function_exists('gzopen')) {
                    $rt[] = 'zlib扩展未启用，您将无法进行在线升级、无法下载应用插件等';
                }
                if (!function_exists('gzinflate')) {
                    $rt[] = '函数gzinflate未启用，您将无法进行在线升级、无法下载应用插件等';
                }
                if (!function_exists('fsockopen')) {
                    $rt[] = 'PHP不支持fsockopen，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等';
                }
                if (!function_exists('openssl_open')) {
                    $rt[] = 'PHP不支持openssl，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等';
                }
                if (!ini_get('allow_url_fopen')) {
                    $rt[] = 'allow_url_fopen未启用，远程图片无法保存、网络图片无法上传、可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等';
                }
                if (!class_exists('ZipArchive')) {
                    $rt[] = 'php_zip扩展未开启，无法使用应用市场功能';
                }
                $url = 'https://www.xunruicms.com/';
                if ($this->cmf_license['cloud']) {
                    $url = $this->cmf_license['cloud'];
                }
                if (!fopen($url, "rb")) {
                    $rt[] = 'fopen无法获取远程数据，无法使用在线下载插件和在线升级';
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
                        $rt[] = $note.'【'.(IS_DEV ? $path : dr_safe_replace_path($path)).'】';
                    }
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
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
                if (is_dir(CONFIGPATH.'language/') && !is_dir(CONFIGPATH.'language/')) {
                    \Phpcmf\Service::L('file')->copy_dir(CONFIGPATH.'language/', CONFIGPATH.'language/', ROOTPATH.'api/language/');
                }

                $rt = [];

                // 语言文件
                $lang = dr_catcher_data(LANG_PATH.'lang.js', 5);
                if ($lang && strlen($lang) < 10) {
                    $rt[] = '语言JS文件异常：'.LANG_PATH.'lang.js';
                } elseif ($lang && strpos($lang, 'finecms_datepicker_lang') === false) {
                    $rt[] = '语言JS文件异常：'.LANG_PATH.'lang.js';
                }

                // 模板文件
                if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/index.html')) {
                    $rt[] = '前端模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/home/index.html';
                }
                if (IS_USE_MEMBER) {
                    if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/index.html')) {
                        $rt[] = '用户中心模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/member/index.html';
                    } elseif (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/msg.html')) {
                        $rt[] = '用户中心模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/member/msg.html';
                    }
                }
                // 必备模板检测
                foreach (['msg.html', '404.html'] as $tt) {
                    if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/'.$tt)) {
                        $rt[] = '前端模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/home/'.$tt;
                    }
                }

                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                // 移动端模板检测
                if (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt('前端模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/home/index.html', 1);
                }

                if (IS_USE_MEMBER) {
                    if (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/index.html')) {
                        $this->halt('用户中心模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/member/index.html', 1);
                    } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/msg.html')) {
                        $this->halt('用户中心模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/member/msg.html', 1);
                    }
                }

                break;

            case '07':

                $prefix = \Phpcmf\Service::M()->prefix;

                // 增加长度
                \Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.'member` CHANGE `salt` `salt` VARCHAR(50) NOT NULL COMMENT \'随机加密码\';');

                $table = $prefix.'cron';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                }

                $table = $prefix.'site';
                if (!\Phpcmf\Service::M()->db->fieldExists('displayorder', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `displayorder` INT(10) DEFAULT NULL COMMENT \'排序\'');
                }

                $table = $prefix.'member_data';
                if (!\Phpcmf\Service::M()->db->fieldExists('is_email', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `is_email` tinyint(1) DEFAULT NULL COMMENT \'邮箱认证\'');
                }

                $table = $prefix.'member_paylog';
                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                    if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                    }
                    if (\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` DROP `username`');
                    }
                    if (\Phpcmf\Service::M()->db->fieldExists('tousername', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` DROP `tousername`');
                    }
                }

                $table = $prefix.'member_group_verify';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('price', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `price` decimal(10,2) DEFAULT NULL COMMENT \'已费用\'');
                }

                $table = $prefix.'member_scorelog';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_notice';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('mark', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `mark` VARCHAR(100) DEFAULT NULL');
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.'member_notice` CHANGE `type` `type` tinyint(2) unsigned NOT NULL COMMENT \'类型\';');
                }

                $table = $prefix.'member_explog';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_oauth';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('unionid', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `unionid` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member';
                if (!\Phpcmf\Service::M()->db->fieldExists('login_attr', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `login_attr` VARCHAR(100) DEFAULT NULL');
                }

                $table = $prefix.'member_menu';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT NOT NULL');
                }

                $table = $prefix.'member_menu';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('client', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `client` TEXT NOT NULL');
                }

                $table = $prefix.'member_level';
                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` CHANGE `stars` `stars` int(10) unsigned NOT NULL COMMENT \'图标\';');
                    if (!\Phpcmf\Service::M()->db->fieldExists('setting', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `setting` TEXT NOT NULL');
                    }
                    if (!\Phpcmf\Service::M()->db->fieldExists('displayorder', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `displayorder` INT(10) DEFAULT NULL COMMENT \'排序\'');
                    }
                }

                $table = $prefix.'admin_menu';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT NOT NULL');
                }

                $table = $prefix.'admin';
                if (!\Phpcmf\Service::M()->db->fieldExists('history', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `history` TEXT NOT NULL');
                }
                $table = $prefix.'admin_setting';
                if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query(dr_format_create_sql('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                      `name` varchar(50) NOT NULL,
                      `value` mediumtext NOT NULL,
                      PRIMARY KEY (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'系统属性参数表\';'));
                }

                if (IS_USE_MEMBER) {
                    $table = $prefix.'member_setting';
                    if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                        \Phpcmf\Service::M()->query(dr_format_create_sql('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                          `name` varchar(50) NOT NULL,
                          `value` mediumtext NOT NULL,
                          PRIMARY KEY (`name`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT=\'用户属性参数表\';'));
                    } else {
                        if (\Phpcmf\Service::M()->db->fieldExists('id', $table)) {
                            // 处理id字段
                            $data = \Phpcmf\Service::M()->db->table('member_setting')->get()->getResultArray();
                            \Phpcmf\Service::M()->query('DROP TABLE IF EXISTS `'.$table.'`;');
                            \Phpcmf\Service::M()->query(dr_format_create_sql('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                              `name` varchar(50) NOT NULL,
                              `value` mediumtext NOT NULL,
                              PRIMARY KEY (`name`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT=\'用户属性参数表\';'));
                            if ($data) {
                                foreach ($data as $t) {
                                    \Phpcmf\Service::M()->table('member_setting')->replace([
                                        'name' => $t['name'],
                                        'value' => $t['value'],
                                    ]);
                                }
                            }
                        }
                    }
                    if (!\Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth2')->get()->getRowArray()) {
                        // 权限数据
                        \Phpcmf\Service::M()->query_sql('REPLACE INTO `{dbprefix}member_setting` VALUES(\'auth2\', \'{"1":{"public":{"home":{"show":"0","is_category":"0"},"form_public":[],"share_category_public":{"show":"1","add":"1","edit":"1","code":"1","verify":"1","exp":"","score":"","money":"","day_post":"","total_post":""},"category_public":[],"mform_public":"","form":null,"share_category":null,"category":null,"mform":null}}}\')');
                    }
                }


                $table = $prefix.'admin_min_menu';
                if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query(dr_format_create_sql('CREATE TABLE IF NOT EXISTS `'.$table.'` (
                      `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                      `pid` smallint(5) unsigned NOT NULL COMMENT \'上级菜单id\',
                      `name` text NOT NULL COMMENT \'菜单语言名称\',
                      `site` text NOT NULL COMMENT \'站点归属\',
                      `uri` varchar(255) DEFAULT NULL COMMENT \'uri字符串\',
                      `url` varchar(255) DEFAULT NULL COMMENT \'外链地址\',
                      `mark` varchar(255) DEFAULT NULL COMMENT \'菜单标识\',
                      `hidden` tinyint(1) unsigned DEFAULT NULL COMMENT \'是否隐藏\',
                      `icon` varchar(255) DEFAULT NULL COMMENT \'图标标示\',
                      `displayorder` int(5) DEFAULT NULL COMMENT \'排序值\',
                      PRIMARY KEY (`id`),
                      KEY `list` (`pid`),
                      KEY `displayorder` (`displayorder`),
                      KEY `mark` (`mark`),
                      KEY `hidden` (`hidden`),
                      KEY `uri` (`uri`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT=\'后台简化菜单表\';'));
                }

                // 模块
                if (IS_USE_MODULE) {

                    $module = \Phpcmf\Service::M()->table('module')->order_by('displayorder ASC,id ASC')->getAll();

                    // 栏目模型字段修正
                    \Phpcmf\Service::M()->db->table('field')->where('relatedname', 'share-'.SITE_ID)->update(['relatedname' => 'catmodule-share']);

                    if ($module) {
                        foreach ($module as $m) {
                            if (!\Phpcmf\Service::M()->table('field')->where('relatedname', 'module')
                                ->where('relatedid', $m['id'])->where('fieldname', 'author')->counts()) {
                                \Phpcmf\Service::M()->db->table('field')->insert(array(
                                    'name' => '笔名',
                                    'fieldname' => 'author',
                                    'fieldtype' => 'Text',
                                    'relatedid' => $m['id'],
                                    'relatedname' => 'module',
                                    'isedit' => 1,
                                    'ismain' => 1,
                                    'ismember' => 1,
                                    'issystem' => 1,
                                    'issearch' => 1,
                                    'disabled' => 0,
                                    'setting' => dr_array2string(array(
                                        'is_right' => 1,
                                        'option' => array(
                                            'width' => 200, // 表单宽度
                                            'fieldtype' => 'VARCHAR', // 字段类型
                                            'fieldlength' => '255', // 字段长度
                                            'value' => '{name}'
                                        ),
                                        'validate' => array(
                                            'xss' => 1, // xss过滤
                                        )
                                    )),
                                    'displayorder' => 0,
                                ));
                            }
                        }
                    }
                }


                // 站点
                foreach ($this->site as $siteid) {
                    // 升级资料库
                    $table = $prefix.$siteid.'_block';
                    if (\Phpcmf\Service::M()->db->tableExists($table)) {
                        // 创建code字段 代码
                        if (!\Phpcmf\Service::M()->db->fieldExists('code', $table)) {
                            \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `code` VARCHAR(100) NOT NULL');
                        }
                    }
                    // 升级栏目表

                    if (IS_USE_MODULE) {
                        $table = $prefix . $siteid . '_share_category';
                        if (\Phpcmf\Service::M()->db->tableExists($table)) {
                            // 创建字段 代码
                            if (!\Phpcmf\Service::M()->db->fieldExists('disabled', $table)) {
                                \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `disabled` tinyint(1) DEFAULT  \'0\'');
                                \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `disabled` = 0');
                            }
                            \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `disabled` = 0 WHERE `disabled` IS NULL ');
                            if (!\Phpcmf\Service::M()->db->fieldExists('ismain', $table)) {
                                \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `ismain` tinyint(1) DEFAULT  \'0\'');
                                \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `ismain` = 1');
                            }
                        }
                        if ($module) {
                            foreach ($module as $m) {
                                if (!\Phpcmf\Service::M()->db->tableExists($prefix . $siteid . '_' . $m['dirname'])) {
                                    continue;
                                }
                                // 增加长度
                                $table = $prefix . $siteid . '_' . $m['dirname'];
                                if (\Phpcmf\Service::M()->db->fieldExists('inputip', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` CHANGE `inputip` `inputip` VARCHAR(100) NOT NULL COMMENT \'客户端ip信息\';');
                                }
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_recycle';
                                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                    // 创建字段 删除理由
                                    if (!\Phpcmf\Service::M()->db->fieldExists('result', $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `result` Text NOT NULL');
                                    }
                                }
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_support';
                                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                    // 创建字段 游客点赞
                                    if (!\Phpcmf\Service::M()->db->fieldExists('agent', $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `agent` VARCHAR(200) DEFAULT NULL');
                                    }
                                }
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_oppose';
                                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                    // 创建字段 游客点赞
                                    if (!\Phpcmf\Service::M()->db->fieldExists('agent', $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `agent` VARCHAR(200) DEFAULT NULL');
                                    }
                                }
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_verify';
                                if (!\Phpcmf\Service::M()->db->fieldExists('vid', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `vid` INT(10) DEFAULT NULL');
                                }
                                if (!\Phpcmf\Service::M()->db->fieldExists('islock', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `islock` tinyint(1) DEFAULT NULL');
                                }
                                // 点击时间
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_hits';
                                foreach (['day_time', 'week_time', 'month_time', 'year_time'] as $a) {
                                    if (!\Phpcmf\Service::M()->db->fieldExists($a, $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `' . $a . '` INT(10) DEFAULT NULL');
                                    }
                                }
                                $table = $prefix . $siteid . '_' . $m['dirname'] . '_category';
                                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                    if (!\Phpcmf\Service::M()->db->fieldExists('disabled', $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `disabled` tinyint(1) DEFAULT \'0\'');
                                        \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `disabled` = 0');
                                    }
                                    \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `disabled` = 0 WHERE `disabled` IS NULL ');
                                    if (!\Phpcmf\Service::M()->db->fieldExists('ismain', $table)) {
                                        \Phpcmf\Service::M()->query('ALTER TABLE `' . $table . '` ADD `ismain` tinyint(1) DEFAULT \'0\'');
                                        \Phpcmf\Service::M()->query('UPDATE `' . $table . '` SET `ismain` = 1');
                                    }
                                }
                                // 栏目模型字段修正
                                \Phpcmf\Service::M()->db->table('field')->where('relatedname', $m['dirname'] . '-' . $siteid)->update(['relatedname' => 'catmodule-' . $m['dirname']]);
                                // 无符号修正
                                //\Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.$siteid.'_'.$m['dirname'].'` CHANGE `updatetime` `updatetime` INT(10) NOT NULL COMMENT \'更新时间\'');
                                //\Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.$siteid.'_'.$m['dirname'].'` CHANGE `inputtime` `inputtime` INT(10) NOT NULL COMMENT \'更新时间\'');
                            }
                        }
                    }
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
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci COMMENT=\'邮件账户表\';');
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

                // 判断模块表
                if (!IS_USE_MODULE
                    && \Phpcmf\Service::M()->is_table_exists('module')
                    &&  is_file(dr_get_app_dir('module').'/Config/App.php')
                ) {
                    // 表示模块表已经操作，手动安装模块
                    $rs = file_put_contents(dr_get_app_dir('module').'/install.lock', 'fix');
                    if (!$rs) {
                        $this->halt('【重要】目录（'.dr_get_app_dir('module').'）无法写入', 0);
                    }
                }

                $local = dr_dir_map(APPSPATH, 1); // 搜索本地模块
                foreach ($local as $dir) {
                    if (is_file(APPSPATH.$dir.'/Config/App.php')) {
                        $file = APPSPATH.$dir.'/Controllers/Search.php';
                        if (is_file($file)) {
                            // 替换搜索控制器
                            $code = file_get_contents($file);
                            if ($code && strpos($code, '\Phpcmf\Home\Search') !== false) {
                                file_put_contents($file, str_replace(
                                    ['\Phpcmf\Home\Search', '_Module_Search'],
                                    ['\Phpcmf\Home\Module', '_Search'],
                                    $code
                                ));
                            }
                        }
                        $file = APPSPATH.$dir.'/Controllers/Recycle.php';
                        if (is_file($file)) {
                            // 替换搜索控制器
                            $code = file_get_contents($file);
                            if ($code && strpos($code, '_Admin_Recycle_Edit') === false) {
                                file_put_contents($file, str_replace(
                                    'public function index() {',
                                    ' public function edit() {
        $this->_Admin_Recycle_Edit();
    }
    public function index() {
    ',
                                    $code
                                ));
                            }
                        }
                    }
                }

                // 升级插件兼容测试
                $error = [];
                $table_app = [
                    SITE_ID.'_form' => 'form',
                    'module_form' => 'mform',
                    'member_notice' => 'notice',
                    'member_scorelog' => 'scorelog',
                    'member_paylog' => 'pay',
                    'member_explog' => 'explog',
                ];
                $app_name = [
                    'form' => '全局表单',
                    'mform' => '模块内容表单',
                    'notice' => '提醒消息',
                    'pay' => '支付系统',
                    'scorelog' => '积分系统',
                    'explog' => '经验值系统',
                    'member' => '用户系统',
                    'chtml' => '内容静态生成',
                ];
                foreach ($table_app as $table => $name) {
                    if (\Phpcmf\Service::M()->is_table_exists($table) && \Phpcmf\Service::M()->table($table)->counts() && !dr_is_app($name)) {
                        $error[] = $app_name[$name];
                    }
                }

                // 用户系统
                if (\Phpcmf\Service::M()->is_table_exists('member_menu') && !dr_is_app('member')) {
                    $error[] = $app_name['member'];
                }

                // 判断静态生成插件
                if (!dr_is_app('chtml')) {
                    $is_html = 0;
                    if (IS_USE_MODULE) {
                        $module = \Phpcmf\Service::M()->table('module')->getAll();
                        if ($module) {
                            foreach ($module as $m) {
                                $site = dr_string2array($m['site']);
                                if ($site) {
                                    foreach ($site as $t) {
                                        if ($t['html']) {
                                            $is_html = 1;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$is_html) {
                                // 共享栏目
                                $category = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category');
                                if ($category) {
                                    foreach ($category as $t) {
                                        if ($t['setting']['html']) {
                                            $is_html = 1;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ($is_html) {
                            $error[] = $app_name['chtml'];
                        }
                    }

                }

                if ($error) {
                    $this->halt('需要手动安装这些应用插件：'.implode('、', $error).'，<a href="javascript:dr_help(1104);">查看解决方案</a>', 0);
                }

                $this->_json(1,'完成');

                break;

            case '09':
				$rt = [];
                // 搜索根目录
                $local = dr_file_map(WEBPATH, 1); // 搜索根目录
                foreach ($local as $file) {
                    if (in_array(strtolower(substr(strrchr($file, '.'), 1)), ['zip', 'rar', 'sql'])) {
                        $rt[] = '文件不安全【/'.$file.'】请及时清理';
                    }
                    $str = file_get_contents(WEBPATH.$file, 0, null, 0, 9286630);
                    if ($str && strlen($str) >= 9286630) {
                        $rt[] = '存在大文件文件【/'.$file.'】请及时清理';
                    }
                }
				
				$dir = ['cache', 'config', 'dayrui', 'template'];
				foreach ($dir as $p) {
					$code = dr_catcher_data(ROOT_URL.$p.'/api.php', 5);
					if (strpos($code, 'phpcmf') !== false) {
						$rt[] = '目录['.$p.']需要设置禁止访问，<a href="javascript:dr_help(1005);">设置方法</a>';
					}
				}

                if (!dr_is_app('safe')) {
                    $rt[] = '<font color="green">安装「系统安全加固」插件可以大大提高安全等级';
                }
				
				if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }
				
                $this->_json(1,'完成');
                break;

            case '10':
                // 数据负载
                $rt = [];
                // 任务队列
                $cron = \Phpcmf\Service::M()->table('cron')->counts();
                if ($cron > 10) {
                    $rt[] = '<font color="red">【任务队列】含有大量未执行的任务，会影响加载速度，建议删除不需要的任务</font>';
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
                                    $r = $this->_check_table_counts($siteid . '_' . $m['dirname'], $m['dirname'] . '模块主表');
                                    $r && $rt[] = $r;
                                    if ($mform) {
                                        foreach ($mform as $mm) {
                                            $r = $this->_check_table_counts($siteid . '_' . $m['dirname'] . '_form_' . $mm['table'], $m['dirname'] . '模块' . $mm['name'] . '表');
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

                $this->_json(1,'正常');
                break;

            case '11':

                // 域名检测
                if (!function_exists('stream_context_create')) {
                    $this->halt('函数没有被启用：stream_context_create', 0);
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
                                $tips[] = '当前站点没有绑定手机域名，可能无法使用移动端界面';
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
                            $code = dr_catcher_data($url, 5);
                            if ($code != 'phpcmf ok') {
                                $error[] = '['.$cname.']域名绑定异常，无法访问：' . $url . '，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功，<a href="'.dr_url('site_domain/index').'">查看详情</a>';
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
                        $this->_json(0, (IS_DEV ? $t['path'] : dr_safe_replace_path($t['path'])).' 无法写入文件');
                    }
                    $code = dr_catcher_data($t['url'].'api.html', 5);
                    if ($code != 'phpcmf ok') {
                        $error[] = '['.$t['name'].']异常，无法访问：' . $t['url'] . 'api.html，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功';
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
                                $error[] = '域名【'.$t.'】被多处重复配置，可能会影响到此域名的作用域或访问异常';
                            }
                        }
                    }
                }


                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                } elseif ($tips) {
                    $this->_json(1, implode('<br>', $tips));
                } else {
                    $this->_json(1, '完成');
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
                    $this->_json(0,'系统没有开启HTTPS服务，<a href="javascript:dr_help(668);">查看解决方案</a>');
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
                        //
                    }
                }

                if (!is_file(ROOTPATH.'static/assets/js/my.js') && is_dir(ROOTPATH.'static/assets/js/')) {
                    @file_put_contents(ROOTPATH.'static/assets/js/my.js', '');
                }

                $this->_json(1, '完成');

                break;

            case '14':
                // 移动端检测

                $error = [];

                // 开起自动识别，
                if ($this->site_info[SITE_ID]['SITE_AUTO']) {
                    // 又开启了首页静态
                    if ($this->site_info[SITE_ID]['SITE_INDEX_HTML']) {
                        $error[] = '当前站点已经开启[首页静态]模式，将无法实现移动端自动跳转功能，<a href="javascript:dr_help(664);">查看解决方案</a>';
                    }
                    $category = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category');
                    if ($category) {
                        foreach ($category as $t) {
                            if ($t['setting']['html']) {
                                $error[] = '当前站点的栏目已经开启[静态]模式，将无法实现移动端自动跳转功能，<a href="javascript:dr_help(664);">查看解决方案</a>';
                                break;
                            }
                        }
                    }
                }

                $config = $this->get_cache('site', SITE_ID, 'mobile');
                if (!$this->site_info[SITE_ID]['SITE_IS_MOBILE'] && $config['tohtml']) {
                    $error[] = '当前站点没有绑定移动端域名，将无法实现移动端静态页面功能，<a href="javascript:dr_help(506);">查看解决方案</a>';
                }

                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                }

                // 单独域名判断
                if (!$this->site_info[SITE_ID]['SITE_IS_MOBILE']) {
                    $this->_json(1,'当前项目没有绑定移动端域名');
                }

                $this->_json(1, '完成');
                break;

            case '15':
                // 服务器环境
                if (is_file(ROOTPATH.'test.php')) {
                    $error[] = '当项目正式上线后，根目录的test.php建议删除';
                }
                if (IS_DEV) {
                    $error[] = '当项目正式上线后，根目录的index.php中的开发者默认是参数，建议关闭';
                }

                if ($error) {
                    $this->_json(0, implode('<br>', $error));
                }

                $this->_json(1, '完成');
                break;

            case '16':
                // 自动任务检测
                if (is_file(WRITEPATH.'config/run_time.php')) {
                    $time = file_get_contents(WRITEPATH.'config/run_time.php');
                    $this->_json(1, '最近执行时间：'.$time);
                }

                $this->_json(0, '没有配置自动任务功能，无法自动清理缓存和更新缓存，<a href="javascript:dr_help(353);">查看解决方案</a>');
                break;

            case '99':

                break;

        }

        $this->_json(1,'完成');
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
            return '数据表【'.$name.'/'.$ptable.'】不存在，请创建';
        }
        $counts = \Phpcmf\Service::M()->table($table)->counts();
        if ($counts > 100000) {
            return '<font color="green">数据表【'.$name.'/'.$ptable.'】数据量超过10万，会影响加载速度，建议对其进行数据优化</font>';
        }
    }

}
