<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 会员管理
class Member extends \Phpcmf\Table
{
    public $group;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 不是超级管理员排除角色账号
        if (!dr_in_array(1, $this->admin['roleid']) && \Phpcmf\Service::M('auth')->is_post_user()) {
            $this->_admin_msg(0, dr_lang('投稿者无权限操作'));
        }
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'member_';
        $this->my_field = array(
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('账号'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    )
                )
            ),
            'email' => array(
                'ismain' => 1,
                'name' => dr_lang('邮箱'),
                'fieldname' => 'email',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    )
                )
            ),
            'phone' => array(
                'ismain' => 1,
                'name' => dr_lang('手机'),
                'fieldname' => 'phone',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    )
                )
            ),
            'name' => array(
                'ismain' => 1,
                'name' => MEMBER_CNAME,
                'fieldname' => 'name',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    )
                )
            ),
        );
        // 表单显示名称
        $this->name = dr_lang('用户');
        // 初始化数据表
        if ($this->member_cache['field']) {
            foreach ($this->member_cache['field'] as $i => $t) {
                $this->member_cache['field'][$i]['setting']['validate']['required'] = 0;
                $this->my_field[$t['fieldname']] = $t;
            }
        }
        $this->_init([
            'table' => 'member',
            'stable' => 'member_data',
            'field' => $this->member_cache['field'],
            'join_list' => ['member_data', 'member.id=member_data.id', 'left'],
            'order_by' => 'member.id desc',
            'list_field' => $this->member_cache['list_field'],
        ]);
        $this->group = $this->member_cache['group'];
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户管理' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-user'],
                    '添加用户' => ['add:'.\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '批量添加' => ['add:'.\Phpcmf\Service::L('Router')->class.'/all_add', 'fa fa-plus-square', '70%'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                ]
            ),
            'group' => $this->group,
            'field' => $this->my_field,
            'member_list_field' => dr_array22array(\Phpcmf\Service::L('Field')->member_list_field(), $this->member_cache['field']),
        ]);
    }

    // 用户管理
    public function index() {

        $p = $where = [];
        $name = dr_safe_replace(\Phpcmf\Service::L('input')->request('field'));
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->request('keyword'));

        if ($name && $value && isset($this->my_field[$name])) {
            if (isset($this->member_cache['field'][$name])) {
                $where[] = 'member_data.'.$name.' LIKE "%'.$value.'%"';
                unset($this->init['field'][$name]);
            } else {
                $where[] = '`'.$name.'` LIKE "%'.$value.'%"';
            }
            $p[$name] = $value;
        }

        $groupid = \Phpcmf\Service::L('input')->request('groupid');
        if ($groupid) {
            $in = implode(',', $groupid);
            if ($in) {
                $where[] = 'member.id IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('member_group_index').'` where gid in ('.$in.'))';
                $p['groupid'] = $groupid;
            }
        }

        $sname = [
            'is_lock' => '账号锁定',
            'is_mobile' => '手机认证',
            'is_email' => '邮箱认证',
        ];
        $status = \Phpcmf\Service::L('input')->request('status');
        if ($status) {
            foreach ($status as $v) {
                if (isset($sname[$v])) {
                    $where[] = 'member_data.'.$v.' = 1';
                }
            }
            $p['status'] = $status;
        }

        // 不是超级管理员排除角色账号
        if (!dr_in_array(1, $this->admin['roleid'])) {
            $where[] = 'member.id NOT IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('admin_role_index').'` where uid<> '.$this->uid.')';
        }

        $where && \Phpcmf\Service::M()->set_where_list(implode(' AND ', $where));

        list($tpl) = $this->_List($p);
        \Phpcmf\Service::V()->assign([
            'status' => $sname,
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 修改账号
    public function username_edit() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        $member = dr_member_info($uid);
        if (!$member) {
            $this->_json(0, dr_lang('该用户不存在'));
        }

        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        if (IS_POST) {

            $name = trim(dr_safe_filename(\Phpcmf\Service::L('input')->post('name')));
            if (!$name) {
                $this->_json(0, dr_lang('新账号不能为空'));
            } elseif ($member['username'] == $name) {
                $this->_json(0, dr_lang('新账号不能和原始账号相同'));
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('username', $name)->countAllResults()) {
                $this->_json(0, dr_lang('新账号%s已经注册', $name), ['field' => 'name']);
            }
            $rt = \Phpcmf\Service::L('form')->check_username($name);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg'], ['field' => 'name']);
            }

            \Phpcmf\Service::M('member')->edit_username($uid, $name);

            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'member' => $member,
        ]);
        \Phpcmf\Service::V()->display('member_edit_username.html');exit;
    }

    // 后台添加
    public function add() {

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (empty($post['password'])) {
                $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
            } else {
                $rt = \Phpcmf\Service::M('member')->register((int)$post['groupid'], [
                    'username' => (string)$post['username'],
                    'phone' => (string)$post['phone'],
                    'email' => (string)$post['email'],
                    'name' => (string)$post['name'],
                    'password' => dr_safe_password($post['password']),
                ]);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg'], ['field' => $rt['data']['field']]);
                }
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('member_add.html');exit;
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
        if (is_file($cache_path.$uid.'.jpg')) {
            unlink($cache_path.$uid.'.jpg');
            if (is_file($cache_path.$uid.'.jpg')) {
                $this->_json(0, dr_lang('文件删除失败，请检查头像目录权限'));
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
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        if (IS_POST) {
            $content = $_POST['file'];
            // 普通文件上传
            if (isset($_FILES['file'])) {
                if (isset($_FILES["file"]["tmp_name"]) && $_FILES["file"]["tmp_name"]) {
                    $content = \Phpcmf\Service::L('file')->base64_image($_FILES["file"]["tmp_name"]);
                }
            }
            list($cache_path, $cache_url) = dr_avatar_path();
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/i', $content, $result)) {
                $ext = strtolower($result[2]);
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $this->_json(0, dr_lang('图片格式不正确'));
                } elseif (!is_dir($cache_path)) {
                    $this->_json(0, dr_lang('头像存储目录不存在'));
                }
                $content = base64_decode(str_replace($result[1], '', $content));
                if (strlen($content) > 30000000) {
                    $this->_json(0, dr_lang('图片太大了'));
                }
                $file = $cache_path.$uid.'.jpg';
                $temp = dr_upload_temp_path().'member.'.$uid.'.jpg';
                $size = file_put_contents($temp, $content);
                if (!$size) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!is_file($temp)) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!getimagesize($temp)) {
                    unlink($file);
                    $this->_json(0, '文件不是规范的图片');
                }
                // 上传图片到服务器
                copy($temp, $file);
                if (!is_file($file)) {
                    $this->_json(0, dr_lang('头像存储失败'));
                }
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
        \Phpcmf\Service::V()->display('member_avatar.html');exit;
    }

    // 后台添加
    public function all_add() {

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['all']) {
                $this->_json(0, dr_lang('用户集未填写'), ['field' => 'all']);
            }

            $all = explode(PHP_EOL, $post['all']);
            $error = $ok = 0;
            foreach ($all as $t) {
                list($username, $password, $email, $phone, $name) = explode('|', $t);
                $name = trim($name == 'null' ? '' : $name);
                $phone = trim($phone == 'null' ? '' : $phone);
                $email = trim($email == 'null' ? '' : $email);
                $username = trim($username == 'null' ? '' : $username);
                $password = trim($password == 'null' ? '' : $password);
                $rt = \Phpcmf\Service::M('member')->register((int)$post['groupid'], [
                    'username' => (string)$username,
                    'phone' => (string)$phone,
                    'email' => (string)$email,
                    'name' => (string)$name,
                    'password' => dr_safe_password($password),
                ]);
                if ($rt['code']) {
                    $ok ++;
                } else {
                    $error ++;
                    log_message('error', dr_lang('后台批量注册会员失败（%s）：%s', trim($t), $rt['msg']));
                }
            }
            $this->_json(1, dr_lang('批量注册%s个用户，失败%s个（查看系统错误日志）', $ok, $error));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('member_add_all.html');exit;
    }

    // 后台修改
    public function edit() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        $field = [];
        if (dr_in_array(1, $this->admin['roleid'])) {
            // 超级管理员，显示全部字段并提示
            if ($this->member_cache['field']) {
                $member = \Phpcmf\Service::M('member')->get_member($uid);
                $fieldid = [];
                if ($member['groupid']) {
                    foreach ($member['groupid'] as $gid) {
                        if ($this->member_cache['group'][$gid]['field']) {
                            $fieldid = dr_array2array($fieldid, $this->member_cache['group'][$gid]['field']);
                        }
                    }
                    if ($fieldid) {
                        foreach ($this->member_cache['field'] as $fname => $t) {
                            if (!in_array($fname, $fieldid)) {
                                $field[$fname] = $t;
                            }
                        }
                    }
                }
            }
        } else {
            // 子管理员排查不可用的字段
            $myfield = [];
            $groupid = \Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll();
            if ($groupid && $this->member_cache['field']) {
                $fieldid = [];
                foreach ($groupid as $t) {
                    if ($this->member_cache['group'][$t['gid']]['field']) {
                        $fieldid = dr_array2array($fieldid, $this->member_cache['group'][$t['gid']]['field']);
                    }
                }
                if ($fieldid) {
                    foreach ($this->member_cache['field'] as $fname => $t) {
                        if (in_array($fname, $fieldid)) {
                            $myfield[$fname] = $t;
                        }
                    }
                }
            }
            $this->_init([
                'table' => 'member',
                'field' => $myfield,
            ]);
        }

        $this->_Post($uid);

        \Phpcmf\Service::V()->assign([
            'uid' => $uid,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'field' => $field,
            'oauth' => \Phpcmf\Service::M()->table('member_oauth')->where('uid', $uid)->getAll(),
            'mygroup' => \Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll(),
            'regfield' => [
                'regip' => array(
                    'ismain' => 1,
                    'name' => dr_lang('注册地区'),
                    'fieldtype' => 'Textbtn',
                    'fieldname' => 'regip',
                    'setting' => array(
                        'option' => array(
                            'width' => 320,
                            'name' => '查看',
                            'icon' => 'fa fa-arrow-right',
                            'func' => 'dr_show_ip',
                            'value' => \Phpcmf\Service::L('input')->ip_info()
                        )
                    )
                ),
                'regtime' => array(
                    'ismain' => 1,
                    'name' => dr_lang('注册时间'),
                    'fieldtype' => 'Date',
                    'fieldname' => 'regtime',
                    'setting' => array(
                        'option' => array(
                            'width' => 280,
                            'value' => 'SYS_TIME',
                            'is_left' => 0,
                        )
                    )
                ),
            ],
        ]);
        \Phpcmf\Service::V()->display('member_edit.html');
    }

    // 后台删除
    public function del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (dr_in_array(1, $ids)) {
            $this->_json(0, dr_lang('创始人账号不能删除'));
        } elseif (dr_in_array($this->uid, $ids)) {
            $this->_json(0, dr_lang('不能删除自己'));
        }

        // 删除时同步删除很多内容
        $this->_Del(
            $ids,
            function ($rows) {
                foreach ($rows as $t) {
                    if (!isset($this->admin['role'][1])
                        && \Phpcmf\Service::M()->table('admin_role_index')->where('uid', intval($t['id']))->where('roleid', 1)->counts()) {
                        return dr_return_data(0, dr_lang('账号[%s]不能删除', $t['username']));
                    }
                }
                return dr_return_data(1, 'ok');
            },
            function ($rows) {
                $ids = [];
                foreach ($rows as $t) {
                    $id = intval($t['id']);
                    if (!\Phpcmf\Service::M('auth')->cleck_edit_member($id)) {
                        continue;
                    }
                    $ids[] = $id;
                    \Phpcmf\Service::M('member')->member_delete($id, (int)\Phpcmf\Service::L('input')->get('sync'));

                }
                return dr_return_data(1, 'ok');
            },
            (int)\Phpcmf\Service::L('input')->get('sync') ? \Phpcmf\Service::M()->dbprefix('member') : 0
        );
    }

    // 解绑
    public function jb_del() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_json(0, dr_lang('无权限操作其他管理员账号'));
        }

        $tid = dr_safe_filename(\Phpcmf\Service::L('input')->get('tid'));

        \Phpcmf\Service::M()->table('member_oauth')->where('uid', $uid)->where('oauth', $tid)->delete();

        if ($tid == 'wechat' && dr_is_app('weixin')) {
            // 判断微信
            \Phpcmf\Service::C()->init_file('weixin');
            \Phpcmf\Service::M()->table(weixin_wxtable('user'))->where('uid', $uid)->delete();
        }

        \Phpcmf\Service::M('member')->clear_cache($uid);

        $this->_json(1, dr_lang('操作成功'), [
            'url' => dr_url('member/edit', ['id'=> $uid, 'page'=>4])
        ]);
    }

    // 登录记录
    public function login_index() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        \Phpcmf\Service::V()->assign([
            'list' => \Phpcmf\Service::M()->table('member_login')->where('uid', $uid)->order_by('logintime desc')->getAll(),
        ]);
        \Phpcmf\Service::V()->display('member_login.html');exit;
    }

    // 删除用户组
    public function group_del() {

        $gid = (int)\Phpcmf\Service::L('input')->get('gid');
        $uid = (int)\Phpcmf\Service::L('input')->get('uid');
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_json(0, dr_lang('无权限操作其他管理员账号'));
        }

        \Phpcmf\Service::M('member')->delete_group($uid, $gid, 1);

        $this->_json(1, dr_lang('操作成功'));
    }

    // 设置用户组
    public function group_edit() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        if (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
            $this->_admin_msg(0, dr_lang('无权限操作其他管理员账号'));
        }

        $groups = \Phpcmf\Service::M('member')->update_group(
            \Phpcmf\Service::M()->table('member')->get($uid),
            \Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll()
        );

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post) {
                $this->_json(0, dr_lang('用户组不存在'));
            } elseif (!$this->member_cache['config']['groups'] && dr_count($groups) > 1) {
                $this->_json(0, dr_lang('不能同时拥有多个用户组'));
            }
            foreach ($post as $gid => $t) {
                // 手动更新等级
                if ($t['lid'] && $t['lid'] != $groups[$gid]['lid']) {
                    \Phpcmf\Service::M('member')->update_level($uid, $gid, $t['lid']);
                }
                $stime = (int)strtotime($t['stime']);
                $etime = (int)strtotime($t['etime']);
                // 设置时间
                \Phpcmf\Service::M()->db->table('member_group_index')->where('uid', $uid)->where('gid', $gid)->update([
                    'stime' => $t['stime'] ? $stime : SYS_TIME,
                    'etime' => $t['etime'] ? ($stime > $etime ? 0 : $etime) : 0,
                ]);
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'id' => $uid,
            'form' => dr_form_hidden(),
            'mygroup' => $groups,
        ]);
        \Phpcmf\Service::V()->display('member_group.html');exit;
    }

    // 批量设置用户组
    public function group_all_edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选用户不存在'));
        }

        $gid = \Phpcmf\Service::L('input')->post('groupid');
        if (!$gid) {
            $this->_json(0, dr_lang('所选用户组不存在'));
        }

        // 验证用户
        foreach ($ids as $i) {
            $uid = intval($i);
            if (!$uid) {
                $this->_json(0, dr_lang('所选用户不存在'));
            } elseif (!\Phpcmf\Service::M('auth')->cleck_edit_member($uid)) {
                $this->_json(0, dr_lang('无权限操作用户#%s', $uid));
            } elseif (!$this->member_cache['config']['groups']
                && dr_count(\Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll()) > 1) {
                $this->_json(0, dr_lang('用户#%s不能同时拥有多个用户组', $uid));
            } elseif (\Phpcmf\Service::M()->counts('member_group_index', 'uid='.$uid.' and gid='.$gid)) {
                $this->_json(0, dr_lang('用户#%s已经拥有了此用户组', $uid));
            }
        }

        foreach ($ids as $i) {
            \Phpcmf\Service::M('member')->insert_group(intval($i), $gid);
        }

        $this->_json(1, dr_lang('共执行%s个用户', dr_count($ids)));
    }

    /**
     * 后台授权登录
     */
    public function alogin_index() {
        $this->_msg(1, dr_lang('正在授权登录此用户...'), dr_url('api/alogin', ['id' => intval(\Phpcmf\Service::L('input')->get('id'))]), 0);
    }

    // 重写存储值
    protected function _Save_Value($id, $name, $value, $after = null, $before = null) {

        \Phpcmf\Service::M('member')->clear_cache($id);

        if ($name == 'username') {
            if (!$value) {
                $this->_json(0, dr_lang('新账号不能为空'));
            } elseif ($this->member['username'] == $value) {
                $this->_json(0, dr_lang('新账号不能和原始账号相同'));
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('username', $value)->countAllResults()) {
                $this->_json(0, dr_lang('新账号%s已经注册', $value), ['field' => 'name']);
            }
            $rt = \Phpcmf\Service::L('form')->check_username($value);
            if (!$rt['code']) {
                $this->_json(0, $rt['code'], ['field' => 'name']);
            }
            \Phpcmf\Service::M('member')->edit_username($id, $value);
            $this->_json(1, dr_lang('操作成功'));
        } elseif ($name == 'email' ) {
            if (!\Phpcmf\Service::L('Form')->check_email($value)) {
                $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('id<>'. $id)->where('email', $value)->countAllResults()) {
                $this->_json(0, dr_lang('邮箱%s已经注册', $value), ['field' => 'email']);
            }
            \Phpcmf\Service::M()->db->table('member')->where('id', $id)->update(['email' => $value]);
            $this->_json(1, dr_lang('操作成功'));
        } elseif ($name == 'phone' ) {
            if (!\Phpcmf\Service::L('Form')->check_phone($value)) {
                $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            } elseif (\Phpcmf\Service::M()->db->table('member')->where('id<>'. $id)->where('phone', $value)->countAllResults()) {
                $this->_json(0, dr_lang('手机号码%s已经注册', $value), ['field' => 'phone']);
            }
            \Phpcmf\Service::M()->db->table('member')->where('id', $id)->update(['phone' => $value]);
            $this->_json(1, dr_lang('操作成功'));
        } elseif ($name == 'money') {
            $this->_json(0, dr_lang('金额不能变更'));
        } elseif ($name == 'score') {
            $this->_json(0, dr_lang('积分不能变更'));
        }


        parent::_Save_Value($id, $name, $value, $after, $before);
    }

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {
        $data = parent::_Data($id);
        $data2 = \Phpcmf\Service::M()->db->table('member_data')->where('id', $id)->get()->getRowArray();
        if ($data2) {
            $data = $data + $data2;
            $data['is_mobile2'] = $data['is_mobile'];
            unset($data['is_mobile']);
        }
        return $data;
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        return parent::_Save($id, $data, $old,
            function ($id, $data, $old){
                // 保存之前的判断
                $member = \Phpcmf\Service::L('input')->post('member');
                if ($member['email'] && !\Phpcmf\Service::L('Form')->check_email($member['email'])) {
                    $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
                } elseif ($member['phone'] && !\Phpcmf\Service::L('Form')->check_phone($member['phone'])) {
                    $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
                } elseif ($member['email'] && \Phpcmf\Service::M()->db->table('member')->where('id<>'. $id)->where('email', $member['email'])->countAllResults()) {
                    return dr_return_data(0, dr_lang('邮箱%s已经注册', $member['email']), ['field' => 'email']);
                } elseif ($member['phone'] && \Phpcmf\Service::M()->db->table('member')->where('id<>'. $id)->where('phone', $member['phone'])->countAllResults()) {
                    return dr_return_data(0, dr_lang('手机号码%s已经注册', $member['phone']), ['field' => 'phone']);
                }
                // 保存附表内容
                $post = \Phpcmf\Service::L('input')->post('data');
                $status = \Phpcmf\Service::L('input')->post('status');

                $member['regip'] = $post['regip'];
                $member['regtime'] = strtotime($post['regtime']);

                $member_data = $data[1] ? $data[1] : [];
                $member_data['is_lock'] = isset($status['is_lock']) ? (int)$status['is_lock'] : 0;
                $member_data['is_auth'] = isset($status['is_auth']) ? (int)$status['is_auth'] : 0;
                $member_data['is_mobile'] = isset($status['is_mobile']) ? (int)$status['is_mobile'] : 0;
                $member_data['is_email'] = isset($status['is_email']) ? (int)$status['is_email'] : 0;
                $member_data['is_verify'] = isset($status['is_verify']) ? (int)$status['is_verify'] : 0;
                $member_data['is_complete'] = isset($status['is_complete']) ? (int)$status['is_complete'] : 0;
                $member_data['is_avatar'] = isset($status['is_avatar']) ? (int)$status['is_avatar'] : 0;
                \Phpcmf\Service::M()->table('member_data')->update($id, $member_data);
                return dr_return_data(1, '', [1 => $member]);
            },
            function ($id, $data, $old) {
                // 保存之后
                // 修改密码
                $password = \Phpcmf\Service::L('input')->post('password');
                if ($password) {
                    $member = \Phpcmf\Service::M()->table('member')->get($id);
                    \Phpcmf\Service::M('member')->edit_password($member, $password);
                }

                \Phpcmf\Service::M('member')->clear_cache($id, $old['username']);

                // 审核状态
                $status = \Phpcmf\Service::L('input')->post('status');
                if (isset($old['is_verify']) && $old['is_verify'] == 0 && $status['is_verify'] == 1) {
                    \Phpcmf\Service::M('member')->verify_member($id);
                }

                return $data;
            }
        );
    }

}
