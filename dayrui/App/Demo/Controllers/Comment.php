<?php namespace Phpcmf\Controllers;

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
