<?php namespace Phpcmf\Controllers\Api;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
 **/

// 测试信息
class Test extends \Phpcmf\Common
{

    public function index() {

        echo 'This is v'.$this->cmf_version['version'];
        exit;
    }

}
