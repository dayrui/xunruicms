<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class $NAME$_verify extends \Phpcmf\Admin\Mform
{

    public function index() {
        $this->_Admin_List();
    }

    public function edit() {
        $this->_Admin_Edit();
    }

    public function show_index() {
        $this->_Admin_Show();
    }

    public function order_edit() {
        $this->_Admin_Order();
    }

    public function del() {
        $this->_Admin_Del();
    }

    public function status_index() {
        $this->_Admin_Status();
    }
}
