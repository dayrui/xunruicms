<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 后台权限控制模型

class Auth extends \Phpcmf\Model {

    protected $_auth_uri = '';
    protected $_is_post_user = -1;
    protected $_is_admin_min_mode = -1;
    protected $_is_post_user_status = -1;

    // 验证操作其他用户身份权限
    public function cleck_edit_member($uid) {

        // 超管不验证
        if (dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            return true;
        } elseif ($this->uid == $uid) {
            // 自己不验证
            return true;
        } elseif (\Phpcmf\Service::M()->table('admin_role_index')->where('uid', $uid)->counts()) {
            // 此账号属于管理账号，禁止操作
            return false;
        } elseif ($this->is_post_user() && $uid != $this->uid) {
            // 投稿者账号，不能操作其他人的
            return false;
        }

        return true;
    }

    // 编辑时的获取自定义面板
    public function edit_main_table($table, $name, $tid = 12) {

        $html = '<div class="portlet portlet-sortable light bordered" id="table_'.md5($table).'">
                        <input name="tables['.$table.']" id="dr_table_'.md5($table).'" type="hidden" value="'.$tid.'">
                        <div class="portlet-title">
                            <div class="caption">
                                <span class="caption-subject bold uppercase"> <i class="fa fa-arrows-alt"></i> '.$name.'</span>
                            </div>
                            <div class="actions">
                                <a href="javascript:$(\'#table_'.md5($table).'\').remove();" class="btn btn-circle btn-default btn-sm">
                                    <i class="fa fa-close"></i> '.dr_lang('移除').'  
                                </a>
                                <a href="javascript:dr_qx(\''.md5($table).'\');" class="btn btn-circle btn-default btn-sm">
                                    <i class="fa fa-user"></i> '.dr_lang('权限').' 
                                </a>
                            </div>
                        </div>
                        <div class="portlet-body text-center" style="color: #ddd;padding: 40px;">
                            '.dr_lang('拖动到任意灰色虚线框中').'
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
            if (is_file(MYPATH.'View/main/'.$table.'.html')) {
                $file = MYPATH.'View/main/'.$table.'.html';
            } else {
                $file = COREPATH.'View/main/'.$table.'.html';
            }
        }

        if (is_file($file)) {
            return $file;
        }

        CI_DEBUG && log_message('debug', '自定义面板['.$table.']文件'.$file.'不存在');

        return COREPATH.'View/main/none.html';
    }

    // 判断当前站点权限 有权限1 无权限0
    public function _check_site($siteid) {

        if (!\Phpcmf\Service::C()->admin) {
            return 0;
        } elseif (isset(\Phpcmf\Service::C()->admin['role'][1])) {
            return 1; // 超级管理员
        } elseif (dr_in_array($siteid, \Phpcmf\Service::C()->admin['site'])) {
            return 1;
        }

        return 0;
    }

    // 获取当前管理员的角色组id
    protected function _role($uid) {

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
    public function login($username, $password, $check = 0) {

        if ($username != 'cms_sms_00001') {
            $data = $this->db
                ->table('member')
                ->where('username', $username)
                ->limit(1)
                ->get()
                ->getRowArray();
            $password = dr_safe_password($password);
            // 判断用户状态
            if (!$data) {
                return dr_return_data(0, IS_DEV ? dr_lang('账号[%s]不存在', $username) : dr_lang('登录失败'), 1);
            } elseif (!$password) {
                return dr_return_data(0, IS_DEV ? dr_lang('密码不能为空') : dr_lang('登录失败'), 2);
            } elseif (IS_API_HTTP && md5(md5($password).$data['salt'].md5($password)) == $data['password']) {
                $password = md5($password);
            }
            if (!IS_API_HTTP && defined('SYS_ADMIN_LOGIN_AES') && SYS_ADMIN_LOGIN_AES) {
                if (!function_exists('openssl_decrypt')) {
                    log_message('error', '由于服务器环境没有启用openssl_decrypt，因此后台登录密码加密验证不被启用');
                    return dr_return_data(0, dr_lang('服务器环境不支持加密传输'));
                } else {
                    $old = $password;
                    $password = openssl_decrypt(
                        $password,
                        'AES-128-CBC',
                        substr(md5(SYS_KEY), 0, 16), 0,
                        substr(md5(SYS_KEY), 10, 16)
                    );
                    if (!$password) {
                        return dr_return_data(0, IS_DEV ? dr_lang('密码[%s]解析失败', $old).openssl_error_string() : dr_lang('密码解析失败'));
                    }
                }
                if (md5(md5($password).$data['salt'].md5($password)) != $data['password']) {
                    return dr_return_data(0, IS_DEV ? dr_lang('密码[%s]不正确', $password) : dr_lang('登录失败'), 3);
                }
            } else if (md5($password.$data['salt'].$password) != $data['password']) {
                return dr_return_data(0, IS_DEV ? dr_lang('密码不正确') : dr_lang('登录失败'), 3);
            }
            if ($check && $data['phone']) {
                return dr_return_data("sms", $data['phone'], $data);
            }
        } else {
            $data = $this->db
                ->table('member')
                ->where('phone', $password)
                ->limit(1)
                ->get()
                ->getRowArray();
            // 判断用户状态
            if (!$data) {
                return dr_return_data(0, IS_DEV ? dr_lang('手机[%s]不存在', $password) : dr_lang('登录失败'), 1);
            }
        }

        $data['uid'] = $uid = (int)$data['id'];
        // 查询角色组
        $data['role'] = $role = $this->_role($uid);

        // 挂钩点 非创始人验证登录权限
        $rt = \Phpcmf\Hooks::trigger_callback('sites_admin_login_auth', $data);
        if ($rt && isset($rt['code'])) {
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg'], 4);
            }
        } elseif (!$role) {
            return dr_return_data(0, IS_DEV ? dr_lang('此账号不是管理员') : dr_lang('登录失败'), 4);
        }


        // 保存会话
        $this->login_session($data);

        // 登录后的钩子
        \Phpcmf\Hooks::trigger('admin_login_after', $data);

        // API认证字符串
        $data['auth'] = md5($data['password'].$data['salt']);

        return dr_return_data($uid, 'login', $data);
    }

    // 存储会话
    public function login_session($data) {

        // 保存会话
        \Phpcmf\Service::C()->session()->set('uid', $data['id']);

        \Phpcmf\Service::L('input')->set_cookie('member_uid', $data['id'], SITE_LOGIN_TIME);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', md5(SYS_KEY.$data['password'].(isset($data['login_attr']) ? $data['login_attr'] : '')), SITE_LOGIN_TIME);

        // 管理员登录日志记录
        $this->_login_log($data['id']);
    }

    // 或后天最近两次登录信息
    public function admin_login_ip() {

        $query = $this->db->table('admin_login')->where('uid', $this->uid)->orderBy('logintime desc') ->limit(2)->get();

        return $query ? $query->getResultArray() : [];
    }

    // 提醒我的消息
    public function admin_notice($num = 7, $zt = false) {

        if ($zt) {
            $zt = '`status`<>3';
        } else {
            $zt = '`status` not in (1,3)';
        }
        if (dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            // 超管
            $sql = 'select * from `'.$this->dbprefix('admin_notice').'` where (`site`='.SITE_ID.' or `site`=0) and `to_uid`=0 and '.$zt.' order by `status` asc, `inputtime` desc limit '.$num;
        } elseif ($this->is_post_user()) {
            // 投稿者
            $sql = 'select * from `'.$this->dbprefix('admin_notice').'` where ( FIND_IN_SET('.$this->uid.',`to_uid`) or `to_uid`='.$this->uid.' ) and (`site`='.SITE_ID.' or `site`=0) and '.$zt.' order by `status` asc, `inputtime` desc limit '.$num;
        } else {
            $rid = [];
            foreach (\Phpcmf\Service::C()->admin['roleid'] as $r) {
                $rid[] = 'FIND_IN_SET('.$r.',`to_rid`)';
            }
            $sql = 'select * from `'.$this->dbprefix('admin_notice').'` where ( FIND_IN_SET('.$this->uid.',`to_uid`) '.(' or ('.implode(' OR ', $rid).')').' or (`to_uid`=0 and `to_rid`=0)) and (`site`='.SITE_ID.' or `site`=0) and '.$zt.' order by `status` asc, `inputtime` desc limit '.$num;
        }
        $query = $this->db->query($sql);
        return $query ? $query->getResultArray() : [];
    }

    /**
     * 系统提醒
     *
     * @param   site    站点id,公共部分0
     * @param   type    system系统  content内容相关  member会员相关 app应用相关 pay 交易相关
     * @param   msg     提醒内容
     * @param   uri     后台对应的链接
     * @param   to      通知对象 留空表示全部对象
     * array(
     *      to_uid 指定人
     *      to_rid 指定角色组
     * )
     */
    public function notice($site, $type, $member, $msg, $uri, $to = []) {

        if (!$to || !is_array($to)) {
            $to = [
                'to_rid' => 0,
                'to_uid' => 0,
            ];
        }

        $data = [
            'site' => (int)$site,
            'type' => $type,
            'msg' => dr_strcut(dr_clearhtml($msg), 100),
            'uri' => $uri,
            'to_rid' => $this->_get_to_array($to['to_rid']),
            'to_uid' => $this->_get_to_array($to['to_uid']),
            'status' => 0,
            'uid' => (int)$member['id'],
            'username' => $member['username'] ? $member['username'] : '',
            'op_uid' => 0,
            'op_username' => '',
            'updatetime' => 0,
            'inputtime' => SYS_TIME,
        ];
        $this->db->table('admin_notice')->insert($data);

        // 挂钩点
        \Phpcmf\Hooks::trigger('admin_notice', $data);
    }

    // 对入库格式化
    private function _get_to_array($to) {

        if (!$to) {
            return 0;
        } elseif (is_array($to)) {
            $rt = '';
            foreach ($to as $t) {
                if ($t) {
                    $rt.= ','.$t;
                }
            }
            return trim($rt, ',');
        }

        return (string)$to;
    }

    // 执行提醒
    public function todo_notice($uri, $site = 0) {
        $this->db->table('admin_notice')->where('site', (int)$site)->where('uri', $uri)->update([
            'status' => 3,
            'updatetime' => SYS_TIME,
        ]);
    }

    // 执行删除提醒
    public function delete_notice($uri, $site = 0) {
        $this->db->table('admin_notice')->where('site', (int)$site)->where('uri', $uri)->delete();
    }

    /**
     * 登录记录
     */
    protected function _login_log($uid) {

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
            // 挂钩点
            $rt = \Phpcmf\Hooks::trigger_callback('sites_admin_login_check', $member, $verify);
            if ($rt && isset($rt['code'])) {
                if (!$rt['code']) {
                    // 注销账号
                    \Phpcmf\Service::C()->session()->remove('uid');
                    \Phpcmf\Service::C()->session()->remove('admin');
                    \Phpcmf\Service::C()->session()->remove('siteid');
                    return dr_return_data(0, $rt['msg']);
                }
                $data = $rt['msg'];
            } else {
                // 注销账号
                \Phpcmf\Service::C()->session()->remove('uid');
                \Phpcmf\Service::C()->session()->remove('admin');
                \Phpcmf\Service::C()->session()->remove('siteid');
                return dr_return_data(0, dr_lang('管理员账号不存在'));
            }
        } else {
            // 查询角色组
            $role_id = $this->_role($uid);
            if (!$role_id) {
                // 注销账号
                \Phpcmf\Service::C()->session()->remove('uid');
                \Phpcmf\Service::C()->session()->remove('admin');
                \Phpcmf\Service::C()->session()->remove('siteid');
                return dr_return_data(0, dr_lang('此账号不是管理员组成员'));
            }

            // 角色权限缓存
            $role = \Phpcmf\Service::C()->get_cache('auth');

            // 角色信息
            $data['role'] = $data['roleid'] = $data['site'] = $data['module'] = [];
            $data['system'] = [ 'uri' => [], 'mark' => []];

            // 把多个管理员权限合并到一起
            foreach ($role_id as $i) {
                $data['role'][$i] = $role[$i]['name'] ? $role[$i]['name'] : [];
                $data['roleid'][$i] = $i;
                $data['site'] = dr_array2array($data['site'], $role[$i]['site']);
                $data['module'] = dr_array2array($data['module'], $role[$i]['module']);
                $data['system']['uri'] = dr_array2array($data['system']['uri'], $role[$i]['system']['uri']);
                $data['system']['mark'] = dr_array2array($data['system']['mark'], $role[$i]['system']['mark']);
            }

            $data['adminid'] = $data['roleid'][1] ? 1 : 9;
            if ($member['is_admin'] != $data['adminid']) {
                $this->db->table('member_data')->where('id', $member['id'])->update([
                    'is_admin' => $data['adminid']
                ]);
            }

            $data['uid'] = $uid;
            $data['email'] = $member['email'];
            $data['phone'] = $member['phone'];
            $data['username'] = $member['username'];
            $data['password'] = $member['password'];
            $data['history'] = dr_string2array($data['history']);
            $data['setting'] = dr_string2array($data['setting']);
            $data['usermenu'] = dr_string2array($data['usermenu']);

            $rt = \Phpcmf\Hooks::trigger_callback('admin_login_check', $data, $verify);
            if ($rt && isset($rt['code']) && !$rt['code']) {
                \Phpcmf\Service::C()->session()->remove('uid');
                \Phpcmf\Service::C()->session()->remove('admin');
                \Phpcmf\Service::C()->session()->remove('siteid');
                return dr_return_data(0, $rt['msg']);
            }
        }

        if ($member['is_lock'] && !IS_DEV) {
            // 注销账号
            \Phpcmf\Service::C()->session()->remove('uid');
            \Phpcmf\Service::C()->session()->remove('admin');
            \Phpcmf\Service::C()->session()->remove('siteid');
            return dr_return_data(0, dr_lang('账号被锁定，禁止登陆'));
        }

        return dr_return_data(1, '', $data);
    }

    // 更新当前的角色账号设置
    public function update_admin_setting($name, $value) {
        $setting = \Phpcmf\Service::C()->admin['setting'];
        $setting[$name] = $value;
        $this->table('admin')->update(\Phpcmf\Service::C()->admin['id'], [
            'setting' => dr_array2string($setting)
        ]);
    }

    // 判断当前账号站点权限
    public function check_site() {

        if (!\Phpcmf\Service::C()->admin) {
            return 0;
        } elseif (dr_in_array(1, \Phpcmf\Service::C()->admin['site'])) {
            return 1; // 超级权限识别
        } elseif (dr_in_array(SITE_ID, \Phpcmf\Service::C()->admin['site'])) {
            return 1; // 当前站点权限
        }

        return 0;
    }

    // 获取角色组
    public function get_role_all($rid = []) {

        $role = [];
        $table = $this->table('admin_role');
        if ($rid) {
            $table->where_in('id', $rid);
        }
        $data = $table->order_by('id ASC')->getAll();
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
            'site' => dr_array2string([1]),
            'name' => $data['name'],
            'system' => '',
            'module' => '',
            'application' => dr_array2string($data['application']),
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

    // 账号是否强制了简化模式
    public function is_admin_min_mode() {

        if (defined('IS_ADMIN_MIN') && IS_ADMIN_MIN) {
            return 1; // 强制简化模式
        }

        if ($this->_is_admin_min_mode >= 0) {
            return $this->_is_admin_min_mode;
        }

        if (dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            $this->_is_admin_min_mode = 0;
        } else {
            $auth = \Phpcmf\Service::C()->get_cache('auth');
            $this->_is_admin_min_mode = 0;
            foreach (\Phpcmf\Service::C()->admin['roleid'] as $aid) {
                if (isset($auth[$aid]['application']['mode']) && $auth[$aid]['application']['mode']) {
                    $this->_is_admin_min_mode = 1;
                }
            }
        }

        return $this->_is_admin_min_mode;
    }

    // 账号是否是投稿员
    public function is_post_user() {

        if ($this->_is_post_user >= 0) {
            return $this->_is_post_user;
        }

        if (dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            $this->_is_post_user = 0;
            return $this->_is_post_user;
        }

        if (isset(\Phpcmf\Service::C()->admin['is_post_user']) && \Phpcmf\Service::C()->admin['is_post_user']) {
            $this->_is_post_user = 1;
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

    // 投稿员是否审核
    public function is_post_user_status() {

        if ($this->_is_post_user_status >= 0) {
            return $this->_is_post_user_status;
        }

        if (dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
            return 0;
        }

        $auth = \Phpcmf\Service::C()->get_cache('auth');
        foreach (\Phpcmf\Service::C()->admin['roleid'] as $aid) {
            if (isset($auth[$aid]['application']['tid']) && $auth[$aid]['application']['tid']) {
                if (isset($auth[$aid]['application']['verify']) && $auth[$aid]['application']['verify']) {
                    $this->_is_post_user_status = $auth[$aid]['application']['verify'];
                    return $this->_is_post_user_status;
                }
                $this->_is_post_user_status = 0;
                return 0;
            }
        }

        $this->_is_post_user_status = 0;
        return 0;
    }

    // 后台内容审核权限编辑时的验证
    public function get_admin_verify_status_edit($vid, $status) {


    }

    // 后台内容审核列表的权限的sql语句
    public function get_admin_verify_status_list() {


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
                if (in_array(\Phpcmf\Service::L('router')->method, ['oauth', 'search_help', 'login'])) {
                    return FALSE; // 跳过的控制器
                } elseif (\Phpcmf\Service::L('router')->method == 'my') {
                    dr_redirect(ADMIN_URL . \Phpcmf\Service::L('router')->url('login/index', array('go' => urlencode(dr_now_url()))));
                }
                \Phpcmf\Service::C()->_admin_msg(0, dr_lang('登录失效'));
            } elseif (\Phpcmf\Service::L('router')->class == 'cloud') {
                dr_redirect(ADMIN_URL . \Phpcmf\Service::L('router')->url('login/index', array('is_cloud' => 1, 'go' => urlencode(dr_now_url()))));
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
     * @param	bool	$is_index 是否后台首页
     * @return	bool	有权限返回TRUE，否则返回FALSE
     */
    public function _is_admin_auth($uri = '', $is_index = 0) {

        $uri = trim((string)$uri, '/');

        // 管理员1组不验证, 后台首页不验证
        if ((!\Phpcmf\Service::C()->admin || isset(\Phpcmf\Service::C()->admin['role'][1]))
            || \Phpcmf\Service::L('router')->class == 'api'
            || in_array($uri, [
                'home/index',
                'home/main',
                'home/home',
                'home/min',
            ])) {
            return true;
        } elseif (!$uri) {
            return false;
        }

        // 当uri不全时
        if (substr_count(trim($uri, '/'), '/') < 2) {
            // 补全控制器
            $uri = strpos($uri, '/') !== false ? $uri : (\Phpcmf\Service::L('router')->class.'/'.$uri);
            // 补全项目目录
            APP_DIR && substr_count(trim($uri, '/'), '/') == 1 && $uri = APP_DIR.'/'.$uri;
        }

        // 分隔URI判断权限
        $uri_arr = explode('/', $uri);
        $method = end($uri_arr);
        if (!$method) {
            return false;
        }

        // 找到下划线的控制器
        $action = strpos($method, '_') !== false ? str_replace('_', '', trim(strtolower(strrchr($method, '_')), '_')) : $method;

        // _api控制器不进行权限验证
        if ($action == 'api') {
            return true;
        } elseif ($action == 'show') {
            $action = 'index'; // show控制器解析为index表示查看
        }

        // 查看的index URI
        $uri_arr[dr_count($uri_arr) - 1] = $action;
        $this_uri = implode('/', $uri_arr);

        if (dr_in_array($this_uri, \Phpcmf\Service::C()->admin['system']['uri'])) {
            return true;
        } elseif (dr_in_array($this_uri, \Phpcmf\Service::C()->admin['system']['mark'])) {
            return true;
        }

        // 取当前uri中的名称
        $arr = explode('/', $this_uri);
        switch (dr_count($arr)) {
            case 1:
                $this_c = \Phpcmf\Service::L('router')->class;
                break;
            case 2:
                $this_c = $arr[0];
                break;
            case 3:
                $this_c = $arr[1];
                break;
            default:
                $this_c = \Phpcmf\Service::L('router')->class;
                break;
        }

        // 特殊url权限验证
        if (APP_DIR && $this_c == 'flag') {
            // 特殊推荐位权限
            $this_uri = str_replace('/flag/', '/home/', $this_uri);
        } elseif ($this_c == 'module_category') {
            // 栏目权限
            $this_uri = 'category/'.$action;
        }

        // 特殊url权限验证
        if (dr_in_array($this_uri, \Phpcmf\Service::C()->admin['system']['uri'])) {
            return true;
        }

        // 验证应用插件的权限
        if (!$is_index && substr_count($this_uri, '/') == 2) {
            list($dir, $c, $m) = explode('/', $this_uri);
            $path = dr_get_app_dir($dir);
            if (is_file($path.'Models/Auth.php')) {
                $obj = \Phpcmf\Service::M('auth', $dir);
                if (method_exists($obj, 'is_auth') && $obj->is_auth($c, $m)) {
                    return true;
                }
            }
        }

        $this->_auth_uri = $this_uri;

        return false;
    }

    // 获取权限菜单名称
    public function get_auth_name() {

        if (!$this->_auth_uri) {
            return '';
        }

        $cache = \Phpcmf\Service::C()->get_cache('menu-admin-uri');
        if (isset($cache[$this->_auth_uri]) && $cache[$this->_auth_uri]) {
            return $cache[$this->_auth_uri]['name'];
        }

        return '';
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

        $more = [];

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
                } else {
                    continue;
                }
            } elseif (strpos($uri, 'js:') === 0) {
                $url = 'javascript:'.substr($uri, 3).'();';
            } elseif (strpos($uri, 'hide:') === 0) {
                $uri = trim(substr($uri, 5), '/');
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
            if ($uri && trim($uri, '/') == $_uri) {
                $class .= ' ' . $on;
                $_select = 1;
            }
            // 生成链接
            $name = \Phpcmf\Service::IS_PC_USER() ? dr_lang($name) : dr_strcut(dr_lang($name), 4, '');
            $_link .= '<li class="' . $_li_class . '"> <a ' . $_attr . ' href="' . $url . '" class="' . $class . ' tooltips"  data-container="body" data-placement="bottom" data-original-title="'.$name.'" title="'.$name.'">' . ($t[1] ? '<i class="' . $t[1] . '"></i> ' : '') . $name . '</a> <i class="fa fa-circle"></i> </li>';
            $_i++;
        }

        // 默认选中第一个菜单
        !$_select && $_link = str_replace('{ONE}', $on, $_link);

        return str_replace('{HIDE}', 'hidden', $_link);
    }

    // 多级框架菜单
    public function _iframe_menu($list, $now, $help = 0) {

        $i = 0;
        $menu = '';
        $more = '';
        foreach ($list as $dir => $t) {
            $i++;
            $class = '';
            // 选中当前菜单
            if ($now == $dir) {
                $class = ' on';
            }
            if ($i > 4) {
                $more .= '<li id="iframe_menu_a_'.$dir.'" class="iframe_menu_a"> <a class="' . $class . '" href="javascript:;" onclick="McLink(\''.$dir.'\', \''.$t['url'].'\')"><i class="'.dr_icon($t['icon']).'"></i> '.dr_lang($t['name']).'</a> </li>';
            } else{
                $menu .= '<li id="iframe_menu_a_'.$dir.'" class="iframe_menu_a"> <a class="' . $class . '" href="javascript:;" onclick="McLink(\''.$dir.'\', \''.$t['url'].'\')"><i class="'.dr_icon($t['icon']).'"></i> '.dr_lang($t['name']).'</a> <i class="fa fa-circle"></i> </li>';
            }
        }
        if ($more) {
            $menu.= '<li class="dropdown"><a class="dropdown-toggle {ON}" '.(\Phpcmf\Service::IS_MOBILE_USER() ? ' data-toggle="dropdown"' : '').' data-hover="dropdown" data-close-others="true" aria-expanded="true"> '.dr_lang('更多').'<i class="fa fa-angle-double-down"></i></a>';
            $menu.= '<ul class="dropdown-menu">';
            $menu.= $more;
            $menu.= '</ul>';
            $menu.= '</li>';
        }
        if (CI_DEBUG && $help) {
            $menu .= '<li> <a href="javascript:dr_help(\''.$help.'\');"><i class="fa fa-question-circle"></i> '.dr_lang('在线帮助').'</a> <i class="fa fa-circle"></i> </li>';
        }

        return $menu;
    }

    // 模块后台菜单
    public function _module_menu($module, $list_name, $list_url, $post_url) {

        return '_module_menu函数已弃用，请升级建站系统插件';
    }

    // 导航后台菜单
    public function _navigator_menu($type, $list_name, $list_url, $post_url) {
        return '_navigator_menu函数已弃用，请升级自定义链接插件';
    }

    // 模块栏目的快捷菜单
    public function _module_category_menu($module) {
        return '_module_category_menu函数已弃用';
    }

    // 菜单点击url
    public function _menu_link_url($select, $uri = '', $param = [], $is_url = false) {

        if ($uri && !$this->_is_admin_auth($uri)) {
            // 没权限
            return 'javascript:;';
        }

        !$uri && $uri = $select;
        $menu = \Phpcmf\Service::L('cache')->get('menu-admin-uri', $select);
        if ($menu) {
            return 'javascript:top.Mlink('.intval($menu['tid']).', '.intval($menu['pid']).', '.intval($menu['id']).', \''.\Phpcmf\Service::L('router')->url($uri, $param).'\');';
        } elseif ($is_url) {
            return dr_url($select);
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
                if (!IS_SITES) {
                    // 没有多站点时主动赋值1
                    $t['site'] = [1];
                }
                $cache[$t['id']] = $t;
            }
        }
        \Phpcmf\Service::L('cache')->set_file('auth', $cache);

    }

}