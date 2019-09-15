<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Tpl_client extends \Phpcmf\Admin\File
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->root_path = TPLPATH;
        $this->not_root_path = [TPLPATH.'mobile', TPLPATH.'pc'];
        $this->backups_dir = 'template';
        $this->backups_path = WRITEPATH.'backups/'.$this->backups_dir.'/';
    }

    public function index() {
        $this->_List();
    }

    public function edit() {
        $this->_Edit();
    }

    public function add() {
        $this->_Add();
    }

    public function clear_del() {
        $this->_Clear();
    }

    public function del() {
        $this->_Del();
    }

    public function image_index() {
        $this->_Image();
    }


}
