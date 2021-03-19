<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 审核管理
class Member_verify extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);

        $this->my_field = array(
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('账号'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'validate' => array(
                        'required' => 1,
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
                    ),
                    'validate' => array(
                        'required' => 1,
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
                    ),
                    'validate' => array(
                        'required' => 1,
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
                    ),
                    'validate' => array(
                        'required' => 1,
                    )
                )
            ),
        );
        // 表单显示名称
        $this->name = dr_lang('用户');
        // 初始化数据表
        $this->_init([
            'table' => 'member',
            'field' => $this->member_cache['field'],
            'sys_field' => [],
            'order_by' => 'id desc',
            'list_field' => [],
        ]);
        $this->group = $this->member_cache['group'];
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '待审核用户' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-user'],
                ]
            ),
            'field' => $this->my_field,
            'group' => $this->group,
        ]);
    }

    // 用户管理
    public function index() {

        $p = [];
        $name = \Phpcmf\Service::L('input')->request('field');
        $value = \Phpcmf\Service::L('input')->request('keyword');

        $where = [
            '`id` IN (select id from `'.\Phpcmf\Service::M()->dbprefix('member_data').'` where `is_verify`=0)',
        ];

        if ($name && $value && isset($this->my_field[$name])) {
            $p[$name] = $value;
            $where[] = '`'.$name.'` LIKE "%'.$value.'%"';
        }
        
        $groupid = (int)\Phpcmf\Service::L('input')->request('groupid');
        if ($groupid) {
            $p['groupid'] = $groupid;
            $where[] = '`id` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('member_group_index').'` where id='.$groupid.')';
        }
        
        $where && \Phpcmf\Service::M()->set_where_list(implode(' AND ', $where));

        $this->_List($p);

        \Phpcmf\Service::V()->assign([
            'is_verify' => 1,
            'uriprefix' => 'member',
        ]);
        \Phpcmf\Service::V()->display('member_list.html');
    }

    // 审核
    public function edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选用户不存在'));
        }

        foreach ($ids as $id) {
            \Phpcmf\Service::M('member')->verify_member($id);
        }

        $this->_json(1, dr_lang('操作成功'));
    }


}
