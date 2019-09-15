<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



// 金币流水
class Member_scorelog extends \Phpcmf\Table
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
        $this->name = SITE_SCORE;
        // 初始化数据表
        $this->_init([
            'table' => 'member_scorelog',
            'field' => $this->my_field,
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    SITE_SCORE => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-diamond'],
                    'help' => [ 599 ],
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
