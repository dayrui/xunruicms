<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 缓存更新
class Cache extends \Phpcmf\Common
{

    public function index() {

        $list = [
            ['系统配置缓存', 'update_cache'],
            ['更新附件缓存', 'update_attachment'],
            ['清理缩略图文件', 'update_thumb'],
        ];
        $cname = [];
        if (dr_is_app('module')) {
            $list[] = ['重建内容搜索索引', 'update_search_index'];
            $cname[] = '更新模块域名目录';
        }
        if (dr_is_app('ueditor') && is_file(CMSPATH.'Field/Ueditor.php')) {
            $list[] = ['更新百度编辑器', 'update_ueditor'];
        }
        if (dr_is_app('sites')) {
            $cname[] = '更新子站目录';
        }
        if (dr_is_app('client')) {
            $cname[] = '更新终端目录';
        }
        if ($cname) {
            $list[] = [implode('、', $cname), 'update_site_config'];
        }

        \Phpcmf\Service::V()->assign([
            'list' => $list,
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
