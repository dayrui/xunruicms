<?php namespace Phpcmf\Controllers\Member;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class $NAME$ extends \Phpcmf\Member\Form
{

    public function index() {
        $this->_Member_List();
    }

    public function add() {
        $this->_Member_Add();
    }

    public function edit() {
        $this->_Member_Edit();
    }

    public function order_edit() {
        $this->_Member_Order();
    }

    public function del() {
        $this->_Member_Del();
    }
}
