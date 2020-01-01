<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 管理员
class Root extends \Phpcmf\Table
{
    public $role;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
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
        $this->role = \Phpcmf\Service::M('auth')->get_role_all();
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
        if ($p['rid']) {
            $where[] = '`uid` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('admin_role_index').'` where roleid='.$p['rid'].')';
        }
        // 不是超级管理员,排除超管账号
        if (!in_array(1, $this->admin['roleid'])) {
            $where[] = '`uid` NOT IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('admin_role_index').'` where roleid=1)';
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
        $this->_init([
            'table' => 'admin_login',
            'order_by' => 'logintime desc',
            'where_list' => 'uid='.$uid,
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

    public function add() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $name = dr_safe_replace($post['username']);
            if (!$name) {
                $this->_json(0, dr_lang('账号不能为空'), ['field' => 'username']);
            } elseif (!$post['role']) {
                $this->_json(0, dr_lang('至少要选择一个角色组'), ['field' => 'role']);
            }
            $data = \Phpcmf\Service::M()->db->table('member')->where('username', $name)->get()->getRowArray();
            if (!$data) {
                // 注册账号
                if (!\Phpcmf\Service::L('Form')->check_email($post['email'])) {
                    $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
                } elseif (!\Phpcmf\Service::L('Form')->check_phone($post['phone'])) {
                    $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
                } elseif (empty($post['password'])) {
                    $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
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
            $rt = \Phpcmf\Service::M()->table('admin')->insert([
                'uid' => $data['id'],
                'setting' => '',
                'usermenu' => '',
            ]);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            foreach ($post['role'] as $t) {
                \Phpcmf\Service::M()->table('admin_role_index')->insert([
                    'uid' => $data['id'],
                    'roleid' => $t,
                ]);
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
            } elseif (!in_array(1, $this->admin['roleid']) && in_array(1, $post['role'])) {
                $this->_admin_msg(0, dr_lang('无权限编辑')); // 不是超级管理员,排除超管账号
            }
            \Phpcmf\Service::M()->table('member')->update($member['id'], [
                'phone' => $post['phone'],
                'email' => $post['email'],
                'name' => $post['name'],
            ]);
            $post['password'] && \Phpcmf\Service::M('member')->edit_password($member, $post['password']);
            \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $member['id'])->delete();
            foreach ($post['role'] as $t) {
                \Phpcmf\Service::M()->table('admin_role_index')->replace([
                    'uid' => $member['id'],
                    'roleid' => $t,
                ]);
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
        if (!in_array(1, $this->admin['roleid'])) {
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
        if (in_array(1, $ids)) {
            $this->_json(0, dr_lang('创始人账号不能删除'));
        } elseif (in_array($this->uid, $ids)) {
            $this->_json(0, dr_lang('不能删除自己'));
        }

        // 批量操作
        foreach ($ids as $u) {
            // 不是超级管理员,排除超管账号
            if (!in_array(1, $this->admin['roleid'])
                && \Phpcmf\Service::M()->table('admin_role_index')->where('uid', $u)->where('roleid', 1)->counts()) {
                continue;
            }
            \Phpcmf\Service::M()->db->table('admin')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->db->table('admin_login')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->db->table('admin_role_index')->where('uid', $u)->delete();
            \Phpcmf\Service::M()->table('member_data')->update($u, ['is_admin' => 0]);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

}
