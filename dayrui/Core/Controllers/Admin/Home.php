<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



class Home extends \Phpcmf\Common
{

	public function home() {
		$this->index();
	}

	public function main() {

        $table_data = [];
        if (is_file(WRITEPATH.'config/main.php')) {
            $table_data = require WRITEPATH.'config/main.php';
        }

        $license = [];
        if (is_file(MYPATH.'Config/License.php')) {
            $license = require MYPATH.'Config/License.php';
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '控制台' => ['home/main', 'fa fa-home'],
                    '自定义控制台' => ['home/edit', 'fa fa-edit'],
                    '访问网站首页' => ['blank:api/gohome', 'fa fa-send'],
                ]
            ),
            'color' => ['blue', 'red', 'green', 'dark', 'yellow'],
            'domain' => dr_get_domain_name(ROOT_URL),
            'tables' => $this->_main_table(),
            'license' => $license,
            'table_data' => $table_data,
            'cmf_update' => $this->cmf_version['updatetime'],
            'cmf_version' => $this->cmf_version['version'],
        ]);
		\Phpcmf\Service::V()->display($table_data ? 'index_main.html' : 'main.html');exit;
	}

	public function init_edit() {

	    $file = WRITEPATH.'config/main.php';
		\Phpcmf\Service::L('Config')->file($file, '后台自定义面板', 32)->to_require([]);
		
		$this->_json(1, dr_lang('自定义面板恢复成功'));
	}

	public function edit() {

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
		\Phpcmf\Service::V()->display('index_edit.html');exit;
	}

	public function index() {

        $menu_top = $my_menu = [];
		$menu = \Phpcmf\Service::L('cache')->get('menu-admin');
        if (!$menu) {
            $m = \Phpcmf\Service::M('menu')->cache();
            $menu = $m['admin'];
        }

        // 自定义后台菜单显示
        if (function_exists('dr_my_admin_menu')) {
            $menu = dr_my_admin_menu($menu);
        }

		if ($this->admin['adminid'] > 1) {
			foreach ($menu as $t) {
				@in_array($t['mark'], $this->admin['system']['mark']) && $my_menu[$t['id']] = $t;
			}
		} else {
			$my_menu = $menu;
		}

		$first = 0;
		$string = '';
        $mstring = '';

        if ($my_menu) {
            foreach ($my_menu as $tid => $top) {
                if (!$top['left']) {
                    continue; // 没有分组菜单就不要
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
                                    continue;
                                }
                            }
                        }
                    }
                }
                $_left = 0; // 是否第一个分组菜单，0表示第一个
                $_link = 0; // 是否第一个链接菜单，0表示第一个
                $left_string = '';
                $mleft_string = [];
                !$first && $first = $tid;
                foreach ($top['left'] as $if => $left) {
                    if (!$left['link']) {
                        unset($top['left'][$if]);
                        continue; // 没有链接菜单就不要
                    }
                    // 链接菜单开始
                    $link_string = '';
                    $mlink_string = '';
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
                        } elseif (SITE_ID > 1 && $link['uri'] && $link['uri'] == 'cloud/local') {
                            // 多站点不显示应用
                            unset($left['link'][$i]);
                            continue;
                        } elseif ($link['mark'] && $left['mark'] == 'content-form') {
                            // 网站表单权限判断
                            list($ac, $name) = explode('-', $link['mark']);
                            if ($ac == 'form' && !$this->get_cache('form-'.SITE_ID, $name)) {
                                unset($left['link'][$i]);
                                continue;
                            }
                        } elseif ($link['mark'] && $left['mark'] == 'content-verify') {
                            // 内容模块审核部分权限判断
                            list($ac, $ab, $name, $cc) = explode('-', $link['mark']);
                            if ($ac.'-'.$ab == 'verify-module' && !$this->get_cache('module-'.SITE_ID.'-content', $name)) {
                                unset($left['link'][$i]);
                                continue;
                            } elseif ($ac.'-'.$ab == 'verify-comment' && !$this->get_cache('module-'.SITE_ID.'-content', $name, 'comment')) {
                                unset($left['link'][$i]);
                                continue;
                            } elseif ($ac.'-'.$ab == 'verify-mform' && !$this->get_cache('module-'.SITE_ID.'-'.$name, 'form', $cc)) {
                                unset($left['link'][$i]);
                                continue;
                            } elseif ($ac.'-'.$ab == 'verify-form' && !$this->get_cache('form-'.SITE_ID, $name)) {
                                unset($left['link'][$i]);
                                continue;
                            }
                        }
                        $url = $link['url'] ? $link['url'] :\Phpcmf\Service::L('Router')->url($link['uri']);
                        if (!$_link) {
                            // 第一个链接菜单时 指定class
                            $class = 'nav-item active open';
                            $top['url'] = $url;
                            $top['link_id'] = $link['id'];
                            $top['left_id'] = $left['id'];
                        } else {
                            $class = 'nav-item';
                        }
                        $_link = 1; // 标识以后的菜单就不是第一个了
                        $link['icon'] = $link['icon'] ? $link['icon'] : 'fa fa-th-large';
                        $link_string.= '<li id="dr_menu_link_'.$link['id'].'" class="'.$class.'"><a href="javascript:Mlink('.$tid.', '.$left['id'].', '.$link['id'].', \''.$url.'\');"><i class="iconm '.$link['icon'].'"></i> <span class="title">'.dr_lang($link['name']).'</span></a></li>';
                        $mlink_string.= '<li id="dr_menu_m_link_'.$link['id'].'" class="'.$class.'"><a href="javascript:Mlink('.$tid.', '.$left['id'].', '.$link['id'].', \''.$url.'\');"><i class="iconm '.$link['icon'].'"></i> <span class="title">'.dr_lang($link['name']).'</span></a></li>';
                    }
                    if (!$link_string) {
                        continue; // 没有链接菜单就不要
                    }
                    $left_string.= '
				<li id="dr_menu_left_'.$left['id'].'" class="dr_menu_'.$tid.' dr_menu_item nav-item '.($_left ? '' : 'active open').' " style="'.($first==$tid ? '' : 'display:none').'">
                    <a href="javascript:;" class="nav-link nav-toggle">
                        <i class="'.$left['icon'].'"></i>
                        <span class="title">'.dr_strcut(dr_lang($left['name']), 5).'</span>
                        <span class="selected" style="'.($_left ? 'display:none' : '').'"></span>
                        <span class="arrow '.($_left ? '' : ' open').'"></span>
                    </a>
					<ul class="sub-menu">'.$link_string.'</ul>
				</li>';
                    $mleft_string[] = $mlink_string;
                    $_left = 1; // 标识以后的菜单就不是第一个了
                }
                if (!$left_string) {
                    $first == $tid && $first = 0;
                    continue; // 没有分组菜单就不要
                }
                $string.= $left_string;
                /*
                $mstring.= '<div class="btn-group pull-left" id="dr_m_top_'.$tid.'" style=" '.($first == $tid ? '' : 'display:none').'">
                    <button type="button" class="btn green btn-sm btn-outline dropdown-toggle" data-toggle="dropdown" aria-expanded="false"> '.dr_lang($top['name']).'
                        <i class="fa fa-angle-down"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        '.implode($mleft_string, '<li class="divider"> </li>').'
                    </ul>
                </div>';<li class="dropdown dropdown-user">*/
                $mstring.= '<li class="dropdown dropdown-extended dropdown-tasks fc-mb-sum-menu" id="dr_m_top_'.$tid.'" style=" '.($first == $tid ? '' : 'display:none').'">
                    <a  class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"> 
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        '.implode($mleft_string, '<li class="divider"> </li>').'
                    </ul>
                </li>';
                unset($top['left']);
                $menu_top[$tid] = $top;
            }
        }

		\Phpcmf\Service::V()->assign([
			'top' => $menu_top,
			'first' => $first,
			'string' => $string,
			'mstring' => $mstring,
            'is_mobile' => $this->_is_mobile(),
			'sys_color' => [
				'default' => '#333438',
				'blue' => '#368ee0',
				'darkblue' => '#2b3643',
				'grey' => '#697380',
				'light' => '#F9FAFD',
				'light2' => '#F1F1F1',
			]
        ]);
		\Phpcmf\Service::V()->display('index.html');exit;
	}
}
