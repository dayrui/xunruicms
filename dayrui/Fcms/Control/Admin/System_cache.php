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

            $config = \Phpcmf\Service::L('input')->post('data');
            $config['SYS_CACHE_LIST'] = $config['SYS_CACHE_SEARCH'] = $config['SYS_CACHE_SHOW']; // 统一视为缓存时间
            if (!\Phpcmf\Service::L('Config')->file($file, '缓存配置文件')->to_require_one($config)) {
                $this->_json(0, dr_lang('配置文件写入失败'));
            }

            \Phpcmf\Service::L('input')->system_log('配置缓存参数'); // 记录日志
            $this->_json(1, dr_lang('操作成功'));
        }

        $data = is_file($file) ? require $file : [];
        if (!isset($data['SYS_CACHE_CRON']) or empty($data['SYS_CACHE_CRON'])) {
            $data['SYS_CACHE_CRON'] = 3;
        }
        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        $run_time = '';
        if (is_file(WRITEPATH.'config/run_time.php')) {
            $run_time = file_get_contents(WRITEPATH.'config/run_time.php');
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'data' => $data,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '缓存设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-clock-o'],
                    'help' => [504],
                ]
            ),
            'cache_var' => [
                'SHOW' => '缓存时间',
                //'ATTACH' => '网站附件',
                //'LIST' => '查询缓存',
                //'SEARCH' => '搜索缓存',
            ],
            'run_time' => $run_time,
        ]);
        \Phpcmf\Service::V()->display('system_cache.html');
    }


}
