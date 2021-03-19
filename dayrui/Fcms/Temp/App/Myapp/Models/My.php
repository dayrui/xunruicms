<?php namespace Phpcmf\Model\Myapp; // Myapp表示插件目录

class My extends \Phpcmf\Model
{

	// 这是插件的类模型方法 控制器调用方法是：
	// \Phpcmf\Service::M('my', 'myapp')->test();
	// 'my' 表示文件名，'myapp'表示插件目录
    public function test() {

        $name = 'hello word';
		return $name;
    }

}
