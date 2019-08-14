<?php namespace Phpcmf\Controllers\Admin;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


// 云服务
class Cloud extends \Phpcmf\Common
{
    private $admin_url;
    private $service_url;
    private $license;
    private $version;
    private $license_sn;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        if (is_file(MYPATH.'Config/Version.php')) {
            $this->version = require MYPATH.'Config/Version.php';
        } else {
            exit('程序需要更新到正式版，请在官网 http://www.xunruicms.com/down/ 下载[安装包]并覆盖dayrui目录');
        }

        if (is_file(MYPATH.'Config/License.php')) {
            $this->license = require MYPATH.'Config/License.php';
            if (!$this->license['license']) {
                exit('程序不是最新，请在官网下载[安装包]并覆盖dayrui目录');
            }
        } else {
            exit('程序需要更新到正式版，请在官网 http://www.xunruicms.com/down/ 下载[安装包]并覆盖dayrui目录');
        }

        $this->license_sn = $this->license['license'];
        \Phpcmf\Service::V()->assign([
            'license' => $this->license,
            'license_sn' => $this->license_sn,
            'cms_version' => $this->version,
            'cmf_version' => $this->cmf_version,
        ]);
        list($this->admin_url) = explode('?', FC_NOW_URL);
        $this->service_url = 'https://www.xunruicms.com/cloud.php?domain='.dr_get_domain_name(ROOT_URL).'&admin='.urlencode($this->admin_url).'&cms='.$this->version['id'].'&license='.$this->license_sn;

    }

    // 服务工单
    public function service() {

        \Phpcmf\Service::V()->assign([
            'url' => 'https://www.xunruicms.com/service.php?cms='.$this->version['id'].'&license='.$this->license_sn,
        ]);
        \Phpcmf\Service::V()->display('cloud_online.html');exit;
    }

    // 我的网站
    public function index() {

        $domain = dr_get_domain_name(ROOT_URL);
        $license_domain = 'https://www.xunruicms.com/license/domain/'.$domain;
        if ($this->license['domain']) {
            $license_domain = trim($this->license['domain'], '/').'/index.php?s=license&m=show&domain='.$domain;
        }
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '我的网站' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-cog'],
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
        $local = dr_dir_map(dr_get_app_list(), 1);
        foreach ($local as $dir) {
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

    //
    public function update_sn() {

    }


    // 本地应用
    public function local() {

        $data = [];
        $local = dr_dir_map(dr_get_app_list(), 1);
        foreach ($local as $dir) {
            $path = dr_get_app_dir($dir);
            if (is_file($path.'Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require $path.'Config/App.php';
                if (($cfg['type'] != 'module' || $cfg['ftype'] == 'module') && is_file($path.'Config/Version.php')) {
                    $vsn = require $path.'Config/Version.php';
                    $data[$key] = [
                        'name' => $cfg['name'],
                        'type' => $cfg['type'],
                        'icon' => $cfg['icon'],
                        'author' => $cfg['author'],
                        'store' => $vsn['store'],
                        'version' => $vsn['version'],
                        'install' => is_file($path.'install.lock'),
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
        \Phpcmf\Service::V()->display('cloud_app.html');exit;
    }

    // 安装程序
    public function install() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        !is_file(dr_get_app_dir($dir).'Config/App.php') && $this->_json(0, dr_lang('安装程序App.php不存在'));

        // 开始安装
        $rt = \Phpcmf\Service::M('App')->install($dir);
        !$rt['code'] && $this->_json(0, $rt['msg']);

        \Phpcmf\Service::M('cache')->sync_cache('');
        $this->_json(1, dr_lang('安装成功，请刷新后台页面'));
    }

    // 卸载程序
    public function uninstall() {

        $dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
        !preg_match('/^[a-z]+$/U', $dir) && $this->_json(0, dr_lang('目录[%s]格式不正确', $dir));

        $path = dr_get_app_dir($dir);
        !is_dir($path) && $this->_json(0, dr_lang('目录[%s]不存在', $path));

        $rt = \Phpcmf\Service::M('App')->uninstall($dir);

        \Phpcmf\Service::M('cache')->sync_cache('');
        $this->_json($rt['code'], $rt['msg']);
    }

    // 验证授权
    public function login() {

        if (IS_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $surl = $this->service_url.'&action=update_login&get_http=1&username='.$post['username'].'&password='.md5($post['password']);
            $json = dr_catcher_data($surl);
            if (!$json) {
                $this->_json(0, '没有从服务端获取到数据');
            }
            exit($json);
        }

        \Phpcmf\Service::V()->display('cloud_login.html');exit;
    }

    // 下载程序
    function down_file() {
        \Phpcmf\Service::V()->assign([
            'ls' => intval($_GET['ls']),
            'app_id' => 'app-'.intval($_GET['cid']),
        ]);
        \Phpcmf\Service::V()->display('cloud_down_file.html');exit;
    }


    // 将下载程序安装到目录中
    function install_app() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->init()->get('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '授权验证过期，请重试');
        } elseif (!$cache['dir']) {
            $this->_json(0, '缺少程序安装目录名称');
        }

        $file = WRITEPATH.'temp/'.$id.'.zip';
        if (!is_file($file)) {
            $this->_json(0, '本站：文件还没有被下载');
        }

        // 解压目录
        $cmspath = WRITEPATH.'temp/'.$id.'/';
        if (!\Phpcmf\Service::L('file')->unzip($file, $cmspath)) {
            cloud_msg(0, '本站：文件解压失败');
        }
        unlink($file);

		if (is_file($cmspath.'APPSPATH/'.ucfirst($cache['dir']).'/install.lock')) {
			unlink($cmspath.'APPSPATH/'.ucfirst($cache['dir']).'/install.lock');
		}

        // 复制文件到程序
        if (is_dir($cmspath.'APPSPATH')) {
            $this->_copy_dir($cmspath.'APPSPATH', APPSPATH);
        }
        if (is_dir($cmspath.'WEBPATH')) {
            $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH);
        }
        if (is_dir($cmspath.'ROOTPATH')) {
            $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH);
        }
        if (is_dir($cmspath.'CSSPATH')) {
            $this->_copy_dir($cmspath.'CSSPATH/', ROOTPATH.'static/');
        }
        if (is_dir($cmspath.'TPLPATH')) {
            $this->_copy_dir($cmspath.'TPLPATH', TPLPATH);
        }
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

        $this->_json(1, '程序导入完成<br>1、如果本程序是应用插件：请到【应用】-【应用管理】中手动安装本程序<br>2、如果本程序是组件：请按本组件的使用教程来操作；<br>3、如果本程序是模板：请按本模板使用教程来操作');
    }

    // 程序升级
    public function update() {

        $data = [];

        $data['phpcmf'] = $this->cmf_version;
        $data['phpcmf']['id'] = 'cms-'.$this->cmf_version['id'];
        $data['phpcmf']['tname'] = '<a href="javascript:dr_help(538);">系统</a>';

        if (!in_array($this->version['id'], [10, 11]) && is_file(MYPATH.'Config/Version.php')) {
            $data['my'] = require MYPATH.'Config/Version.php';
            $cms_id = $data['my']['id'];
            $data['my']['id'] = 'cms-'.$cms_id;
            $data['my']['tname'] = '<a href="javascript:dr_help(539);">程序</a>';
        }

        $local = dr_dir_map(APPSPATH, 1);
        foreach ($local as $dir) {
            if (is_file(APPSPATH.$dir.'/Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require APPSPATH.$dir.'/Config/App.php';
                if ($cfg['type'] != 'module' && is_file(APPSPATH.$dir.'/Config/Version.php')) {
                    $vsn = require APPSPATH.$dir.'/Config/Version.php';
                    $vsn['id'] && $data[$key] = [
                        'id' => $cfg['type'].'-'.$vsn['id'],
                        'name' => $cfg['name'],
                        'type' => $cfg['type'],
                        'tname' => '<a href="javascript:dr_help(540);">应用</a>',
                        'version' => $vsn['version'],
                        'license' => $vsn['license'],
                        'updatetime' => $vsn['updatetime'],
                    ];
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'list' => $data,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '版本升级' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-refresh'],
                    '文件对比' => [\Phpcmf\Service::L('Router')->class.'/bf', 'fa fa-code'],
                    'help' => [379],
                ]
            ),
            'cms_id' => $cms_id,
            'domain_id' => $this->license['id'],
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
                !dr_check_put_path($t) && $this->_json(0, dr_lang('目录【%s】不可写', $t));
            }
        }

        $vid = dr_safe_replace($_GET['version']);
        $surl = $this->service_url.'&action=check_version&get_http=1&id='.$cid.'&version='.$vid;
        $json = dr_catcher_data($surl);
        !$json ? $this->_json(0, '没有从服务端获取到数据') : exit($json);
    }

    // 执行更新程序的界面
    public function todo_update() {

        \Phpcmf\Service::V()->assign([
            'app_id' => dr_safe_replace($_GET['id']),
        ]);
        \Phpcmf\Service::V()->display('cloud_todo_update.html');exit;
    }

    // 服务器下载升级文件
    public function update_file() {

        $id = dr_safe_replace($_GET['id']);
        if (!$id) {
            $this->_json(0, '没有选择任何升级程序');
        }

        $surl = $this->service_url.'&action=update_file&get_http=1&appid='.$id;
        $json = dr_catcher_data($surl);
        if (!$json) {
            $this->_json(0, '没有从服务端获取到数据', $surl);
        }

        $data = dr_string2array($json);
        if (!$data) {
            $this->_json(0, '服务端数据异常，请重新下载', $json);
        } elseif (!$data['code']) {
            $this->_json(0, $data['msg']);
        } elseif (!$data['data']['size']) {
            $this->_json(0, '服务端文件总大小异常');
        } elseif (!$data['data']['url']) {
            $this->_json(0, '服务端文件下载地址异常');
        }

        \Phpcmf\Service::L('cache')->init()->save('cloud-update-'.$id, $data['data'], 3600);

        $this->_json(1, 'ok', $data['data']);
    }
    // 开始下载脚本
    public function update_file_down() {
        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->init()->get('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '授权验证过期，请重试');
        } elseif (!$cache['size']) {
            $this->_json(0, '关键数据不存在，请重试');
        }
        // 执行下载文件
        $file = WRITEPATH.'temp/'.$id.'.zip';

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
            $this->_json(0, '本站：fopen打开远程文件失败', $cache['url']);
        }
    }
    // 检测下载进度
    public function update_file_check() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->init()->get('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '本站：授权验证过期，请重试');
        } elseif (!$cache['size']) {
            $this->_json(0, '本站：关键数据不存在，请重试');
        }

        // 执行下载文件
        $file = WRITEPATH.'temp/'.$id.'.zip';
        if (is_file($file)) {
            $now = max(1, filesize($file));
            $jd = max(1, round($now / $cache['size'] * 100, 0));
            $this->_json($jd, '<p><label class="rleft">需下载文件大小：'.dr_format_file_size($cache['size']).'，已下载：'.dr_format_file_size($now).'</label><label class="rright"><span class="ok">'.$jd.'%</span></label></p>');
        } else {
            $this->_json(0, '本站：文件还没有被下载');
        }
    }
    // 升级程序
    public function update_file_install() {

        $id = dr_safe_replace($_GET['id']);
        $cache = \Phpcmf\Service::L('cache')->init()->get('cloud-update-'.$id);
        if (!$cache) {
            $this->_json(0, '授权验证过期，请重试');
        }

        $file = WRITEPATH.'temp/'.$id.'.zip';
        if (!is_file($file)) {
            $this->_json(0, '本站：文件还没有被下载');
        }

        // 解压目录
        $cmspath = WRITEPATH.'temp/'.$id.'/';
        if (!\Phpcmf\Service::L('file')->unzip($file, $cmspath)) {
            cloud_msg(0, '本站：文件解压失败');
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
            $this->_copy_dir($cmspath, ROOTPATH);

        } else {
            // 插件部分

            if (is_file($cmspath.'APPSPATH/'.ucfirst($cache['dir']).'/install.lock')) {
                unlink($cmspath.'APPSPATH/'.ucfirst($cache['dir']).'/install.lock');
            }

            // 复制文件到程序
            if (is_dir($cmspath.'APPSPATH')) {
                $this->_copy_dir($cmspath.'APPSPATH', APPSPATH);
            }
            if (is_dir($cmspath.'WEBPATH')) {
                $this->_copy_dir($cmspath.'WEBPATH', ROOTPATH);
            }
            if (is_dir($cmspath.'ROOTPATH')) {
                $this->_copy_dir($cmspath.'ROOTPATH', ROOTPATH);
            }
            if (is_dir($cmspath.'CSSPATH')) {
                $this->_copy_dir($cmspath.'CSSPATH/', ROOTPATH.'static/');
            }
            if (is_dir($cmspath.'TPLPATH')) {
                $this->_copy_dir($cmspath.'TPLPATH', TPLPATH);
            }
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
        }

        dr_dir_delete($cmspath, 1);

        $this->_json(1, '<p><label class="rleft">升级完成</label><label class="rright"><span class="ok">完成</span></label></p>');
    }

    // 文件对比
    public function bf() {

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

        $surl = 'https://www.xunruicms.com/version.php?action=bf_count&domain='.dr_get_domain_name(ROOT_URL).'&cms='.$this->version['id'].'&license='.$this->license_sn;
        $json = dr_catcher_data($surl);
        if (!$json) {
            $this->_json(0, '没有从服务端获取到数据');
        }

        $data = dr_string2array($json);
        if (!$data) {
            $this->_json(0, '服务端数据异常，请重新再试');
        } elseif (!$data['code']) {
            $this->_json(0, $data['msg']);
        }

        \Phpcmf\Service::L('cache')->init()->save('cloud-bf', $data['data'], 3600);

        $this->_json(dr_count($data['data']), $data['msg']);
    }

    public function bf_check() {

        $page = max(1, intval($_GET['page']));
        $cache = \Phpcmf\Service::L('cache')->init()->get('cloud-bf');
        !$cache && $this->_json(0, '数据缓存不存在');

        $data = $cache[$page];
        if ($data) {
            $html = '';
            foreach ($data as $filename => $value) {
                if (strpos($filename, '/dayrui') === 0) {
                    $cname = 'FCPATH'.substr($filename, 7);
                    $ofile = FCPATH.substr($filename, 8);
                } else {
                    $cname = 'WEBPATH'.$filename;
                    $ofile = WEBPATH.substr($filename, 1);
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
                    $html.= '<p class="rbf" style="display: none"><label class="rleft">'.$ofile.'</label><label class="rright">'.$ok.'</label></p>';
                }
            }
            $this->_json($page + 1, $html);
        }

        // 完成
        \Phpcmf\Service::L('cache')->clear('cloud-bf');
        $this->_json(100, '');
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
                            $this->_error_msg($dst . '/' . $file, '移动失败');
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
        if (is_file(CMSPATH.'Config/Version.php')) {
            $cms = require CMSPATH.'Config/Version.php';
            $data['cms-'.$cms['id']] = $cms['version'];
        }
        $cms = require MYPATH.'Config/Version.php';
        $data['cms-'.$cms['id']] = $cms['version'];
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
