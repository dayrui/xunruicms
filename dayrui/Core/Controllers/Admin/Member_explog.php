<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 升级经验值记录
class Member_explog extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->my_field = array(
            'note' => array(
                'ismain' => 1,
                'name' => dr_lang('关键字'),
                'fieldname' => 'note',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
            'uid' => array(
                'ismain' => 1,
                'isint' => 1,
                'name' => dr_lang('账号uid'),
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
        $this->name = SITE_EXPERIENCE;
        // 初始化数据表
        $this->_init([
            'table' => 'member_explog',
            'field' => $this->my_field,
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    dr_lang('升级经验：%s', SITE_EXPERIENCE) => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-compass'],
                    'help' => [ 870 ],
                ]
            ),
            'field' => $this->my_field,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('member_scorelog_list.html');
    }

    // 删除
    public function del() {
        $this->_Del(\Phpcmf\Service::L('input')->get_post_ids());
    }
    
}
