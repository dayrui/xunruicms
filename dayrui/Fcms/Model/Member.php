<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Member extends \Phpcmf\Model {

    protected $sso_url;

    /**
     * 由用户名获取uid
     */
    public function uid($name) {

        if (!$name || dr_lang('游客') == $name) {
            return 0;
        } elseif ($name == $this->member['username']) {
            return $this->member['uid'];
        }

        $data = $this->db->table('member')
            ->select('id')
            ->where('username', dr_safe_replace($name))
            ->get()->getRowArray();

        return intval($data['id']);
    }

    /**
     * 由uid获取用户名
     */
    public function username($uid) {

        $uid = intval($uid);

        if (!$uid) {
            return '';
        } elseif ($uid == $this->member['uid']) {
            return $this->member['username'];
        }

        $data = $this->db->table('member')
            ->select('username')
            ->where('id', $uid)
            ->get()->getRowArray();

        return $data['username'];
    }

    /**
     * 后台账号字段获取用户名
     */
    public function author($uid) {

        if (!$uid) {
            return dr_lang('游客');
        }

        return $this->username($uid);
    }

    /**
     * 由uid获取电话
     */
    public function phone($uid) {

        $uid = intval($uid);
        if (!$uid) {
            return '';
        } elseif ($uid == $this->member['uid']) {
            return $this->member['phone'];
        }

        $data = $this->db->table('member')
            ->select('phone')
            ->where('id', $uid)
            ->get()->getRowArray();

        return $data['phone'];
    }

    // 用户基本信息
    public function member_info($uid) {

        $uid = intval($uid);
        if (!$uid) {
            return [];
        } elseif ($uid == $this->member['uid']) {
            return $this->member;
        }

        $data = $this->db->table('member')
            ->where('id', $uid)
            ->get()->getRowArray();
        if (!$data) {
            return [];
        }

        $data['uid'] = $data['id'];

        return $data;
    }

    /**
     * 登录记录
     *
     * @param   intval  $data       会员
     * @param   string  $OAuth      登录方式
     */
    protected function _login_log($data, $type = '') {

        if (!IS_USE_MEMBER) {
            return;
        }

        \Phpcmf\Service::M('member', 'member')->_login_log($data, $type);
    }

    /**
     * 取会员COOKIE
     */
    public function member_uid() {

        // 获取本地cookie
        $uid = (int)\Phpcmf\Service::L('input')->get_cookie('member_uid');
        if (!$uid) {
            return 0;
        }

        return $uid;
    }


    /**
     * 初始化处理
     */
    public function init_member($member) {

        if (!$member || !IS_USE_MEMBER) {
            return;
        }


        \Phpcmf\Service::M('member', 'member')->init_member($member);
    }

    /**
     * 存储cookie
     */
    public function save_cookie($data, $remember = 0) {

        // 存储cookie
        $expire = $remember ? 8640000 : SITE_LOGIN_TIME;
        \Phpcmf\Service::L('input')->set_cookie('member_uid', $data['id'], $expire);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', md5(SYS_KEY.$data['password'].(isset($data['login_attr']) ? $data['login_attr'] : '')), $expire);

        // 登录后的钩子，在_login_log中执行他
        //\Phpcmf\Hooks::trigger('member_login_after', $data);

        $this->clear_cache($data['id']);
    }

    /**
     * 验证会员有效性 1表示通过 0表示不通过
     */
    public function check_member_cookie($member) {

        // 获取本地认证cookie
        $cookie = \Phpcmf\Service::L('input')->get_cookie('member_cookie');

        // 授权登陆时不验证
        if ($member['id'] && \Phpcmf\Service::C()->session()->get('member_auth_uid') == $member['id']) {
            return 1;
        } elseif (!$cookie) {
            return 0;
        } elseif (md5(SYS_KEY.$member['password'].(isset($member['login_attr']) ? $member['login_attr'] : '')) != $cookie) {
            return 0;
        }

        return 1;
    }

    /**
     * 授权登录信息
     */
    public function oauth($uid) {

        if (!IS_USE_MEMBER) {
            return [];
        }

        return \Phpcmf\Service::M('member', 'member')->oauth($uid);
    }

    /**
     * 会员信息
     */
    public function get_member($uid = 0, $name = '') {

        $uid = intval($uid);
        if ($uid && $uid == $this->member['id']) {
            return $this->member;
        }

        if ($uid) {
            $data = $this->db->table('member')->where('id', $uid)->get()->getRowArray();
        } elseif ($name) {
            $data = $this->db->table('member')->where('username', $name)->get()->getRowArray();
            $uid = (int)$data['id'];
        } else {
            return null;
        }

        if (!$data) {
            return null;
        }

        // 附表字段
        $data2 = $this->db->table('member_data')->where('id', $uid)->get()->getRowArray();
        $data2 && $data = array_merge($data, \Phpcmf\Service::L('Field')->app('member')->format_value(\Phpcmf\Service::C()->member_cache['field'], $data2));

        $data['uid'] = $data['id'];
        $data['avatar'] = dr_avatar($data['id']);
        $data['adminid'] = (int)$data['is_admin'];
        //$data['tableid'] = (int)substr((string)$data['id'], -1, 1);

        $data['group'] = $data['groupid'] = $data['levelid'] = $data['authid'] = $data['group_name'] = [];
        $data['group_timeout'] = 0;

        // 会员组信息
        if (IS_USE_MEMBER) {
            if (!is_file(IS_USE_MEMBER.'Models/Member.php')) {
                exit('需要离线下载【用户系统】插件，然后手动覆盖到本站：https://www.xunruicms.com/doc/1220.html');
            }
            $data = \Phpcmf\Service::M('member', 'member')->get_member_group($data);
        }

        return $data;
    }

    // 获取authid
    public function authid($uid) {
        if (IS_USE_MEMBER) {
            return \Phpcmf\Service::M('member', 'member')->authid($uid);
        }
    }

    // 更新用户组
    // member 用户信息
    // groups 拥有的用户组
    public function update_group($member, $groups) {

        $g = [];
        if (!$member || !$groups) {
            return $g;
        } elseif (IS_USE_MEMBER) {
            return \Phpcmf\Service::M('member', 'member')->update_group($member, $groups);
        }

        return $g;
    }

    // 删除用户组 is_admin 是否是管理员删除，否则就是过期删除
    public function delete_group($uid, $gid, $is_admin = 1) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：delete_group');
            return false;
        }

        \Phpcmf\Service::M('member', 'member')->delete_group($uid, $gid, $is_admin);
    }

    // 新增用户组
    public function insert_group($uid, $gid, $is_notice = 1) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：insert_group');
            return false;
        }

        return \Phpcmf\Service::M('member', 'member')->insert_group($uid, $gid, $is_notice);
    }

    // 手动变更等级
    public function update_level($uid, $gid, $lid) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：update_level');
            return false;
        }

        return \Phpcmf\Service::M('member', 'member')->update_level($uid, $gid, $lid);
    }

    // 申请用户组
    public function apply_group($verify_id, $member, $gid, $lid, $price, $my_verify) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：apply_group');
            return false;
        }

        return \Phpcmf\Service::M('member', 'member')->apply_group($verify_id, $member, $gid, $lid, $price, $my_verify);
    }

    /**
     * 添加一条通知
     *
     * @param   string  $uid
     * @param   string  $note
     * @return  null
     */
    public function notice($uid, $type, $note, $url = '', $mark = '') {

        if (!$uid || !$note) {
            return '';
        }

        if (dr_is_app('notice')) {
            \Phpcmf\Service::M('notice', 'notice')->add_notice($uid, $type, $note, $url, $mark);
        }

        return '';
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
    public function admin_notice($site, $type, $member, $msg, $uri, $to = []) {
        \Phpcmf\Service::M('auth')->notice($site, $type, $member, $msg, $uri, $to);
    }

    // 执行提醒
    public function todo_admin_notice($uri, $site = 0) {
        \Phpcmf\Service::M('auth')->todo_notice($uri, $site);
    }

    // 执行删除提醒
    public function delete_admin_notice($uri, $site = 0) {
        \Phpcmf\Service::M('auth')->delete_notice($uri, $site);
    }

    // 审核用户
    public function verify_member($uid) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：verify_member');
            return false;
        }

        return \Phpcmf\Service::M('member', 'member')->verify_member($uid);
    }

    // 获取本站通讯地址
    public function get_sso_url() {

        if ($this->sso_url) {
            return $this->sso_url;
        }

        $this->sso_url = [];

        if (is_file(WRITEPATH.'config/domain_sso.php')) {
            $sso = require WRITEPATH.'config/domain_sso.php';
            $rts = [];
            foreach ($sso as $u) {
                list($a) = explode(',', $u);
                if (in_array($a, $rts)) {
                    continue;
                }
                $rts[] = $a;
                $this->sso_url[] = $u ? dr_http_prefix($u).'/' : '/';
            }
        }

        return $this->sso_url;
    }

    /**
     * sso 登录url
     */
    public function sso($data, $remember = 0) {

        $sso = [];
        $url = $this->get_sso_url();
        foreach ($url as $u) {
            $code = dr_authcode($data['id'].'-'.$data['salt'], 'ENCODE');
            $sso[]= $u.'index.php?s=api&c=sso&action=login&remember='.$remember.'&code='.$code;
        }

        return $sso;
    }

    /**
     * 前端会员退出登录
     */
    public function logout() {

        \Phpcmf\Hooks::trigger('member_logout', $this->member);

        \Phpcmf\Service::L('input')->set_cookie('member_uid', 0, -100000000);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', '', -100000000);
        \Phpcmf\Service::L('input')->set_cookie('admin_login_member', '', -100000000);

        $sso = [];
        $url = $this->get_sso_url();
        foreach ($url as $u) {
            $sso[]= $u.'index.php?s=api&c=sso&action=logout';
        }

        return $sso;
    }

    // 查询会员信息
    protected function _find_member_info($username) {

        $data = $this->db->table('member')->where('username', $username)->get()->getRowArray();
        if (!$data && \Phpcmf\Service::C()->member_cache['login']['field']) {
            if (dr_in_array('email', \Phpcmf\Service::C()->member_cache['login']['field'])
                && \Phpcmf\Service::L('Form')->check_email($username)) {
                $data = $this->db->table('member')->where('email', $username)->get()->getRowArray();
            } elseif (dr_in_array('phone', \Phpcmf\Service::C()->member_cache['login']['field'])
                && \Phpcmf\Service::L('Form')->check_phone($username)) {
                $data = $this->db->table('member')->where('phone', $username)->get()->getRowArray();
            }
        }

        if (!$data) {
            return [];
        }

        $data['uid'] = $data['id'];

        return $data;
    }

    // 验证管理员登录权限
    protected function _is_admin_login_member($uid) {

        if (!$uid) {
            return dr_return_data(1, 'ok');
        }

        if (!\Phpcmf\Service::C()->member_cache['login']['admin']
            && $this->db->table('admin')->where('uid', $uid)->countAllResults()) {
            return dr_return_data(0, dr_lang('管理员账号不允许前台登录'));
        }

        return dr_return_data(1, 'ok');
    }

    /**
     * 验证登录
     *
     * @param   string  $username   用户名
     * @param   string  $password   明文密码
     * @param   intval  $remember   是否记住密码
     */
    public function login($username, $password, $remember = 0) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：login');
            return dr_return_data(0, dr_lang('没有安装【用户系统】插件'));
        }

        return \Phpcmf\Service::M('member', 'member')->login($username, $password, $remember);
    }

    // 短信登录
    public function login_sms($phone, $remember) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：login_sms');
            return dr_return_data(0, dr_lang('没有安装【用户系统】插件'));
        }

        return \Phpcmf\Service::M('member', 'member')->login_sms($phone, $remember);
    }

    // 授权登录
    public function login_oauth($name, $data) {

        // 保存本地会话
        $this->save_cookie($data);

        // 记录日志
        $this->_login_log($data, $name);

        return $this->sso($data);
    }

    // 绑定注册模式 授权注册绑定
    public function register_oauth_bang($oauth, $groupid, $member, $data = []) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：register_oauth_bang');
            return dr_return_data(0, dr_lang('没有安装【用户系统】插件'));
        }

        return \Phpcmf\Service::M('member', 'member')->register_oauth_bang($oauth, $groupid, $member, $data);
    }

    // api直接按uid登录
    public function login_uid($oauth, $uid) {

        $member = $this->get_member($uid);
        if (!$member) {
            return dr_return_data(0, dr_lang('用户不存在'));
        }

        // 保存本地会话
        $this->save_cookie($member);

        // 记录日志
        $this->_login_log($member, $oauth['oauth']);

        return dr_return_data($member['id'], dr_lang('登录成功'), [
            'auth'=> md5($member['password'].$member['salt']), // API认证字符串,
            'member' => $member,
        ]);
    }

    // 直接登录模式 授权注册
    public function register_oauth($groupid, $oauth) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：register_oauth_bang');
            return dr_return_data(0, dr_lang('没有安装【用户系统】插件'));
        }

        return \Phpcmf\Service::M('member', 'member')->register_oauth($groupid, $oauth);
    }

    /**
     * 用户注册
     *
     * @param   用户组
     * @param   注册账户信息
     * @param   自定义字段信息
     * @param   快捷登录注册
     */
    public function register($groupid, $member, $data = [], $oauth = []) {

        $member['email'] && $member['email'] = strtolower($member['email']);
        $member['name'] = htmlspecialchars(!$member['name'] ? '' : dr_strcut($member['name'], intval(\Phpcmf\Service::C()->member_cache['register']['cutname']), ''));

        // 没有账号，随机一个默认登录账号
        if (!$member['username']) {
            $member['username'] = $this->_register_rand_username($member);
            // 随机账号不满足规范时
        } else {
            $member['username'] = strtolower(dr_safe_filename($member['username']));
        }

        $member['salt'] = substr(md5(rand(0, 999)), 0, 10); // 随机10位密码加密码

        // 默认注册组
        !$groupid && $groupid = (int)\Phpcmf\Service::C()->member_cache['register']['groupid'];

        if ((\Phpcmf\Service::C()->member_cache['oauth']['login'] || !\Phpcmf\Service::C()->member_cache['oauth']['field']) && $oauth) {
            // 授权登录直接模式
            if ($member['username'] && dr_in_array('username', \Phpcmf\Service::C()->member_cache['register']['field'])) {
                // 验证账号是否规范
                $rt = \Phpcmf\Service::L('Form')->check_username($member['username']);
                if (!$rt['code']) {
                    $member['username'] = $this->_register_rand_username($member);
                }
            }
            if ($member['name'] && \Phpcmf\Service::C()->member_cache['config']['unique_name']
                && $this->db->table('member')->where('name', $member['name'])->countAllResults()) {
                $member['name'] = $member['name'].'_XR_RAND_';
            }
        } else {
            // 验证格式
            if ($member['username'] && dr_in_array('username', \Phpcmf\Service::C()->member_cache['register']['field'])) {
                $rt = \Phpcmf\Service::L('Form')->check_username($member['username']);
                if (!$rt['code']) {
                    return $rt;
                }
            }
            if ($member['email'] && dr_in_array('email', \Phpcmf\Service::C()->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_email($member['email'])) {
                return dr_return_data(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif ($member['phone'] && dr_in_array('phone', \Phpcmf\Service::C()->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_phone($member['phone'])) {
                return dr_return_data(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            }
            // 前端验证密码格式
            if (!IS_ADMIN) {
                if ($member['password'] == SYS_KEY.'_login_sms') {
                    $member['salt'] = 'login_sms';
                    $member['password'] = rand(0, 99999); // 表示短信直接注册
                } else {
                    $rt = \Phpcmf\Service::L('Form')->check_password($member['password'], $member['username']);
                    if (!$rt['code']) {
                        return $rt;
                    }
                }
            }

            // 验证唯一性
            if ($member['username'] && $this->db->table('member')->where('username', $member['username'])->countAllResults()) {
                return dr_return_data(0, dr_lang('账号已经注册'), ['field' => 'username']);
            } elseif ($member['email'] && $this->db->table('member')->where('email', $member['email'])->countAllResults()) {
                return dr_return_data(0, dr_lang('邮箱已经注册'), ['field' => 'email']);
            } elseif ($member['phone'] && $this->db->table('member')->where('phone', $member['phone'])->countAllResults()) {
                return dr_return_data(0, dr_lang('手机号码已经注册'), ['field' => 'phone']);
            } elseif ($member['name'] && \Phpcmf\Service::C()->member_cache['config']['unique_name']
                && $this->db->table('member')->where('name', $member['name'])->countAllResults()) {
                return dr_return_data(0, dr_lang('%s已经注册', MEMBER_CNAME), ['field' => 'name']);
            }

            if ($member['username'] == 'guest') {
                return dr_return_data(0, dr_lang('此名称guest系统不允许注册'), ['field' => 'username']);
            }
        }
        /*
        elseif (!IS_ADMIN && \Phpcmf\Service::C()->member_cache['register']['notallow']) {
            foreach (\Phpcmf\Service::C()->member_cache['register']['notallow'] as $mt) {
                if ($mt && stripos($member['username'], $mt) !== false) {
                    return dr_return_data(0, dr_lang('账号[%s]禁止包含关键字[%s]', $member['username'], $mt), ['field' => 'username']);
                }
            }
        }*/

        $member['password'] = $member['password'] ? md5(md5($member['password']).$member['salt'].md5($member['password'])) : '';
        $member['login_attr'] = '';
        $member['money'] = 0;
        $member['freeze'] = 0;
        $member['spend'] = 0;
        $member['score'] = 0;
        $member['experience'] = 0;
        $member['regip'] = $oauth ? '' : \Phpcmf\Service::L('input')->ip_info(); // 快捷登录时不记录ip
        $member['regtime'] = SYS_TIME;
        $member['randcode'] = \Phpcmf\Service::L('form')->get_rand_value();

        // 防止重复账号
        if ($member['username'] && $this->db->table('member')->where('username', $member['username'])->countAllResults()) {
            $member['username'] = '';
        }

        $rt = $this->table('member')->insert($member);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        $update = [];
        // 姓名随机替换
        if (\Phpcmf\Service::C()->member_cache['config']['unique_name'] && strpos($member['name'], '_XR_RAND_')) {
            $update['name'] = str_replace( '_XR_RAND_', intval($rt['code']+date('Ymd')), $member['name']);
        }
        // 再次判断没有账号，随机一个默认登录账号
        if (!$member['username']) {
            $member['username'] = '';
            if (isset(\Phpcmf\Service::C()->member_cache['register']['unprefix']) && \Phpcmf\Service::C()->member_cache['register']['unprefix']) {
                $member['username'] = strtolower(trim((string)\Phpcmf\Service::C()->member_cache['register']['unprefix']));
            }
            $member['username'].= intval($rt['code']+date('Ymd'));
            $update['username'] = $member['username'];
        }
        // 更新操作
        $update && $this->table('member')->update($rt['code'], $update);

        // 附表信息
        $data['id'] = $member['uid'] = $member['id'] = $uid = $rt['code'];
        $data['is_admin'] = 0;
        $data['is_avatar'] = 0;
        // 审核状态值
        if (IS_ADMIN) {
            $status = \Phpcmf\Service::L('input')->post('status');
            $data['is_lock'] = isset($status['is_lock']) ? intval($status['is_lock']) : 0;
            $data['is_email'] = isset($status['is_email']) ? intval($status['is_email']) : 0;
            $data['is_verify'] = isset($status['is_verify']) ? intval($status['is_verify']) : 0;
            $data['is_mobile'] = isset($status['is_mobile']) ? intval($status['is_mobile']) : 0;
        } else {
            $data['is_lock'] = 0;
            $data['is_email'] = 0;
            $data['is_verify'] = \Phpcmf\Service::C()->member_cache['register']['verify'] ? 0 : 1;
            $data['is_mobile'] = 0;
        }
        $data['is_complete'] = 0;
        $rt = $this->table('member_data')->replace($data);
        if (!$rt['code']) {
            // 删除主表
            $this->table('member')->delete($uid);
            return dr_return_data(0, $rt['msg']);
        }

        // 归属用户组
        if (IS_USE_MEMBER) {
            $lid = 0;
            if (\Phpcmf\Service::C()->member_cache['group'][$groupid]['level']) {
                $level = \Phpcmf\Service::C()->member_cache['group'][$groupid]['level'];
                $one = array_shift($level);
                $lid = $one['id'];
            }
            $this->apply_group(0, $member, $groupid, $lid, 0, ['content' => $data]);
        }

        // 组合字段信息
        $data = array_merge($member, $data);
        $data['oauth'] = $oauth;
        $data['groupid'] = $groupid;

        // 审核判断
        if (!$data['is_verify']) {
            switch (\Phpcmf\Service::C()->member_cache['register']['verify']) {

                case 'phone':
                    $this->sendsms_code($member['phone'], $member['randcode']);
                    break;

                case 'email':
                    $this->sendmail($member['email'], dr_lang('注册邮件验证'), 'member_verify.html', $data);
                    break;
            }
            // 发送审核提醒
            IS_USE_MEMBER && $this->admin_notice(
                0,
                'member',
                $member,
                dr_lang('新会员【%s】注册审核', $member['username']),
                'member/verify/index:field/id/keyword/'.$uid
            );
        }

        // 注册后的通知
        \Phpcmf\Service::L('notice')->send_notice('member_register', $data);

        // 注册后的钩子
        \Phpcmf\Hooks::trigger('member_register_after', $data);

        // API认证字符串
        $data['auth'] = md5($data['password'].$data['salt']);

        // 记录日志
        $this->_login_log($data);

        return dr_return_data($data['id'], dr_lang('注册成功'), $data);
    }

    /**
     * 存储授权信息
     */
    public function insert_oauth($uid, $type, $data, $state = '', $back = '') {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：insert_oauth');
            return dr_return_data(0, dr_lang('没有权限'));
        }

        return \Phpcmf\Service::M('member', 'member')->insert_oauth($uid, $type, $data, $state, $back);
    }

    // 修改密码
    public function edit_password($member, $password) {

        $id = (int)$member['id'];
        $password = dr_safe_password($password);
        if (!$id || !$password) {
            return false;
        }

        $update['salt'] = substr(md5(rand(0, 999)), 0, 10); // 随机10位密码加密码
        $update['randcode'] = 0;
        $update['password'] = md5(md5($password).$update['salt'].md5($password));
        $this->db->table('member')->where('id', $id)->update($update);

        $member['uid'] = $id;
        $member['password_value'] = $password;

        // 通知
        \Phpcmf\Service::L('Notice')->send_notice('member_edit_password', $member);
        // 钩子
        \Phpcmf\Hooks::trigger('member_edit_password_after', $member);

        $this->clear_cache($id);

        return true;
    }

    /**
     * 邮件发送
     */
    public function sendmail($tomail, $subject, $msg, $data = []) {
        return \Phpcmf\Service::M('email')->sendmail($tomail, $subject, $msg, $data);
    }

    /**
     * 短信发送验证码
     */
    public function sendsms_code($mobile, $code) {
        return $this->sendsms_text($mobile, $code, 'code');
    }

    /**
     * 短信发送文本
     */
    public function sendsms_text($mobile, $content, $type = 'text') {

        if (!$mobile || !$content) {
            return dr_return_data(0, dr_lang('手机号码或内容不能为空'));
        }

        $file = WRITEPATH.'config/sms.php';
        if (!is_file($file)) {
            log_message('error', '短信接口配置文件（'.$file.'）不存在');
            return dr_return_data(0, dr_lang('接口配置文件不存在'));
        }

        $config = \Phpcmf\Service::R($file);
        if ($config['third']) {
            if (is_file(CONFIGPATH.'mysms.php')) {
                require_once CONFIGPATH.'mysms.php';
            }
            $method = 'my_sendsms_'.$type;
            if (function_exists($method)) {
                return call_user_func_array($method, [
                    $mobile,
                    $content,
                    $config['third'],
                ]);
            } else {
                $error = dr_lang('你没有定义第三方短信接口: '. $method);
                @file_put_contents(WRITEPATH.'sms_log.txt', date('Y-m-d H:i:s').' ['.$mobile.'] ['.$error.'] （'.str_replace(array(chr(13), chr(10)), '', $content).'）'.PHP_EOL, FILE_APPEND);
                return dr_return_data(0, $error);
            }
        } else {
            $content = $type == 'code' ? dr_lang('您的本次验证码是: %s', $content) : $content;
            $url = 'https://www.xunruicms.com/index.php?s=vip&c=home&uid='.$config['uid'].'&key='.$config['key'].'&mobile='.$mobile.'&content='.urlencode($content).'【'.$config['note'].'】&domain='.trim(str_replace('http://', '', SITE_URL), '/').'&sitename='.SITE_NAME;
            $result = dr_catcher_data($url);
            if (!$result) {
                log_message('error', '访问官方云短信服务器失败');
                return dr_return_data(0, dr_lang('访问官方云短信服务器失败'));
            }
            $result = json_decode($result, true);
        }

        @file_put_contents(WRITEPATH.'sms_log.txt', date('Y-m-d H:i:s').' ['.$mobile.'] ['.$result['msg'].'] （'.str_replace(array(chr(13), chr(10)), '', $content).'）'.PHP_EOL, FILE_APPEND);

        return $result;
    }

    /**
     * 发送微信通知模板
     *
     * $uid 会员id
     * $id  微信模板id
     * $data    通知内容
     * $url 详细地址
     * $color   top颜色
     */
    public function weixin_template($uid, $id, $data, $url = '', $color = '') {
        return $this->wexin_template($uid, $id, $data, $url, $color);
    }
    public function wexin_template($uid, $id, $data, $url = '', $color = '') {

        if (dr_is_app('weixin')) {
            \Phpcmf\Service::C()->init_file('weixin');
            return \Phpcmf\Service::M('weixin', 'weixin')->send_template($uid, $id, $data, $url, $color);
        } else {
            return dr_return_data(0, '没有安装微信插件');
        }
    }

    /**
     * 增加经验
     *
     * @param   intval  $uid    会员id
     * @param   intval  $value  分数变动值
     * @param   string  $mark   标记
     * @param   string  $note   备注
     * @param   intval  $count  统计次数
     * @return  intval
     */
    public function add_experience($uid, $val, $note = '', $url = '', $mark = '', $count = 0) {

        if (!dr_is_app('explog')) {
            return dr_return_data(0, '未安装explog插件');
        }

        return \Phpcmf\Service::M('exp', 'explog')->add_experience($uid, $val, $note, $url, $mark, $count);
    }

    /**
     * 增加金币
     *
     * @param   intval  $uid    会员id
     * @param   intval  $value  分数变动值
     * @param   string  $mark   标记
     * @param   string  $note   备注
     * @param   intval  $count  统计次数
     * @return  intval
     */
    public function add_score($uid, $val, $note = '', $url = '', $mark = '', $count = 0) {

        if (!dr_is_app('scorelog')) {
            return dr_return_data(0, '未安装scorelog插件');
        }

        return \Phpcmf\Service::M('score', 'scorelog')->add_score($uid, $val, $note, $url, $mark, $count);
    }

    // 增加money
    public function add_money($uid, $value) {

        $value = floatval($value);
        if (!$value) {
            return dr_return_data(0, dr_lang('金额不正确'));
        }

        $member = $this->member_info($uid);
        if (!$member) {
            return dr_return_data(0, dr_lang('用户不存在'));
        }

        $money = (float)$member['money'] + $value;
        if ($money < 0) {
            return dr_return_data(0, dr_lang('账户可用余额不足'));
        }

        $update = [
            'money' => $money,
        ];
        $value < 0 && $update['spend'] = max(0, (float)$member['spend'] + abs($value));

        $rt = $this->table('member')->update($uid, $update);

        return $rt;
    }

    // 冻结资金
    public function add_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `money`=`money`-'.$value.',`freeze`=`freeze`+'.$value.' where id='.$uid);
        return $rt;
    }

    // 取消冻结资金
    public function cancel_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `money`=`money`+'.$value.',`freeze`=`freeze`-'.$value.' where id='.$uid);
        return $rt;
    }

    // 使用消费资金
    public function use_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `spend`=`spend`+'.$value.',`freeze`=`freeze`-'.$value.' where id='.$uid);

        return $rt;
    }

    // 删除会员后执行 sync是否删除相关数据表
    public function member_delete($id, $sync = 0) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：member_delete');
            return false;
        }

        return \Phpcmf\Service::M('member', 'member')->member_delete($id, $sync);
    }

    // 头像认证执行
    public function do_avatar($member) {

        if ($member['is_avatar'] || !IS_USE_MEMBER) {
            return;
        }

        $this->db->table('member_data')->where('id', $member['id'])->update(['is_avatar' => 1]);

        // avatar_score
        $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('avatar_score', $member);
        if ($value) {
            \Phpcmf\Service::M('member')->add_experience($member['id'], $value, dr_lang('头像认证'), '', 'avatar_score', 1);
        }

        $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('avatar_exp', $member);
        if ($value) {
            $this->add_score($member['id'], $value, dr_lang('头像认证'), '', 'avatar_exp', 1);
        }
    }

    // 注册随机账号
    protected function _register_rand_username($member, $ct = 0) {

        if ($member['email']) {
            list($name) = explode('@', $member['email']);
        } elseif ($member['phone']) {
            $name = substr($member['phone'], 5);
        } elseif ($member['name']) {
            $name = \Phpcmf\Service::L('pinyin')->result(dr_clear_emoji($member['name']));
            if ($name) {
                $name = urlencode((string)$name);
                $name = preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/",'', $name);
                $name = urldecode($name);
            }
        }

        if (!$name || $ct > 5) {
            return '';
        }

        // 重复名称加随机数
        $ct && $name.= $ct + rand(0, 999);

        // 最大位数
        if (\Phpcmf\Service::C()->member_cache['config']['userlenmax']
            && mb_strlen($name) > (int)\Phpcmf\Service::C()->member_cache['config']['userlenmax']) {
            $name = dr_strcut($name, (int)\Phpcmf\Service::C()->member_cache['config']['userlenmax'], '');
        }

        // 增加用户名前缀
        if (isset(\Phpcmf\Service::C()->member_cache['register']['unprefix'])
            && \Phpcmf\Service::C()->member_cache['register']['unprefix']) {
            $name = strtolower(trim((string)\Phpcmf\Service::C()->member_cache['register']['unprefix'])).$name;
        }

        // 重复账号时
        if ($this->db->table('member')->where('username', $name)->countAllResults()) {
            $name = $this->_register_rand_username($member, $ct + 1);
        }

        return $name;
    }

    // 修改账号
    public function edit_username($uid, $username) {

        $this->clear_cache($uid, $username);

        $this->table('member')->update($uid, [
            'username' => $username,
        ]);

        $this->db->table('member_group_verify')->where('uid', $uid)->update([ 'username' => $username ]);

        \Phpcmf\Service::L('cache')->set_data('member-info-'.$uid, '', 1);
    }

    // 清理指定用户缓存
    public function clear_cache($uid, $username = '') {

        \Phpcmf\Service::L('cache')->del_data('member-info-'.$uid);
        $username && \Phpcmf\Service::L('cache')->del_data('member-info-name-'.$username);
    }

    // 按用户uid查询表id集合
    protected function _get_data_ids($uid, $table) {

    }

    // 用户系统缓存
    public function cache($site = SITE_ID) {

        if (!IS_USE_MEMBER) {
            // 获取会员全部配置信息
            $cache = [
                'field' => [],
                'authid' => [ 0 ],
                'group' => [],
                'config' => [],
                'pay' => [],
            ];
            if ($this->db->tableExists($this->dbprefix('member_setting'))) {
                $result = $this->db->table('member_setting')->get()->getResultArray();
                if ($result) {
                    foreach ($result as $t) {
                        $cache[$t['name']] = dr_string2array($t['value']);
                    }
                }
            }

            \Phpcmf\Service::L('cache')->set_file('member', $cache);
        } else {
            \Phpcmf\Service::M('member', 'member')->member_cache();
        }
    }

}