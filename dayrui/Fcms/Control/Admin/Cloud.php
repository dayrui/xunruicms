<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 云服务
class Cloud extends \Phpcmf\Common {

    private $vs;
    private $admin_url;
    private $service_url;

    public function __construct()
    {
        parent::__construct();

        // 不是超级管理员
        if (!dr_in_array(1, $this->admin['roleid'])) {
            $this->_admin_msg(0, dr_lang('需要超级管理员账号操作'));
        }

        if (!$this->cmf_license) {
            $this->cmf_license = [
                'id' => 10,
                'license' => SYS_TIME,
            ];
        } elseif (!$this->cmf_license['license']) {
            $this->cmf_license['license'] = SYS_TIME;
        }

        list($this->admin_url) = explode('?', FC_NOW_URL);
        $this->service_url = 'https://www.xunruicms.com/cloud.php?domain=' . dr_get_domain_name(ROOT_URL) . '&admin=' . urlencode($this->admin_url) .'&frame='.FRAME_NAME. '&version=' . $this->cmf_version['version']  . '&cms=' . $this->cmf_version['id'] . '&license=' . $this->cmf_license['license'];
        if ($this->cmf_license['cloud'] && IS_OEM_CMS) {
            if (trim($this->cmf_license['cloud'], '/') == trim(ROOT_URL, '/')) {
                $this->_admin_msg(0, '云端服务器域名不能与本站点相同');
            }
            $this->service_url = $this->cmf_license['cloud'] . '/index.php?s=cloud&c=api&domain=' . dr_get_domain_name(ROOT_URL) . '&admin=' . urlencode($this->admin_url) . '&license=' . $this->cmf_license['license'];
        }

        $this->vs = 0;
        if (defined('IS_VERSION') && IS_VERSION) {
            // 版本控制
            $this->vs = 1;
            $this->service_url.= '&vs=1';
        }

        \Phpcmf\Service::V()->assign([
            'vs' => $this->vs,
            'is_oem' => $this->cmf_license['oem'] ? 1 : 0,
            'license' => $this->cmf_license,
            'license_sn' => $this->cmf_license['license'],
            'cms_version' => $this->cmf_version,
            'cmf_version' => $this->cmf_version,
        ]);
    }

    // 服务工单
    public function service() {

        $url = 'https://www.xunruicms.com/service.php?cms='.$this->cmf_version['id'].'&license='.$this->cmf_license['license'];
        if ($this->cmf_license['service']) {
            $url = $this->cmf_license['service'];
        }

        \Phpcmf\Service::V()->assign([
            'url' => $url,
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }

    // 我的
    public function index() {

        $domain = dr_get_domain_name(ROOT_URL);
        $license_domain = 'https://www.xunruicms.com/license/domain/'.$domain;
        if ($this->cmf_license['domain']) {
            $license_domain = trim($this->cmf_license['domain'], '/').'/index.php?s=license&m=show&domain='.$domain;
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '我的项目' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-cog'],
                ]
            ),
            'domain' => $domain,
            'ip_address' => \Phpcmf\Service::L('input')->ip_address(),
            'license_domain' => $license_domain,
        ]);
        \Phpcmf\Service::V()->display('cloud_index.html');exit;
    }

    // 插件应用
    public function app() {

        $id = [];
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file(dr_get_app_dir($dir).'Config/App.php')) {
                $cfg = require dr_get_app_dir($dir).'Config/App.php';
                if (($cfg['type'] != 'module' || $cfg['ftype'] == 'module')
                    && is_file(dr_get_app_dir($dir).'Config/Version.php')) {
                    $vsn = require dr_get_app_dir($dir).'Config/Version.php';
                    $vsn['id'] && $id[] = $vsn['id'];
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'url' => $this->service_url.'&action=app&catid=15&id='.implode(',', $id),
        ]);

        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }

    // 功能组件
    public function func() {


        \Phpcmf\Service::V()->assign([
            'url' => $this->service_url.'&action=app&catid=16',
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }

    // 模板界面
    public function template() {


        \Phpcmf\Service::V()->assign([
            'url' => $this->service_url.'&action=app&catid=14',
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }


    // 本地应用
    public function local() {

        if (SITE_ID > 1) {
            $this->_admin_msg(0, dr_lang('请切换到[主站点]操作'));exit;
        }

        $data = [];
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require $path.'Config/App.php';
                if (($cfg['type'] != 'module' || $cfg['ftype'] == 'module') && is_file($path.'Config/Version.php')) {
                    $vsn = require $path.'Config/Version.php';
                    $menu = [];
                    $install = is_file($path.'install.lock');
                    if ($install && is_file($path.'Config/Menu.php')) {
                        //$cfg['uri'] ? \Phpcmf\Service::M('auth')->_menu_link_url($cfg['uri'], '', [], true) : ''
                        $m = require $path.'Config/Menu.php';
                        if ($m['admin']) {
                            foreach ($m['admin'] as $m1) {
                                if ($m1['left']) {
                                    foreach ($m1['left'] as $m2) {
                                        if ($m2['link']) {
                                            foreach ($m2['link'] as $m3) {
                                                if ($m3['uri']) {
                                                    $menu[] = [
                                                        'name' => $m3['name'],
                                                        'url' =>  \Phpcmf\Service::M('auth')->_menu_link_url($m3['uri'], '', [], true),
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $data[$key] = [
                        'id' => $vsn['id'],
                        'name' => $cfg['name'],
                        'type' => $cfg['type'],
                        'mtype' => $cfg['mtype'],
                        'ftype' => $cfg['ftype'],
                        'icon' => $cfg['icon'],
                        'author' => $cfg['author'],
                        'menu' => $menu,
                        'version' => $vsn['version'],
                        'vip' => $vsn['vip'],
                        'install' => $install,
                    ];
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'list' => $data,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '本地应用' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-puzzle-piece'],
                    'help' => [574],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('cloud_app.html');
    }

    // 安装程序
    public function install() {

        $id = dr_safe_filename(\Phpcmf\Service::L('input')->get('id'));
        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        $type = intval(\Phpcmf\Service::L('input')->get('type'));
        if (!is_file(dr_get_app_dir($dir).'Config/App.php')) {
            if (IS_DEV) {
                $this->_json(0, dr_lang('安装程序'.dr_get_app_dir($dir).'Config/App.php不存在'));
            }
            $this->_json(0, dr_lang('安装程序App.php不存在'));
        }

        // 开始安装
        $rt = \Phpcmf\Service::M('App')->install($dir, $type);
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        // 运行安装脚本
        if (is_file(WRITEPATH.'cloud/run-'.$id.'.php')) {
            require WRITEPATH.'cloud/run-'.$id.'.php';
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
        $this->_json(1, dr_lang('安装成功，请刷新后台页面'), $rt['data']);
    }

    // 卸载程序
    public function uninstall() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        if (!preg_match('/^[a-z_]+$/U', $dir)) {
            $this->_json(0, dr_lang('目录[%s]格式不正确', $dir));
        }

        $path = dr_get_app_dir($dir);
        if (!is_dir($path)) {
            $this->_json(0, dr_lang('目录[%s]不存在', $path));
        }

        $rt = \Phpcmf\Service::M('App')->uninstall($dir);

        $this->_json($rt['code'], $rt['msg']);
    }

    // 验证授权
    public function login() {

        if (IS_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $surl = $this->service_url.'&action=update_login&get_http=1&username='.$post['username'].'&password='.md5($post['password']);
            $json = dr_catcher_data($surl);
            if (!$json) {
                $this->_json(0, '本站：没有从服务端获取到数据，建议尝试离线方式');
            }
            $rt = dr_string2array($json);
            if (!$rt) {
                $this->_json(0, '本站：从服务端获取到的数据不规范（'.dr_strcut($json, 30).'）');
            }
            if (!$rt['code']) {
                $this->_json(0, '服务端：'.$rt['msg']);
            }

            if (dr_strlen($rt['data']) > 8) {
                $myfile = MYPATH.'Config/License.php';
                if (is_file($myfile)) {
                    // 存在就更新id
                    if ($rt['data'] != $this->cmf_license['license']) {
                        $this->cmf_license['license'] = $rt['data'];
                        \Phpcmf\Service::L('Config')->file($myfile, '此文件是版本文件，每次下载安装包会自动生成，请勿修改', 32)
                            ->to_require($this->cmf_license['license']);
                    }
                } else {
                    $text = "<?php
// 此文件是版本文件，每次下载安装包会自动生成，请勿修改
return [

    'license' => '".$rt['data']."',
    'name' => '迅睿CMS开源框架',
    'url' => 'https://www.xunruicms.com',

];
";
                    if (!file_put_contents($myfile, $text)) {
                        $this->_json(0, '本站：dayrui/My/目录无法写入文件，请给于777权限');
                    }
                }
            }
            $this->_json(1, $rt['msg'], $rt['data']);
        }

        \Phpcmf\Service::V()->display('cloud_login_ajax.html');exit;
    }

    // 下载程序
    function down_file() {
        \Phpcmf\Service::V()->assign([
            'ls' => intval($_GET['ls']),
            'app_id' => 'app-'.intval($_GET['cid']),
        ]);
        \Phpcmf\Service::V()->display('cloud_down_file.html');exit;
    }

    // 安装模板
    public function install_tpl() {

        $id = dr_safe_filename(\Phpcmf\Service::L('input')->get('id'));
        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        if (!$dir) {
            $this->_json(0, dr_lang('缺少模板参数'));
        }

        \Phpcmf\Service::M('app')->install_tpl($dir, $id);

        $this->_json(1, dr_lang('当前站点模板安装成功，请访问前台预览'));
    }

    // 将下载程序安装到目录中
    function install_app() {

        $id = dr_safe_filename($_GET['id']);
        $file = WRITEPATH.'cloud/'.$id.'.zip';
        $cmspath = WRITEPATH.'cloud/'.$id.'/';
        if (!is_file($file)) {
            $this->_json(0, '本站：文件还没有被下载');
        } elseif (!class_exists('ZipArchive')) {
            $this->_json(0, '本站：php_zip扩展未开启，无法在线安装功能，建议尝试离线方式');
        }

        $cache = \Phpcmf\Service::L('cache')->get_data('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '本站：授权验证缓存过期，请重试');
        }

        // 解压目录
        if (!\Phpcmf\Service::L('file')->unzip($file, $cmspath)) {
            $this->_json(0, '本站：文件解压失败');
        }

        // 查询插件目录
        $is_app = $is_module_app = $is_tpl = 0;
        if (is_file($cmspath.'Install.php') && strpos(file_get_contents($cmspath.'Install.php'), 'return') !== false) {
            $ins = require $cmspath.'Install.php';
            if (isset($ins['type']) && $ins['type'] == 'app') {
                if ($ins['name'] && is_file($cmspath.'APPSPATH/'.ucfirst($ins['name']).'/Config/App.php')) {
                    $cfg = require $cmspath.'APPSPATH/'.ucfirst($ins['name']).'/Config/App.php';
                    $is_app = $ins['name'];
                    $is_module_app = $cfg['ftype'] == 'module' && $cfg['mtype'] == 0;
                }
            } elseif (isset($ins['type']) && $ins['type'] == 'tpl') {
                if ($ins['name']) {
                    $is_tpl = $ins['name'];
                }
            }
        }

        // 安装之前的验证
        if (is_file($cmspath.'Check.php')) {
            require $cmspath.'Check.php';
        }

        if (is_file($cmspath.'Run.php')) {
            copy($cmspath.'Run.php',WRITEPATH.'cloud/run-'.$id.'.php');
        }

        if (!$is_tpl && !$is_app && is_dir($cmspath.'APPSPATH/')) {
            $p = dr_dir_map($cmspath.'APPSPATH/', 1);
            foreach ($p as $name) {
                if (is_file($cmspath.'APPSPATH/'.$name.'/Config/App.php')
                    && is_file($cmspath.'APPSPATH/'.$name.'/Config/Version.php')) {
                    if (is_file($cmspath.'APPSPATH/'.$name.'/install.lock')) {
                        unlink($cmspath.'APPSPATH/'.$name.'/install.lock');
                    }
                    $is_app = strtolower($name);
                    $cfg = require $cmspath.'APPSPATH/'.$name.'/Config/App.php';
                    $is_module_app = $cfg['ftype'] == 'module' && $cfg['mtype'] == 0;
                    break;
                }
                if (is_file($cmspath.'APPSPATH/'.$name.'/Config/Before.php')) {
                    $rt = require $cmspath.'APPSPATH/'.$name.'/Config/Before.php';
                    if (!$rt['code']) {
                        $this->_json(0, $rt['msg']);
                    }
                }
            }
        }

        // 判断本地目录是否有重名的
        if ($is_app && is_dir(APPSPATH.ucfirst($is_app))) {
            $cf = dr_safe_filename($_GET['cf']);
            if ('ok' != $cf) {
                $msg = '<font color="red">本插件文件夹（'.ucfirst($is_app).'）已经存在（'.APPSPATH.ucfirst($is_app).'）是否覆盖本站文件夹？</font></p><p style="margin-top:20px;">建议提前备份本站文件夹，<a href="javascript:dr_install(\'ok\');">确定覆盖本站文件夹</a>';
                $this->_json(1, $msg);
            }
        }

        // 备份模板
        \Phpcmf\Service::L('file')->zip(
            WRITEPATH.'backups/update/template/'.date('Y-m-d-H-i-s').'.zip',
            TPLPATH
        );

        // 复制文件到程序
        $is_ok = 0;
        if (is_dir($cmspath.'APPSPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'APPSPATH', APPSPATH);
        }
        /*
        if ($this->vs) {
            if (is_dir($cmspath.'WEBPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH.'public/');
            }
            if (is_dir($cmspath.'ROOTPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH.'public/');
            }
            if (is_dir($cmspath.'CSSPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'CSSPATH/', ROOTPATH.'public/static/');
            }
        } else {*/
            if (is_dir($cmspath.'WEBPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH);
            }
            if (is_dir($cmspath.'ROOTPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH);
            }
            if (is_dir($cmspath.'CSSPATH')) {
                $is_ok = 1;
                $this->_copy_dir($cmspath.'CSSPATH/', ROOTPATH.'static/');
            }
        //}
        if (is_dir($cmspath.'TPLPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'TPLPATH', TPLPATH);
        }
        if (is_dir($cmspath.'WRITEPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'WRITEPATH', WRITEPATH);
        }
        if (is_dir($cmspath.'FCPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'FCPATH', FCPATH);
        }
        if (is_dir($cmspath.'MYPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'MYPATH', MYPATH);
        }
        if (is_dir($cmspath.'COREPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'COREPATH', COREPATH);
        }
        if (is_dir($cmspath.'CMSPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'CMSPATH', COREPATH);
        }
        if (is_dir($cmspath.'CONFIGPATH')) {
            $is_ok = 1;
            $this->_copy_dir($cmspath.'CONFIGPATH', CONFIGPATH);
        }

        // 开发者模式下保留目录
        if (!IS_DEV) {
            unlink($file);
            dr_dir_delete($cmspath, 1);
        }

        if (!$is_ok) {
            $this->_json(0, '本站：当前下载的应用程序'.($is_app ? '（'.$is_app.'）' : '').'压缩包已损坏，请尝试离线下载安装');
        }

        if ($is_app) {
            if ($is_module_app) {
                $msg = '程序导入完成</p><p style="margin-top:20px;"><a href="javascript:dr_install_module_select(\''.dr_url('cloud/install', ['id' => $id, 'dir'=>$is_app]).'\');">立即安装应用插件</a>';
            } else {
                $msg = '程序导入完成</p><p style="margin-top:20px;"><a href="javascript:dr_install_app(\''.dr_url('cloud/install', ['id' => $id, 'dir'=>$is_app]).'\', 0);">立即安装应用插件</a>';
            }
        } elseif ($is_tpl) {
            $msg = '模板导入完成</p><p style="margin-top:20px;"><a href="javascript:dr_load_ajax(\''.dr_lang('确定安装此模板到当前站点吗？').'\', \''.dr_url('cloud/install_tpl', ['id' => $id, 'dir'=>$is_tpl]).'\', 0);">立即安装模板</a>';
        } else {
            if ($this->cmf_license['oem']) {
                $msg = '程序导入完成';
            } else {
                $msg = '程序导入完成<br><a href="https://www.xunruicms.com/api/shop-doc.php?id='.$id.'" target="_blank">请按本商品的使用教程来操作</a>';
            }
        }

        $this->_json(1, $msg);
    }
	
	// 判断备份目录是否有效
	private function _is_backup_file($path) {
		
		if (is_dir($path)) {
			if ($dh = opendir($path)) {
				while (($file = readdir($dh)) !== false) {
					if (strpos($file, '.zip') !== false){
						closedir($dh);
						return $path;
					}
				}
				closedir($dh);
			}
		}
		
		return '';
	}

    // 程序升级
    public function update() {

        $data = [];

        $data['phpcmf'] = $this->cmf_version;
        $data['phpcmf']['id'] = 'cms-1';
        $data['phpcmf']['tname'] = $this->cmf_license['oem'] ? '系统' : '<a href="javascript:dr_help(538);">系统</a>';
        $data['phpcmf']['backup'] = $this->_is_backup_file(WRITEPATH.'backups/update/cms/');
        $data['phpcmf']['backup_tpl'] = $this->_is_backup_file(WRITEPATH.'backups/update/template/');
        $data['phpcmf']['backup_time'] = is_dir(WRITEPATH.'backups/update/cms/') ? dr_date(filemtime(WRITEPATH.'backups/update/cms/')) : '';

        $local = dr_dir_map(APPSPATH, 1);
        foreach ($local as $dir) {
            if (is_file(APPSPATH.$dir.'/Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require APPSPATH.$dir.'/Config/App.php';
                if (($cfg['type'] != 'module' || $cfg['ftype'] == 'module')
                    && is_file(APPSPATH.$dir.'/Config/Version.php')) {
                    $vsn = require APPSPATH.$dir.'/Config/Version.php';
                    $path = WRITEPATH.'backups/update/'.$key.'/';
                    $vsn['id'] && $data[$key] = [
                        'id' => $cfg['type'].'-'.$vsn['id'],
                        'name' => $cfg['name'],
                        'type' => $cfg['type'],
                        'tname' => $this->cmf_license['oem'] ? '应用' : '<a href="javascript:dr_help(540);">应用</a>',
                        'version' => $vsn['version'],
                        'license' => $vsn['license'],
                        'vip' => $vsn['vip'],
                        'updatetime' => $vsn['updatetime'],
                        'backup' => $this->_is_backup_file($path),
                        'backup_tpl' => $data['phpcmf']['backup_tpl'],
                        'backup_time' => is_dir($path) ? dr_date(filemtime($path)) : '',
                    ];
                }
            }
        }

        $menu = [
            '版本升级' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-refresh'],
            '文件对比' => [\Phpcmf\Service::L('Router')->class.'/bf', 'fa fa-code'],
            'help' => [379],
        ];
        if ($this->cmf_license['oem']) {
            unset($menu['文件对比']);
        }

        \Phpcmf\Service::V()->assign([
            'list' => $data,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu($menu),
            'cms_id' => $this->cmf_version['cms'],
            'domain_id' => $this->cmf_license['id'],
        ]);
        \Phpcmf\Service::V()->display('cloud_update.html');exit;
    }

    // 检查服务器版本
    public function check_version() {

        $cid = dr_safe_replace($_GET['id']);

        if ($cid == 'cms-1') {
            // 目录权限检查
            $dir = [
                WRITEPATH,
                ROOTPATH,
                APPSPATH,
                TPLPATH,
                FCPATH,
                MYPATH,
            ];
            foreach ($dir as $t) {
                if (!dr_check_put_path($t)) {
                    $this->_json(0, dr_lang('目录【%s】不可写', $t));
                }
            }
        }

        $index = isset($_GET['isindex']) ? 1 : 0;
        $vid = dr_safe_replace($_GET['version']);
        $surl = $this->service_url.'&action=check_version&php='.PHP_VERSION
            .'&get_http=1&time='.strtotime((string)$this->cmf_version['downtime'])
            .'&id='.$cid.'&isindex='.$index.'&version='
            .$vid.'&license='.$this->cmf_license['license'];
        if ($index) {
            // 首页检查附带部分插件
            $surl.= '&extends='.dr_array2string($this->_get_app_version());
        }

        $json = dr_catcher_data($surl);
        if (!$json) {
            $this->_json(0, '本站：没有从服务端获取到数据，检查本地环境是否支持远程下载功能');
        }
        $rt = json_decode($json, true);
        $this->_json($rt['code'], $index ? dr_clearhtml($rt['msg']) : $rt['msg']);
    }

    // 执行更新程序的界面
    public function todo_update() {
        \Phpcmf\Service::V()->assign([
            'ls' => dr_safe_replace($_GET['ls']),
            'dir' => dr_safe_replace($_GET['dir']),
            'is_bf' => intval($_GET['is_bf']),
            'app_id' => dr_safe_replace($_GET['id']),
        ]);
        \Phpcmf\Service::V()->display('cloud_todo_update.html');exit;
    }

    // 备份本站文件
    public function update_backup() {

        $dir = dr_safe_filename($_GET['dir']);
        if (!$dir) {
            $this->_json(0, '本站：没有选择任何升级程序');
        }

        $is_bf = intval($_GET['is_bf']);
        if ($is_bf) {
            $this->_json(1, '你选择不备份直接升级程序');
        }

        if ($dir == 'phpcmf') {
            // 主程序备份
            $rt = \Phpcmf\Service::L('file')->zip(
                WRITEPATH.'backups/update/cms/'.date('Y-m-d-H-i-s').'.zip',
                FCPATH
            );
        } else {
            $arr = explode(',', $dir);
            foreach ($arr as $dir) {
                if ($dir) {
                    // 插件备份
                    $rt = \Phpcmf\Service::L('file')->zip(
                        WRITEPATH.'backups/update/'.$dir.'/'.date('Y-m-d-H-i-s').'.zip',
                        dr_get_app_dir($dir)
                    );
                }
            }

        }

        if ($rt) {
            $this->_json(0, '本站：文件备份失败（'.$rt.'）');
        }

        // 备份模板
        \Phpcmf\Service::L('file')->zip(
            WRITEPATH.'backups/update/template/'.date('Y-m-d-H-i-s').'.zip',
            TPLPATH
        );

        $this->_json(1, '本站文件备份完成');
    }

    // 服务器下载升级文件
    public function update_file() {

        $id = dr_safe_replace($_GET['id']);
        if (!$id) {
            $this->_json(0, '本站：没有选择任何升级程序');
        }

        $surl = $this->service_url.'&action=update_file&php='.PHP_VERSION.'&get_http=1&app_id='.$id.'&ls='.dr_safe_replace($_GET['ls']).'&is_update='.intval($_GET['is_update']);
        $json = dr_catcher_data($surl);
        if (!$json) {
            $this->_json(0, '本站：没有从服务端获取到数据，检查本地环境是否支持远程下载功能', $surl);
        }
        $data = dr_string2array($json);
        if (!$data) {
			CI_DEBUG && log_message('error', '服务端['.$surl.']返回数据异常：'.$json);
            $this->_json(0, '本站：服务端数据异常，请重新下载', $json);
        } elseif (!$data['code']) {
            $this->_json(0, $data['msg']);
        } elseif (!$data['data']['size']) {
            $this->_json(0, '本站：服务端文件总大小异常');
        } elseif (!$data['data']['url']) {
            $this->_json(0, '本站：服务端文件下载地址异常');
        }

        \Phpcmf\Service::L('cache')->set_data('cloud-update-'.$id, $data['data'], 3600);

        $this->_json(1, 'ok', $data['data']);
    }

    // 开始下载脚本
    public function update_file_down() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->get_data('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '本站：授权验证缓存过期，请重试');
        } elseif (!$cache['size']) {
            $this->_json(0, '本站：关键数据不存在，请重试');
        } elseif (!function_exists('fsockopen')) {
            $this->_json(0, '本站：PHP环境不支持fsockopen，建议尝试离线方式');
        }

        // 执行下载文件
        $file = WRITEPATH.'cloud/'.$id.'.zip';

        set_time_limit(0);
        touch($file);
        // 做些日志处理
        if ($fp = fopen($cache['url'], "rb")) {
            if (!$download_fp = fopen($file, "wb")) {
                $this->_json(0, '本站：无法写入远程文件', $cache['url']);
            }
            while (!feof($fp)) {
                if (!is_file($file)) {
                    // 如果临时文件被删除就取消下载
                    fclose($download_fp);
                    $this->_json(0, '本站：临时文件被删除', $cache['url']);
                }
                fwrite($download_fp, fread($fp, 1024 * 8 ), 1024 * 8);
            }
            fclose($download_fp);
            fclose($fp);

            $this->_json(1, 'ok');
        } else {
            unlink($file);
            $this->_json(0, '本站：fopen打开远程文件失败，建议尝试离线方式', $cache['url']);
        }
    }

    // 检测下载进度
    public function update_file_check() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->get_data('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '本站：授权验证缓存过期，请重试');
        } elseif (!$cache['size']) {
            $this->_json(0, '本站：关键数据不存在，请重试');
        }

        // 执行下载文件
        $file = WRITEPATH.'cloud/'.$id.'.zip';
        if (is_file($file)) {
            $now = max(1, filesize($file));
            $jd = max(1, round($now / $cache['size'] * 100, 0)); // 进度百分百
            $count = isset($_GET['is_count']) ? intval($_GET['is_count']) : 0; // 表示请求次数
            if (($count > 3 && $jd > 98) || (isset($_GET['is_jd']) && $_GET['is_jd'] == $jd)) {
                $jd = 100;
            }
            $this->_json($jd, '<p><label class="rleft">需下载文件大小：'.dr_format_file_size($cache['size']).'，已下载：'.dr_format_file_size($now).'</label><label class="rright"><span class="ok">'.$jd.'%</span></label></p>');
        } else {
            $this->_json(0, '本站：文件下载失败，建议尝试离线方式');
        }
    }

    // 升级程序
    public function update_file_install() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->get_data('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '本站：授权验证缓存过期，请重试');
        }

        $file = WRITEPATH.'cloud/'.$id.'.zip';
        if (!is_file($file)) {
            $this->_json(0, '本站：文件还没有被下载');
        }

        // 解压目录
        $cmspath = WRITEPATH.'cloud/'.$id.'/';
        if (!\Phpcmf\Service::L('file')->unzip($file, $cmspath)) {
            $this->_json(0, '本站：文件解压失败');
        }

        unlink($file);

        list($type) = explode('-', $id);
        if ($type == 'cms') {
            // cms

            // 缓存目录
            if (is_dir($cmspath.'cache')) {
                $this->_copy_dir($cmspath.'cache', WRITEPATH);
                dr_dir_delete($cmspath.'cache', 1);
            }
            // APP目录
            if (is_dir($cmspath.'dayrui/App')) {
                $this->_copy_dir($cmspath.'dayrui/App', APPSPATH);
                dr_dir_delete($cmspath.'dayrui/App', 1);
            }
            // MYAPP目录
            if (is_dir($cmspath.'dayrui/My')) {
                $this->_copy_dir($cmspath.'dayrui/My', MYPATH);
                dr_dir_delete($cmspath.'dayrui/My', 1);
            }
            // FCPACH目录
            if (is_dir($cmspath.'dayrui')) {
                $this->_copy_dir($cmspath.'dayrui', FCPATH);
                dr_dir_delete($cmspath.'dayrui', 1);
            }
            // public目录复制到主目录
            if (is_dir($cmspath.'public')) {
                $this->_copy_dir($cmspath.'public', ROOTPATH);
                dr_dir_delete($cmspath.'public', 1);
            }
            $this->_copy_dir($cmspath, ROOTPATH);

        } else {
            // 插件部分

            // 查询插件目录
            if (is_dir($cmspath.'APPSPATH/')) {
                $p = dr_dir_map($cmspath.'APPSPATH/', 1);
                foreach ($p as $name) {
                    if (is_file($cmspath.'APPSPATH/'.$name.'/Config/App.php')) {
                        if (is_file($cmspath.'APPSPATH/'.$name.'/install.lock')) {
                            unlink($cmspath.'APPSPATH/'.$name.'/install.lock');
                        }
                        break;
                    }
                }
            }

            // 复制文件到程序
            if (is_dir($cmspath.'APPSPATH')) {
                $this->_copy_dir($cmspath.'APPSPATH', APPSPATH);
            }
            /*
            if ($this->vs) {
                if (is_dir($cmspath.'WEBPATH')) {
                    $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH.'public/');
                }
                if (is_dir($cmspath.'ROOTPATH')) {
                    $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH.'public/');
                }
            } else {*/
                if (is_dir($cmspath.'WEBPATH')) {
                    $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH);
                }
                if (is_dir($cmspath.'ROOTPATH')) {
                    $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH);
                }
            //}
            if (is_dir($cmspath.'CSSPATH')) {
                $this->_copy_dir($cmspath.'CSSPATH/', ROOTPATH.'static/');
            }
            /*升级不覆盖模板
            if (is_dir($cmspath.'TPLPATH')) {
                $this->_copy_dir($cmspath.'TPLPATH', TPLPATH);
            }*/
            if (is_dir($cmspath.'WRITEPATH')) {
                $this->_copy_dir($cmspath.'WRITEPATH', WRITEPATH);
            }
            if (is_dir($cmspath.'FCPATH')) {
                $this->_copy_dir($cmspath.'FCPATH', FCPATH);
            }
            if (is_dir($cmspath.'MYPATH')) {
                $this->_copy_dir($cmspath.'MYPATH', MYPATH);
            }
            if (is_dir($cmspath.'COREPATH')) {
                $this->_copy_dir($cmspath.'COREPATH', COREPATH);
            }
            if (is_dir($cmspath.'CMSPATH')) {
                $this->_copy_dir($cmspath.'CMSPATH', COREPATH);
            }
            if (is_dir($cmspath.'CONFIGPATH')) {
                $this->_copy_dir($cmspath.'CONFIGPATH', CONFIGPATH);
            }
        }

        dr_dir_delete($cmspath, 1);

        file_put_contents(WRITEPATH.'update.lock', SYS_TIME);

        $this->_json(1, '<p><label class="rleft">升级完成</label><label class="rright"><span class="ok">完成</span></label></p>');
    }

    // 文件对比
    public function bf() {

        if ($this->cmf_license['oem']) {
            $this->_admin_msg(0, '无法使用此功能');
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '文件对比' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-code'],
                    'help' => [608],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('cloud_bf.html');exit;
    }

    public function bf_count() {

        $surl = 'https://www.xunruicms.com/version.php?action=bf_count&domain='
            .dr_get_domain_name(ROOT_URL).'&cms='.$this->version['id'].
            '&version='.$this->cmf_version['version'].'&time='.strtotime((string)$this->cmf_version['downtime'])
            .'&license='.$this->cmf_license['license'];
        $json = dr_catcher_data($surl);
        if (!$json) {
            $this->_json(0, '本站：没有从服务端获取到数据，检查本地环境是否支持远程下载功能');
        }

        $data = dr_string2array($json);
        if (!$data) {
			CI_DEBUG && log_message('error', '迅睿云端返回数据异常：'.$json);
            $this->_json(0, '本站：服务端数据异常，请重新再试');
        } elseif (!$data['code']) {
            $this->_json(0, $data['msg']);
        }

        \Phpcmf\Service::L('cache')->set_data('cloud-bf', $data['data'], 3600);

        $this->_json(dr_count($data['data']), $data['msg']);
    }

    public function bf_check() {

        $page = max(1, intval($_GET['page']));
        $cache = \Phpcmf\Service::L('cache')->get_data('cloud-bf');
        if (!$cache) {
            $this->_json(0, '本站：数据缓存不存在');
        }

        $data = $cache[$page];
        if ($data) {
            $html = '';
            foreach ($data as $filename => $value) {
                if (strpos($filename, 'Version.php')) {
                    continue;
                }
                if (strpos($filename, '/dayrui') === 0) {
                    $cname = 'FCPATH'.substr($filename, 7);
                    $ofile = FCPATH.substr($filename, 8);
                } else {
                    $cname = 'WEBPATH'.$filename;
                    $ofile = WEBPATH.substr($filename, 1);
                }
                if (CI_DEBUG) {
                    $cname = $ofile;
                }
                $class = '';
                if (!is_file($ofile)) {
                    $ok = "<span class='error'>不存在</span>";
                    $class = 'p_error';
                } elseif (md5_file($ofile) != $value) {
                    $ok = "<span class='error'>有变化</span>";
                    $class = 'p_error';
                } else {
                    $ok = "<span class='ok'>正常</span>";
                }
                $html.= '<p class="'.$class.'"><label class="rleft">'.$cname.'</label><label class="rright">'.$ok.'</label></p>';
                if ($class) {
                    $html.= '<p class="rbf" style="display: none"><label class="rleft">'.$cname.'</label><label class="rright">'.$ok.'</label></p>';
                }
            }
            $this->_json($page + 1, $html);
        }

        // 完成
        \Phpcmf\Service::L('cache')->clear('cloud-bf');
        $this->_json(100, '');
    }

    // 删除app的提示信息
    public function app_delete() {

        $dir = dr_safe_filename($_GET['dir']);
        if (!$dir) {
            $this->_json(0, dr_lang('目录不能为空'));
        }

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/App.php')) {
            $this->_json(0, dr_lang('目录%s不是一个有效的应用', $dir));
        }

        if (is_file($path.'install.lock')) {
            $this->_json(0, dr_lang('应用%s需要卸载后才能删除', $dir));
        }

        if (IS_POST) {

            dr_dir_delete($path, true);
            if (is_dir($path)) {
                $this->_json(0, dr_lang('目录未删除成功，建议手动删除该目录'), [
                    'time' => -1
                ]);
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        $files = [];
        if (is_file($path.'Files.txt')) {
            $arr = explode(',', file_get_contents($path.'Files.txt'));
            if ($arr) {
                foreach ($arr as $t) {
                    if (!$t) {
                        continue;
                    }
                    if (IS_DEV) {
                        $t = $this->_replace_path($t);
                    }
                    $files[] = $t;
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'dir' => $dir,
            'path' => IS_DEV ? $path : dr_safe_replace_path($path),
            'files' => $files,
        ]);
        \Phpcmf\Service::V()->display('cloud_app_delete.html');exit;
    }

    // app的文件列表
    public function app_file() {

        $dir = dr_safe_filename($_GET['dir']);
        if (!$dir) {
            $this->_json(0, dr_lang('目录不能为空'));
        }

        $path = dr_get_app_dir($dir);

        $files = [];
        if (is_file($path.'Files.txt')) {
            $arr = explode(',', file_get_contents($path.'Files.txt'));
            if ($arr) {
                foreach ($arr as $t) {
                    if (!$t) {
                        continue;
                    }
                    if (IS_DEV) {
                        $t = $this->_replace_path($t);
                    }
                    $files[] = $t;
                }
            }
        } else {
            $this->_json(0, dr_lang('没有找到相关文件'));
        }

        \Phpcmf\Service::V()->assign([
            'files' => $files,
        ]);
        \Phpcmf\Service::V()->display('cloud_app_file.html');exit;
    }

    // 服务器下载离线包
    public function down_app() {

        $id = dr_safe_replace($_GET['id']);
        if (!$id) {
            $this->_msg(0, '没有选择需要下载的程序');
        }

        $surl = $this->service_url.'&action=lx_admin&php='.PHP_VERSION.'&app_id='.$id.'&ls='.dr_safe_replace($_GET['ls']);
        dr_redirect($surl);
    }


    ///////////////////////////////////////////////////

    // 替换域名
    private function _replace_path($t) {
        return str_replace(
            [
                'WRITEPATH/',
                'ROOTPATH/',
                'WEBPATH/',
                'APPSPATH/',
                'TPLPATH/',
                'FCPATH/',
                'COREPATH/',
                'MYPATH/',
                'CSSPATH/',
            ],
            [
                WRITEPATH,
                ROOTPATH,
                WEBPATH,
                APPSPATH,
                TPLPATH,
                FCPATH,
                COREPATH,
                MYPATH,
                ROOTPATH.'static/',
            ],
            trim($t, '/')
        );
    }

    // 复制目录
    private function _copy_dir($src, $dst) {

        $dir = opendir($src);
        if (!is_dir($dst)) {
            @mkdir($dst);
        }

        $src = rtrim($src, '/');
        $dst = rtrim($dst, '/');

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file) ) {
                    dr_mkdirs($dst . '/' . $file);
                    $this->_copy_dir($src . '/' . $file, $dst . '/' . $file);
                    continue;
                } else {
                    dr_mkdirs(dirname($dst . '/' . $file));
                    $rt = copy($src . '/' . $file, $dst . '/' . $file);
                    if (!$rt) {
                        // 验证目标是不是空文件
                        if (filesize($src . '/' . $file) > 1) {
                            $this->_error_msg($dst . '/' . $file, '移动失败，检查文件目录权限');
                        }

                    }
                }
            }
        }
        closedir($dir);
    }

    // 版本日志
    function log_show() {
        $url = 'https://www.xunruicms.com/version.php?id='.\Phpcmf\Service::L('input')->get('id', true).'&version='.\Phpcmf\Service::L('input')->get('version', true);
        \Phpcmf\Service::V()->assign([
            'url' => $url,
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }

    // 获取本地程序和应用的版本号
    private function _get_app_version() {

        $data = [];
        $data['cms-1'] = $this->cmf_version['version'];
        $local = dr_dir_map(APPSPATH, 1);
        foreach ($local as $dir) {
            if (is_file(APPSPATH.$dir.'/Config/App.php') && is_file(APPSPATH.$dir.'/Config/Version.php')) {
                $cfg = require APPSPATH.$dir.'/Config/App.php';
                if ($cfg['type'] != 'module') {
                    $vsn = require APPSPATH.$dir.'/Config/Version.php';
                    $vsn['id'] && $data[$cfg['type'].'-'.$vsn['id']] = $vsn['version'];
                }
            }
        }

        return $data;
    }

    // 错误进度
    private function _error_msg($filename, $msg) {
        $html = '<p class=" p_error"><label class="rleft">'.$filename.'</label><label class="rright">'.$msg.'</label></p>';
        $this->_json(0, $html);
    }
}
