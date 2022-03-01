<?php namespace Config;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
	public $psr4 = [];

	public $classmap = [];
	
	public $files = [];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$psr4 = [

            'App'                           => COREPATH,
			'Config'                        => COREPATH.'Config',

            'Phpcmf\Controllers'            => APPPATH.'Controllers',

            'Phpcmf\Control'                => CMSPATH.'Control',
			'Phpcmf\Extend'                 => CMSPATH.'Extend',
            'Phpcmf\Library'                => CMSPATH.'Library',
            'Phpcmf\Field'                  => CMSPATH.'Field',
            'Phpcmf\ThirdParty'             => FCPATH.'ThirdParty',

            'My\Field'                      => MYPATH.'Field',
            'My\Library'                	=> MYPATH.'Library',
            'My\Model'                	    => MYPATH.'Model',

		];

		$classmap = [
            'Phpcmf\App'                  => CMSPATH.'Core/App.php',
		    'Phpcmf\Table'                => CMSPATH.'Core/Table.php',
		    'Phpcmf\Model'                => CMSPATH.'Core/Model.php',
		    'Phpcmf\View'                 => CMSPATH.'Core/View.php',
            'Phpcmf\Common'               => CMSPATH.'Core/Common.php',
            'Phpcmf\Admin\File'           => CMSPATH.'Extend/File.php',
        ];

        if (IS_USE_MODULE) {
            $classmap['Phpcmf\Home\Module'] = IS_USE_MODULE.'Extends/Home/Module.php';
            $classmap['Phpcmf\Admin\Config'] = IS_USE_MODULE.'Extends/Admin/Config.php';
            $classmap['Phpcmf\Admin\Module'] = IS_USE_MODULE.'Extends/Admin/Module.php';
            $classmap['Phpcmf\Model\Content'] = IS_USE_MODULE.'Models/Content.php';
            $classmap['Phpcmf\Admin\Category'] = IS_USE_MODULE.'Extends/Admin/Category.php';
        }

		if (IS_USE_MEMBER) {
		    $classmap['Phpcmf\Member\Module'] = IS_USE_MEMBER.'Extends/Module.php';
        }

		$this->psr4 = array_merge($this->psr4, $psr4);
		$this->classmap = array_merge($this->classmap, $classmap);

		unset($psr4, $classmap);
	}
}
