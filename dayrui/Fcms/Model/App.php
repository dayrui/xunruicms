<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 程序插件管理
class App extends \Phpcmf\Model {

    public $cfg_cache;

    // 是否是系统保留的app目录
    public function is_sys_dir($dir) {
        return in_array($dir, ['case', 'class', 'extends', 'site',
            'new', 'var', 'member', 'category', 'linkage', 'api', 'content',
            'module', 'form', 'all', 'admin', 'weixin']);
    }

    // 安装模板到站点
    public function install_tpl($dir, $cloud_id = 0) {

        \Phpcmf\Service::M('Site')->set_theme($dir, SITE_ID);
        \Phpcmf\Service::M('Site')->set_template($dir, SITE_ID);

        // 运行安装脚本
        if (is_file(WRITEPATH.'cloud/run-'.$cloud_id.'.php')) {
            require WRITEPATH.'cloud/run-'.$cloud_id.'.php';
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
        }

        \Phpcmf\Service::M('cache')->sync_cache('');
    }

    // 开始安装app
    public function install($dir, $type = 0) {

        if (!$dir) {
            return dr_return_data(0, dr_lang('应用参数不存在'));
        }

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/App.php')) {
            return dr_return_data(0, dr_lang('应用配置文件不存在'));
        }

        if ($dir == 'module' && \Phpcmf\Service::M()->is_table_exists('module')) {
            // 表示module表已经操作，防止误安装
            $rs = file_put_contents($path.'install.lock', 'fix');
            if (!$rs) {
                return dr_return_data(0, 'App/'.ucfirst($dir).'/程序目录无法写入');
            }
            return dr_return_data(0, dr_lang('此程序已经安装'));
        }

        $config = require $path.'Config/App.php';

        // 写入锁定文件
        $rt = file_put_contents($path.'install.test', SYS_TIME);
        if (!$rt) {
            return dr_return_data(0, 'App/'.ucfirst($dir).'/程序目录无法写入');
        }

        if (is_file($path.'install.lock')) {
            return dr_return_data(0, dr_lang('此程序已经安装'));
        }

        if (isset($config['ftype']) && $config['ftype'] == 'module') {
            // 如果是内容模块，就进入内容模块安装模式
            if (!IS_USE_MODULE) {
                return dr_return_data(0, dr_lang('没有安装<建站系统>插件'));
            }
            $config['share'] = $type ? 0 : 1;
            $rt = \Phpcmf\Service::M('module', 'module')->install($dir, $config, 1);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
        } else {
            // 安装前的判断
            if (is_file($path.'Config/Before.php')) {
                $rt = require $path.'Config/Before.php';
                if (!$rt['code']) {
                    return dr_return_data(0, $rt['msg']);
                }
            }
            // 执行sql语句，主站的才执行
            if (SITE_ID == 1 && is_file($path.'Config/Install.sql')) {
                $rt = $this->query_all(file_get_contents($path.'/Config/Install.sql'));
                if ($rt) {
                    return dr_return_data(0, $rt);
                }
            }
            // 执行各个站点sql语句
            if (is_file($path.'Config/Install_site.sql')) {
                $sql = file_get_contents($path.'Config/Install_site.sql');
                foreach ($this->site as $siteid) {
                    $rt = $this->query_all(str_replace(['{dbprefix}', '{siteid}'],  [$this->dbprefix($siteid.'_'), $siteid], $sql));
                    if ($rt) {
                        return dr_return_data(0, $rt);
                    }
                }
            }
            // 执行模块自己的安装程序
            if (is_file($path.'Config/Install.php')) {
                require $path.'Config/Install.php';
            }
        }

        // 创建菜单
        $first_uri = \Phpcmf\Service::M('Menu')->add_app($dir);

        // 写入锁定文件
        file_put_contents($path.'install.lock', SYS_TIME);
        file_put_contents(WRITEPATH.'install.lock', SYS_TIME);
        unlink($path.'install.test');

        if (isset($config['uri']) && $config['uri']) {
            $url = dr_url($config['uri']);
        } elseif ($first_uri) {
            $url = dr_url($first_uri);
        } elseif (isset($config['ftype']) && $config['ftype'] == 'module') {
            $url = dr_url('module/module/index');
        } else {
            $url = dr_url('cloud/local');
        }

        if (defined('IS_VERSION') && IS_VERSION) {
            // 存在public时，执行
        } elseif (is_dir(WEBPATH.'public/')) {
            //将public合并到根目录
            \Phpcmf\Service::L('file')->copy_dir(WEBPATH.'public/', WEBPATH.'public/', WEBPATH);
            dr_dir_delete(WEBPATH.'public/', true);
        }

        return dr_return_data(1, dr_lang('安装成功'), [
            'url' => $url
        ]);
    }

    // 卸载
    public function uninstall($dir) {

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/App.php')) {
            return dr_return_data(0, dr_lang('应用配置文件不存在'));
        }

        $config = require $path.'Config/App.php';
        if (isset($config['ftype']) && $config['ftype'] == 'module') {
            // 如果是内容模块，就进入内容模块安装模式
            if (!IS_USE_MODULE) {
                return dr_return_data(0, dr_lang('没有安装<建站系统>插件'));
            }
            \Phpcmf\Service::M('module')->uninstall($dir, $config, 1);
        } else {
            // 删除菜单
            \Phpcmf\Service::M('Menu')->delete_app($dir);

            // 执行sql语句
            if (is_file($path.'Config/Uninstall.sql')) {
                $rt = $this->query_all(file_get_contents($path.'Config/Uninstall.sql'));
                if ($rt) {
                    return dr_return_data(0, $rt);
                }
            }

            // 执行站点sql语句
            if (is_file($path.'Config/Uninstall_site.sql')) {
                $sql = file_get_contents($path.'Config/Uninstall_site.sql');
                foreach ($this->site as $siteid) {
                    $rt = $this->query_all(str_replace(['{dbprefix}', '{siteid}'],  [$this->dbprefix($siteid.'_'), $siteid], $sql));
                    if ($rt) {
                        return dr_return_data(0, $rt);
                    }
                }
            }

            // 执行自己的卸载程序
            if (is_file($path.'Config/Uninstall.php')) {
                require $path.'Config/Uninstall.php';
            }
        }

        @unlink($path.'install.lock');

        if (is_file($path.'install.lock')) {
            return dr_return_data(0, dr_lang('目录%s权限写入', $path));
        }

        return dr_return_data(1, dr_lang('卸载成功'));
    }

    // 安装自定义table控制器表
    public function install_table($file) {

        if (!is_file($file)) {
            $file = WEBPATH.$file;
            if (!is_file($file)) {
                return dr_return_data(0, dr_lang('配置文件不存在'));
            }
        }

        $data = dr_string2array(file_get_contents($file));
        if (!$data) {
            return dr_return_data(0, dr_lang('配置文件格式错误'));
        } elseif (!$data['table']) {
            return dr_return_data(0, dr_lang('配置文件格式错误，缺少table值'));
        } elseif (!$data['config']) {
            return dr_return_data(0, dr_lang('配置文件格式错误，缺少config值'));
        } elseif (!$data['table_sql']) {
            return dr_return_data(0, dr_lang('配置文件格式错误，缺少table_sql值'));
        }

        $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix(''), $data['table_sql']));
        if ($rt) {
            return dr_return_data(0, $rt);
        }

        $rname = 'table-'.$data['table'];
        foreach ($data['field'] as $field) {
            if ($this->db->table('field')
                ->where('fieldname', $field['fieldname'])
                ->where('relatedid', 0)
                ->where('relatedname', $rname)->countAllResults()) {
                continue;
            }
            $this->db->table('field')->insert(array(
                'name' => (string)($field['name'] ? $field['name'] : $field['textname']),
                'ismain' => 1,
                'setting' => dr_array2string($field['setting']),
                'issystem' => isset($field['issystem']) ? (int)$field['issystem'] : 1,
                'ismember' => isset($field['ismember']) ? (int)$field['ismember'] : 1,
                'disabled' => isset($field['disabled']) ? (int)$field['disabled'] : 0,
                'fieldname' => $field['fieldname'],
                'fieldtype' => $field['fieldtype'],
                'relatedid' => 0,
                'relatedname' => $rname,
                'displayorder' => (int)$field['displayorder'],
            ));
        }

        \Phpcmf\Service::L('cache')->set_file('table-config-'.$data['table'], $data['config'], 'table');

        return dr_return_data(1, dr_lang('导入成功'));
    }

    // 读取配置信息
    public function get_config($dir) {

        if (!$dir) {
            return [];
        }

        if (!isset($this->cfg_cache[$dir])) {
            $this->cfg_cache[$dir] = \Phpcmf\Service::L('cache')->get_file($dir, 'app');
        }

        return $this->cfg_cache[$dir];
    }

    // 存储配置缓存
    public function save_config($dir, $data) {

        if (!$dir) {
            return 0;
        }

        \Phpcmf\Service::L('Cache')->set_file($dir, $data, 'app');
    }

}