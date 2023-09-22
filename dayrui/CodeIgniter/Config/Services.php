<?php namespace Config;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Config\Services as CoreServices;
use CodeIgniter\HTTP\UserAgent;
use Config\Security as SecurityConfig;

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


    // 异常处理
    public static function exceptions(
         $config = null,
         $request = null,
         $response = null,
        bool $getShared = true
    )
    {
        if ($getShared)
        {
            return static::getSharedInstance('exceptions', $config, $request, $response);
        }

        $config   = $config ?? config('Exceptions');
        $request  = $request ?? static::request();
        $response = $response ?? static::response();

        return new \Phpcmf\Extend\Exceptions($config, $request, $response);
    }

    // 防跨站验证
    public static function security(?SecurityConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('security', $config);
        }

        $config ??= config(SecurityConfig::class);

        return new \Phpcmf\Extend\Security($config);
    }

    // 路由模式
    public static function request(App $config = null, bool $getShared = true)
    {
        if ($getShared)
        {
            return static::getSharedInstance('request', $config);
        }

        $config = $config ?? config('App');

        return new \Phpcmf\Extend\Request(
            $config,
            static::uri(),
            'php://input',
            new UserAgent()
        );
    }

}
