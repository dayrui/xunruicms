<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



class Member_payconfig extends \Phpcmf\Common
{

    public function index() {

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'pay')->get()->getRowArray();
        $data = dr_string2array($data['value']);

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', true);
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'pay',
                'value' => dr_array2string($post)
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '支付设置' => ['member_payconfig/index', 'fa fa-cog'],
                    'help' => [625],
                ]
            )
        ]);
        \Phpcmf\Service::V()->display('member_payconfig.html');
    }


}
