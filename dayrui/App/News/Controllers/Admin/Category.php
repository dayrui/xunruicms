<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Category extends \Phpcmf\Admin\Category
{

    public function index() {
        $this->_Admin_List();
    }

    public function all_add() {
        $this->_Admin_All_Add();
    }

    public function add() {
        $this->_Admin_Add();
    }

    public function edit() {
        $this->_Admin_Edit();
    }

    public function url_edit() {
        $this->_Admin_Url_Edit();
    }

    public function move_edit() {
        $this->_Admin_Move_Edit();
    }
    
    public function show_edit() {
        $this->_Admin_Show_Edit();
    }
    
    public function displayorder_edit() {
        $this->_Admin_Order();
    }
    
    public function html_edit() {
        $this->_Admin_Html_Edit();
    }

    public function del() {
        $this->_Admin_Del();
    }
}
