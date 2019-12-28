<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Member_setting extends \Phpcmf\Common
{

    public function index() {

        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        // 获取会员全部配置信息
        $data = [];
        $result = \Phpcmf\Service::M()->db->table('member_setting')->get()->getResultArray();
        if ($result) {
            foreach ($result as $t) {
                $data[$t['name']] = dr_string2array($t['value']);
            }
        }

        if (IS_AJAX_POST) {
            $save = ['register', 'login', 'oauth', 'config'];
            $post = \Phpcmf\Service::L('input')->post('data');
            if ($post['register']['sms']) {
                if (!in_array('phone', $post['register']['field'])) {
                    $this->_json(0, dr_lang('短信验证注册必须让手机号作为注册字段'));
                } elseif (!$post['register']['code']) {
                    $this->_json(0, dr_lang('短信验证注册必须开启图片验证码'));
                }
            }
            foreach ($save as $name) {
                \Phpcmf\Service::M()->db->table('member_setting')->replace([
                    'name' => $name,
                    'value' => dr_array2string($post[$name])
                ]);
            }
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户设置' => ['member_setting/index', 'fa fa-cog'],
                ]
            ),
            'group' => \Phpcmf\Service::M()->table('member_group')->getAll(),
            'synurl' => \Phpcmf\Service::M('member')->get_sso_url(),
        ]);
        \Phpcmf\Service::V()->display('member_setting.html');
    }


}
