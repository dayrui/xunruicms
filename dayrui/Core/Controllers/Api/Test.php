<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 测试信息
class Test extends \Phpcmf\Common
{

    public function index() {

        echo 'This is Xunruicms v'.$this->cmf_version['version'];
        exit;
    }

}
