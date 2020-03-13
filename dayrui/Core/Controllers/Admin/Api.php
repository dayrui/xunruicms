<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Api extends \Phpcmf\Common
{

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
            if (function_exists($call)) {
                $this->_json(1, dr_lang('定义成功'));
            } else {
                $this->_json(0, '函数【'.$call.'】未定义');
            }
        }
    }

    // 通知跳转
    public function notice() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table('admin_notice')->get($id);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('该数据不存在'));
        }

        // 权限判断
        if (!isset($this->admin['roleid'][1])) {
            if ($data['to_uid'] && $data['to_uid'] != $this->uid) {
                $this->_admin_msg(0, dr_lang('您无权限执行'));
            } elseif ($data['to_rid'] && !isset($this->admin['roleid'][$data['to_rid']])) {
                $this->_admin_msg(0, dr_lang('您无权限执行'));
            }
        }

        list($uri, $param) = explode(':', $data['uri']);
        $url = ADMIN_URL.\Phpcmf\Service::L('Router')->url($uri);
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

		if (IS_AJAX_POST) {
            if (!\Phpcmf\Service::L('form')->check_captcha('code')) {
                $this->_json(0, dr_lang('验证码不正确'), ['field' => 'code']);
            }
			$menu = [];
			$data = \Phpcmf\Service::L('input')->post('data');
			if ($data['usermenu']) {
				foreach ($data['usermenu']['name'] as $id => $v) {
					$v && $data['usermenu']['url'][$id] && $menu[$id] = [
						'name' => $v,
						'url' => $data['usermenu']['url'][$id],
						'color' => $data['usermenu']['color'][$id],
                        'target' => $data['usermenu']['target'][$id],
					];
				}
			}
			// 修改密码
			$password = dr_safe_password(\Phpcmf\Service::L('input')->post('password'));
			$password && \Phpcmf\Service::M('member')->edit_password($this->member, $password);

			\Phpcmf\Service::M()->db->table('admin')->where('uid', $this->admin['id'])->update([
				'usermenu' => dr_array2string($menu)
            ]);
			\Phpcmf\Service::M()->db->table('member_data')->where('id', $this->admin['id'])->update([
				'is_admin' => 1
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

        $name = ['qq', 'weixin', 'weibo', 'wechat'];
        foreach ($name as $key => $value) {
            if (!isset($this->member_cache['oauth'][$value]['id'])
                || !$this->member_cache['oauth'][$value]['id']) {
                unset($name[$key]);
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
            'oauth_data' => $name,
            'oauth_list' => \Phpcmf\Service::M('member')->oauth($this->uid),
            'select_color' => $select,
            'is_post_user' => \Phpcmf\Service::M('auth')->is_post_user(),
            'select_target' => $select2,
		]);
		\Phpcmf\Service::V()->display('api_my.html');exit;
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
		$admin = \Phpcmf\Service::M()->db->table('admin')->where('uid', $this->uid)->get()->getRowArray();
		if ($admin) {
			$menu = dr_string2array($admin['usermenu']);
			foreach ($menu as $t) {
				$t['url'] == $url && $this->_json(1, dr_lang('已经存在'));
			}
			$menu[] = array(
				'name' => $name,
				'url' => $url,
			);
			\Phpcmf\Service::M()->db->table('admin')->where('uid', $this->uid)->update(array(
					'usermenu' => dr_array2string($menu)
				)
			);
			$this->_json(1, dr_lang('操作成功'));
		}

		$this->_json(0, dr_lang('加入失败'));
	}

	// 执行更新缓存
	public function cache() {

        $name = dr_safe_replace($_GET['id']);
        \Phpcmf\Service::M('cache')->$name();

        exit($this->_json(1, dr_lang('更新完成'), 0));

    }


	// 执行清空缓存数据
	public function cache_clear() {

        \Phpcmf\Service::M('cache')->update_data_cache();
        exit($this->_json(1, dr_lang('前台数据缓存已被更新')));
	}

	// 执行更新缓存
	public function cache_update() {

        \Phpcmf\Service::M('cache')->update_cache();
        exit($this->_json(1, dr_lang('更新完成')));
	}

	// 执行重建模块索引
	public function cache_search() {

        \Phpcmf\Service::M('cache')->update_search_index();
        exit($this->_json(1, dr_lang('更新完成')));
	}

	// 执行重建模块索引
	public function cache_site_config() {

        \Phpcmf\Service::M('cache')->update_search_index();
        exit($this->_json(1, dr_lang('更新完成')));
	}

	/**
	 * 生成安全码
	 */
	public function syskey() {
		echo 'PHPCMF'.strtoupper(substr((md5(SYS_TIME)), rand(0, 10), 13));exit;
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
            foreach ($this->site_domain as $name => $sid) {
                if ($sid == SITE_ID) {
                    unset($this->site_domain[$name]);
                }
            }
		    foreach ($post as $name => $t) {
		        if (!$t) {
		            continue;
                }
		        if ($name == 'site_domains') {
		            $v = explode(',', str_replace([chr(13), PHP_EOL], ',', $t));
                    if ($v) {
                        foreach ($v as $t) {
                            $t && $my[] = $t;
                            $this->site_domain[$t] && $html.= '<p>'.$t.' 已经存在于其他站点</p>';
                        }
                    }
                } else {
                    $my[] = $t;
                    $this->site_domain[$t] && $html.= $t.' 已经存在于其他站点';
                }
            }
            $my && count($my) != count(array_unique($my)) && $html.= '<p>当前配置项存在重复域名</p>';
            $html && exit($html);
        }

		exit('ok');
	}

	// 统计
	public function mtotal() {

		$t1 = $t2 = $t3 = $t4 = $t5 = 0;
		$dir = dr_safe_filename(\Phpcmf\Service::L('input')->get('dir'));
        $status = \Phpcmf\Service::M('auth')->get_admin_verify_status();
		if (is_dir(APPSPATH.ucfirst($dir))) {
			$t1 = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_index')->where('status=9')->where('DATEDIFF(from_unixtime(inputtime),now())=0')->countAllResults();
			$t2 = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_index')->where('status=9')->countAllResults();
			$t3 = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_verify')->where(($status ? 'status IN('.implode(',', $status).')' : 'status>=0'))->countAllResults();
			$t4 = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_recycle')->countAllResults();
			$t5 = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_time')->countAllResults();
		}
		echo '$("#'.$dir.'_today").html('.$t1.');';
		echo '$("#'.$dir.'_all").html('.$t2.');';
		echo '$("#'.$dir.'_verify").html('.$t3.');';
		echo '$("#'.$dir.'_recycle").html('.$t4.');';
		echo '$("#'.$dir.'_timing").html('.$t5.');';
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

		if (!SYS_EMAIL) {
		    $this->_json(0, dr_lang('系统邮箱没有设置'));
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

		if ($dmail->send(SYS_EMAIL, 'test', 'test for '.SITE_NAME)) {
			$this->_json(1, dr_lang('测试成功'));
		} else {
			$this->_json(0, $dmail->error());
		}
	}

	/**
	 * 预览移动端网站
	 */
	public function site() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!$this->site_info[$id]) {
            $this->_admin_msg(0, dr_lang('站点不存在'));
        } elseif (!$this->admin) {
            $this->_admin_msg(0, dr_lang('你还没有登录'));
        }

        // 判断站点权限
        \Phpcmf\Service::L('cache')->init('', 'site')->save('admin_login_site', $this->admin, 300);
        $this->_msg(1, dr_lang('正在切换到【%s】...', $this->site_info[$id]['SITE_NAME']).'<script src="'.$this->site_info[$id]['SITE_URL'].'index.php?s=api&c=sso&action=slogin&code='.dr_authcode($this->admin['uid'].'-'.md5($this->admin['uid'].$this->admin['password']), 'ENCODE').'"></script>', $this->site_info[$id]['SITE_URL'].SELF, 0);
        exit;
    }

    /**
     * 后台授权登录
     */
    public function alogin() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        $code = md5($this->admin['id'].$this->admin['password']);

        \Phpcmf\Service::L('cache')->set_data('admin_login_member', $this->admin, 300);

        $sso = '';
        $url = \Phpcmf\Service::M('member')->get_sso_url();
        foreach ($url as $u) {
            $sso.= '<script src="'.$u.'index.php?s=api&c=sso&action=alogin&code='.dr_authcode($uid.'-'.$code, 'ENCODE').'"></script>';
        }
        \Phpcmf\Service::V()->assign([
            'menu' => '',
        ]);

        $url = urldecode(\Phpcmf\Service::L('input')->get('url', true));
        !$url && $url = MEMBER_URL;

        $this->_msg(1, dr_lang('正在授权登录此用户...').$sso, $url, 0);exit;
    }

	/**
	 * 预览移动端网站
	 */
	public function mobile() {

        \Phpcmf\Service::V()->assign([
            'url' => SITE_MURL,
        ]);
        \Phpcmf\Service::V()->display('api_mobile.html');exit;
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
        $type = intval($data['type']);
        $value = $data['value'][$type];
        if (!$value) {
            $this->_json(0, dr_lang('参数不存在'));
        } elseif ($type == 0) {
            if (substr($value['path'],-1, 1) != '/') {
                $this->_json(0, dr_lang('存储路径目录一定要以“/”结尾'));
            } elseif ((strpos($value['path'], '/') === 0 || strpos($value['path'], ':') !== false)) {
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
            $this->_json(1, dr_lang('测试成功'));
        }

        $this->_json(0, dr_lang('无法访问到附件: %s', $rt['data']['url']));
    }

	/**
	 * 测试短信验证码
	 */
	public function test_mobile() {

	    $data = \Phpcmf\Service::L('input')->post('data');
		if (is_file(ROOTPATH.'config/mysms.php')) {
			require_once ROOTPATH.'config/mysms.php';
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
	 * 导出 字段设置
	 */
	public function export_field() {
        exit('此功能不可用');
    }

	/**
	 * 导出
	 */
	public function export_list() {
        $this->_admin_msg(0, '此功能不可用');
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
        }
		
        $path = dr_get_dir_path($v);
		if (is_file($path.SELF)) {
			$this->_json(0, dr_lang('目录不能是网站根目录'));
		} elseif (is_dir($path)) {
            $this->_json(1, dr_lang('目录正常'));
        } else {
            $this->_json(0, dr_lang('目录[%s]不存在', $path));
        }

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
        }
        $path = dr_get_dir_path($v);
        if (is_dir($path)) {
            $this->_json(1, dr_lang('目录正常'));
        } else {
            $this->_json(0, dr_lang('目录[%s]不存在', $path));
        }

    }

    /**
     * 测试附件域名是否可用
     */
    public function test_attach_domain() {

        $note = '';
        $data = \Phpcmf\Service::L('input')->post('data');
        if (!$data['SYS_ATTACHMENT_PATH']) {
            $note = dr_lang('上传目录留空时，采用系统默认分配的目录');
            $data['SYS_ATTACHMENT_PATH'] = 'uploadfile';
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
            $note = dr_lang('已使用自定义上传目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['SYS_ATTACHMENT_PATH'], '/').'/';
            $url = ROOT_URL.trim($data['SYS_ATTACHMENT_PATH'], '/').'/';
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
        if (!$data['cache_path']) {
            $note = dr_lang('存储目录留空时，采用系统默认分配的目录');
            $data['cache_path'] = 'uploadfile/thumb/';
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
            $note = dr_lang('已使用自定义存储目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['cache_path'], '/').'/';
            $url = ROOT_URL.trim($data['cache_path'], '/').'/';
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
        if (!$data['avatar_path']) {
            $note = dr_lang('存储目录留空时，采用系统默认分配的目录');
            $data['avatar_path'] = 'api/member/';
        } elseif (!$data['cache_url']) {
            $note = dr_lang('URL地址留空时，采用系统默认分配的URL');
        }

        if ((strpos($data['avatar_path'], '/') === 0 || strpos($data['avatar_path'], ':') !== false) && is_dir($data['avatar_path'])) {
            // 相对于根目录
            $path = rtrim($data['avatar_path'], DIRECTORY_SEPARATOR).'/';
            if (!$data['avatar_url']) {
                $this->_json(0, '<font color="red">'.dr_lang('没有设置访问URL地址').'</font>');
            }
            $url = trim($data['avatar_url'], '/').'/';
            $note = dr_lang('已使用自定义存储目录和自定义访问地址');
        } else {
            // 在当前网站目录
            $path = ROOTPATH.trim($data['avatar_path'], '/').'/';
            $url = ROOT_URL.trim($data['avatar_path'], '/').'/';
            !$note && $note = dr_lang('存储目录不是绝对的路径时采用，系统分配的URL地址');
        }

        $this->_json(1, $note.'<br>'.dr_lang('存储目录：%s', $path) .'<br>' . dr_lang('访问地址：%s', $url));
    }

    /**
     * 测试https是否可用
     */
    public function test_https() {
        $url = str_replace('http://', 'https://', ROOT_URL);
        $code = dr_catcher_data($url.'index.php?s=api&c=test', 5);
        if ($code && strpos($code, 'Xunruicms') !== false) {
            $this->_json(1, dr_lang('支持HTTPS访问'));
        } else {
            $this->_json(0, dr_lang('无法访问：%s', $url));
        }
    }

    /**
     * 测试缓存是否可用
     */
    public function test_cache() {

        $config = new \Config\Cache();

        $adapter = new $config->validHandlers[$config->handler]($config);
        if (!$adapter->isSupported()) {
            $this->_json(0, dr_lang('缓存方式[%s]，PHP环境不支持', $config->handler));
        }

        $adapter->initialize();
        $rt = $adapter->save('test', 'phpcmf', 60);
        if (!$rt) {
            $this->_json(1, dr_lang('缓存方式[%s]存储失败', $config->handler));
        } elseif ($adapter->get('test') == 'phpcmf') {
            $this->_json(1, dr_lang('缓存方式[%s]已生效', $config->handler));
        } else {
            $this->_json(0, dr_lang('缓存方式[%s]未生效', $config->handler));
        }

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

}
