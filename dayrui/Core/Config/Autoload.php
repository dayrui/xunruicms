<?php namespace Config;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

require BASEPATH.'Config/AutoloadConfig.php';

/**
 * -------------------------------------------------------------------
 * AUTO-LOADER
 * -------------------------------------------------------------------
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 */
class Autoload extends \CodeIgniter\Config\AutoloadConfig
{
	public $psr4 = [];

	public $classmap = [];

	//--------------------------------------------------------------------

	/**
	 * Collects the application-specific autoload settings and merges
	 * them with the framework's required settings.
	 *
	 * NOTE: If you use an identical key in $psr4 or $classmap, then
	 * the values in this file will overwrite the framework's values.
	 */
	public function __construct()
	{
		parent::__construct();

		/**
		 * -------------------------------------------------------------------
		 * Namespaces
		 * -------------------------------------------------------------------
		 * This maps the locations of any namespaces in your application
		 * to their location on the file system. These are used by the
		 * Autoloader to locate files the first time they have been instantiated.
		 *
		 * The '/application' and '/system' directories are already mapped for
		 * you. You may change the name of the 'App' namespace if you wish,
		 * but this should be done prior to creating any namespaced classes,
		 * else you will need to modify all of those classes for this to work.
		 *
		 * DO NOT change the name of the CodeIgniter namespace or your application
		 * WILL break. *
		 * Prototype:
		 *
		 *   $Config['psr4'] = [
		 *       'CodeIgniter' => SYSPATH
        'Phpcmf\Home'                  => CMSPATH.'Control/Home',
        'Phpcmf\Admin'                 => CMSPATH.'Control/Admin',
        'Phpcmf\Member'                => CMSPATH.'Control/Member',
        'Phpcmf\PhpQuery'              => COREPATH.'ThirdParty/PhpQuery',
		 *   `];
		 */
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

            'Zend'                          => FCPATH.'Zend',
            'My\Field'                      => MYPATH.'Field',
            'My\Admin'                      => MYPATH.'Control/Admin',
            'My\Home'                       => MYPATH.'Control/Home',
            'My\Member'                     => MYPATH.'Control/Member',
            'My\Library'                	=> MYPATH.'Library',
            'My\Model'                	    => MYPATH.'Model',




		];

		/**
		 * -------------------------------------------------------------------
		 * Class Map
		 * -------------------------------------------------------------------
		 * The class map provides a map of class names and their exact
		 * location on the drive. Classes loaded in this manner will have
		 * slightly faster performance because they will not have to be
		 * searched for within one or more directories as they would if they
		 * were being autoloaded through a namespace.
		 *
		 * Prototype:
		 *
		 *   $Config['classmap'] = [
		 *       'MyClass'   => '/path/to/class/file.php'
		 *   ];
		 */
		$classmap = [

            'Phpcmf\App'                  => CMSPATH.'Core/App.php',
		    'Phpcmf\Table'                => CMSPATH.'Core/Table.php',
		    'Phpcmf\Model'                => CMSPATH.'Core/Model.php',
		    'Phpcmf\View'                 => CMSPATH.'Core/View.php',
		    'Phpcmf\Hooks'                => CMSPATH.'Core/Hooks.php',
		    'Phpcmf\Service'              => CMSPATH.'Core/Service.php',
            'Phpcmf\App\Common'           => CMSPATH.'Core/Common.php',
            //'CodeIgniter\Events\Events'	  => CMSPATH.'Extend/Events.php',
            //'CodeIgniter\Debug\Toolbar\Collectors\Views'	  => CMSPATH.'Extend/View.php',




        ];

		//--------------------------------------------------------------------
		// Do Not Edit Below This Line
		//--------------------------------------------------------------------

		$this->psr4 = array_merge($this->psr4, $psr4);
		$this->classmap = array_merge($this->classmap, $classmap);

		unset($psr4, $classmap);
	}

	//--------------------------------------------------------------------

}
