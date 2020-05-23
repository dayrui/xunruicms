<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 属性配置
class Config extends \Phpcmf\Common
{

    // 模块自由属性配置
    protected function _Module_Param() {

        // 初始化模块
        $this->_module_init(APP_DIR);

        $data = \Phpcmf\Service::M()->table('module')->where('dirname', APP_DIR)->getRow();
        if (!$data) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', APP_DIR));
        }


        $data['setting'] = dr_string2array($data['setting']);
        if (!isset($data['setting']['param'])) {
            $data['setting']['param'] = [];
        }

        if (IS_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $data['setting']['param'] = $post;
            \Phpcmf\Service::M()->db->table('module')->where('dirname', APP_DIR)->update([
                'setting' => dr_array2string($data['setting']),
            ]);
            $this->_json(1, '操作成功');
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'data' => $data['setting']['param'],
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '参数配置' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-cog'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('param.html');
    }
}