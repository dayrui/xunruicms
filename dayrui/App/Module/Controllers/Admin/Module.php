<?php namespace Phpcmf\Controllers\Admin;


class Module extends \Phpcmf\Common {

    private $dir;

    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        $menu = [
            '内容模块' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-cogs'],
            '模块配置' => ['hide:'.APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-cog'],
            '推荐位配置' => ['hide:'.APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/flag_edit', 'fa fa-flag'],
            'help' => [57]
        ];
        if (strpos(\Phpcmf\Service::L('Router')->method, 'flag') !== false) {
            $menu['help'] = [440];
        } elseif (strpos(\Phpcmf\Service::L('Router')->method, 'edit') !== false) {
            $menu['help'] = [1040];
        }
        \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu($menu));
    }

    // 安装模块
    public function install() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        $type = (int)\Phpcmf\Service::L('input')->get('type');

        if (!preg_match('/^[a-z]+$/U', $dir)) {
            $this->_json(0, dr_lang('模块目录[%s]格式不正确', $dir));
        } elseif (\Phpcmf\Service::M('app')->is_sys_dir($dir)) {
            $this->_json(0, dr_lang('模块目录[%s]名称是系统保留名称，请重命名', $dir));
        }

        $path = dr_get_app_dir($dir);
        if (!is_dir($path)) {
            $this->_json(0, dr_lang('模块目录[%s]不存在', $path));
        }

        // 对当前模块属性判断
        $cfg = require $path.'Config/App.php';
        if (!$cfg) {
            $this->_json(0, dr_lang('文件[%s]不存在', 'App/'.ucfirst($dir).'/Config/App.php'));
        }

        $cfg['share'] = $type ? 0 : 1;

        $rt = \Phpcmf\Service::M('module')->install($dir, $cfg);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        $this->_json($rt['code'], $rt['msg'], $rt['data']);
    }

    // 卸载模块
    public function uninstall() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        if (!preg_match('/^[a-z]+$/U', $dir)) {
            $this->_json(0, dr_lang('模块目录[%s]格式不正确', $dir));
        }

        $path = dr_get_app_dir($dir);
        if (!is_dir($path)) {
            $this->_json(0, dr_lang('模块目录[%s]不存在', $path));
        }

        $cfg = require $path.'Config/App.php';
        if (!$cfg) {
            $this->_json(0, dr_lang('文件[%s]不存在', 'App/'.ucfirst($dir).'/Config/App.php'));
        }

        $rt = \Phpcmf\Service::M('Module')->uninstall($dir, $cfg);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        $this->_json($rt['code'], $rt['msg']);

    }

    // 模块管理
    public function index() {

        $list = [];
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file(dr_get_app_dir($dir).'Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require dr_get_app_dir($dir).'Config/App.php';
                if ($cfg['type'] == 'module' || $cfg['ftype'] == 'module') {
                    if (isset($cfg['hlist']) && $cfg['hlist']) {
                        // 不在列表显示
                        continue;
                    }
                    $cfg['dirname'] = $key;
                    $list[$key] = $cfg;
                }
            }
        }

        $my = [];
        $module = \Phpcmf\Service::M('Module')->All(); // 库中已安装模块
        if ($module) {
            foreach ($module as $t) {
                $dir = $t['dirname'];
                if ($list[$dir]) {
                    $t['name'] = dr_lang($list[$dir]['name']);
                    $t['mtype'] = $list[$dir]['mtype'];
                    $t['system'] = $list[$dir]['system'];
                    $t['version'] = $list[$dir]['version'];
                    $site = dr_string2array($t['site']);
                    $t['install'] = isset($site[SITE_ID]) && $site[SITE_ID] ? 1 : 0;
                    $my[$dir] = $t;
                    unset($list[$dir]);
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'my' => $my,
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('module_list.html');
    }

    // 重命名
    public function name_edit() {

        $mid = dr_safe_filename($_GET['dir']);
        $file = dr_get_app_dir($mid).'Config/App.php';
        if (!is_file($file)) {
            $this->_json(0, dr_lang('当前模块配置文件不存在'));
        }

        $config = require $file;

        if (IS_POST) {

            $data = \Phpcmf\Service::L('input')->post('data');

            // 参数判断
            if (!$data['name']) {
                $this->_json(0, dr_lang('名称不能为空'), ['field' => 'name']);
            } elseif (!$data['icon']) {
                $this->_json(0, dr_lang('模块图标不能为空'), ['field' => 'icon']);
            } elseif (!dr_check_put_path(dirname($file))) {
                $this->_json(0, dr_lang('目录[%s]没有创建文件权限', dirname($file)), ['field' => 'dirname']);
            }

            $old = $config['name'];
            $config['name'] = dr_safe_filename($data['name']);
            $config['icon'] = dr_safe_replace($data['icon']);
            file_put_contents($file, '<?php return '.var_export($config, true).';');

            // 变更菜单
            \Phpcmf\Service::M('menu', 'module')->update_module_name($mid, $old, $config['name'], $config['icon']);

            // 重置Zend OPcache
            function_exists('opcache_reset') && opcache_reset();

            \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存

            \Phpcmf\Service::L('input')->system_log('模块['.$mid.']名称变更');
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'config' => $config,
        ]);
        \Phpcmf\Service::V()->display('module_name_edit.html');exit;
    }

    // 排序
    public function displayorder_edit() {

        // 查询数据
        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $row = \Phpcmf\Service::M('Module')->table('module')->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

        $value = (int)\Phpcmf\Service::L('input')->get('value');
        $rt = \Phpcmf\Service::M('Module')->table('module')->save($id, 'displayorder', $value);
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        \Phpcmf\Service::L('input')->system_log('修改模块('.$row['dirname'].')的排序值为'.$value);
        $this->_json(1, dr_lang('操作成功'));
    }

    // 隐藏或者启用
    public function hidden_edit() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $row = \Phpcmf\Service::M('Module')->table('module')->get($id);
        if (!$row) {
            $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

        $v = $row['disabled'] ? 0 : 1;
        \Phpcmf\Service::M('Module')->table('module')->update($id, ['disabled' => $v]);

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        $this->_json(1, dr_lang($v ? '模块已被禁用' : '模块已被启用'), ['value' => $v]);
    }

    // 模块配置
    public function edit() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if ($this->dir) {
            $data = \Phpcmf\Service::M()->table('module')->where('dirname', $this->dir)->getRow();
            if (!$data) {
                $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
            }
            $id = $data['id'];
        } else {
            $data = \Phpcmf\Service::M()->table('module')->get($id);
            if (!$data) {
                $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
            }
        }

        // 格式转换
        $data['site'] = dr_string2array($data['site']);
        $data['setting'] = dr_string2array($data['setting']);

        // 判断站点
        if (!$data['site'][SITE_ID]) {
            $this->_admin_msg(0, dr_lang('当前站点尚未安装'));
        }

        // 主表字段
        $field = \Phpcmf\Service::M()->db->table('field')
            ->where('disabled', 0)
            ->where('ismain', 1)
            ->where('relatedname', 'module')
            ->where('relatedid', $id)
            ->orderBy('displayorder ASC,id ASC')
            ->get()->getResultArray();
        $sys_field = \Phpcmf\Service::L('Field')->sys_field(['id', 'catid', 'uid', 'inputtime', 'updatetime', 'hits', 'displayorder']);
        $field = dr_list_field_value($data['setting']['list_field'], $sys_field, $field);

        if (IS_AJAX_POST) {
            $this->init_file($data['dirname']);
            $post = \Phpcmf\Service::L('input')->post('data');
            if ($post['setting']['list_field']) {
                foreach ($post['setting']['list_field'] as $t) {
                    if ($t['func']
                        && !method_exists(\Phpcmf\Service::L('Function_list'), $t['func']) && !function_exists($t['func'])) {
                        $this->_json(0, dr_lang('列表回调函数[%s]未定义', $t['func']));
                    }
                }
            }
            if ($post['setting']['search_time'] && !isset($field[$post['setting']['search_time']])) {
                $this->_json(0, dr_lang('后台列表时间搜索字段%s不存在', $post['setting']['search_time']));
            }
            if ($post['setting']['order']) {
                $arr = explode(',', $post['setting']['order']);
                foreach ($arr as $t) {
                    list($a) = explode(' ', $t);
                    if ($a && !isset($field[$a])) {
                        $this->_json(0, dr_lang('后台列表的默认排序字段%s不存在', $a));
                    }
                }
            }

            $rt = \Phpcmf\Service::M('Module')->config($data, $post);
            if ($rt['code']) {
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                $this->_json(1, '操作成功');
            } else {
                $this->_json(0, $rt['msg']);
            }
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        $config = require dr_get_app_dir($data['dirname']).'Config/App.php';

        if (!$data['site'][SITE_ID]['title']) {
            $data['site'][SITE_ID]['title'] = $config['name'];
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'site' => $data['site'][SITE_ID],
            'form' => dr_form_hidden(['page' => $page]),
            'field' => $field,
            'is_hcategory' => isset($config['hcategory']) && $config['hcategory'],
        ]);
        \Phpcmf\Service::V()->display('module_edit.html');
    }

    // 推荐位
    public function flag_edit() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if ($this->dir) {
            $data = \Phpcmf\Service::M()->table('module')->where('dirname', $this->dir)->getRow();
            if (!$data) {
                $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
            }
        } else {
            $data = \Phpcmf\Service::M()->table('module')->get($id);
            if (!$data) {
                $this->_admin_msg(0, dr_lang('数据#%s不存在', $id));
            }
        }

        // 格式转换
        $data['setting'] = dr_string2array($data['setting']);

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('flag');
            if ($post) {
                $role = \Phpcmf\Service::L('input')->post('role');
                foreach ($post as $fid => $t) {
                    $post[$fid]['role'] = [];
                    if (isset($role[$fid]) && $role[$fid]) {
                        foreach ($role[$fid] as $fid2 => $aid) {
                            $post[$fid]['role'][$aid] = 1;
                        }
                    }
                }
            }
            $rt = \Phpcmf\Service::M('Module')->config($data, null, [
                'flag' => $post,
            ]);
            if ($rt['code']) {
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                $this->_json(1, '操作成功');
            } else {
                $this->_json(0, $rt['msg']);
            }
        }

        \Phpcmf\Service::V()->assign([
            'flag' => $data['setting']['flag'],
            'form' => dr_form_hidden(),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
        ]);
        \Phpcmf\Service::V()->display('module_flag.html');
    }

}
