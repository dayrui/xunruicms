<?php namespace Phpcmf\Control\Admin;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Api extends \Phpcmf\Common {

    // 切换系统内核
    public function sys_edit() {

        if (!IS_DEV && !defined('DEMO_ADMIN_USERNAME')) {
            $this->_json(0, dr_lang('开发者模式下才能进行'));
        }

        $version = [
            'CodeIgniter' => '7.4.0',
            'CodeIgniter72' => '7.2.0',
            'ThinkPHP' => '7.4.0',
            'Laravel' => '8.0.2',
        ];

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$name) {
            $this->_json(0, dr_lang('目录参数不能为空'));
        } elseif (!isset($version[$name])) {
            $this->_json(0, '内核（'.$name.'）不支持');
        }

        if (!is_file(FCPATH.$name.'/Init.php')) {
            $this->_json(0, '内核文件（'.FCPATH.$name.'/Init.php'.'）缺少');
        }

        // 判断环境
        if (version_compare(PHP_VERSION, $version[$name]) < 0) {
            $this->_json(0, '内核（'.$name.'）要求PHP版本不能低于'.$version[$name].'（当前'.PHP_VERSION.'）');
        }

        if (!in_array($name, ['CodeIgniter', 'CodeIgniter72'])
            && !is_file(FCPATH.$name.'/System/vendor/autoload.php')) {
            $this->_json(0, '内核目录（'.FCPATH.$name.'/System/vendor/'.'）缺少文件');
        }

        file_put_contents(WRITEPATH.'frame.lock', $name);

        $this->_json(1, dr_lang('操作成功'));
    }

    // 清理通知
    public function clear_notice() {

        if (\Phpcmf\Service::M('auth')->is_post_user()) {
            \Phpcmf\Service::M()->db->table('member_notice')->where('uid', $this->uid)->update(['isnew' => 0]);
        } else {

        }

        $this->_json(1, dr_lang('操作成功'), [
            'url' => dr_url('home/main')
        ]);
    }

    // 设置风格
    public function set_theme() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$name) {
            $this->_json(0, dr_lang('目录参数不能为空'));
        }

        if (!is_dir(WEBPATH.'static/'.$name)) {
            $this->_json(0, dr_lang('当前目录不存在'));
        }

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $data['config']['SITE_THEME'] = $name;
        \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $data['config']);
        \Phpcmf\Service::M('cache')->sync_cache('');

        $this->_json(1, dr_lang('操作成功'));
    }

    // 设置模板
    public function set_tpl() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$name) {
            $this->_json(0, dr_lang('目录参数不能为空'));
        }

        if (!is_dir(TPLPATH.'pc/'.$name) && !is_dir(TPLPATH.'mobile/'.$name)) {
            $this->_json(0, dr_lang('当前目录不存在'));
        }

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $data['config']['SITE_TEMPLATE'] = $name;
        \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $data['config']);
        \Phpcmf\Service::M('cache')->sync_cache('');

        $this->_json(1, dr_lang('操作成功'));
    }

    // 来自快捷登录
    public function oauth() {

        $uid = intval(\Phpcmf\Service::L('input')->get('uid'));
        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$uid) {
            $this->_admin_msg(0, dr_lang('uid参数传递失败'));
        }
        $oauth = \Phpcmf\Service::L('cache')->get_data('admin_auth_login_'.$name.'_'.$uid);
        if (!$oauth) {
            $this->_admin_msg(0, dr_lang('授权信息(%s)获取失败', $name));
        } elseif (SYS_TIME - $oauth > 60) {
            $this->_admin_msg(0, dr_lang('授权信息(%s)验证超时', $name));
        }

        $data = \Phpcmf\Service::M('member')->get_member($uid);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('账号(%s)不存在', $uid));
        } elseif (!$data['is_admin']) {
            $this->_admin_msg(0, dr_lang('账号(%s)不是管理员', $data['username']));
        }

        // 保存会话
        \Phpcmf\Service::M('auth')->login_session($data);
        \Phpcmf\Service::L('cache')->set_data('admin_auth_login_'.$name.'_'.$uid, 0, 10);

        // 转向后台
        dr_redirect(ADMIN_URL.SELF);
    }

    // 添加后台面板页面
    public function add_main_table() {

        $table = dr_safe_filename($_GET['table']);
        $tables = $this->_main_table();
        if (!$tables[$table]) {
            $this->_json(0, dr_lang('自定义面板[%s]不存在', $table));
        }

        $this->_json(1, \Phpcmf\Service::M('auth')->edit_main_table($table, $tables[$table]));
    }

    // 跳转首页
    public function gohome() {
        dr_redirect('index.php');
    }

    // 应用市场
    public function app() {
        dr_redirect(dr_url('cloud/app'));
    }

    // 模板市场
    public function template() {
        dr_redirect(dr_url('cloud/template'));
    }

    /**
     * Ajax调用字段属性表单
     */
    public function field() {
        \Phpcmf\Service::L('api')->field();
    }

    /**
     * 附件改名
     */
    public function name_edit() {
        \Phpcmf\Service::L('api')->name_edit();
    }

    /**
     * 输入一个附件
     */
    public function input_file_url() {
        \Phpcmf\Service::L('api')->input_file_url();
    }

    /**
     * 图片编辑
     */
    public function image_edit() {
        \Phpcmf\Service::L('api')->image_edit();
    }

    // 测试字段回调方法
    public function field_call() {

        $call = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$call) {
            $this->_json(0, dr_lang('没有填写函数方法'));
        }

        if (strpos($call, '_') === 0) {
            if (method_exists(\Phpcmf\Service::L('form'), $call)) {
                $this->_json(1, dr_lang('定义成功'));
            } else {
                $this->_json(0, 'form类方法【'.$call.'】未定义');
            }
        } else {
            if (dr_is_call_function($call)) {
                $this->_json(1, dr_lang('定义成功'));
            } else {
                $this->_json(0, '函数【'.$call.'】不可用');
            }
        }
    }

    // 通知跳转
    public function notice() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table('admin_notice')->get($id);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('该通知数据不存在'));
        }

        // 权限判断
        if (!isset($this->admin['roleid'][1])) {
            if ($data['to_uid'] && !dr_in_array($this->uid, explode(',', (string)$data['to_uid']))) {
                $this->_admin_msg(0, dr_lang('需要指定账号才能执行，当前账号无法执行'));
            } elseif ($data['to_rid'] && !dr_array_intersect($this->admin['roleid'], explode(',', (string)$data['to_rid']))) {
                $this->_admin_msg(0, dr_lang('需要指定角色组才能执行，当前账号角色无法执行'));
            }
        }

        list($uri, $param) = explode(':', $data['uri']);
        $url = ADMIN_URL.ltrim(dr_web_prefix(\Phpcmf\Service::L('Router')->url($uri)), '/');
        $param && $url.= '&'.http_build_query(dr_rewrite_decode($param, '/'));

        // 标记为已经查看
        if (!$data['status']) {
            \Phpcmf\Service::M()->table('admin_notice')->update($id, array(
                'status' => 1,
                'op_uid' => $this->uid,
                'op_username' => $this->admin['username'],
            ));
        }

        dr_redirect($url, 'refresh');
    }

	// 修改资料
	public function my() {

		$color = ['default', 'blue', 'red', 'green', 'dark', 'yellow'];
        $target = [0 => dr_lang('内链'), 1 => dr_lang('外链')];

        $setting = is_array($this->admin['setting']) ? $this->admin['setting'] : [];

		if (IS_AJAX_POST) {
            if (!\Phpcmf\Service::L('form')->check_captcha('code')) {
                $this->_json(0, dr_lang('验证码不正确'), ['field' => 'code']);
            }
			$menu = [];
			$data = \Phpcmf\Service::L('input')->post('data');
			if ($data['usermenu']) {
				foreach ($data['usermenu']['name'] as $id => $v) {
					$v && $data['usermenu']['url'][$id] && $menu[$id] = [
						'name' => trim($v),
						'url' => trim($data['usermenu']['url'][$id]),
						'color' => $data['usermenu']['color'][$id],
                        'target' => $data['usermenu']['target'][$id],
					];
				}
			}
			// 修改密码
			$password = dr_safe_password(\Phpcmf\Service::L('input')->post('password'));
			if ($password) {
                $rt = \Phpcmf\Service::L('Form')->check_password($password, $this->member['username']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg'], ['field' => 'password2']);
                }
                \Phpcmf\Service::M('member')->edit_password($this->member, $password);
            }

            $setting['font_size'] = (int)$data['font_size'];
			\Phpcmf\Service::M()->db->table('admin')->where('id', $this->admin['id'])->update([
				'setting' => dr_array2string($setting),
				'usermenu' => dr_array2string($menu),
            ]);

			$this->_json(1, dr_lang('操作成功'));
		}

        $select = '';
        foreach ($color as $t) {
            $select.= '<option value="'.$t.'">'.$t.'</option>';
        }

        $select2 = '';
        foreach ($target as $i => $t) {
            $select2.= '<option value="'.$i.'">'.$t.'</option>';
        }

        $name = dr_oauth_list();
        if (dr_is_app('weixin')) {
            $name['wechat'] = [];
        }
        $oauth = [];
        if ($name) {
            foreach ($name as $value => $t) {
                if (!isset($this->member_cache['oauth'][$value]['id'])
                    || !$this->member_cache['oauth'][$value]['id']) {
                    continue;
                }
                $oauth[] = $value;
            }
        }

		\Phpcmf\Service::V()->assign([
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
				[
					'资料修改' => ['api/my', 'fa fa-user'],
					'登录记录' => ['root/login_index{id='.$this->uid.'}', 'fa fa-calendar'],
				]
			),
            'color' => $color,
            'target' => $target,
            'setting' => $setting,
            'oauth_data' => $oauth,
            'oauth_list' => IS_USE_MEMBER ? \Phpcmf\Service::M('member')->oauth($this->uid) : [],
            'select_color' => $select,
            'is_post_user' => \Phpcmf\Service::M('auth')->is_post_user(),
            'select_target' => $select2,
		]);
		\Phpcmf\Service::V()->display('api_my.html');exit;
	}

    public function search_menu() {

        $kw = (dr_safe_replace(\Phpcmf\Service::L('input')->get('kw')));
        if (!$kw) {
            $menu = [];
        } else {
            $menu = \Phpcmf\Service::M()->table('admin_menu')->where('uri<>""')->like('name', $kw)->getAll();
        }

        \Phpcmf\Service::V()->assign('list', $menu);
        $menu && \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::L('cache')->get('menu-admin'));
        ob_start();
        \Phpcmf\Service::V()->display('api_search_menu.html');
        $html = ob_get_contents();
        ob_clean();

        $this->_json(1, $html);
    }

	// 加入菜单
	public function menu() {

		$url = urldecode(dr_safe_replace(\Phpcmf\Service::L('input')->get('v')));
		$arr = parse_url($url);
		$queryParts = explode('&', $arr['query']);
		$params = [];
		foreach ($queryParts as $param) {
			$item = explode('=', $param);
			$params[$item[0]] = $item[1];
		}
		// 基础uri
		$uri = ($params['s'] ? $params['s'].'/' : '').($params['c'] ? $params['c'] : 'home').'/'.($params['m'] ? $params['m'] : 'index');
		// 查询名称
		$menu = \Phpcmf\Service::M()->db->table('admin_menu')->select('name')->where('uri', $uri)->get()->getRowArray();
		$name = $menu ? $menu['name'] : '未知名称';
		// 替换URL
		if ($this->admin) {
			$menu = dr_string2array($this->admin['usermenu']);
			foreach ($menu as $t) {
				if ($t['url'] == $url) {
				    $this->_json(0, dr_lang('%s已经存在', $name));
                }
			}
			$menu[] = [
                'name' => $name,
                'url' => $url,
            ];
			\Phpcmf\Service::M()->db->table('admin')->where('uid', $this->uid)->update([
                'usermenu' => dr_array2string($menu)
            ]);

            ob_start();
            $this->admin['usermenu'] = $menu;
            \Phpcmf\Service::V()->assign('admin', $this->admin);
            \Phpcmf\Service::V()->display('api_link_menu.html');
            $html = ob_get_contents();
            ob_clean();

			$this->_json(1, dr_lang('操作成功'), $html);
		}

		$this->_json(0, dr_lang('加入失败'));
	}

	// 删除历史菜单
	public function clear_history() {
        \Phpcmf\Service::M()->db->table('admin')->where('uid', $this->uid)->update([
            'history' => ''
        ]);
        $this->_json(1, dr_lang('操作成功'));
    }

    // 加入历史菜单
	public function history() {

        $url = urldecode(dr_safe_replace(\Phpcmf\Service::L('input')->get('v')));
        $name = urldecode(dr_safe_replace(\Phpcmf\Service::L('input')->get('n')));

        // 替换URL
        if ($this->admin) {
            $menu = dr_string2array($this->admin['history']);
            if (is_array($menu) && $menu) {
                foreach ($menu as $t) {
                    if ($t['url'] == $url) {
                        $this->_json(0, dr_lang('%s已经存在', $name));
                    }
                }
            } else {
                $menu = [];
            }
            array_unshift($menu, [
                'name' => $name,
                'url' => trim($url),
            ]);
            $max = 30;
            if (count($menu) > $max) {
                $menu = array_slice($menu, 0, $max);
            }
            \Phpcmf\Service::M()->db->table('admin')->where('uid', $this->uid)->update([
                'history' => dr_array2string($menu)
            ]);
            $html = '';
            foreach ($menu as $t) {
                $html.= '<a class="btn btn-default href="'.trim($t['url']).'" onclick="dr_hide_left_tab()" target="right"> '.$t['name'].' </a>';
            }
            $this->_json(1, dr_lang('操作成功'), $html);
        }

		$this->_json(0, dr_lang('加入失败'));
	}

	// 执行更新缓存
	public function cache() {

        $name = dr_safe_replace($_GET['id']);
        \Phpcmf\Service::M('cache')->$name();

        $this->_json(1, dr_lang('执行完成'), 0);
    }

	// 执行清空缓存数据
	public function cache_clear() {

        $all = intval($_GET['all']);
        \Phpcmf\Service::M('cache')->update_data_cache($all);
        $this->_json(1, dr_lang('前台数据缓存已被更新'));
	}

	// 同步更新缓存
	public function cache_sync() {

        if (SYS_CACHE_CLEAR) {
            // 自动缓存
            \Phpcmf\Service::M('cache')->update_site_cache();
        } else {
            // 手动模式
            \Phpcmf\Service::M('cache')->update_data_cache();
        }

        $this->_json(1, dr_lang('更新完成'));
	}

	// 执行更新缓存
	public function cache_update() {

        \Phpcmf\Service::M('cache')->update_cache();
        $this->_json(1, dr_lang('更新完成'));
	}

	// 执行重建模块索引
	public function cache_search() {

        \Phpcmf\Service::M('cache')->update_search_index();
        $this->_json(1, dr_lang('更新完成'));
	}

    // 执行重建模块索引
    public function cache_site_config() {

        \Phpcmf\Service::M('cache')->update_site_config();
        $this->_json(1, dr_lang('更新完成'));
    }

    // 执行编辑器更新
    public function cache_ueditor() {

        \Phpcmf\Service::M('cache')->update_ueditor();
        $this->_json(1, dr_lang('更新完成'));
    }

	/**
	 * 生成安全码
	 */
	public function syskey() {
		echo 'PHPCMF'.strtoupper(substr((md5(SYS_TIME)), rand(0, 10), 13));exit;
	}

	// 当前时间值
	public function site_time() {
	    $this->_json(1, dr_date(SYS_TIME));
	}

	/**
	 * 生成来路随机字符
	 */
	public function referer() {
		$s = strtoupper(base64_encode(md5(SYS_TIME).md5(rand(0, 2015).md5(rand(0, 2015)))).md5(rand(0, 2009)));
		echo str_replace('=', '', substr($s, 0, 42));exit;
	}

	// 域名检查
	public function domain() {

	    $html = '';
	    $post = \Phpcmf\Service::L('input')->post('data');
		if ($post) {
		    $my = [];
		    $site_domian = \Phpcmf\Service::R(WRITEPATH.'config/domain_site.php');
            foreach ($site_domian as $name => $sid) {
                if ($sid == SITE_ID) {
                    unset($site_domian[$name]);
                }
            }
		    foreach ($post as $name => $t) {
		        if (!$t) {
		            continue;
                }
                $my[] = $t;
                isset($site_domian[$t]) && $html.= $t.' 已经存在于其他站点';
            }
            $unique = array_unique ( $my );
            if ($my && count($my) != count($unique)) {
                $arr = array_diff_assoc ( $my, $unique );
                $html.= '<p>当前配置项存在重复</p>';
                foreach ($arr as $t) {
                    $html.= '<p>【'.$t.'】被重复配置过，请检查</p>';
                }
            }
            $html && exit($html);
        }

		exit('<p>ok</p>');
	}

	// 统计
	public function mtotal() {

        if (!IS_USE_MODULE) {
            $this->_json(1, '');
        }

        require IS_USE_MODULE.'Controllers/Admin/Api.php';
        $ci = new \Phpcmf\Controllers\Admin\Api($this);
        $ci->mtotal();
		exit;
	}

	// 统计栏目
	public function ctotal() {

        if (!IS_USE_MODULE) {
            $this->_json(1, '');
        }

        require IS_USE_MODULE.'Controllers/Admin/Api.php';
        $ci = new \Phpcmf\Controllers\Admin\Api($this);
        $ci->ctotal();
        exit;
	}
	
	// api
	public function icon() {
		\Phpcmf\Service::V()->display('api_icon.html');exit;
	}

	// 常用配置
	public function config() {
		\Phpcmf\Service::V()->display('api_config.html');exit;
	}

	// phpinfo
	public function phpinfo() {
		phpinfo();exit;
	}

	// 邮件发送测试
	public function email_test() {

		if (!$this->member['email']) {
		    $this->_json(0, dr_lang('当前登录的账号没有设置邮箱'));
        }

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M()->table('mail_smtp')->get($id);
		if (!$data) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		$dmail = \Phpcmf\Service::L('email')->set([
			'host' => $data['host'],
			'user' => $data['user'],
			'pass' => $data['pass'],
			'port' => $data['port'],
			'from' => $data['user']
		]);
		if ($dmail->send($this->member['email'], 'test', 'test for '.SITE_NAME)) {
			$this->_json(1, dr_lang('已发送至邮箱：%s，并不代表已发送成功', $this->member['email']));
		} else {
			$this->_json(0, 'Error:'. $dmail->error());
		}
	}

    /**
     * 后台授权登录
     */
    public function alogin() {

        if (!IS_USE_MEMBER) {
            $this->_admin_msg(0, dr_lang('需要安裝官方版【用户系统】插件'));
        }

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        // 当不具备用户操作权限时，只能授权登录当前账号
        if (!\Phpcmf\Service::M('auth')->_is_admin_auth('member/home/index') && $uid != $this->uid) {
            $this->_admin_msg(0, dr_lang('无权限操作其他账号'));
        }

        $admin = \Phpcmf\Service::M()->table('member')->get($this->admin['uid']);
        \Phpcmf\Service::L('cache')->set_data('admin_login_member', $admin, 30);
        $this->session()->set('admin_login_member_code', md5($uid.$this->admin['id'].$this->admin['password']));

        $sso = '';
        $url = \Phpcmf\Service::M('member')->get_sso_url();
        $code = md5($admin['id'].$admin['password']);
        foreach ($url as $u) {
            $sso.= '<script src="'.$u.'index.php?s=api&c=sso&action=alogin&code='.dr_authcode($uid.'-'.$code, 'ENCODE').'"></script>';
        }

        \Phpcmf\Service::V()->assign([
            'menu' => '',
        ]);

        $url = urldecode(\Phpcmf\Service::L('input')->get('url', true));
        !$url && $url = MEMBER_URL;

        return $this->_msg(1, dr_lang('正在授权登录此用户...').$sso, dr_url_prefix(dr_member_url('api/alogin', ['url' => $url])), 0, true);
    }

    /**
     * 伪静态代码
     */
    public function rewrite_code() {

        if (IS_USE_MODULE) {
            dr_redirect(dr_url('module/urlrule/rewrite_index'));exit;
        }

        list($name, $note, $code) = \Phpcmf\Service::L('router')->rewrite_code();
        \Phpcmf\Service::V()->assign([
            'name' => $name,
            'code' => $code,
            'note' => $note,
            'count' => $code ? dr_count(explode(PHP_EOL, $code)) : 0,
        ]);
        \Phpcmf\Service::V()->display('api_rewrite_code.html');exit;
    }

    /**
     * 后台发送登录数据
     */
    public function slogin() {

        $arr = \Phpcmf\Service::M('member')->sso($this->member);
        $sso = '';

        $url = urldecode(\Phpcmf\Service::L('input')->get('url', true));
        !$url && $url = SITE_URL;

        $info = parse_url($url);

        foreach ($arr as $u) {
            if (strpos($u, $info['host']) !== false) {
                $sso.= '<script src="'.$u.'"></script>';
                break;
            }
        }

        \Phpcmf\Service::V()->assign([
            'menu' => '',
        ]);

        return $this->_msg(1, dr_lang('正在授权登录...').$sso,$url, 0, true);
    }

	/**
	 * 预览
	 */
	public function demo() {

	    $name = \Phpcmf\Service::L('input')->get('name');
	    if ($name == 'pc') {
	        $url = SITE_URL;
        } else {
	        $url = SITE_MURL;
        }

        \Phpcmf\Service::V()->assign([
            'url' => $url,
            'demo' => $name,
        ]);
        \Phpcmf\Service::V()->display('api_demo.html');exit;
    }

	/**
	 * 水印图片预览
	 */
	public function preview() {

	    $data = $_GET['data'];
        $data['source_image'] = WRITEPATH.'preview.png';
        $data['dynamic_output'] = true;

        $rt = \Phpcmf\Service::L('Image')->watermark($data, 1);
        if (!$rt) {
            echo \Phpcmf\Service::L('Image')->display_errors();
        }
        exit;
    }

	/**
	 * 测试远程附件
	 */
	public function test_attach() {

	    $data = \Phpcmf\Service::L('input')->post('data');
	    if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        }

        $type = intval($data['type']);
        $value = $data['value'][$type];
        if (!$value) {
            $this->_json(0, dr_lang('参数不存在'));
        } elseif ($type == 0) {
            if (substr($value['path'],-1, 1) != '/') {
                $this->_json(0, dr_lang('存储路径目录一定要以“/”结尾'));
            } elseif ((dr_strpos($value['path'], '/') === 0
                || dr_strpos($value['path'], ':') === 1)) {
				if (!is_dir($value['path'])) {
					$this->_json(0, dr_lang('本地路径[%s]不存在', $value['path']));
				}
			} elseif (is_dir(SYS_UPLOAD_PATH.$value['path'])) {

			} else {
				$this->_json(0, dr_lang('本地路径[%s]不存在', SYS_UPLOAD_PATH.$value['path']));
			}
		} 

        $rt = \Phpcmf\Service::L('upload')->save_file(
            'content',
            'this is phpcmf file-test',
            'test/test.txt',
            [
                'id' => 0,
                'url' => $data['url'],
                'type' => $type,
                'value' => $value,
            ]
        );

        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        } elseif (strpos(dr_catcher_data($rt['data']['url']), 'phpcmf') !== false) {
            $this->_json(1, dr_lang('测试成功：%s', $rt['data']['url']));
        }

        $this->_json(0, dr_lang('无法访问到附件: %s', $rt['data']['url']));
    }

	/**
	 * 测试短信验证码
	 */
	public function test_mobile() {

	    $data = \Phpcmf\Service::L('input')->post('data');
        if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        }

		if (is_file(CONFIGPATH.'mysms.php')) {
			require_once CONFIGPATH.'mysms.php';
		}
        $method = 'my_sendsms_code';
        if (function_exists($method)) {
            $rt =  call_user_func_array($method, [
                $data['mobile'],
                rand(10000, 99999),
                $data['third'],
            ]);
            $this->_json($rt['code'], $rt['msg']);
        } else {
            $this->_json(0, dr_lang('你没有定义第三方短信接口: '. $method));
        }
    }

	/**
	 * 显示用户资料
	 */
	public function member() {

		$uid = intval(\Phpcmf\Service::L('input')->get('uid'));
		if ($uid) {
            $data = \Phpcmf\Service::M('member')->get_member($uid);
            if (!$data) {
                $this->_json(0, dr_lang('账号uid[%s]不存在', $uid));
            }
        } else {
            $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
            $data = \Phpcmf\Service::M('member')->get_member(0, $name);
            if (!$data) {
                $this->_json(0, dr_lang('账号[%s]不存在', $name));
            }
        }

        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($data['id'])) {
            $this->_json(0, dr_lang('无权限操作其他管理员账号'));
        }

        // 当不具备用户操作权限时，只能授权登录当前账号
        if (!\Phpcmf\Service::M('auth')->_is_admin_auth('member/home/index') && $uid != $this->uid) {
            $this->_json(0, dr_lang('无权限操作其他账号'));
        }

		\Phpcmf\Service::V()->assign([
			'm' => $data,
		]);
		\Phpcmf\Service::V()->display('api_show_member.html');
		exit;
	}

    /**
     * 测试目录是否可用
     */
    public function test_dir() {

        $v = \Phpcmf\Service::L('input')->get('v');
        if (!$v) {
            $this->_json(0, dr_lang('目录为空'));
        } elseif (strpos($v, ' ') === 0) {
            $this->_json(0, dr_lang('不能用空格开头'));
        } elseif (strpos($v, '..') !== false) {
            $this->_json(0, dr_lang('不能出现..符号'));
        }
		
        $path = dr_get_dir_path($v);
		if (is_file($path.SELF)) {
			$this->_json(0, dr_lang('目录不能是项目根目录'));
		} elseif (is_dir($path)) {
            $this->_json(1, dr_lang('目录正常'));
        } else {
            $this->_json(0, dr_lang('目录[%s]不存在', $path));
        }
    }

    /**
     * 测试电脑新域名是否可用
     */
    public function test_site_domain() {

        $v = \Phpcmf\Service::L('input')->get('v');
        if (!$v) {
            $this->_json(0, dr_lang('域名不能为空'));
        } elseif (!\Phpcmf\Service::L('Form')->check_domain($v)) {
            $this->_json(0, dr_lang('域名（%s）格式不正确', $v));
        } elseif (!function_exists('stream_context_create')) {
            $this->_json(0, '函数没有被启用：stream_context_create');
        }

        $url = dr_http_prefix($v) . '/mobile/api.php';
        if (strpos($v, ':') !== false) {
            $this->_json(0, '可以尝试手动访问：' . $url . '，如果提示phpcmf ok就表示成功');
        }

        $code = dr_catcher_data($url, 5);
        if ($code != 'phpcmf ok') {
            ;
            $this->_json(0, dr_lang('[%s]域名绑定异常，无法访问：%s，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功', dr_lang($v), $url));
        }

        $this->_json(1, dr_lang('绑定正常'));
    }

    /**
     * 测试手机域名是否可用
     */
    public function test_mobile_domain() {

        $v = \Phpcmf\Service::L('input')->get('v');
        if (!$v) {
            $this->_json(0, dr_lang('域名不能为空'));
        } elseif (!\Phpcmf\Service::L('Form')->check_domain($v)) {
            $this->_json(0, dr_lang('域名（%s）格式不正确', $v));
        } elseif (!function_exists('stream_context_create')) {
            $this->_json(0, '函数没有被启用：stream_context_create');
        } elseif ($this->site_info[SITE_ID]['SITE_DOMAIN'] == $v) {
            $this->_json(0, dr_lang('手机域名不能与电脑相同'));
        }

        $url = dr_http_prefix($v) . '/api.php';
        if (strpos($v, ':') !== false) {
            $this->_json(0, dr_lang('可以尝试手动访问：%s，如果提示phpcmf ok就表示成功', $url));
        }

        $code = dr_catcher_data($url, 5);
        if ($code != 'phpcmf ok') {
            $this->_json(0, dr_lang('[%s]域名绑定异常，无法访问：%s，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功', dr_lang($v), $url));
        }

        $this->_json(1, dr_lang('绑定正常'));
    }

    /**
     * 测试目录模式的域名是否可用
     */
    public function test_mobile_dir() {

        $v = trim(\Phpcmf\Service::L('input')->get('v'));
        if (!$v) {
            $this->_json(0, dr_lang('目录不能为空'));
        } elseif (strpos($v, '/') !== false) {
            $this->_json(0, dr_lang('目录不能包含/符号'));
        } elseif (!function_exists('stream_context_create')) {
            $this->_json(0, '函数没有被启用：stream_context_create');
        } elseif (strpos($v, '..') !== false) {
            $this->_json(0, dr_lang('不能出现..符号'));
        }

        // 生成手机目录
        $rt = \Phpcmf\Service::M('cache')->update_mobile_webpath(WEBPATH, $v);
        if ($rt) {
            $this->_json(0, dr_lang($rt));
        }

        $url = SITE_URL.$v . '/api.php';
        if (strpos($v, ':') !== false) {
            $this->_json(0, dr_lang('可以尝试手动访问：%s，如果提示phpcmf ok就表示成功', $url));
        }

        $code = dr_catcher_data($url, 5);
        if ($code != 'phpcmf ok') {
            $this->_json(0, dr_lang('[%s]目录绑定异常，无法访问：%s，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功', $v, $url));
        }

        $this->_json(1, dr_lang('目录正常'));
    }

    /**
     * 测试附件目录是否可用
     */
    public function test_attach_dir() {

        $v = \Phpcmf\Service::L('input')->get('v');
        if (!$v) {
            $this->_json(1, dr_lang('默认目录'));
        } elseif (strpos($v, ' ') === 0) {
            $this->_json(0, dr_lang('不能用空格开头'));
        } elseif (strpos($v, 'config') !== false) {
            $this->_json(0, dr_lang('不能包含config目录'));
        } elseif (strpos($v, '..') !== false) {
            $this->_json(0, dr_lang('不能出现..符号'));
        }

        $path = dr_get_dir_path($v);
        if (is_dir($path)) {
            $this->_json(1, dr_lang('目录正常'));
        } else {
            if (strpos($path, ROOTPATH) !== false) {
                $this->_json(0, dr_lang('目录[%s]不存在', $path));
            }
            $this->_json(0, dr_lang('目录[%s]无法识别，请检查服务器的防跨站开关或者.user.ini权限文件', $path));
        }
    }

    /**
     * 测试附件域名是否可用
     */
    public function test_attach_domain() {

        $note = '';
        $data = \Phpcmf\Service::L('input')->post('data');
        if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        } elseif (!$data['SYS_ATTACHMENT_PATH']) {
            $note = dr_lang('上传目录留空时，采用系统默认分配的目录');
            $data['SYS_ATTACHMENT_PATH'] = 'uploadfile';
        } elseif (strpos($data['SYS_ATTACHMENT_PATH'], 'config') !== false) {
            $this->_json(0, dr_lang('不能包含config目录'));
        } elseif (!$data['SYS_ATTACHMENT_URL']) {
            $note = dr_lang('URL地址留空时，采用系统默认分配的URL');
        }

        if ((strpos($data['SYS_ATTACHMENT_PATH'], '/') === 0 || strpos($data['SYS_ATTACHMENT_PATH'], ':') !== false)
            && is_dir($data['SYS_ATTACHMENT_PATH'])) {
            // 相对于根目录
            if (!$data['SYS_ATTACHMENT_URL']) {
                $this->_json(0, '<font color="red">'.dr_lang('没有设置附件URL地址').'</font>');
            }
            // 附件上传目录
            $path = rtrim($data['SYS_ATTACHMENT_PATH'], DIRECTORY_SEPARATOR).'/';
            // 附件访问URL
            $url = trim($data['SYS_ATTACHMENT_URL'], '/').'/';
            if (!dr_is_url($url)) {
                $url.= '<font color="red">'.dr_lang('（不是一个合法的地址，缺少http://或者https://前缀）').'</font>';
            }
            $note = dr_lang('已使用自定义上传目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['SYS_ATTACHMENT_PATH'], '/').'/';
            $url = (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).trim($data['SYS_ATTACHMENT_PATH'], '/').'/';
            !$note && $note = dr_lang('上传目录不是绝对的路径时采用，系统分配的URL地址');
        }

        $this->_json(1, $note.'<br>'.dr_lang('附件上传目录：%s', $path) .'<br>' . dr_lang('附件访问地址：%s', $url));
    }

    /**
     * 测试缩略图域名是否可用
     */
    public function test_thumb_domain() {

        $note = '';
        $data = \Phpcmf\Service::L('input')->post('image');
        if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        } elseif (!$data['cache_path']) {
            $note = dr_lang('存储目录留空时，采用系统默认分配的目录');
            $data['cache_path'] = 'uploadfile/thumb/';
        } elseif (strpos($data['cache_path'], 'config') !== false) {
            $this->_json(0, dr_lang('不能包含config目录'));
        } elseif (!$data['cache_url']) {
            $note = dr_lang('URL地址留空时，采用系统默认分配的URL');
        }

        if ((strpos($data['cache_path'], '/') === 0 || strpos($data['cache_path'], ':') !== false) && is_dir($data['cache_path'])) {
            // 相对于根目录
            $path = rtrim($data['cache_path'], DIRECTORY_SEPARATOR).'/';
            if (!$data['cache_url']) {
                $this->_json(0, '<font color="red">'.dr_lang('没有设置访问URL地址').'</font>');
            }
            $url = trim($data['cache_url'], '/').'/';
            if (!dr_is_url($url)) {
                $url.= '<font color="red">'.dr_lang('（不是一个合法的地址，缺少http://或者https://前缀）').'</font>';
            }
            $note = dr_lang('已使用自定义存储目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['cache_path'], '/').'/';
            $url = (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).trim($data['cache_path'], '/').'/';
            !$note && $note = dr_lang('存储目录不是绝对的路径时采用，系统分配的URL地址');
        }

        $this->_json(1, $note.'<br>'.dr_lang('存储目录：%s', $path) .'<br>' . dr_lang('访问地址：%s', $url));
    }

    /**
     * 测试头像域名是否可用
     */
    public function test_avatar_domain() {

        $note = '';
        $data = \Phpcmf\Service::L('input')->post('image');
        if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        } elseif (!$data['avatar_path']) {
            $note = dr_lang('存储目录留空时，采用系统默认分配的目录');
            $data['avatar_path'] = 'uploadfile/member/';
        } elseif (!$data['cache_url']) {
            $note = dr_lang('URL地址留空时，采用系统默认分配的URL');
        } elseif (strpos($data['avatar_path'], 'config') !== false) {
            $this->_json(0, dr_lang('不能包含config目录'));
        }

        if ((strpos($data['avatar_path'], '/') === 0 || strpos($data['avatar_path'], ':') !== false) && is_dir($data['avatar_path'])) {
            // 相对于根目录
            $path = rtrim($data['avatar_path'], DIRECTORY_SEPARATOR).'/';
            if (!$data['avatar_url']) {
                $this->_json(0, '<font color="red">'.dr_lang('没有设置访问URL地址').'</font>');
            }
            $url = trim($data['avatar_url'], '/').'/';
            if (!dr_is_url($url)) {
                $url.= '<font color="red">'.dr_lang('（不是一个合法的地址，缺少http://或者https://前缀）').'</font>';
            }
            $note = dr_lang('已使用自定义存储目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['avatar_path'], '/').'/';
            $url = (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).trim($data['avatar_path'], '/').'/';
            !$note && $note = dr_lang('存储目录不是绝对的路径时采用，系统分配的URL地址');
        }

        $this->_json(1, $note.'<br>'.dr_lang('存储目录：%s', $path) .'<br>' . dr_lang('访问地址：%s', $url));
    }

    /**
     * 测试https是否可用
     */
    public function test_https() {
        $url = str_replace('http://', 'https://', ROOT_URL);
        $code = dr_catcher_data($url.'index.php?s=api&c=test&m=https', 5);
        if ($code && strpos($code, 'xunruicms') !== false) {
            $this->_json(1, dr_lang('支持HTTPS访问'));
        } else {
            ob_start();
            \Phpcmf\Service::V()->assign('url', $url);
            \Phpcmf\Service::V()->display('api_test_https.html');
            $html = ob_get_contents();
            ob_clean();

            $this->_json(0, $html);
        }
    }

    /**
     * 测试缓存是否可用
     */
    public function test_cache() {

        if (!isset($_POST['data'])) {
            $this->_json(0, dr_lang('参数错误'));
        }

		$type = intval(isset($_POST['data']['SYS_CACHE_TYPE']) ? $_POST['data']['SYS_CACHE_TYPE'] : 0);
        switch ($type) {
            case 1:
                $name = 'memcached';
                if (!extension_loaded('memcached') && !extension_loaded('memcache')) {
                    $this->_json(0, dr_lang('PHP环境没有安装[%s]扩展', $name));
                }
                break;
            case 2:
                $name = 'redis';
                if (!extension_loaded('redis')) {
                    $this->_json(0, dr_lang('PHP环境没有安装[%s]扩展', $name));
                }
                break;
            default:
                $name = 'file';
                if (!dr_check_put_path(WRITEPATH.'file/')) {
                    $this->_json(0, dr_lang('请分配cache/file目录的可读写权限'));
                }
                break;
        }

        if (is_file(FRAMEPATH.'Extend/Cache.php')) {
            require_once FRAMEPATH.'Extend/Cache.php';
            $cache = new \Frame\Cache();
            $rt = $cache->test($name);
            $this->_json($rt['code'], $rt['msg']);
        } else {
            $this->_json(0, dr_lang('此版本不支持自定义缓存功能'));
        }
    }

    // 测试正则表达式
    public function test_pattern() {

        if (IS_POST) {

            $data = \Phpcmf\Service::L('input')->post('data');
            if (!$data['text']) {
                $this->_json(0, dr_lang('测试文字不能为空'));
            } elseif (!$data['code']) {
                $this->_json(0, dr_lang('正则表达式不能为空'));
            }

            if (!preg_match($data['code'], $data['text'])) {
                $this->_json(0, dr_lang('正则表达式验证结果：%s', dr_lang('未通过')));
            }

            $this->_json(1, dr_lang('正则表达式验证结果：%s', dr_lang('通过')));
        }

        \Phpcmf\Service::V()->assign('code', [
            '纯数字' => '/^[0-9]+$/',
            '纯汉字' => '/^[\x{4e00}-\x{9fa5}]+$/u',
            '手机号码' => '/^1[345789]\d{9}$/ims',
            '电子邮箱' => '/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims',
        ]);
        \Phpcmf\Service::V()->display('api_pattern.html');exit;
    }

    // 简化模式切换
    public function admin_min() {

        \Phpcmf\Service::M('auth')->update_admin_setting('admin_min', 1);
        $this->_admin_msg(1, dr_lang('正在切换简化模式'), dr_url('home/min'), 0);
    }

    // 完整模式切换
    public function admin_all() {

        if (\Phpcmf\Service::M('auth')->is_admin_min_mode()) {
            $this->_admin_msg(0, dr_lang('此账号无法切换到完整模式'));
        }

        \Phpcmf\Service::M('auth')->update_admin_setting('admin_min', 0);

        $this->_admin_msg(1, dr_lang('正在切换完整模式'), dr_url('home/index'), 0);
    }

    // 短信接口查询
    public function sms_info() {
        exit($this->_api_sms_info());
    }

    // 版本检查
    public function version_cmf() {
        exit($this->_api_version_cmf());
    }

    // 版本检查
    public function version_cms() {
        exit($this->_api_version_cms());
    }

    // 搜索帮助
    public function search_help() {
        exit($this->_api_search_help());
    }

    public function count_total() {

    }

    // 更新url
    public function update_url() {
        require IS_USE_MODULE.'Controllers/Admin/Api.php';
        $ci = new \Phpcmf\Controllers\Admin\Api($this);
        $ci->update_url();
    }

    // 更新栏目缓存配置
    public function update_category_cache() {
        require IS_USE_MODULE.'Controllers/Admin/Api.php';
        $ci = new \Phpcmf\Controllers\Admin\Api($this);
        $ci->update_category_cache();
    }

    // 头像设置空
    public function avatar_del() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        $member = dr_member_info($uid);
        if (!$member) {
            $this->_json(0, dr_lang('该用户不存在'));
        }

        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        list($cache_path, $cache_url) = dr_avatar_path();

        foreach ([$cache_path.$uid.'.jpg', $cache_path.dr_avatar_dir($uid).$uid.'.jpg'] as $file) {
            if (is_file($file)) {
                unlink($file);
                if (is_file($file)) {
                    $this->_json(0, dr_lang('文件删除失败，请检查头像目录权限'));
                }
            }
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 头像设置
    public function avatar_edit() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        $member = dr_member_info($uid);
        if (!$member) {
            $this->_json(0, dr_lang('该用户不存在'));
        }

        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_json(0, dr_lang('无权限操作其他管理员账号'));
        }

        if (IS_POST) {
            $content = $_POST['file'];
            // 普通文件上传
            if (isset($_FILES['file'])) {
                if (isset($_FILES["file"]["tmp_name"]) && $_FILES["file"]["tmp_name"]) {
                    $content = \Phpcmf\Service::L('file')->base64_image($_FILES["file"]["tmp_name"]);
                }
            }
            if (!$content) {
                $this->_json(0, dr_lang('上传文件失败'));
            }
            list($cache_path) = dr_avatar_path();
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/i', $content, $result)) {
                $content = base64_decode(str_replace($result[1], '', $content));
                if (strlen($content) > 30000000) {
                    $this->_json(0, dr_lang('图片太大了'));
                }
                // 头像上传成功之前
                \Phpcmf\Hooks::trigger('upload_avatar_before', [
                    'member' => $member,
                    'base64_image' => $content,
                ]);
                $rt = \Phpcmf\Service::L('upload')->base64_image([
                    'content' => $content,
                    'ext' => 'jpg',
                    'save_name' => $uid,
                    'save_file' => $cache_path.dr_avatar_dir($uid).$uid.'.jpg',
                ]);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                // 头像上传成功之后
                \Phpcmf\Hooks::trigger('upload_avatar_after', [
                    'member' => $member,
                    'base64_image' => $content,
                ]);
                \Phpcmf\Service::M()->db->table('member_data')->where('id', $uid)->update(['is_avatar' => 1]);
                \Phpcmf\Service::M('member')->clear_cache($uid);
                $this->_json(1, dr_lang('上传成功'));
            } else {
                $this->_json(0, dr_lang('头像内容不规范'));
            }
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(['file' => '']),
            'member' => $member,
        ]);
        \Phpcmf\Service::V()->display('api_avatar.html');exit;
    }

    // 验证权限脚本
    public function _check_upload_auth($editor = 0) {
        return \Phpcmf\Service::L('api')->check_upload_auth($editor);
    }

    /**
     * 文件上传
     */
    public function upload() {
        \Phpcmf\Service::L('api')->upload();
    }

    /**
     * 浏览文件
     */
    public function input_file_list() {
        \Phpcmf\Service::L('api')->input_file_list();
    }

    /**
     * 浏览文件
     */
    public function file_list() {
        \Phpcmf\Service::L('api')->file_list();
    }

    /**
     * 删除文件
     */
    public function file_delete() {

        $rt = \Phpcmf\Service::M('Attachment')->file_delete(
            $this->member,
            (int)\Phpcmf\Service::L('input')->get('id')
        );

        $this->_json($rt['code'], $rt['msg']);
    }

    /**
     * 下载文件
     */
    public function down() {
        \Phpcmf\Service::L('api')->down();
    }

    /**
     * 百度编辑器处理接口
     */
    public function ueditor() {
        require ROOTPATH.'api/ueditor/php/controller.php';exit;
    }

    /**
     * base64图片上传
     */
    public function upload_base64_image() {
        \Phpcmf\Service::L('api')->upload_base64_image();
    }

    /**
     * 下载远程图片
     */
    public function down_img_list() {
        \Phpcmf\Service::L('api')->down_img_list();
    }

    /**
     * 下载远程图片
     */
    public function down_img_url() {
        \Phpcmf\Service::L('api')->down_img_url();
    }

    /**
     * 下载远程图片
     */
    public function down_img() {
        \Phpcmf\Service::L('api')->down_img();
    }
}
