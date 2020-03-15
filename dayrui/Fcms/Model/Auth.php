<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 后台权限控制模型

class Auth extends \Phpcmf\Model {

    private $_is_post_user = -1;

    // 验证操作其他用户身份权限
    public function cleck_edit_member($uid) {

        // 超管不验证
        if (in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            return true;
        } elseif ($this->uid == $uid) {
            // 自己不验证
            return true;
        } elseif (\Phpcmf\Service::M()->table('admin_role_index')->where('uid', $uid)->counts()) {
            // 此账号属于管理账号，禁止操作
            return false;
        }

        return true;
    }

    // 编辑时的获取自定义面板
    public function edit_main_table($table, $name, $tid = 12) {

        $html = '<div class="portlet portlet-sortable light bordered" id="table_'.$table.'">
                        <input name="tables['.$table.']" id="dr_table_'.$table.'" type="hidden" value="'.$tid.'">
                        <div class="portlet-title">
                            <div class="caption">
                                <span class="caption-subject bold uppercase"> <i class="fa fa-arrows-alt"></i> '.$name.'</span>
                            </div>
                            <div class="actions">
                                <a href="javascript:$(\'#table_'.$table.'\').remove();" class="btn btn-circle btn-default btn-sm">
                                    <i class="fa fa-close"></i> 移除 
                                </a>
                                <a href="javascript:dr_qx(\''.$table.'\');" class="btn btn-circle btn-default btn-sm">
                                    <i class="fa fa-user"></i> 权限 
                                </a>
                            </div>
                        </div>
                        <div class="portlet-body text-center" style="color: #ddd;padding: 40px;">
                            拖动到任意灰色虚线框中
                        </div>
                    </div>';

        return $html;
    }

    // 获取自定义面板
    public function get_main_table($table) {

        if (strpos($table, '-') !== false) {
            list($app, $name) = explode('-', $table);
            $file = dr_get_app_dir($app).'Views/main/'.$name.'.html';
        } else {
            if (is_file(MYPATH.'Views/main/'.$table.'.html')) {
                $file = MYPATH.'Views/main/'.$table.'.html';
            } else {
                $file = COREPATH.'Views/main/'.$table.'.html';
            }
        }

        if (is_file($file)) {
            return $file;
        }

        return COREPATH.'Views/main/none.html';
    }

    // 判断当前站点权限 有权限1 无权限0
    public function _check_site($siteid) {

        if (!\Phpcmf\Service::C()->admin) {
            return 0;
        } elseif (isset(\Phpcmf\Service::C()->admin['role'][1])) {
            return 1; // 超级管理员
        } elseif (in_array($siteid, \Phpcmf\Service::C()->admin['site'])) {
            return 1;
        }
        
        return 0;
    }

    // 获取当前管理员的角色组id
    private function _role($uid) {

        $role = $this->db->table('admin_role_index')->where('uid', $uid)->get()->getResultArray();
        if (!$role) {
            return [];
        }

        $id = [];
        foreach ($role as $t) {
            $id[] = $t['roleid'];
        }

        return $id;
    }

    // 存储授权登录信息
    public function save_login_auth($name, $uid) {
        \Phpcmf\Service::L('cache')->set_data('admin_auth_login_'.$name.'_'.$uid, SYS_TIME, 300);
    }

    // 后台管理员登录
    public function login($username, $password) {

        $data = $this->db
                    ->table('member')
                    ->select('password, salt, id')
                    ->where('username', $username)
                    ->limit(1)
                    ->get()
                    ->getRowArray();
        $password = dr_safe_password($password);
        // 判断用户状态
        if (!$data) {
            return dr_return_data(0, dr_lang('账号[%s]不存在', $username));
        } elseif (md5(md5($password).$data['salt'].md5($password)) != $data['password']) {
            return dr_return_data(0, dr_lang('密码不正确'));
        }

        $data['uid'] = $uid = (int)$data['id'];
        // 查询角色组
        $data['role'] = $role = $this->_role($uid);
        if (!$role) {
            return dr_return_data(0, dr_lang('此账号不是管理员'));
        }

        // 保存会话
        $this->login_session($data);

        // 登录后的钩子
        \Phpcmf\Hooks::trigger('admin_login_after', $data);

        return dr_return_data($uid);
    }

    // 存储会话
    public function login_session($data) {

        // 保存会话
        \Phpcmf\Service::C()->session()->set('uid', $data['id']);
        \Phpcmf\Service::L('input')->set_cookie('member_uid', $data['id'], SITE_LOGIN_TIME);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', substr(md5(SYS_KEY . $data['password']), 5, 20), SITE_LOGIN_TIME);

        // 管理员登录日志记录
        $this->_login_log($data['id']);
    }

    // 或后天最近两次登录信息
    public function admin_login_ip() {

        $query = $this->db->table('admin_login')->where('uid', $this->uid)->orderBy('logintime desc') ->limit(2)->get();

        return $query ? $query->getResultArray() : [];
    }

    // 提醒我的消息
    public function admin_notice($num = 7) {

        if (in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            // 超管
            $sql = 'select * from `'.$this->dbprefix('admin_notice').'` where (`site`='.SITE_ID.' or `site`=0) and `status`<>3 order by `status` asc, `inputtime` desc limit '.$num;
        } else {
            $sql = 'select * from `'.$this->dbprefix('admin_notice').'` where ((`to_uid`='.$this->uid.') '.(' or (`to_rid` IN ('.implode(',', \Phpcmf\Service::C()->admin['roleid']).'))').' or (`to_uid`=0 and `to_rid`=0)) and (`site`='.SITE_ID.' or `site`=0) and `status`<>3 order by `status` asc, `inputtime` desc limit '.$num;
        }

        $query = $this->db->query($sql);
        return $query ? $query->getResultArray() : [];
    }

    /**
     * 登录记录
     */
    private function _login_log($uid) {

        if (!$uid) {
            return;
        }

        $this->db->table('admin_login')->insert([
            'uid' => $uid,
            'loginip' => (string)\Phpcmf\Service::L('input')->ip_address(),
            'logintime' => SYS_TIME,
            'useragent' => substr(\Phpcmf\Service::L('input')->get_user_agent(), 0, 255),
        ]);
    }

    /**
     * 管理员用户信息
     *
     * @param	arr	$member	用户信息
     * @param	int	$verify	是否验证该管理员权限
     */
    public function member($member, $verify = 0) {

        // 查询用户信息
        $uid = (int)$member['uid'];
        $data = $this->db->table('admin')->where('uid', $uid)->get()->getRowArray();
        if (!$data) {
            return dr_return_data(0, dr_lang('管理员账号不存在'));
        } elseif ($member['is_lock'] && !IS_DEV) {
            return dr_return_data(0, dr_lang('账号被锁定，禁止登陆'));
        }

        // 查询角色组
        $role_id = $this->_role($uid);
        if (!$role_id) {
            return dr_return_data(0, dr_lang('此账号不是管理员组成员'));
        }

        // 角色权限缓存
        $role = \Phpcmf\Service::C()->get_cache('auth');

        // 角色信息
        $data['role'] = $data['roleid'] = $data['site'] = $data['module'] = [];
        $data['system'] = [ 'uri' => [], 'mark' => []];

        foreach ($role_id as $i) {
            $data['role'][$i] = $role[$i]['name'] ? $role[$i]['name'] : [];
            $data['roleid'][$i] = $i;
            $data['site'] = dr_array22array($data['site'], $role[$i]['site']);
            $data['system']['uri'] = dr_array22array($data['system']['uri'], $role[$i]['system']['uri']);
            $data['system']['mark'] = dr_array22array($data['system']['mark'], $role[$i]['system']['mark']);
            $data['module'] = dr_array22array($data['module'], $role[$i]['module']);
        }

        // 非创始人验证登录权限
        if ($verify && !isset($data['role'][1]) && !in_array(SITE_ID, $data['site'])) {
            return dr_return_data(0, dr_lang('无权限登录此站点'));
        }

        $data['adminid'] = $data['roleid'][1] ? 1 : 9;
        $data['uid'] = $uid;
        $data['email'] = $member['email'];
        $data['phone'] = $member['phone'];
        $data['username'] = $member['username'];
        $data['password'] = $member['password'];
        $data['usermenu'] = dr_string2array($data['usermenu']);
        $data['setting'] = dr_string2array($data['setting']);

        return dr_return_data(1, '', $data);
    }

    // 判断当前账号站点权限
    public function check_site() {

        if (!\Phpcmf\Service::C()->admin) {
            return 0;
        } elseif (in_array(1, \Phpcmf\Service::C()->admin['site'])) {
            return 1; // 超级权限识别
        } elseif (in_array(SITE_ID, \Phpcmf\Service::C()->admin['site'])) {
            return 1; // 当前站点权限
        }

        return 0;
    }

    // 获取全部角色组
    public function get_role_all() {

        $role = [];
        $data = $this->table('admin_role')->order_by('id ASC')->getAll();
        if ($data) {
            foreach ($data as $t) {
                $t['site'] = dr_string2array($t['site']);
                $t['system'] = dr_string2array($t['system']);
                $t['module'] = dr_string2array($t['module']);
                $t['application'] = dr_string2array($t['application']);
                $role[$t['id']] = $t;
            }
        }

        return $role;
    }

    // 添加角色组
    public function add_role($data) {

        return $this->table('admin_role')->insert([
            'site' => '',
            'name' => $data['name'],
            'system' => '',
            'module' => '',
            'application' => dr_string2array($data['application']),
        ]);
    }

    public function get_role($id) {

        $t = $this->table('admin_role')->get($id);
        if (!$t) {
            return null;
        }
        
        $t['site'] = dr_string2array($t['site']);
        $t['system'] = dr_string2array($t['system']);
        $t['module'] = dr_string2array($t['module']);
        $t['application'] = dr_string2array($t['application']);
        
        return $t;
    }

    public function update_role($id, $data) {
        $data['application'] = dr_array2string($data['application']);
        $this->table('admin_role')->update($id, $data);
    }

    public function delete_role($ids) {
        $ids && $this->db->table('admin_role')->whereIn('id', $ids)->delete();
    }

    // 账号是否是投稿员
    public function is_post_user() {

        if ($this->_is_post_user >= 0) {
            return $this->_is_post_user;
        }

        if (in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            $this->_is_post_user = 0;
            return $this->_is_post_user;
        }

        $auth = \Phpcmf\Service::C()->get_cache('auth');
        foreach (\Phpcmf\Service::C()->admin['roleid'] as $aid) {
            if (isset($auth[$aid]['application']['tid']) && $auth[$aid]['application']['tid']) {
                $this->_is_post_user = 1;
                return $this->_is_post_user;
            }
        }

        $this->_is_post_user = 0;
        return $this->_is_post_user;
    }


    // 后台内容审核列表的权限
    public function get_admin_verify_status() {

        if (in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            return []; // 超管用户
        }

        $verify = \Phpcmf\Service::C()->get_cache('verify');
        if (!$verify) {
            return []; // 没有审核流程时
        }

        $my = [0];
        foreach ($verify as $t) {
            if ($t['value']['role']) {
                foreach ($t['value']['role'] as $status => $rid) {
                    if (in_array($rid, \Phpcmf\Service::C()->admin['roleid'])) {
                        $my[] = $status;
                    }
                }
            }
        }

        return $my;
    }

    /**
     * 后台登录判断
     *
     * @return void
     */
    public function is_admin_login($member) {

        if (IS_ADMIN && \Phpcmf\Service::L('router')->class === 'login') {
            return FALSE; // 登录界面判断
        }

        $uid = (int)\Phpcmf\Service::C()->session()->get('uid');
        if (!$member || $uid != $member['uid']) {
            // 登录超时
            if (\Phpcmf\Service::L('router')->class == 'api') {
                if (in_array(\Phpcmf\Service::L('router')->method, ['oauth', 'search_help'])) {
                    return FALSE; // 跳过的控制器
                }
                \Phpcmf\Service::C()->_admin_msg(0, dr_lang('登录失效'));
            }
            dr_redirect(ADMIN_URL . \Phpcmf\Service::L('router')->url('login/index', array('go' => urlencode(dr_now_url()))));
            return FALSE;
        }

        $rt = $this->member($member, 1);
        if (!$rt['code']) {
            \Phpcmf\Service::C()->_admin_msg(0, $rt['msg']);
        }

        return $rt['data'];
    }

    /**
     * 判断是否具有操作权限
     *
     * @param	string	$uri
     * @return	bool	有权限返回TRUE，否则返回FALSE
     */
    public function _is_admin_auth($uri = '') {

        // 管理员1组不验证, 后台首页不验证
        if ((!\Phpcmf\Service::C()->admin || isset(\Phpcmf\Service::C()->admin['role'][1]))
            || \Phpcmf\Service::L('router')->class == 'api'
            || in_array($uri, [
            'home/index',
            'home/main',
            'home/home',
            ])) {
            return true;
        } elseif (!$uri) {
            return false;
        }

        // 补全控制器
        $uri = strpos($uri, '/') !== false ? $uri : (\Phpcmf\Service::L('router')->class.'/'.$uri);
        // 补全项目目录
        APP_DIR && strpos($uri, APP_DIR.'/') === false && $uri = APP_DIR.'/'.$uri;

        // 分隔URI判断权限
        $uri_arr = explode('/', $uri);
        $method = end($uri_arr);
        if (!$method) {
            return false;
        }

        // 找到下划线的控制器
        $action = strpos($method, '_') !== false ? str_replace('_', '', trim(strtolower(strrchr($method, '_')), '_')) : $method;

        // 查看的index URI
        $uri_arr[dr_count($uri_arr) - 1] = $action;
        $this_uri = implode('/', $uri_arr);

        if (in_array($this_uri, \Phpcmf\Service::C()->admin['system']['uri'])) {
            return true;
        }

        // 特殊url权限验证
        if (\Phpcmf\Service::L('router')->class == 'content') {
            // 内容维护
            $this_uri = APP_DIR ? str_replace(APP_DIR.'/', 'module_', $this_uri) : 'module_content/'.$action;
        } elseif (APP_DIR && \Phpcmf\Service::L('router')->class == 'flag') {
            // 特殊推荐位权限
            $this_uri = str_replace('/flag/', '/home/', $this_uri);
        } elseif (\Phpcmf\Service::L('router')->class == 'category') {
            // 栏目权限
            $this_uri = APP_DIR ? str_replace(APP_DIR.'/', 'module_', $this_uri) : 'module_category/'.$action;
        } elseif (\Phpcmf\Service::L('router')->class == 'member' && APP_DIR) {
            // 用户内容权限
            $this_uri =  str_replace(APP_DIR.'/', 'module_', $this_uri);
        } elseif (\Phpcmf\Service::L('router')->class == 'site_member' && !APP_DIR) {
            // 用户内容权限
            $this_uri =  'module_member/'.$action;
        }

        return in_array($this_uri, \Phpcmf\Service::C()->admin['system']['uri']);
    }

    // 后台菜单字符串
    public function _admin_menu($menu) {

        if (!$menu) {
            return '';
        }

        $_i = 1;
        $on = 'on'; //$this->admin['color'];
        $_uri = \Phpcmf\Service::L('router')->uri();
        $_link = '';
        $_select = 0;

        foreach ($menu as $name => $t) {
            $p = [];
            $uri = $t[0];
            if (strpos($uri, '{') !== false && preg_match('/\{(.+)\}/', $uri, $m)) {
                $uri = str_replace($m[0], '', $uri);
                $param = explode('&', $m[1]);
                foreach ($param as $tt) {
                    list($a, $b) = explode('=', $tt);
                    $p[$a] = $b;
                }
            }
            $_attr = $_li_class = '';
            // 获取URL
            if (strpos($uri, 'ajax:') === 0) {
                $uri = substr($uri, 5);
                $url = 'javascript:dr_admin_menu_ajax(\'' . \Phpcmf\Service::L('router')->url($uri, $p) . '\');';
            } elseif (strpos($uri, 'blank:') === 0) {
                $uri = substr($uri, 6);
                $url = dr_url($uri).'" target="_blank';
            } elseif (strpos($uri, 'add:') === 0) {
                $w = isset($t[2]) ? $t[2] : '';
                $h = isset($t[3]) ? $t[3] : '';
                $uri = substr($uri, 4);
                $url = 'javascript:dr_iframe(\''.dr_lang($name).'\', \'' . \Phpcmf\Service::L('router')->url($uri, $p) . '\', \'' . $w . '\',\'' . $h . '\');';
            }elseif (strpos($uri, 'show:') === 0) {
                $w = isset($t[2]) ? $t[2] : '';
                $h = isset($t[3]) ? $t[3] : '';
                $uri = substr($uri, 5);
                $url = 'javascript:dr_iframe_show(\''.$name.'\', \'' . \Phpcmf\Service::L('router')->url($uri, $p) . '\', \'' . $w . '\',\'' . $h . '\');';
            } elseif (in_array($name, ['help', 'ba'])) {
                if (CI_DEBUG && !IS_OEM_CMS) {
                    $t[1] = 'fa fa-question-circle';
                    $name = dr_lang('在线帮助');
                    $url = 'javascript:dr_help(\''.$uri.'\');';
                    /*
                    if (SYS_HTTPS) {
                        $url = 'http://help.xunruicms.com/'.$uri.'.html" target="_blank';
                    }*/
                } else {
                    continue;
                }
            } elseif (strpos($uri, 'hide:') === 0) {
                $uri = substr($uri, 5);
                $url = dr_now_url();
                $_li_class = $uri == $_uri ? '' : '{HIDE}';
            } elseif (strpos($uri, 'url:') === 0) {
                $url = substr($uri, 4);
                $uri = $t[2];
                if (!$url && !$uri) {
                    continue;
                }
            } else {
                $url = \Phpcmf\Service::L('router')->url($uri, $p);
            }
            // 验证URI权限
            if (!$this->_is_admin_auth($uri)) {
                continue;
            }
            $class = '';
            $_i == 1 && $class = ' {ONE}'; // 第一个菜单标识
            // 选中当前菜单
            if (trim($uri, '/') == $_uri) {
                $class .= ' ' . $on;
                $_select = 1;
            }
            // 生成链接
            $name = !\Phpcmf\Service::C()->_is_mobile() ? dr_lang($name) : dr_strcut(dr_lang($name), 4, '');
            $_link .= '<li class="' . $_li_class . '"> <a ' . $_attr . ' href="' . $url . '" class="' . $class . '">' . ($t[1] ? '<i class="' . $t[1] . '"></i> ' : '') . $name . '</a> <i class="fa fa-circle"></i> </li>';
            $_i++;
        }

        // 默认选中第一个菜单
        !$_select && $_link = str_replace('{ONE}', $on, $_link);

        return str_replace('{HIDE}', 'hidden', $_link);
    }
	
	// 多级框架菜单
	public function _iframe_menu($list, $now, $help = 0) {
		
		$menu = '';
		foreach ($list as $dir => $t) {
			$class = '';
            // 选中当前菜单
            if ($now == $dir) {
                $class = ' on';
            }
			$menu .= '<li id="iframe_menu_a_'.$dir.'" class="iframe_menu_a"> <a class="' . $class . '" href="javascript:;" onclick="McLink(\''.$dir.'\', \''.$t['url'].'\')"><i class="'.dr_icon($t['icon']).'"></i> '.dr_lang($t['name']).'</a> <i class="fa fa-circle"></i> </li>';
		}
		if (CI_DEBUG && $help) {
			$menu .= '<li> <a href="javascript:dr_help(\''.$help.'\');"><i class="fa fa-question-circle"></i> '.dr_lang('在线帮助').'</a> <i class="fa fa-circle"></i> </li>';
		}
		
		return $menu;
	}

    // 模块后台菜单
    public function _module_menu($module, $list_name, $list_url, $post_url) {

        // <a class="btn green-haze btn-outline btn-circle btn-sm" href="javascript:;" data-toggle="dropdown" data-hover="dropdown" data-close-others="true" aria-expanded="false">
        $module_menu = '<a class="dropdown-toggle {ON}" '.(\Phpcmf\Service::C()->_is_mobile() ? ' data-toggle="dropdown"' : '').' data-hover="dropdown" data-close-others="true" aria-expanded="true"><i class="fa fa-angle-double-down"></i></a>';
        $module_menu.= '<ul class="dropdown-menu">';
        $this->_is_admin_auth($module['dirname'].'/home/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/home/index').'"> <i class="'.dr_icon($module['icon']).'"></i> '.dr_lang('%s管理', $module['cname']).' </a></li>';
        $this->_is_admin_auth($module['dirname'].'/comment/index') && $module['comment'] && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/comment/index').'"> <i class="fa fa-comment"></i> '.dr_lang('%s管理', dr_comment_cname($module['comment']['cname'])).' </a></li>';

        if ($module['setting']['flag']) {
            $module_menu.= '<li class="divider"> </li>';
            foreach ($module['setting']['flag'] as $i => $t) {
                $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/flag/index', array('flag'=>$i)).'"> <i class="'.dr_icon($t['icon']).'"></i> '.dr_lang($t['name']).' </a></li>';
            }
        }

        if ($module['form']) {
            $module_menu.= '<li class="divider"> </li>';
            foreach ($module['form'] as $i => $t) {
                $this->_is_admin_auth($module['dirname'].'/'.$i.'/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/'.$i.'/index').'"> <i class="'.dr_icon($t['setting']['icon']).'"></i> '.dr_lang('%s管理', $t['name']).' </a></li>';
            }
        }

        $module_menu.= '<li class="divider"> </li>';
        $this->_is_admin_auth($module['dirname'].'/draft/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/draft/index').'"> <i class="fa fa-pencil"></i> '.dr_lang('草稿箱管理').' </a></li>';
        $this->_is_admin_auth($module['dirname'].'/recycle/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/recycle/index').'"> <i class="fa fa-trash-o"></i> '.dr_lang('回收站管理').' </a></li>';
        $this->_is_admin_auth($module['dirname'].'/time/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/time/index').'"> <i class="fa fa-clock-o"></i> '.dr_lang('待发布管理').' </a></li>';
        $this->_is_admin_auth($module['dirname'].'/verify/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url($module['dirname'].'/verify/index').'"> <i class="fa fa-edit"></i> '.dr_lang('待审核管理').' </a></li>';
        $module_menu.= '</ul>';

        // 显示菜单
        $menu = '';
        $menu.= '<li class="dropdown"> <a href="'.$list_url.'" class="{ON}">'.$list_name.'</a> '.$module_menu.' <i class="fa fa-circle"></i> </li>';

        // 非内容页面就显示返回链接
        if (\Phpcmf\Service::L('router')->uri() != $module['dirname'].'/home/index'
            && $this->_is_admin_auth($module['dirname'].'/home/index') ) {
            $menu.= '<li> <a href="'.\Phpcmf\Service::L('Router')->get_back($module['dirname'].'/home/index').'" class=""> <i class="fa fa-reply"></i> '.dr_lang('返回').'</a> <i class="fa fa-circle"></i> </li>';
        }

        // 发布和编辑权限
        $this->_is_admin_auth($module['dirname'].'/home/add') && $post_url && $menu.= '<li> <a href="'.$post_url.'" class="'.(\Phpcmf\Service::L('router')->method == 'add' ? 'on' : '').'"> <i class="fa fa-plus"></i> '.(isset($module['post_name']) && $module['post_name'] ? dr_lang($module['post_name']) : dr_lang('发布')).'</a> <i class="fa fa-circle"></i> </li>';
        \Phpcmf\Service::L('router')->method == 'edit' && $menu.= '<li> <a href="'.dr_now_url().'" class="on"> <i class="fa fa-edit"></i> '.dr_lang('修改').'</a> <i class="fa fa-circle"></i> </li>';


        // 选中判断
        strpos($menu, 'class="on"') === false && $menu = str_replace('{ON}', 'on', $menu);

        return $menu;
    }

    // 导航后台菜单
    public function _navigator_menu($type, $list_name, $list_url, $post_url) {

        $module_menu = '<a class="dropdown-toggle {ON}" data-toggle="dropdown" data-close-others="true" aria-expanded="true"><i class="fa fa-angle-double-down"></i></a>';
        $module_menu.= '<ul class="dropdown-menu">';

        if ($type) {
            foreach ($type as $i => $t) {
                $t && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url('navigator/home/index', ['tid'=>$i]).'">'.$i.' <i class="fa fa-"></i> '.$t.' </a></li>';
            }
        }

        $module_menu.= '</ul>';

        // 显示菜单
        $menu = '';
        $menu.= '<li class="dropdown"> <a href="'.$list_url.'" class="{ON}">'.$list_name.'</a> '.$module_menu.' <i class="fa fa-circle"></i> </li>';
        $post_url && $menu.= '<li> <a href="'.$post_url.'" class="'.(\Phpcmf\Service::L('router')->method == 'add' ? 'on' : '').'"> <i class="fa fa-plus"></i> '.dr_lang('添加').'</a> <i class="fa fa-circle"></i> </li>';
        \Phpcmf\Service::L('router')->method == 'edit' && $menu.= '<li> <a href="'.dr_now_url().'" class="on"> <i class="fa fa-edit"></i> '.dr_lang('修改').'</a> <i class="fa fa-circle"></i> </li>';
        // 自定义字段
        $menu.= '<li> <a href="'.\Phpcmf\Service::L('router')->url('field/index', ['rname'=>'navigator', 'rid'=>SITE_ID]).'"> <i class="fa fa-code"></i> '.dr_lang('自定义字段').'</a> <i class="fa fa-circle"></i> </li>';
        $menu.= '<li> <a href="javascript:dr_iframe(\'save\', \''.\Phpcmf\Service::L('router')->url('navigator/home/config_edit').'\');"> <i class="fa fa-save"></i> '.dr_lang('链接分类').'</a> <i class="fa fa-circle"></i> </li>';
        // 选中判断
        strpos($menu, 'class="on"') === false && $menu = str_replace('{ON}', 'on', $menu);


        return $menu;
    }

    // 模块栏目的快捷菜单
    public function _module_category_menu($module) {

        $module_menu = '';
        $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url(APP_DIR.'/category/index').'"> <i class=" fa fa-reorder"></i> '.dr_lang('栏目管理').' </a></li>';
        $this->_is_admin_auth($module['dirname'].'/category/edit') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url(APP_DIR.'/category/url_edit').'"> <i class="fa fa-link"></i> '.dr_lang('自定义URL').' </a></li>';
        $this->_is_admin_auth('field/index') && $module_menu.= '<li><a href="'.\Phpcmf\Service::L('router')->url('field/index', ['rname' => 'category-'.$module['dirname']]).'"> <i class="fa fa-code"></i> '.dr_lang('自定义栏目字段').' </a></li>';

        return $module_menu;
    }

    // 菜单点击url
    public function _menu_link_url($select, $uri = '', $param = []) {

        if ($uri && !$this->_is_admin_auth($uri)) {
            // 没权限
            return 'javascript:;';
        }

        !$uri && $uri = $select;
        $menu = \Phpcmf\Service::L('cache')->get('menu-admin-uri', $select);
        if ($menu) {
            return 'javascript:top.Mlink('.intval($menu['tid']).', '.intval($menu['pid']).', '.intval($menu['id']).', \''.\Phpcmf\Service::L('router')->url($uri, $param).'\');';
        } else {
            return 'javascript:;';
        }

    }

    // 程序鉴权
    public function license() {

    }

    // 缓存
    public function cache($site = SITE_ID) {

        $data = $this->get_role_all();
        $cache = [];
        if ($data) {
            foreach ($data as $i => $t) {
                if ($t['system']) {
                    // 系统可见菜单uri
                    $uri = [];
                    if ($t['system']['id']) {
                        $m = $this->db->table('admin_menu')->whereIn('id', $t['system']['id'])->get()->getResultArray();
                        if ($m) {
                            foreach ($m as $c) {
                                $c['uri'] && $uri[] = $c['uri'];
                            }
                        }
                    }
                    if ($t['system']['auth']) {
                        foreach ($t['system']['auth'] as $u => $action) {
                            $uri[] = $u;
                            $u = str_replace('/index', '/', $u);
                            if ($action) {
                                foreach ($action as $at) {
                                    $uri[] = $u.$at;
                                }
                            }
                        }
                    }
                    unset($t['system']['auth']);
                    $t['system']['uri'] = @array_unique($uri);
                    sort($t['system']['uri']);
                    sort($t['module']);
                    $t['module'] && $t['system']['uri'] = dr_array2array($t['module'], $t['system']['uri']);
                }
                $cache[$t['id']] = $t;
            }
        }
        \Phpcmf\Service::L('cache')->set_file('auth', $cache);

    }
}