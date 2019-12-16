<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 提醒

class Member_notice extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
            [
                '站内消息' => ['member_notice/index', 'fa fa-bell'],
                '发送消息' => ['member_notice/add', 'fa fa-plus'],
                'help' => [ 669 ],
            ]
        ));
        // 表单显示名称
        $this->name = dr_lang('站内消息');
    }

    public function index() {

        $this->my_field = array(
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('用户名'),
                'fieldname' => 'username',
            ),
            'uid' => array(
                'ismain' => 1,
                'name' => dr_lang('uid'),
                'fieldname' => 'uid',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
        );

        $param = [
            'table' => 'member_notice',
            'field' => $this->my_field,
        ];
        $name = dr_safe_replace(\Phpcmf\Service::L('input')->request('field'));
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->request('keyword'));
        if ($name == 'username' && $value) {
            unset($param['field']['username']);
            $param['where_list'] = '`uid` IN (select id from `'.\Phpcmf\Service::M()->dbprefix('member').'` where username="'.$value.'")';
        }

        // 初始化数据表
        $this->_init($param);
        \Phpcmf\Service::V()->assign([
            'type' => dr_notice_info(),
            'field' => $this->my_field,
        ]);
        $this->_List();
        \Phpcmf\Service::V()->display('member_notice_list.html');
    }

    // 删除
    public function del() {
        // 初始化数据表
        $this->_init([
            'table' => 'member_notice',
        ]);
        $this->_Del(\Phpcmf\Service::L('input')->get_post_ids());
    }

    // 发送
    public function add() {

        $type = [
            0 => dr_lang('全部'),
            1 => dr_lang('单个'),
            2 => dr_lang('批量'),
        ];
        foreach ($this->member_cache['group'] as $id => $t) {
            $type['g'.$id] = $t['name'];
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'type' => $type,
        ]);
        \Phpcmf\Service::V()->display('member_notice_post.html');
    }


    // 内容查看
    public function show() {

        $id = intval($_GET['id']);
        $data = \Phpcmf\Service::M()->table('member_notice')->get($id);

        \Phpcmf\Service::V()->assign([
            'data' => $data,
        ]);
        \Phpcmf\Service::V()->display('member_notice_show.html');exit;
    }

    // 内容处理
    public function todo_add() {

        $post = \Phpcmf\Service::L('input')->post('data');
        $cache = [];

        switch ($post['type']) {

            case '0':
                // 全部会员
                $data = \Phpcmf\Service::M()->table('member')->getAll();
                if ($data) {
                    foreach ($data as $t) {
                        $t['username'] && $cache[] = $t['username'];
                    }
                }
                break;

            case '1':
                //单个
                $post['username'] && $cache[] = $post['username'];
                break;

            case '2':
                //批量
                $data = explode(PHP_EOL, $post['usernames']);
                if ($data) {
                    foreach ($data as $t) {
                        $t && $cache[] = $t;
                    }
                }
                break;

            default:
                // 用户组
                $gid = (int)substr($post['type'], 1);
                $data = \Phpcmf\Service::M()->query_sql('select `username` from `{dbprefix}member` where `id` in (select `uid` from `{dbprefix}member_group_index` where `gid`='.$gid.' )', 1);
                if ($data) {
                    foreach ($data as $t) {
                        $t['username'] && $cache[] = $t['username'];
                    }
                }
                break;

        }

        $cache && $cache = array_unique($cache);

        if (!dr_count($cache)) {
            $this->_json(0, dr_lang('无可用账号'));
        } elseif (!$post['note']) {
            $this->_json(0, dr_lang('消息内容不存在'));
        }

        // 存储文件
        \Phpcmf\Service::L('cache')->set_data('member-notice-send', [
            'usernames' => dr_save_bfb_data($cache),
            'note' => $post['note'],
            'url' => $post['url'],
        ], 3600);

        $this->_json(1, 'ok', ['url' => dr_url('member_notice/show_index', ['counts'=> dr_count($cache)])]);
    }

    // 内容处理
    public function show_index() {
        \Phpcmf\Service::V()->assign([
            'menu' => '',
            'hidebtn' => 1,
            'todo_url' =>  dr_url('member_notice/send_add'),
            'count_url' =>  dr_url('member_notice/show_count_index'),
        ]);
        \Phpcmf\Service::V()->display('member_notice_bfb.html');exit;
    }

    // 内容数量统计
    public function show_count_index() {

        $data = \Phpcmf\Service::L('cache')->get_data('member-notice-send');
        if (!dr_count($data)) {
            $this->_json(0, dr_lang('无可用缓存内容'));
        }

        $this->_json(dr_count($data['usernames']), 'ok');
    }

    public function send_add() {

        $page = max(1, intval($_GET['pp']));
        $cache = \Phpcmf\Service::L('cache')->get_data('member-notice-send');
        if (!$cache) {
            $this->_json(0, dr_lang('缓存不存在'));
        }

        $data = $cache['usernames'][$page];
        if ($data) {
            $html = '';
            foreach ($data as $username) {

                $user = \Phpcmf\Service::M()->db->table('member')->where('username', $username)->get()->getRowArray();
                if (!$user) {
                    $ok = "<span class='error'>".dr_lang('账号不存在', $username)."</span>";
                    $class = ' p_error';
                } else {
                    $ok = "<span class='ok'>".dr_lang('发送成功')."</span>";
                    \Phpcmf\Service::M('member')->notice($user['id'], 1, $cache['note'], $cache['url']);
                }

                $html.= '<p class="'.$class.'"><label class="rleft">'.$username.'</label><label class="rright">'.$ok.'</label></p>';

            }
            $this->_json($page + 1, $html);
        }

        // 完成
        \Phpcmf\Service::L('cache')->init()->delete('member-notice-send');
        $this->_json(100, '');
    }

    
}
