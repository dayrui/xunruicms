<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Comment extends \Phpcmf\Admin\Comment
{

    public function index() {
        $this->_Admin_List();
    }

    public function add() {
        $this->_Admin_Add();
    }

    public function edit() {
        $this->_Admin_Edit();
    }

    public function review_index() {
        $this->_Admin_Review();
    }

    public function show_index() {
        $this->_Admin_Show();
    }

    public function del() {
        $this->_Admin_Del();
    }
}
