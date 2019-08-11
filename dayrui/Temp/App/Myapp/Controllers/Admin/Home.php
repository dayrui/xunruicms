<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\App
{

	public function index() {

	    $name = 'hello word';

        // 将变量传入模板
        \Phpcmf\Service::V()->assign([
            'testname' => $name,
        ]);
        // 选择输出模板 后台位于 ./Views/test.html 此文件已经创建好了
        \Phpcmf\Service::V()->display('test.html');
    }

}
