<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 用户组申请
class Member_apply extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->my_field = array(
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('账户'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
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
        // 表单显示名称
        $this->name = dr_lang('用户组审核');
        // 初始化数据表
        $this->_init([
            'table' => 'member_group_verify',
            'field' => $this->my_field,
            'sys_field' => [],
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
            'list_field' => [],
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户组审核' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-users'],
                    '详情' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                ]
            ),
            'field' => $this->my_field,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('member_apply_list.html');
    }

    // edit
    public function edit() {

        list($tpl, $data) = $this->_Post((int)\Phpcmf\Service::L('input')->get('id'), [], 1);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('申请记录不存在'));
        }

        $my = dr_string2array($data['content']);
        $user = dr_member_info($data['uid']);
        $my = dr_array22array($user, $my);

        // 获取该组可用字段
        $field = [
            'note' => array(
                'ismain' => 1,
                'name' => dr_lang('审核备注'),
                'fieldname' => 'note',
                'fieldtype' => 'Textarea',
                'setting' => array(
                    'option' => array(
                        'width' => '80%',
                    ),
                )
            ),
        ];
        if ($this->member_cache['field'] && $this->member_cache['group'][$data['gid']]['field']) {
            foreach ($this->member_cache['field'] as $fname => $t) {
                in_array($fname, $this->member_cache['group'][$data['gid']]['field']) && $field[$fname] = $t;
            }
        }

        $verify_msg = [];
        if ($this->member_cache['config']['verify_msg']) {
            $msg = @explode(PHP_EOL, $this->member_cache['config']['verify_msg']);
            $msg && $verify_msg = $msg;
        }

        \Phpcmf\Service::V()->assign([
            'myfield' => \Phpcmf\Service::L('field')->toform($this->uid, $field, $my),
            'verify_msg' => $verify_msg,
        ]);
        \Phpcmf\Service::V()->display('member_apply_post.html');
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        \Phpcmf\Service::M('member')->todo_admin_notice('member_apply/edit:id/'.$id);

        $post = \Phpcmf\Service::L('input')->post('data');
        $member = \Phpcmf\Service::M('member')->member_info($old['uid']);
        $member['verify_group'] = $this->member_cache['group'][$old['gid']]['name'];
        $member['verify_status'] = $post['status'] ? dr_lang('成功') : dr_lang('被拒绝');
        $member['verify_content'] = $post['note'];

        if ($post['status']) {
            unset($post['status']);
            // 获取该组可用字段
            $field = [];
            if ($this->member_cache['field'] && $this->member_cache['group'][$old['gid']]['field']) {
                foreach ($this->member_cache['field'] as $fname => $t) {
                    in_array($fname, $this->member_cache['group'][$old['gid']]['field']) && $field[$fname] = $t;
                }
            }

            // 表单操作类
            \Phpcmf\Service::L('form')->id($id); // 初始化id
            list($post, $return, $attach) = \Phpcmf\Service::L('form')->validation($post, [], $field, []);
            if ($return) {
                $this->_json(0, $return['error'], ['field' => $return['name']]);
            }
            unset($post[1]['uid']);
            unset($post[1]['username']);

            $post[1] && \Phpcmf\Service::M()->table('member_data')->update($old['uid'], $post[1]);
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle($old['uid'], \Phpcmf\Service::M()->dbprefix('member').'-'.$id, $attach);

            \Phpcmf\Service::M()->table('member_group_verify')->delete($id);
            $post[1] && \Phpcmf\Service::M()->table('member_data')->update($old['uid'], $post[1]);
            \Phpcmf\Service::M('member')->insert_group($old['uid'], $old['gid']);
            $old['lid'] && \Phpcmf\Service::M('member')->update_level($old['uid'], $old['gid'], $old['lid']);
        } else {
            // 审核拒绝
            unset($post['status']);
            \Phpcmf\Service::M()->table('member_group_verify')->update($id, [
                'content' => dr_array2string($post),
                'status' => 1, // 拒绝
            ]);
            // 不退回金额
        }

        // 通知 钩子
        \Phpcmf\Service::L('Notice')->send_notice('member_verify_group', $member);
        \Phpcmf\Hooks::trigger('member_verify_group_after', $member);

        $this->_json(1, dr_lang('操作成功'));
    }

    // 删除
    public function del() {
        $this->_Del(\Phpcmf\Service::L('input')->get_post_ids(), null, function($rows) {
            foreach ($rows as $t) {
                // 删除审核提醒
                \Phpcmf\Service::M('member')->delete_admin_notice('member_apply/edit:id/'.$t['id'], SITE_ID);
                // 退回金额
                $this->_call_score($t);
            }
            return dr_return_data(1, 'ok');
        });
    }

    // 退回金额
    private function _call_score($t) {

        if ($t['price'] > 0) {
            $group = $this->member_cache['group'][$t['gid']];
            $notice = $t['lid'] ? dr_lang('[退回]申请用户组（%s）: %s', $group['name'], $group['level'][$t['lid']]['name']) : dr_lang('[退回]申请用户组（%s）', $group['name']);
            if ($this->member_cache['group'][$t['gid']]['unit']) {
                // 金币
                // 扣分
                $rt = \Phpcmf\Service::M('member')->add_score($t['uid'], (int)$t['price'], $notice, '', '');
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                // 提醒通知
                \Phpcmf\Service::M('member')->notice($t['uid'], 2, $notice);
            } else {
                // rmb
                $rt = \Phpcmf\Service::M('Pay')->add_money($t['uid'], $t['price']);
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                // 增加到交易流水
                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                    'uid' => $t['uid'],
                    'username' => $t['username'],
                    'touid' => 0,
                    'tousername' => '',
                    'mid' => 'system',
                    'title' => $notice,
                    'value' => $t['price'],
                    'type' => 'finecms',
                    'status' => 1,
                    'result' => '',
                    'paytime' => SYS_TIME,
                    'inputtime' => SYS_TIME,
                ]);
                // 提醒通知
                \Phpcmf\Service::M('member')->notice(
                    $t['uid'],
                    2,
                    $notice,
                    \Phpcmf\Service::L('Router')->member_url('paylog/show', ['id'=>$rt['code']])
                );

            }

        }
    }
}
