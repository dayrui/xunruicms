<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Module_category extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        !$module && $this->_admin_msg(0, dr_lang('系统没有安装内容模块'));

        $share = 0;

        // 设置url
        foreach ($module as $dir => $t) {
            if ($t['share']) {
                $share = 1;
                unset($module[$dir]);
                continue;
            } elseif ($t['system'] == 2) {
                unset($module[$dir]);
                continue;
            }
            $module[$dir]['url'] =\Phpcmf\Service::L('Router')->url($dir.'/category/index');
        }

        if ($share) {
            $tmp['share'] = [
                'name' => '共享',
                'icon' => 'fa fa-share-alt',
                'title' => '共享',
                'url' =>\Phpcmf\Service::L('Router')->url('category/index'),
                'dirname' => 'share',
            ];
            $one = $tmp['share'];
            $module = dr_array22array($tmp, $module);
        } else {
            $one = reset($module);
        }

        !$module && $this->_admin_msg(0, dr_lang('系统没有可用内容模块'));

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_iframe_menu($module, $one['dirname']),
            'module' => $module,
            'dirname' => $one['dirname'],
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

}
