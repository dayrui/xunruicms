<?php namespace Phpcmf\Controllers;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Comment extends \Phpcmf\Home\Comment
{

    public function index() {
        parent::_Index();
    }

    public function post() {
        parent::_Post();
    }


    public function op() {
        parent::_Op();
    }

}
