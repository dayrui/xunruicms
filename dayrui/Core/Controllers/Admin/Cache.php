<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 缓存更新
class Cache extends \Phpcmf\Common
{

    public function index() {

        \Phpcmf\Service::V()->assign([
            'list' => [
                ['系统配置缓存', 'update_cache'],
                ['重建搜索索引', 'update_search_index'],
                ['更新附件缓存', 'update_attachment'],
                ['更新子站目录、更新模块域名目录、更新终端目录', 'update_site_config'],
            ],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统更新' => ['cache/index', 'fa fa-refresh'],
                    '系统体检' => ['check/index', 'fa fa-wrench'],
                    'help' => [378],
                ]
            )
        ]);
        \Phpcmf\Service::V()->display('cache.html');
    }

}
