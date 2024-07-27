<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 缓存更新
class Cache extends \Phpcmf\Common {

    public function index() {

        $list = [
            ['系统配置缓存', 'update_cache'],
            ['表或表字段异常时，更新数据结构', 'update_db'],
            ['附件地址未更新时，更新附件缓存', 'update_attachment'],
            ['手动清理缩略图文件（如果生成过静态页面，请慎用）', 'update_thumb'],
            ['清理全部系统日志、操作日志、邮件日志、短信日志、慢查询日志等', 'update_log'],
        ];
        $cname[] = '更新模块域名目录';
        $module_more = $module = $cname = [];
        if (dr_is_app('module')) {
            $list[] = ['手动重建内容搜索索引', 'update_search_index'];
            $cname[] = '更新模块域名目录';
            $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
            if ($module) {
                $module = dr_array_sort($module, 'share', 'asc');
                $limit = 10;
                if (dr_count($module) > $limit) {
                    $module_more = array_slice($module, $limit);
                    $module = array_slice($module, 0, $limit);
                }
            }
        }
        if (dr_is_app('ueditor') && is_file(CMSPATH.'Field/Ueditor.php')) {
            $list[] = ['生成百度编辑器到其他域名', 'update_ueditor'];
        }
        if (dr_is_app('sites')) {
            $cname[] = '更新子站目录';
        }
        if (dr_is_app('client')) {
            $cname[] = '更新终端目录';
        }
        if ($cname) {
            $list[] = [implode('、', $cname), 'update_site_config'];
        }

        \Phpcmf\Service::V()->assign([
            'list' => $list,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统更新' => ['cache/index', 'fa fa-refresh'],
                    '系统体检' => ['check/index', 'fa fa-wrench'],
                    'help' => [378],
                ]
            ),
            'module' => $module,
            'module_more' => $module_more,
        ]);
        \Phpcmf\Service::V()->display('cache.html');
    }

    public function update_index() {

        $page = intval($_GET['page']);
        $next = dr_url('cache/update_index');
        if (!$page) {
            file_put_contents(WRITEPATH.'install.lock', SYS_TIME);
            if (is_file(WRITEPATH.'update.lock')) {
                @unlink(WRITEPATH.'update.lock');
                if (is_file(WRITEPATH.'update.lock')) {
                    $this->_html_msg(0, dr_lang('%s目录请给可写入权限', IS_DEV ? WRITEPATH : 'cache'));
                }
            }
            $this->_html_msg(1, dr_lang('正在更新升级程序'), $next.'&page=1');
        }

        switch ($page) {

            case 1:

                $dir = [
                    WRITEPATH.'cloud/',
                    WRITEPATH.'watermark/',
                ];
                foreach ($dir as $path) {
                    if (!is_dir($path)) {
                        dr_mkdirs($path);
                    }
                    if (!dr_check_put_path($path)) {
                        $this->_html_msg(0, dr_lang('目录创建失败'), '', $path);
                    }
                }

                // 移动水印目录
                if (is_dir(WEBPATH.'config/watermark/')) {
                    \Phpcmf\Service::L('file')->copy_dir(WEBPATH.'config/watermark/', WEBPATH.'config/watermark/', WRITEPATH.'watermark/');
                }

                // 判断public
                if (defined('IS_VERSION') && IS_VERSION) {

                } else {
                    // 传统结构时复制目录到根目录去
                    $path = ROOTPATH.'public/';
                    if (is_dir($path)) {
                        \Phpcmf\Service::L('file')->copy_dir($path, $path, ROOTPATH);
                        dr_dir_delete($path, true);
                    }
                    if (is_dir($path)) {
                        $this->_html_msg(0, dr_lang('目录移动失败'), '', '请手动将['.$path.']下面的文件移动到网站根目录中['.ROOTPATH.']，再删除public目录');
                    }
                }

                // 移动头像目录
                $path = ROOTPATH.'api/member/';
                if (is_dir($path)) {
                    \Phpcmf\Service::L('file')->copy_dir($path, $path, SYS_UPLOAD_PATH.'member/');
                    dr_dir_delete($path, true);
                }

                $this->_html_msg(1, dr_lang('正在升级文件目录结构'), $next.'&page='.($page+1));
                break;

            case 2:

                // 判断模块表
                if (!IS_USE_MODULE
                    && \Phpcmf\Service::M()->is_table_exists('module')
                    &&  is_file(dr_get_app_dir('module').'/Config/App.php')
                ) {
                    // 表示模块表已经操作，手动安装模块
                    $rs = file_put_contents(dr_get_app_dir('module').'/install.lock', 'fix');
                    if (!$rs) {
                        $this->_html_msg(0, dr_lang('目录无法写入'), '', dr_get_app_dir('module'));
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
                    'module' => 'module',
                    SITE_ID.'_form' => 'form',
                    'module_form' => 'mform',
                    'member_notice' => 'notice',
                    'member_scorelog' => 'scorelog',
                    'member_paylog' => 'pay',
                    'member_explog' => 'explog',
                ];
                $app_name = [
                    'module' => '建站系统',
                    'form' => '表单系统',
                    'mform' => '模块内容表单',
                    'notice' => '提醒消息',
                    'pay' => '支付系统',
                    'scorelog' => '积分系统',
                    'explog' => '经验值系统',
                    'member' => '用户系统',
                ];
                foreach ($table_app as $table => $name) {
                    if (\Phpcmf\Service::M()->is_table_exists($table) && \Phpcmf\Service::M()->table($table)->counts()) {
                        $cpath = dr_get_app_dir($name);
                        if (is_dir($cpath)) {
                            file_put_contents($cpath.'/install.lock', 1);
                        } else {
                            $error[] = $app_name[$name];
                        }
                    }
                }

                // 用户系统
                if (\Phpcmf\Service::M()->is_table_exists('member_menu') && !dr_is_app('member')) {
                    $error[] = $app_name['member'];
                }

                if ($error) {
                    $this->_html_msg(0, '需要手动安装这些应用插件：'.implode('、', $error).'
<br><br><a href="http://help.xunruicms.com/1104.html" target="_blank">查看解决方案</a><br><br>将以上问题处理之后继续更新此脚本', 0);
                }

                $this->_html_msg(1, dr_lang('正在升级程序兼容性'), $next.'&page='.($page+1));
                break;

            case 3:

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

                \Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.'admin_notice` CHANGE `to_rid` `to_rid` varchar(100) NOT NULL COMMENT \'指定角色组\';');
                \Phpcmf\Service::M()->query('ALTER TABLE `'.$prefix.'admin_notice` CHANGE `to_uid` `to_uid` varchar(100) NOT NULL COMMENT \'指定管理员\';');

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
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT DEFAULT NULL');
                }

                $table = $prefix.'member_menu';
                if (\Phpcmf\Service::M()->db->tableExists($table) && !\Phpcmf\Service::M()->db->fieldExists('client', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `client` TEXT DEFAULT NULL');
                }

                $table = $prefix.'member_level';
                if (\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` CHANGE `stars` `stars` int(10) unsigned NOT NULL COMMENT \'图标\';');
                    if (!\Phpcmf\Service::M()->db->fieldExists('setting', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `setting` TEXT DEFAULT NULL');
                    }
                    if (!\Phpcmf\Service::M()->db->fieldExists('displayorder', $table)) {
                        \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `displayorder` INT(10) DEFAULT NULL COMMENT \'排序\'');
                    }
                }

                $table = $prefix.'admin_menu';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT DEFAULT NULL');
                }

                $table = $prefix.'admin';
                if (!\Phpcmf\Service::M()->db->fieldExists('history', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `history` TEXT DEFAULT NULL');
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
                $is_module = \Phpcmf\Service::M()->db->tableExists('module');
                if ($is_module) {
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

                    if ($is_module) {
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


                $this->_html_msg(1, dr_lang('正在升级数据表结构'), $next.'&page='.($page+1));
                break;

            default:

                \Phpcmf\Service::M('cache')->update_db();
                \Phpcmf\Service::M('cache')->update_cache();
                \Phpcmf\Service::M('cache')->update_data_cache();
                $this->_html_msg(1, dr_lang('更新完成'));
                break;
        }
    }

}
