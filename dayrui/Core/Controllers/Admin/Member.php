<?php namespace Phpcmf\Controllers\Admin;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



// 会员管理
class Member extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
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
                'name' => dr_lang('姓名'),
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
            }
        }
        $this->_init([
            'table' => 'member',
            'field' => $this->member_cache['field'],
            'order_by' => 'id desc',
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
        ]);
    }

    // 用户管理
    public function index() {

        $p = $where = [];
        $name = dr_safe_replace(\Phpcmf\Service::L('input')->request('field'));
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->request('keyword'));
        
        if ($name && $value && isset($this->my_field[$name])) {
            $p[$name] = $value;
            $where[] = '`'.$name.'` LIKE "%'.$value.'%"';
        }
        
        $groupid = (int)\Phpcmf\Service::L('input')->request('groupid');
        if ($groupid) {
            $p['groupid'] = $groupid;
            $where[] = '`id` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('member_group_index').'` where gid='.$groupid.')';
        }
        
        $where && \Phpcmf\Service::M()->set_where_list(implode(' AND ', $where));

        list($tpl) = $this->_List($p);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台添加
    public function add() {
        
        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if (in_array('username', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('form')->check_username($post['username'])) {
                $this->_json(0, dr_lang('账号格式不正确'), ['field' => 'username']);
            } elseif (in_array('email', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('form')->check_email($post['email'])) {
                $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif (in_array('phone', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('form')->check_phone($post['phone'])) {
                $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            } elseif (empty($post['password'])) {
                $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
            } else {
                $rt = \Phpcmf\Service::M('member')->register((int)$post['groupid'], [
                    'username' => (string)$post['username'],
                    'phone' => (string)$post['phone'],
                    'email' => (string)$post['email'],
                    'name' => (string)$post['name'],
                    'password' => dr_safe_password($post['password']),
                ]);
                !$rt['code'] && $this->_json(0, $rt['msg'], ['field' => $rt['data']['field']]);
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

        list($cache_path, $cache_url) = dr_avatar_path();
        if (is_file($cache_path.$uid.'.jpg')) {
            @unlink($cache_path.$uid.'.jpg');
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
                $size = @file_put_contents($temp, $content);
                if (!$size) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!is_file($temp)) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!getimagesize($temp)) {
                    @unlink($file);
                    $this->_json(0, '文件不是规范的图片');
                }
                // 上传图片到服务器
                copy($temp, $file);
                !is_file($file) && $this->_json(0, dr_lang('头像存储失败'));
                if (defined('UCSSO_API')) {
                    $rt = ucsso_avatar($uid, $content);
                    !$rt['code'] && $this->_json(0, dr_lang('通信失败：%s', $rt['msg']));
                }
                \Phpcmf\Service::M()->db->table('member_data')->where('id', $uid)->update(['is_avatar' => 1]);
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

            $post = \Phpcmf\Service::L('input')->post('data', true);
            if (!$post['all']) {
                $this->_json(0, dr_lang('用户集未填写'), ['field' => 'all']);
            }

            $all = explode(PHP_EOL, $post['all']);
            $error = $ok = 0;
            foreach ($all as $t) {
                list($username, $password, $email, $phone, $name) = explode('|', $t);
                $username = trim($username == 'null' ? '' : $username);
                $password = trim($password == 'null' ? '' : $password);
                $phone = trim($phone == 'null' ? '' : $phone);
                $name = trim($name == 'null' ? '' : $name);

                if (in_array('username', $this->member_cache['register']['field'])
                    && !\Phpcmf\Service::L('form')->check_username($username)) {
                    $this->_json(0, dr_lang('账号[%s]格式不正确', $username), ['field' => 'all']);
                } elseif (in_array('email', $this->member_cache['register']['field'])
                    && !\Phpcmf\Service::L('form')->check_email($email)) {
                    $this->_json(0, dr_lang('邮箱[%s]格式不正确', $email), ['field' => 'all']);
                } elseif (in_array('phone', $this->member_cache['register']['field'])
                    && !\Phpcmf\Service::L('form')->check_phone($phone)) {
                    $this->_json(0, dr_lang('手机号码[%s]格式不正确', $phone), ['field' => 'all']);
                }
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

                }

            }


            $this->_json(1, dr_lang('批量注册%s个用户，失败%s个', $ok, $error));
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
        // 关闭分组字段
        \Phpcmf\Service::L('Field')->is_hide_merge_group();
        $this->_Post($uid);
        
        \Phpcmf\Service::V()->assign([
            'uid' => $uid,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'oauth' => \Phpcmf\Service::M()->table('member_oauth')->where('uid', $uid)->getAll(),
            'mygroup' => \Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll(),
        ]);
        \Phpcmf\Service::V()->display('member_edit.html');
    }

    // 后台删除
    public function del() {

        // 删除时同步删除很多内容
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            function ($rows) {
                $ids = [];
                foreach ($rows as $t) {
                    $ids[] = $id = intval($t['id']);
                    \Phpcmf\Service::M('member')->member_delete($id);

                }
                defined('UCSSO_API') && ucsso_delete($ids);
                return dr_return_data(1, 'ok');
            },
            \Phpcmf\Service::M()->dbprefix('member')
        );
    }

    // 解绑
    public function jb_del() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        $tid = dr_safe_filename(\Phpcmf\Service::L('input')->get('tid'));

        \Phpcmf\Service::M()->table('member_oauth')->where('uid', $uid)->where('oauth', $tid)->delete();

        if ($tid == 'wechat' && dr_is_app('weixin')) {
            // 判断微信
            \Phpcmf\Service::C()->init_file('weixin');
            \Phpcmf\Service::M()->table(weixin_wxtable('user'))->where('uid', $uid)->delete();
        }

        $this->_json(1, dr_lang('操作成功'), [
            'url' => dr_url('member/edit', ['id'=> $uid, 'page'=>4])
        ]);
    }
    
    // 登录记录
    public function login_index() {
        
        $uid = (int)\Phpcmf\Service::L('input')->get('id');
        $list = \Phpcmf\Service::M()->table('member_login')->where('uid', $uid)->order_by('logintime desc')->getAll();

        \Phpcmf\Service::V()->assign([
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('member_login.html');exit;
        
    }

    // 删除用户组
    public function group_del() {

        $gid = (int)\Phpcmf\Service::L('input')->get('gid');
        $uid = (int)\Phpcmf\Service::L('input')->get('uid');

        \Phpcmf\Service::M('member')->delete_group($uid, $gid);

        $this->_json(1, dr_lang('操作成功'));
    }

    // 设置用户组
    public function group_edit() {

        $uid = (int)\Phpcmf\Service::L('input')->get('id');
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
                // 设置时间
                \Phpcmf\Service::M()->db->table('member_group_index')->where('uid', $uid)->where('gid', $gid)->update([
                   'stime' => (int)strtotime($t['stime']),
                   'etime' => (int)strtotime($t['etime']),
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
        !$ids && $this->_json(0, dr_lang('所选用户不存在'));

        $gid = \Phpcmf\Service::L('input')->post('groupid');
        !$gid && $this->_json(0, dr_lang('所选用户组不存在'));

        $c = 0;
        foreach ($ids as $i) {
            $uid = intval($i);
            if (!$uid) {
                continue;
            } elseif (!$this->member_cache['config']['groups']
                && dr_count(\Phpcmf\Service::M()->table('member_group_index')->where('uid', $uid)->getAll()) > 0) {
                $this->_json(0, dr_lang('不能同时拥有多个用户组'));
            } elseif (!\Phpcmf\Service::M()->counts('member_group_index', 'uid='.$uid.' and gid='.$gid)) {
                \Phpcmf\Service::M('member')->insert_group($uid, $gid);
                $c++;
            }
        }

        $this->_json(1, dr_lang('共执行%s个用户', $c));
    }

    /**
     * 后台授权登录
     */
    public function alogin_index() {

        $uid = intval(\Phpcmf\Service::L('input')->get('id'));
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
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {
        $data = parent::_Data($id);
        $data2 = \Phpcmf\Service::M()->db->table('member_data')->where('id', $id)->get()->getRowArray();
        $data2 && $data = $data + $data2;
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
                if (defined('UCSSO_API')) {
                    if ($member['phone'] != $old['phone'] && $rt = ucsso_edit_phone($id, $member['phone'])) {
                        if (!$rt['code']) {
                            return dr_return_data(0, dr_lang('通信失败：%s', $rt['msg']));
                        }
                    }
                    if ($member['email'] != $old['email'] && $rt = ucsso_edit_email($id, $member['email'])) {
                        if (!$rt['code']) {
                            return dr_return_data(0, dr_lang('通信失败：%s', $rt['msg']));
                        }
                    }
                }
                // 保存附表内容
                $status = \Phpcmf\Service::L('input')->post('status');
                $member_data = $data[1] ? $data[1] : [];
                $member_data['is_lock'] = (int)$status['is_lock'];
                $member_data['is_auth'] = (int)$status['is_auth'];
                $member_data['is_mobile'] = (int)$status['is_mobile'];
                $member_data['is_verify'] = (int)$status['is_verify'];
                $member_data['is_complete'] = (int)$status['is_complete'];
                $member_data['is_avatar'] = (int)$status['is_avatar'];
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
                    defined('UCSSO_API') && ucsso_edit_password($id, $password);
                }
                // 审核状态
                $status = \Phpcmf\Service::L('input')->post('status');
                $old['is_verify'] == 0 && $status['is_verify'] == 1 && \Phpcmf\Service::M('member')->verify_member($id);
                return $data;
            }
        );
    }

}
