<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Explog extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单显示名称
        $this->name = dr_lang('升级经验');
        // 初始化数据表
        $this->_init([
            'table' => 'member_explog',
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
            'where_list' => '`uid`='.$this->uid,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('explog_index.html');
    }


}
