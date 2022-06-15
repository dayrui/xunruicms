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
            ['手动清理缩略图文件', 'update_thumb'],
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
            $this->_html_msg(1, dr_lang('正在更新升级程序'), $next.'&page=1');
        }

        switch ($page) {

            case 1:

                $dir = [
                    WRITEPATH.'cloud/'
                ];
                foreach ($dir as $path) {
                    if (!is_dir($path)) {
                        dr_mkdirs($path);
                    }
                    if (!dr_check_put_path($path)) {
                        $this->_html_msg(0, dr_lang('目录创建失败'), '', $path);
                    }
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
                                $category = \Phpcmf\Service::L('category', 'module')->get_category('share');
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

                $table = $prefix.'member';
                if (!\Phpcmf\Service::M()->db->fieldExists('login_attr', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `login_attr` VARCHAR(100) DEFAULT NULL');
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
