<?php namespace Phpcmf\Controllers\Admin;

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
