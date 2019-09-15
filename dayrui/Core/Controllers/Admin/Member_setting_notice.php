<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



class Member_setting_notice extends \Phpcmf\Common
{

    public function index() {

        $local = dr_dir_map(dr_get_app_list(), 1);
        $notice['member'] = [
            'value' => require CMSPATH.'Config/Notice.php',
        ];

        if (is_file(MYPATH.'Config/Notice.php')) {
            $notice['my'] = [
                'value' => require MYPATH.'Config/Notice.php',
            ];
        }

        foreach ($local as $dir) {
            $path = dr_get_app_dir($dir);
            if (is_file($path.'/Config/Notice.php')
                && is_file($path.'/Config/App.php')) {
                $app = require $path.'/Config/App.php';
                $cfg = require $path.'/Config/Notice.php';
                $app && $cfg && $notice[strtolower($dir)] = [
                    'name' => $app['name'],
                    'value' => $cfg
                ];
            }
        }

        foreach ($notice as $i => $t) {
            if ($t['value']) {
                foreach ($t['value'] as $ii => $v) {
                    $path = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', '');
                    $notice[$i]['value'][$ii] = [
                        'name' => $v,
                    ];
                    $notice[$i]['value'][$ii]['file'] = [
                        'mobile' => is_file($path.'config/notice/mobile/'.$ii.'.html') ? 1 : 0,
                        'notice' => is_file($path.'config/notice/mobile/'.$ii.'.html') ? 1 : 0,
                        'email' => is_file($path.'config/notice/email/'.$ii.'.html') ? 1 : 0,
                        'weixin' => is_file($path.'config/notice/weixin/'.$ii.'.html') ? 1 : 0,
                    ];
                }
            }
        }

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'notice')->get()->getRowArray();

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '通知设置' => ['member_setting_notice/index', 'fa fa-volume-up'],
                ]
            ),
            'value' => dr_string2array($data['value']),
            'notice_config' => $notice,
        ]);
        \Phpcmf\Service::V()->display('member_setting_notice.html');
    }

    // 保存配置
    public function add() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'notice',
                'value' => dr_array2string(\Phpcmf\Service::L('input')->post('data', true))
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        } else {
            $this->_json(0, dr_lang('异常请求'));
        }
    }

    // 修改模板
    public function edit() {

        $file = dr_safe_filename($_GET['file']);
        $list = [];
        foreach ($this->site_info as $sid => $t) {
            $path = \Phpcmf\Service::L('html')->get_webpath($sid, 'site', '');
            $list[$sid] = [
                'name' => $t['SITE_NAME'],
                'data' => [
                    'mobile' => [
                        'name' => dr_lang('短信和消息'),
                        'code' => htmlentities(file_get_contents($path.'config/notice/mobile/'.$file.'.html'),ENT_COMPAT,'UTF-8'),
                        'file' => '/config/notice/mobile/'.$file.'.html',
                        'help' => 'javascript:dr_help(479);', //'http://help.phpcmf.net/479.html',
                    ],
                    'email' => [
                        'name' => dr_lang('邮件'),
                        'code' => htmlentities(file_get_contents($path.'config/notice/email/'.$file.'.html'),ENT_COMPAT,'UTF-8'),
                        'file' => '/config/notice/email/'.$file.'.html',
                        'help' => 'javascript:dr_help(480);', //'http://help.phpcmf.net/480.html',
                    ],
                    'weixin' => [
                        'name' => dr_lang('微信'),
                        'code' => htmlentities(file_get_contents($path.'config/notice/weixin/'.$file.'.html'),ENT_COMPAT,'UTF-8'),
                        'file' => '/config/notice/weixin/'.$file.'.html',
                        'help' => 'javascript:dr_help(481);', //'http://help.phpcmf.net/481.html',
                    ],
                ]
            ];
        }

        \Phpcmf\Service::V()->assign([
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('member_setting_notice_edit.html');exit;
    }

}
