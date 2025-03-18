<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Home extends \Phpcmf\Common
{

	public function home() {
		$this->index();
	}

	public function main() {

        if (is_file(WRITEPATH.'update.txt')) {
            unlink(WRITEPATH.'update.txt');
            dr_redirect(dr_url('cache/index'));
        } elseif (is_file(WRITEPATH.'check.txt')) {
            unlink(WRITEPATH.'check.txt');
            dr_redirect(dr_url('check/index'));
        }

        $local = \Phpcmf\Service::Apps(1);
        if ($local) {
            foreach ($local as $dir => $path) {
                if (is_file(dr_get_app_dir($dir).'Config/Panel.php')) {
                    \Phpcmf\Service::V()->admin(dr_get_app_dir($dir).'Views/');
                    require dr_get_app_dir($dir).'Config/Panel.php';
                    exit;
                }
            }
        }

        $table_data = [];
        if (is_file(WRITEPATH.'config/main.php')) {
            $table_data = require WRITEPATH.'config/main.php';
        }

        // 验证权限
        if ($table_data && !dr_in_array(1, $this->admin['roleid'])) {
            // 不是超管用户
            $auth = \Phpcmf\Service::M('system')->get_setting('index_main');
            if ($auth) {
                foreach ($table_data as $name => $t) {
                    $key = md5($name);
                    if (isset($auth[$key]) && !dr_array_intersect($this->admin['roleid'], (array)$auth[$key])) {
                        unset($table_data[$name]); // 无权限移除
                    }
                }
            }
        }

        $menu = [
            '控制台' => ['home/main', 'fa fa-home'],
            '自定义控制台' => ['home/edit', 'fa fa-edit'],
            //'后台功能地图' => ['js:dr_sitemap', 'fa fa-sitemap'],
            '访问项目首页' => ['blank:api/gohome', 'fa fa-send'],
        ];
        if (!dr_in_array(1, $this->admin['roleid'])) {
            unset($menu['自定义控制台']);
        }

        $frame = [];
        if (IS_DEV || defined('DEMO_ADMIN_USERNAME')) {
            $frame = [
                'CodeIgniter',
                'CodeIgniter72',
                'ThinkPHP',
                'Laravel',
            ];
            foreach ($frame as $i => $name) {
                if (!is_file(FCPATH.$name.'/Init.php')) {
                    unset($frame[$i]);
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu($menu),
            'admin' => $this->admin,
            'frame' => dr_count($frame) > 1 ? $frame : [],
            'domain' => dr_get_domain_name(ROOT_URL),
            'license' => $this->cmf_license,
            'table_data' => $table_data,
            'cmf_update' => $this->cmf_version['updatetime'],
            'cmf_version' => $this->cmf_version['version'],
        ]);
		\Phpcmf\Service::V()->display($table_data ? 'index_main.html' : 'main.html');
	}

	public function init_edit() {

	    $file = WRITEPATH.'config/main.php';
		\Phpcmf\Service::L('Config')->file($file, '后台自定义面板', 32)->to_require([]);
		
		$this->_json(1, dr_lang('自定义面板恢复成功'));
	}

	//后台自定义面板
	public function edit() {

        if (!dr_in_array(1, $this->admin['roleid'])) {
            $this->_admin_msg(0, dr_lang('无权限操作'));
        }

	    $file = WRITEPATH.'config/main.php';

	    if (IS_POST) {
            $data = \Phpcmf\Service::L('input')->post('tables');
            \Phpcmf\Service::L('Config')->file($file, '后台自定义面板', 32)->to_require($data);
            $this->_json(1, dr_lang('操作成功'));
	        exit;
        }

        if (is_file($file)) {
            $data = require $file;
        } else {
	        $data = [];
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '控制台' => ['home/main', 'fa fa-home'],
                    '自定义控制台' => ['home/edit', 'fa fa-edit'],
                    'help' => [718],
                ]
            ),
            'tables' => $this->_main_table(),
            'table_data' => $data,
        ]);
		\Phpcmf\Service::V()->display('index_edit.html');
	}

	//后台自定义面板 权限划分
    public function auth_edit() {

        $name = \Phpcmf\Service::L('input')->get('name');
        $data = \Phpcmf\Service::M('system')->get_setting('index_main');

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post) {
                $post = [];
            }
            $post[] = 1;
            $data[$name] = array_unique($post);
            \Phpcmf\Service::M('system')->save_setting('index_main', $data);
            \Phpcmf\Service::M('system')->cache();
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data[$name],
            'form' => dr_form_hidden(),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
        ]);
        \Phpcmf\Service::V()->display('index_auth_edit.html');
    }

	public function index() {

        // 账号是否强制了简化模式
        if (!IS_API_HTTP) {
            if (\Phpcmf\Service::M('auth')->is_admin_min_mode()) {
                $this->min();exit;
            } elseif ($this->admin['setting']['admin_min']) {
                // 自己切换的
                $this->min();exit;
            }
        }

        $menu = \Phpcmf\Service::L('cache')->get('menu-admin');
        if (!$menu) {
            $m = \Phpcmf\Service::M('menu')->cache();
            $menu = $m['admin'];
        }

        $first = 0;
        $mstring = $string = '';
        $menu_top = $my_menu = [];
		if ($this->admin['adminid'] > 1) {
			foreach ($menu as $t) {
				dr_in_array($t['mark'], $this->admin['system']['mark']) && $my_menu[$t['id']] = $t;
			}
		} else {
			$my_menu = $menu;
		}

        // 挂钩点
        $rt = \Phpcmf\Hooks::trigger_callback('admin_menu', $menu, $my_menu);
        if ($rt && isset($rt['code']) && $rt['code']) {
            $my_menu = $rt['msg'];
        }

        // 默认的首页内容
        $main_url = dr_url('home/main');
        $main_link = '';
        $main_menu = [];
        if (isset($_GET['go']) && $_GET['go']) {
            $go = urldecode((string)\Phpcmf\Service::L('input')->get('go'));
            $url = parse_url($go);
            if (isset($url['query']) && $url['query']) {
                parse_str($url['query'], $p);
                $uri = trim($p['s'].'/'.$p['c'].'/'.$p['m'], '/');
                $main_menu = \Phpcmf\Service::L('cache')->get('menu-admin-uri', $uri);
                if ($main_menu) {
                    $first = $main_menu['tid'];
                    $main_url = dr_url($uri);
                    $main_link = 'Mlink('.$main_menu['tid'].', '.$main_menu['pid'].', '.$main_menu['id'].', \'\');';
                }
            }
        }

        if ($my_menu) {
            // 加载全部插件的
            $local = \Phpcmf\Service::Apps();
            foreach ($local as $dir => $path) {
                // 判断插件目录
                if (is_file($path.'install.lock') && is_file($path.'Models/Auth.php') && is_file($path.'Config/App.php')) {
                    $cfg = require $path.'Config/App.php';
                    if ($cfg['type'] == 'app' && !$cfg['ftype']) {
                        $obj = \Phpcmf\Service::M('auth', $dir);
                        if (method_exists($obj, 'get_admin_menu_data')) {
                            $my_menu = $obj->get_admin_menu_data($my_menu);
                        }
                    }
                }
            }
		    // 权限判断并筛选
            foreach ($my_menu as $tid => $top) {
                if (!$top['left']) {
                    unset($my_menu[$tid]);
                    continue; // 没有分组菜单就不要
                } elseif (SITE_ID > 1 && !dr_in_array(SITE_ID, $top['site'])) {
                    unset($my_menu[$tid]);
                    continue; // 没有划分本站点就不显示
                } elseif ($top['mark'] && strpos($top['mark'], 'app-') === 0) {
                    // 判断应用模块权限
                    list($a, $mm) = explode('-', $top['mark']);
                    if ($mm) {
                        $mp = dr_get_app_dir($mm);
                        if (is_file($mp.'Config/App.php')) {
                            $config = require $mp.'Config/App.php';
                            // 如果是内容模块
                            if ((isset($config['ftype']) && $config['ftype'] == 'module') || $config['type'] == 'module') {
                                if (!$this->get_cache('module-'.SITE_ID.'-content', $mm)) {
                                    unset($top[$tid]);
                                    unset($my_menu[$tid]);
                                    continue;
                                }
                            }
                        }
                    }
                }
                $left_string = '';
                !$first && $first = $tid;
                foreach ($top['left'] as $if => $left) {
                    if (!$left['link']) {
                        unset($top['left'][$if]);
                        unset($my_menu[$tid]['left'][$if]);
                        continue; // 没有链接菜单就不要
                    } elseif (SITE_ID > 1 && !dr_in_array(SITE_ID, $left['site'])) {
                        unset($top['left'][$if]);
                        unset($my_menu[$tid]['left'][$if]);
                        continue; // 没有划分本站点就不显示
                    }
                    // 链接菜单开始
                    $link_string = '';
                    foreach ($left['link'] as $i => $link) {
                        if (SITE_ID > 1 && !dr_in_array(SITE_ID, $link['site'])) {
                            unset($left['link'][$i]);
                            unset($my_menu[$tid]['left'][$if]['link'][$i]);
                            continue; // 没有划分本站点就不显示
                        } elseif ($link['uri'] && !\Phpcmf\Service::M('auth')->_is_admin_auth($link['uri'], 1)) {
                            // 判断权限
                            unset($left['link'][$i]);
                            unset($my_menu[$tid]['left'][$if]['link'][$i]);
                            continue;
                        } elseif ($link['mark'] && $left['mark'] == 'content-module') {
                            // 内容模块权限判断
                            list($ac, $name, $cname) = explode('-', $link['mark']);
                            if ($ac == 'module' && !$this->get_cache('module-'.SITE_ID.'-content', $name)) {
                                unset($left['link'][$i]);
                                unset($my_menu[$tid]['left'][$if]['link'][$i]);
                                continue;
                            }
                            // 网站表单权限判断
                            if ($ac == 'app' && $name == 'form' && !$this->get_cache('form-'.SITE_ID, $cname)) {
                                unset($left['link'][$i]);
                                unset($my_menu[$tid]['left'][$if]['link'][$i]);
                                continue;
                            }
                        } elseif (SITE_ID > 1 && $link['uri'] && $link['uri'] == 'cloud/local') {
                            // 多站点不显示应用
                            unset($left['link'][$i]);
                            unset($my_menu[$tid]['left'][$if]['link'][$i]);
                            continue;
                        } elseif ($link['mark'] && $left['mark'] == 'content-verify') {
                            // 内容模块审核部分权限判断
                            list($ac, $ab, $name, $cc, $dd) = explode('-', $link['mark']);
                            if ($ac.'-'.$ab == 'verify-module' && !$this->get_cache('module-'.SITE_ID.'-content', $name)) {
                                unset($left['link'][$i]);
                                unset($my_menu[$tid]['left'][$if]['link'][$i]);
                                continue;
                            }
                        } elseif (IS_OEM_CMS && $link['uri'] == 'cloud/bf') {
                            // oem版排除对比菜单
                            unset($left['link'][$i]);
                            unset($my_menu[$tid]['left'][$if]['link'][$i]);
                            continue;
                        }

                        $left['link'][$i]['url'] = $link['url'] ? $link['url'] : \Phpcmf\Service::L('Router')->url($link['uri']);
                        $left['link'][$i]['icon'] = $link['icon'] ? $link['icon'] : 'fa fa-th-large';

                        if (!$main_menu) {
                            $main_menu = $link;
                        }
                        $link_string = 'true';
                    }
                    if (!$link_string) {
                        unset($top['left'][$if]);
                        unset($my_menu[$tid]['left'][$if]);
                        continue; // 没有链接菜单就不要
                    }
                    $left_string = 'true';
                    $top['left'][$if] = $left;
                }
                if (!$left_string) {
                    $first == $tid && $first = 0;
                    unset($my_menu[$tid]);
                    continue; // 没有分组菜单就不要
                }
                $my_menu[$tid] = $top;
                unset($top['left']);
                $menu_top[$tid] = $top;
            }
        }
        if (!$menu_top && SITE_ID > 1) {
            $this->_admin_msg(0, dr_lang('没有给当前站点分配管理菜单权限'));
        }

        if (IS_API_HTTP) {
            $vue_menu = [];
            foreach ($my_menu as $i => $top) {
                $vue_menu[$i] = [
                    'heading' => $top['name'],
                    'route' => $top['mark'],
                    'fontIcon' => $top['icon'],
                    'pages' => [],
                ];
                foreach ($top['left'] as $f => $left) {
                    $vue_menu[$i]['pages'][$f] = [
                        'sectionTitle' => $left['name'],
                        'route' => $left['mark'],
                        'fontIcon' => $left['icon'],
                        'sub' => [],
                    ];
                    foreach ($left['link'] as $l => $link) {
                        $vue_menu[$i]['pages'][$f]['sub'][] = [
                            'heading' => $link['name'],
                            'route' => '/'.$link['uri'],
                            'fontIcon' => $link['icon'],
                        ];
                    }
                }
            }
            $this->_json(1, 'ok', ['menu' => $vue_menu]);
        }

		\Phpcmf\Service::V()->assign([
			'top' => $menu_top,
			'first' => $first,
            'my_menu' => $my_menu,
            'is_index' => 1,
            'main_url' => $main_url,
            'main_link' => $main_link,
            'main_menu' => $main_menu,
            'is_search_help' => IS_OEM_CMS ? 0 : CI_DEBUG,
        ]);
		\Phpcmf\Service::V()->display('index.html');exit;
	}

	// 简化模式界面
    public function min() {

        $menu = \Phpcmf\Service::L('cache')->get('menu-admin-min');
        if (!$menu) {
            $m = \Phpcmf\Service::M('menu')->cache();
            $menu = $m['admin-min'];
        }

        $admin_menu = \Phpcmf\Service::L('cache')->get('menu-admin-uri');

        $my_menu = $menu;

        // 默认的首页内容
        $main_url = dr_url('home/main');
        $main_link = '';
        $main_menu = [];
        if (isset($_GET['go']) && $_GET['go']) {
            $go = urldecode((string)\Phpcmf\Service::L('input')->get('go'));
            $url = parse_url($go);
            if (isset($url['query']) && $url['query']) {
                parse_str($url['query'], $p);
                $uri = trim($p['s'].'/'.$p['c'].'/'.$p['m'], '/');
                $main_menu = \Phpcmf\Service::L('cache')->get('menu-admin-uri', $uri);
                if ($main_menu) {
                    $first = $main_menu['tid'];
                    $main_url = dr_url($uri);
                    $main_link = 'Mlink('.$main_menu['tid'].', '.$main_menu['pid'].', '.$main_menu['id'].', \'\');';
                }
            }
        }

        if ($my_menu) {
            // 加载全部插件的
            $local = \Phpcmf\Service::Apps();
            foreach ($local as $dir => $path) {
                // 判断插件目录
                if (is_file($path.'install.lock') && is_file($path.'Models/Auth.php') && is_file($path.'Config/App.php')) {
                    $cfg = require $path.'Config/App.php';
                    if ($cfg['type'] == 'app' && !$cfg['ftype']) {
                        $obj = \Phpcmf\Service::M('auth', $dir);
                        if (method_exists($obj, 'get_admin_menu_data')) {
                            $my_menu = $obj->get_admin_menu_data($my_menu);
                        }
                    }
                }
            }
            // 权限判断并筛选
            foreach ($my_menu as $tid => $left) {
                if (!$left['link']) {
                    continue; // 没有分组菜单就不要
                }
                // 链接菜单开始
                $link_string = '';
                foreach ($left['link'] as $i => $link) {
                    if ($link['uri'] && !$this->_is_admin_auth($link['uri'])) {
                        // 判断权限
                        unset($left['link'][$i]);
                        continue;
                    } elseif ($link['mark'] && $left['mark'] == 'content-module') {
                        // 内容模块权限判断
                        list($ac, $name) = explode('-', $link['mark']);
                        if ($ac == 'module' && !$this->get_cache('module-'.SITE_ID.'-content', $name)) {
                            unset($left['link'][$i]);
                            continue;
                        }
                        // 网站表单权限判断
                        list($ac, $name, $cname) = explode('-', $link['mark']);
                        if ($ac == 'app' && $name == 'form' && !$this->get_cache('form-'.SITE_ID, $cname)) {
                            unset($left['link'][$i]);
                            continue;
                        }
                    } elseif (SITE_ID > 1 && $link['uri'] && $admin_menu[$link['uri']] && !dr_in_array(SITE_ID, $admin_menu[$link['uri']]['site'])) {
                        // 没有划分本站点就不显示
                        unset($left['link'][$i]);
                        continue;
                    } elseif (SITE_ID > 1 && $link['uri'] && $link['uri'] == 'cloud/local') {
                        // 多站点不显示应用
                        unset($left['link'][$i]);
                        continue;
                    } elseif ($link['mark'] && $left['mark'] == 'content-form') {
                        // 网站表单权限判断
                    } elseif ($link['mark'] && $left['mark'] == 'content-verify') {
                        // 内容模块审核部分权限判断
                        list($ac, $ab, $name, $cc) = explode('-', $link['mark']);
                        if ($ac.'-'.$ab == 'verify-module' && !$this->get_cache('module-'.SITE_ID.'-content', $name)) {
                            unset($left['link'][$i]);
                            continue;
                        }
                    }
                    $left['link'][$i]['url'] = $link['url'] ? $link['url'] :\Phpcmf\Service::L('Router')->url($link['uri']);
                    $left['link'][$i]['icon'] = $link['icon'] ? $link['icon'] : 'fa fa-th-large';
                    $link_string.= 'true';
                    if (!$main_menu) {
                        $first = $tid;
                        $main_menu = $link;
                    }
                }
                if (!$link_string) {
                    unset($my_menu[$tid]);
                    continue; // 没有链接菜单就不要
                }
                $my_menu[$tid] = $left;
            }
        }
        \Phpcmf\Service::V()->assign([
            'first' => $first,
            'is_min' => 1,
            'is_mode' => \Phpcmf\Service::M('auth')->is_admin_min_mode(),
            'my_menu' => $my_menu,
            'is_index' => 1,
            'main_url' => $main_url,
            'main_link' => $main_link,
            'main_menu' => $main_menu,
            'is_search_help' => IS_OEM_CMS ? 0 : CI_DEBUG,
        ]);
        \Phpcmf\Service::V()->display('index_min.html');exit;
    }
}
