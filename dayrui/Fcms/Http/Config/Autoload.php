<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
	public $psr4 = [];

	public $classmap = [];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$psr4 = [

            'App'                           => COREPATH,
			'Config'                        => COREPATH.'Config',

            'Phpcmf\Controllers'            => APPPATH.'Controllers',
			'Phpcmf\Extend'                 => CMSPATH.'Extend',
            'Phpcmf\Library'                => CMSPATH.'Library',
            'Phpcmf\Field'                  => CMSPATH.'Field',
            'Phpcmf\ThirdParty'             => FCPATH.'ThirdParty',
            'Phpcmf\Admin'                  => CMSPATH.'Control/Admin',
            'Phpcmf\Home'                   => CMSPATH.'Control/Home',
            'Phpcmf\Member'                 => CMSPATH.'Control/Member',

            'My\Field'                      => MYPATH.'Field',
            'My\Admin'                      => MYPATH.'Control/Admin',
            'My\Home'                       => MYPATH.'Control/Home',
            'My\Member'                     => MYPATH.'Control/Member',
            'My\Library'                	=> MYPATH.'Library',
            'My\Model'                	    => MYPATH.'Model',

		];

		$classmap = [

            'Phpcmf\App'                  => CMSPATH.'Core/App.php',
		    'Phpcmf\Table'                => CMSPATH.'Core/Table.php',
		    'Phpcmf\Model'                => CMSPATH.'Core/Model.php',
		    'Phpcmf\View'                 => CMSPATH.'Core/View.php',
            'Phpcmf\App\Common'           => CMSPATH.'Core/Common.php',

        ];

		$this->psr4 = array_merge($this->psr4, $psr4);
		$this->classmap = array_merge($this->classmap, $classmap);

		unset($psr4, $classmap);
	}
}
