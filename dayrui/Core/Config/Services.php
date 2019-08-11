<?php namespace Config;

use CodeIgniter\Config\Services as CoreServices;
use CodeIgniter\Config\BaseConfig;

require_once BASEPATH.'Config/Services.php';

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends CoreServices
{

//    public static function example($getShared = true)
//    {
//        if ($getShared)
//        {
//            return self::getSharedInstance('example');
//        }
//
//        return new \CodeIgniter\Example();
//    }

    public static function honeypot(BaseConfig $config = null, $getShared = true)
    {
        if ($getShared)
        {
            return static::getSharedInstance('honeypot', $config);
        }

        if (is_null($config)) 
        {
            $config = new \Config\Honeypot();
        }

        return new \CodeIgniter\Honeypot\Honeypot($config);
    }

	
	
    public static function exceptions(\Config\Exceptions $config = null, \CodeIgniter\HTTP\IncomingRequest $request = null, \CodeIgniter\HTTP\Response $response = null, $getShared = true)
    {
        if ($getShared)
        {
            return static::getSharedInstance('exceptions', $config, $request, $response);
        }

        if (empty($config))
        {
            $config = new \Config\Exceptions();
        }

        if (empty($request))
        {
            $request = static::request();
        }

        if (empty($response))
        {
            $response = static::response();
        }

        return new \Phpcmf\Extend\Exceptions($config, $request, $response);
    }




    public static function request(\Config\App $config = null, $getShared = true)
    {

        if ($getShared)
        {
            return static::getSharedInstance('request', $config);
        }

        if ( ! is_object($config))
        {
            $config = new \Config\App();
        }

        return new \Phpcmf\Extend\Request(
            $config,
            new \CodeIgniter\HTTP\URI(),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );
    }


    public static function security(\Config\App $config = null, $getShared = true)
    {
        if ($getShared)
        {
            return static::getSharedInstance('security', $config);
        }

        if (! is_object($config))
        {
            $config = new \Config\App();
        }

        return new \Phpcmf\Extend\Security($config);
    }


    /**
     * @param \Config\Toolbar $config
     * @param boolean         $getShared
     *
     * @return \CodeIgniter\Debug\Toolbar
     */
    public static function toolbar(\Config\Toolbar $config = null, bool $getShared = true)
    {
        if ($getShared)
        {
            return static::getSharedInstance('toolbar', $config);
        }

        if (! is_object($config))
        {
            $config = config('Config\Toolbar');
        }


        return new \CodeIgniter\Debug\Toolbar($config);
    }


}
