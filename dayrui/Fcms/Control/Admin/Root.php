<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 管理员
class Root extends \Phpcmf\Table
{
    public $role;
    private $myrole;
    private $mywhere;

    public function __construct()
    {
        parent::__construct();
        if (!dr_in_array(1, $this->admin['roleid'])) {
            // 不是超级管理员
            if (dr_is_app('cqx')) {
                $this->myrole = \Phpcmf\Service::M('content', 'cqx')->myrole();
            } else {
                $this->myrole = $this->admin['roleid'];
            }
            $this->role = \Phpcmf\Service::M('auth')->get_role_all($this->myrole);
            $this->mywhere = '`uid` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('admin_role_index').'` where roleid in ('.implode(',', $this->myrole).'))';
        } else {
            $this->role = \Phpcmf\Service::M('auth')->get_role_all();
            $this->myrole = [];
        }
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '管理员' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-user'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    '登录记录' => ['hide:root/login_index', 'fa fa-calendar'],
                    'help' => [813],
                ]
            ),
        ]);
        $this->name = dr_lang('管理员');
    }

    public function index() {

        $p = [
            'rid' => intval($_GET['rid']),
            'keyword' => dr_safe_replace($_GET['keyword']),
        ];
        $where = [];
        if ($p['keyword']) {
            $where[] = '`username` LIKE "%'.$p['keyword'].'%"';
        }
        if ($this->myrole) {
           $where[] = $this->mywhere;
        }
        if ($p['rid']) {
            $where[] = '`uid` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('admin_role_index').'` where roleid='.$p['rid'].')';
        }

        $this->_init([
            'table' => 'admin',
            'join_list' => ['member', 'member.id=admin.uid', 'left'],
            'order_by' => 'uid desc',
            'where_list' => implode(' AND ', $where),
        ]);
        list($a, $data) = $this->_List($p);
        if ($data['list']) {
            foreach ($data['list'] as $i => $t) {
                $role = \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $t['uid'])->get()->getResultArray();
                if ($role) {
                    foreach ($role as $r) {
                        $data['list'][$i]['role'][$r['roleid']] = $this->role[$r['roleid']]['name'];
                    }
                }
            }
        }
        \Phpcmf\Service::V()->assign('p', $p);
        \Phpcmf\Service::V()->assign('list', $data['list']);
        \Phpcmf\Service::V()->display('root_index.html');
    }

    // 登录记录
    public function login_index() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        $where = 'uid='.$uid;
        if ($this->myrole) {
            $where.= ' and '.$this->mywhere;
        }
        $this->_init([
            'table' => 'admin_login',
            'order_by' => 'logintime desc',
            'where_list' => $where,
        ]);
        list($a, $data) = $this->_List(['uid' => $uid]);

        \Phpcmf\Service::V()->assign([
            'list' => $data['list'],
            'user' => dr_member_info($uid),
        ]);
        \Phpcmf\Service::V()->display('root_login.html');

    }

    public function ck_index() {
        $name = dr_safe_replace($_GET['name']);
        $data = \Phpcmf\Service::M()->db->table('member')->where('username', $name)->get()->getRowArray();
        $this->_json($data ? 0 : 1, 'ok');
    }

    public function status_edit() {
        $uid = intval($_GET['id']);
        $data = \Phpcmf\Service::M()->db->table('member_data')->where('id', $uid)->get()->getRowArray();
        if ($data['is_lock']) {
            \Phpcmf\Service::M()->db->table('member_data')->where('id', $uid)->update(['is_lock' => 0]);
            $this->_json(1, dr_lang('解除登录锁定'));
        } else {
            if ($this->uid == $uid) {
                $this->_json(0, dr_lang('无法对自己设置状态'));
            } elseif (1 == $uid) {
                $this->_json(0, dr_lang('不能设置超管账号'));
            }
            \Phpcmf\Service::M()->db->table('member_data')->where('id', $uid)->update(['is_lock' => 1]);
            $this->_json(1, dr_lang('登录已锁定，将无法登陆账号'));
        }

    }

    public function add() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $name = dr_safe_replace($post['username']);
            if (!$name) {
                $this->_json(0, dr_lang('账号不能为空'), ['field' => 'username']);
            } elseif (!$post['role']) {
                $this->_json(0, dr_lang('至少要选择一个角色组'), ['field' => 'role']);
            }
            if ($this->myrole) {
                foreach ($post['role'] as $t) {
                    if (!in_array($t, $this->myrole)) {
                        $this->_json(0, dr_lang('存在无权限操作的角色组'), ['field' => 'role']);
                    }
                }
            }
            $data = \Phpcmf\Service::M()->db->table('member')->where('username', $name)->get()->getRowArray();
            if (!$data) {
                // 注册账号
                $rt = \Phpcmf\Service::L('form')->check_username($name);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg'], ['field' => 'username']);
                }
                if (!\Phpcmf\Service::L('Form')->check_email($post['email'])) {
                    $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
                } elseif (!\Phpcmf\Service::L('Form')->check_phone($post['phone'])) {
                    $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
                } elseif (empty($post['password'])) {
                    $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
                } elseif ($post['name'] && $this->member_cache['config']['unique_name']
                    && \Phpcmf\Service::M()->db->table('member')->where('name', $post['name'])->countAllResults()) {
                    $this->_json(0, dr_lang('%s已经注册', MEMBER_CNAME), ['field' => 'name']);
                }
                $rt = \Phpcmf\Service::L('Form')->check_password($post['password'], $name);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg'], ['field' => 'password']);
                }
                $rt = \Phpcmf\Service::M('member')->register(0, [
                    'username' => $post['username'],
                    'phone' => $post['phone'],
                    'email' => $post['email'],
                    'name' => $post['name'],
                    'password' => dr_safe_password($post['password']),
                ]);
                if (!$rt['code']) {
                    // 注册失败
                    $this->_json(0, $rt['msg'], ['field' => $rt['data']['field']]);
                }
                $data = $rt['data'];
            }
            if (\Phpcmf\Service::M()->table('admin')->where('uid', $data['id'])->counts()) {
                $this->_json(0, dr_lang('此账号已经是管理员'));
            }
            $rt = \Phpcmf\Service::M()->table('admin')->insert([
                'uid' => $data['id'],
                'setting' => '',
                'usermenu' => '',
            ]);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            if (dr_in_array(1, $post['role'])) {
                // 超管模式
                \Phpcmf\Service::M()->table('admin_role_index')->insert([
                    'uid' => $data['id'],
                    'roleid' => 1,
                ]);
            } else {
                foreach ($post['role'] as $t) {
                    \Phpcmf\Service::M()->table('admin_role_index')->insert([
                        'uid' => $data['id'],
                        'roleid' => $t,
                    ]);
                }
            }
            \Phpcmf\Service::M()->table('member_data')->update($data['id'], ['is_admin' => 1]);
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'post_url' =>\Phpcmf\Service::L('Router')->url('root/add'),
            'reply_url' =>\Phpcmf\Service::L('Router')->url('root/index'),
        ]);
        \Phpcmf\Service::V()->display('root_add.html');
    }

    public function edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $data = \Phpcmf\Service::M()->db->table('admin')->where('uid', $id)->get()->getRowArray();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('账号不存在'));
        }

        $member = \Phpcmf\Service::M()->db->table('member')->where('id', $data['uid'])->get()->getRowArray();
        if (!$member) {
            $this->_admin_msg(0, dr_lang('账号不存在'));
        }

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['role']) {
                $this->_json(0, dr_lang('至少要选择一个角色组'), ['field' => 'role']);
            } elseif (!\Phpcmf\Service::L('Form')->check_email($post['email'])) {
                $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif (!\Phpcmf\Service::L('Form')->check_phone($post['phone'])) {
                $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('id<>'. $member['id'])->where('email', $post['email'])->countAllResults()) {
                $this->_json(0, dr_lang('邮箱%s已经注册', $post['email']), ['field' => 'email']);
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('id<>'. $member['id'])->where('phone', $post['phone'])->countAllResults()) {
                $this->_json(0, dr_lang('手机号码%s已经注册', $post['phone']), ['field' => 'phone']);
            } elseif ($post['name'] && $this->member_cache['config']['unique_name']
                && \Phpcmf\Service::M()->db->table('member')->where('id<>'. $member['id'])->where('name', $post['name'])->countAllResults()) {
                $this->_json(0, dr_lang('%s已经注册', MEMBER_CNAME), ['field' => 'name']);
            } elseif ($this->myrole) {
                foreach ($post['role'] as $t) {
                    if (!in_array($t, $this->myrole)) {
                        $this->_json(0, dr_lang('存在无权限操作的角色组'), ['field' => 'role']);
                    }
                }
            }
            if ($post['username'] != $member['username']) {
                // 改账号时
                $rs = \Phpcmf\Service::L('Form')->check_username($post['username']);
                if (!$rs['code']) {
                    $this->_json(0, $rs['msg'], ['field' => 'username']);
                } elseif (\Phpcmf\Service::M()->db->table('member')->where('username', $post['username'])->countAllResults()) {
                    $this->_json(0, dr_lang('账号%s已经注册', $post['username']), ['field' => 'username']);
                }
                \Phpcmf\Service::M()->table('member')->update($member['id'], [
                    'username' => $post['username'],
                    'phone' => $post['phone'],
                    'email' => $post['email'],
                    'name' => $post['name'],
                ]);
                \Phpcmf\Service::M('member')->clear_cache($member['id'], $member['username']);
            } else {
                \Phpcmf\Service::M()->table('member')->update($member['id'], [
                    'phone' => $post['phone'],
                    'email' => $post['email'],
                    'name' => $post['name'],
                ]);
            }

            if ($post['password']) {
                $rt = \Phpcmf\Service::L('Form')->check_password($post['password'], $post['username']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg'], ['field' => 'password']);
                }
                \Phpcmf\Service::M('member')->edit_password($member, $post['password']);
            }
            if ($id > 1) {
                \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $member['id'])->delete();
                foreach ($post['role'] as $t) {
                    \Phpcmf\Service::M()->table('admin_role_index')->replace([
                        'uid' => $member['id'],
                        'roleid' => $t,
                    ]);
                }
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        $data = $data + $member;
        $role = \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $data['uid'])->get()->getResultArray();
        if ($role) {
            foreach ($role as $r) {
                $data['role'][$r['roleid']] = $this->role[$r['roleid']]['name'];
            }
        }

        // 不是超级管理员,排除超管账号
        if (!dr_in_array(1, $this->admin['roleid'])) {
            unset($this->role[1]);
            if (isset($data['role'][1])) {
                $this->_admin_msg(0, dr_lang('无权限编辑'));
            }
        }

        \Phpcmf\Service::V()->assign([
            'edit' => 1,
            'data' => $data,
            'post_url' =>\Phpcmf\Service::L('Router')->url('root/add'),
            'reply_url' =>\Phpcmf\Service::L('Router')->url('root/index'),
        ]);
        \Phpcmf\Service::V()->display('root_add.html');
    }

    // 后台删除url内容
    public function del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (dr_in_array(1, $ids)) {
            $this->_json(0, dr_lang('创始人账号不能删除'));
        } elseif (dr_in_array($this->uid, $ids)) {
            $this->_json(0, dr_lang('不能删除自己'));
        }

        // 批量操作
        foreach ($ids as $u) {
            // 不是超级管理员,排除超管账号
            if ($this->myrole && !\Phpcmf\Service::M()->table('admin_role_index')->where('uid', $u)->where($this->mywhere)->counts()) {
                $this->_json(0, dr_lang('存在无权限操作的角色组'), ['field' => 'role']);
            }
            \Phpcmf\Service::M()->db->table('admin')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->db->table('admin_login')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->table('member_data')->update($u, ['is_admin' => 0]);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

}
