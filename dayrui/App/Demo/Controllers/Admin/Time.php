<?php namespace Phpcmf\Controllers\Admin;

class Time extends \Phpcmf\Admin\Module
{

    public function index() {
        $this->_Admin_Time_List();
    }

    public function add() {
        $this->_Admin_Time_add();
    }

    public function edit() {
        $this->_Admin_Time_Edit();
    }

    public function del() {
        $this->_Admin_Time_Del();
    }
}
