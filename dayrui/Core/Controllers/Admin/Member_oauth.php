<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



// 授权登录
class Member_oauth extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->my_field = array(
            'nickname' => array(
                'ismain' => 1,
                'name' => dr_lang('昵称'),
                'isemoji' => 1,
                'fieldname' => 'nickname',
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
            'table' => 'member_oauth',
            'field' => $this->my_field,
            'sys_field' => [],
            'order_by' => 'id desc',
            'list_field' => [],
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '授权账号' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-qq'],
                    '详情' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                ]
            ),
            'field' => $this->my_field,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('member_oauth.html');
    }

    // 删除
    public function del() {
        $this->_Del(\Phpcmf\Service::L('input')->get_post_ids());
    }
    
}
