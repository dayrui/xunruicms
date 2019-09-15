<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// Http接口处理
class Http extends \Phpcmf\Common
{

    /**
     * 调用接口
     */
    public function index() {

    }

    /**
     * 接口测试
     */
    public function test() {
        $this->_api_auth();
        $this->_json(1, 'ok');
    }

}
