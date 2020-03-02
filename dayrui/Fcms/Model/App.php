<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 程序插件管理
class App extends \Phpcmf\Model
{
    public $cfg_cache;

    // 是否是系统保留的app目录
    public function is_sys_dir($dir) {
        return in_array($dir, ['case', 'class', 'extends',
            'new', 'var', 'member', 'category', 'linkage',
            'module', 'form', 'admin', 'weixin']);
    }

    // 开始安装app
    public function install($dir) {

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/App.php')) {
            return dr_return_data(0, dr_lang('应用配置文件不存在'));
        }

        $config = require $path.'Config/App.php';

        // 写入锁定文件
        $rt = file_put_contents($path.'install.test', SYS_TIME);
        if (!$rt) {
            return dr_return_data(0, 'App/'.ucfirst($dir).'/程序目录无法写入');
        }

        // 安装前的判断
        if (is_file($path.'Config/Before.php')) {
            $rt = require $path.'Config/Before.php';
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
        }

        if (is_file($path.'install.lock')) {
            return dr_return_data(0, dr_lang('此程序已经安装'));
        }

        if (isset($config['ftype']) && $config['ftype'] == 'module') {
            // 如果是内容模块，就进入内容模块安装模式
            \Phpcmf\Service::M('module')->install($dir, $config, 1);
        } else {
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
                    $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix($siteid.'_'), $sql));
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
        \Phpcmf\Service::M('Menu')->add_app($dir);

        // 写入锁定文件
        file_put_contents($path.'install.lock', SYS_TIME);
        unlink($path.'install.test');

        return dr_return_data(1, dr_lang('安装成功'));
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
            \Phpcmf\Service::M('module')->uninstall($dir, $config, 1);
        } else {
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
                    $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix($siteid.'_'), $sql));
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


        // 删除菜单
        \Phpcmf\Service::M('Menu')->delete_app($dir);
        unlink($path.'install.lock');

        return dr_return_data(1, dr_lang('卸载成功'));
    }

    // 读取配置信息
    public function get_config($dir) {

        if (!$dir) {
            return [];
        }

        if (!isset($this->cfg_cache[$dir])) {
            $this->cfg_cache[$dir] = \Phpcmf\Service::L('Cache')->get_file($dir, 'app');
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