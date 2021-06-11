<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class System_cache extends \Phpcmf\Common
{

    public function index() {

        $file = WRITEPATH.'config/cache.php';

        if (IS_AJAX_POST) {

            if (!\Phpcmf\Service::L('Config')->file($file, '缓存配置文件')->to_require_one(\Phpcmf\Service::L('input')->post('data'))) {
                $this->_json(0, dr_lang('配置文件写入失败'));
            }

            \Phpcmf\Service::L('input')->system_log('配置缓存参数'); // 记录日志
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'data' => is_file($file) ? require $file : [],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '缓存设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-clock-o'],
                    'help' => [504],
                ]
            ),
            'cache_var' => [
                'SHOW' => '模块内容',
                //'ATTACH' => '网站附件',
                'LIST' => '查询标签',
                'SEARCH' => '内容搜索',
            ],
        ]);
        \Phpcmf\Service::V()->display('system_cache.html');
    }


}
