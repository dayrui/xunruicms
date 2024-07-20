<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 系统缓存
class Cache extends \Phpcmf\Model {

    protected $site_cache;
    protected $module_cache;
    protected $is_sync_cache;

    // 清理缩略图
    public function update_thumb() {
        list($cache_path) = dr_thumb_path();
        if (strpos(WEBPATH, $cache_path) !== false || is_file($cache_path.'index.php')) {
            // 防止误删除
            \Phpcmf\Service::C()->_json(0, dr_lang('缩略图目录异常，请手动清理：%s', $cache_path));
        }
        dr_dir_delete($cache_path);
        dr_mkdirs($cache_path);
        \Phpcmf\Service::C()->_json(1, dr_lang('清理完成'), 1);
    }


    // 清理日志文件
    public function update_log() {

        foreach ([
            WRITEPATH.'log/',
            WRITEPATH.'debugbar/',
            WRITEPATH.'debuglog/',
            WRITEPATH.'error/',
            WRITEPATH.'database/Sql/',
                 ] as $t) {
            if (is_dir($t)) {
                dr_dir_delete($t);
                dr_mkdirs($t);
            }
        }
        foreach ([
                     WRITEPATH.'email_log.txt',
                     WRITEPATH.'sms_log.txt',
                 ] as $t) {
            if (is_file($t)) {
                unlink($t);
            }
        }
        \Phpcmf\Service::C()->_json(1, dr_lang('清理完成'), 1);
    }

    // 更新附件缓存
    public function update_attachment() {

        $page = intval($_GET['page']);
        if (!$page) {
            dr_dir_delete(WRITEPATH.'attach');
            dr_mkdirs(WRITEPATH.'attach');
            /*不清理缩略图文件是因为静态页面会导致缩略图404的悲剧
            list($cache_path) = dr_thumb_path();
            dr_dir_delete($cache_path);
            dr_mkdirs($cache_path);*/
            \Phpcmf\Service::C()->_json(1, dr_lang('正在检查附件'), 1);
        }

        $total = $this->table('attachment')->counts();
        if (!$total) {
            \Phpcmf\Service::C()->_json(1, dr_lang('无可用附件更新'), 0);
        }

        $psize = 300;
        $tpage = ceil($total/$psize);
        $result = $this->db->table('attachment')->orderBy('id ASC')->limit($psize, $psize * ($page - 1))->get()->getResultArray();
        if ($result) {
            foreach ($result as $t) {
                \Phpcmf\Service::C()->get_attachment($t['id']);
            }
        }

        if ($page > $tpage) {
            \Phpcmf\Service::C()->_json(1, dr_lang('已更新%s个附件', $total), 0);
        }

        \Phpcmf\Service::C()->_json(1, dr_lang('正在更新中（%s/%s）', $page, $tpage), $page + 1);
    }

    // 同步更新缓存
    // \Phpcmf\Service::M('cache')->sync_cache(); $is_site 表示是否作为项目来更新缓存
    public function sync_cache($name = '', $namepspace = '', $is_site_param = 0) {

        \Phpcmf\Hooks::trigger('update_cache');

        if (!$this->is_sync_cache) {
            if (dr_is_use_module()) {
                $this->site_cache = $this->table('site')->where('disabled', 0)->getAll();
                $this->module_cache = $this->table('module')->order_by('displayorder ASC,id ASC')->getAll();
                \Phpcmf\Service::M('site', 'module')->cache(0, $this->site_cache, $this->module_cache);
            }
        }

        if ($name) {
            if (!$is_site_param) {
                // 普通缓存执行
                \Phpcmf\Service::M($name, $namepspace)->cache();
            } else {
                // 传入项目参数
                \Phpcmf\Service::M($name, $namepspace)->cache(SITE_ID);
            }
        }

        \Phpcmf\Service::M('table')->cache(SITE_ID, $this->module_cache);
        if ($this->module_cache) {
            \Phpcmf\Service::M('module')->cache(SITE_ID, $this->module_cache);
        }

        \Phpcmf\Service::M('menu')->cache();

        if (!$this->is_sync_cache) {
            $this->is_sync_cache = 1;
        }

        $this->update_data_cache();
    }

    // 更新数据结构
    public function update_db() {
        // 执行插件自己的更新程序
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock')
                && is_file($path.'Config/Update.php')) {
               require $path.'Config/Update.php';
            }
        }
    }

    // 更新全部项目缓存
    public function update_site_cache() {

        \Phpcmf\Hooks::trigger('update_cache');

        if (dr_is_use_module()) {
            if (is_file(IS_USE_MODULE.'Models/Site.php')) {
                \Phpcmf\Service::M('site', 'module')->update_site_cache();
            } else {
                \Phpcmf\Service::C()->_json(0, dr_lang('请升级建站系统插件'));
            }
        } else {
            // 按项目更新的缓存
            $cache = [];
            if (is_file(MYPATH.'/Config/Cache.php')) {
                $_cache = require MYPATH.'/Config/Cache.php';
                $_cache && $cache = dr_array22array($cache, $_cache);
            }
            // 执行插件自己的缓存程序
            $local = \Phpcmf\Service::Apps();
            $app_cache = [];
            foreach ($local as $dir => $path) {
                if (is_file($path.'install.lock')
                    && is_file($path.'Config/Cache.php')) {
                    $_cache = require $path.'Config/Cache.php';
                    $_cache && $app_cache[$dir] = $_cache;
                }
            }
            dr_dir_delete(WRITEPATH.'data');
            foreach (['auth', 'email', 'member', 'attachment', 'system'] as $m) {
                \Phpcmf\Service::M($m)->cache();
            }
            // 自定义缓存
            foreach ($cache as $m => $namespace) {
                \Phpcmf\Service::M($m, $namespace)->cache(1);
            }
            // 插件缓存
            $apps = [];
            if ($app_cache) {
                foreach ($app_cache as $namespace => $c) {
                    \Phpcmf\Service::C()->init_file($namespace);
                    foreach ($c as $i => $apt) {
                        $class = is_numeric($i) ? $apt : $i;
                        $apps[] = '['.$namespace.'-'.$class.']';
                        \Phpcmf\Service::M($class, $namespace)->cache(1);
                    }
                }
            }
            // 记录日志
            CI_DEBUG && \Phpcmf\Service::L('input')->system_log('更新缓存：'.implode(' - ', $apps));
            \Phpcmf\Service::C()->_json(1, dr_lang('更新完成'));
        }
    }

    // 更新当前项目缓存
    public function update_cache() {

        \Phpcmf\Hooks::trigger('update_cache');

        if (dr_is_use_module()) {
            if (is_file(IS_USE_MODULE.'Models/Site.php')) {
                \Phpcmf\Service::M('site', 'module')->update_cache();
            } else {
                \Phpcmf\Service::C()->_json(0, dr_lang('请升级建站系统插件'));
            }
        } else {
            // 全局缓存
            foreach (['auth', 'email', 'member', 'attachment', 'system'] as $m) {
                \Phpcmf\Service::M($m)->cache();
            }

            // 按项目更新的缓存
            $cache = [];

            if (is_file(MYPATH.'/Config/Cache.php')) {
                $_cache = require MYPATH.'/Config/Cache.php';
                $_cache && $cache = dr_array22array($cache, $_cache);
            }

            // 执行插件自己的缓存程序
            $local = \Phpcmf\Service::Apps();
            $app_cache = [];
            foreach ($local as $dir => $path) {
                if (is_file($path.'install.lock')
                    && is_file($path.'Config/Cache.php')) {
                    $_cache = require $path.'Config/Cache.php';
                    $_cache && $app_cache[$dir] = $_cache;
                }
            }

            \Phpcmf\Service::M('table')->cache(1, []);
            \Phpcmf\Service::M('menu')->cache();
        }
    }

    // 更新数据 $all表示是否强制更新缓存
    public function update_data_cache($all = false) {

        if ($all or !SYS_CACHE_CLEAR) {

            // 清空系统缓存
            \Phpcmf\Service::L('cache')->init()->clean();

            // 清空文件缓存
            \Phpcmf\Service::L('cache')->init('file')->clean();

            // 递归删除文件
            $path = [
                WRITEPATH.'file',
                WRITEPATH.'template',
                WRITEPATH.'debugbar',
            ];
            // 默认文件内容
            $cache_index = '<IfModule authz_core_module>
	Require all denied
</IfModule>
<IfModule !authz_core_module>
	Deny from all
</IfModule>
';
            // 开始删除目录数据
            foreach ($path as $p) {
                dr_dir_delete($p);
                mkdir($p, 0777);
                file_put_contents($p.'/.htaccess', $cache_index);
            }
        }

        // 删除缓存保留24小时内的文件
        $path = [
            WRITEPATH.'authcode',
            WRITEPATH.'debugbar',
            WRITEPATH.'session',
            WRITEPATH.'thread',
            WRITEPATH.'temp',
        ];
        foreach ($path as $p) {
            if ($fp = opendir($p)) {
                while (FALSE !== ($file = readdir($fp))) {
                    if ($file === '.' OR $file === '..'
                        OR $file === 'index.html'
                        OR $file === '.htaccess'
                        OR $file[0] === '.'
                        OR !is_file($p.'/'.$file)
                        OR SYS_TIME - filemtime($p.'/'.$file) <  3600 * 24 // 保留24小时内的文件
                    ) {
                        continue;
                    }
                    unlink($p.'/'.$file);
                }
                file_put_contents($p.'/.htaccess', $cache_index);
            }
        }

        // 删除首页静态文件
        if (isset(\Phpcmf\Service::C()->site_info[SITE_ID]['SITE_INDEX_HTML'])
            && \Phpcmf\Service::C()->site_info[SITE_ID]['SITE_INDEX_HTML']) {
            unlink(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', 'index.html'));
            unlink(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', SITE_MOBILE_DIR.'/index.html'));
        }

        @unlink(WRITEPATH.'config/run_lock.php');

        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();
    }

    // 编辑器更新
    public function update_ueditor() {

        $site = $this->table('site')->where('disabled', 0)->getAll();
        foreach ($site as $t) {
            $t['setting'] = dr_string2array($t['setting']);
            if ($t['id'] > 1 && $t['setting']['webpath']) {
                $path = rtrim($t['setting']['webpath'], '/').'/';
            } else {
                $path = WEBPATH;
            }
            // 复制百度编辑器到当前目录
            $this->cp_ueditor_file($path);
            // 复制百度编辑器到移动端项目
            $this->cp_ueditor_file($path.'mobile/');
            if ($t['setting']['client']) {
                foreach ($t['setting']['client'] as $c) {
                    if ($c['name'] && $c['domain']) {
                        // 复制百度编辑器到当前目录
                        $this->cp_ueditor_file($path.$c['name'].'/');
                    }
                }
            }
        }

        // 为后台域名移动编辑器目录
        if (dr_is_app('safe')) {
            $safe = \Phpcmf\Service::M('app')->get_config('safe');
            if ($safe) {
                foreach ($safe as $key => $path) {
                    if (is_string($path) && is_numeric($key) && is_dir($path)) {
                        $this->cp_ueditor_file($path.'/');
                    }
                }
            }
        }
    }

    // 复制编辑器
    public function cp_ueditor_file($path) {

        $npath = $path.'api/ueditor/';
        if (!is_file($npath.'ueditor.config.js')) {
            return;
        }

        dr_mkdirs($npath);

        \Phpcmf\Service::L('file')->copy_dir(ROOTPATH.'api/ueditor/dialogs/', ROOTPATH.'api/ueditor/dialogs/', $npath.'dialogs/');
        \Phpcmf\Service::L('file')->copy_dir(ROOTPATH.'api/ueditor/third-party/', ROOTPATH.'api/ueditor/third-party/', $npath.'third-party/');
    }

    public function update_search_index() {
        \Phpcmf\Service::M('site', 'module')->update_search_index();
    }

    // 重建子站配置文件
    public function update_site_config() {
        \Phpcmf\Service::M('site', 'module')->update_site_config();
    }

    // 生成目录式手机目录
    public function update_mobile_webpath($path, $dirname) {
        \Phpcmf\Service::M('site', 'module')->update_mobile_webpath($path, $dirname);
    }

    // 更新项目
    public function update_webpath($name, $path, $value, $root = TEMPPATH) {
        \Phpcmf\Service::M('site', 'module')->update_webpath($name, $path, $value, $root);
    }

    // 错误输出
    public function _error_msg($msg) {
        echo dr_array2string(dr_return_data(0, $msg));exit;
    }
}