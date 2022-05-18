<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * Debug工具栏路由类
 */

class Routes extends \CodeIgniter\Debug\Toolbar\Collectors\Routes {

    /**
     * Returns the data of this collector to be formatted in the toolbar
     *
     * @throws ReflectionException
     */
    public function display(): array
    {


        return [
            'matchedRoute' => [
               [
                   'uri' => \Phpcmf\Service::L('Router')->uri(),
                   'url' => dr_now_url(),
                   'app' => APP_DIR,
                   'controller' => \Phpcmf\Service::L('Router')->class,
                   'method' => \Phpcmf\Service::L('Router')->method,
               ]
            ],
            'get'       => $_GET,
        ];
    }

}
