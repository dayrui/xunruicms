<?php namespace Phpcmf\Controllers;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Show extends \Phpcmf\Home\Module
{

    public function index() {
        $this->_module_init();
        $this->_Show(
            (int)\Phpcmf\Service::L('Input')->get('id'),
            [
                'field' => dr_safe_replace(\Phpcmf\Service::L('Input')->get('field')),
                'value' => dr_safe_replace(\Phpcmf\Service::L('Input')->get('value')),
            ],
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        );
    }

    public function time() {
        $this->_module_init();
        $this->_MyShow(
            'time',
            (int)\Phpcmf\Service::L('Input')->get('id'),
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        );
    }

    public function recycle() {
        $this->_module_init();
        $this->_MyShow(
            'recycle',
            (int)\Phpcmf\Service::L('Input')->get('id'),
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        );
    }

    public function draft() {
        $this->_module_init();
        $this->_MyShow(
            'draft',
            (int)\Phpcmf\Service::L('Input')->get('id'),
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        );
    }

    public function verify() {
        $this->_module_init();
        $this->_MyShow(
            'verify',
            (int)\Phpcmf\Service::L('Input')->get('id'),
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        );
    }

}
