<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Site_image extends \Phpcmf\Common
{

	public function index() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M('Site')->config(
                SITE_ID,
                'watermark',
                \Phpcmf\Service::L('input')->post('data', true)
            );
            \Phpcmf\Service::M('Site')->config(
                SITE_ID,
                'image_reduce',
                \Phpcmf\Service::L('input')->post('image', true)
            );
            \Phpcmf\Service::M('cache')->sync_cache('');
            \Phpcmf\Service::L('input')->system_log('设置网站图片参数');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);

        $locate = [

            'left-top' => '左上',
            'center-top' => '中上',
            'right-top' => '右上',

            'left-middle' => '左中',
            'center-middle' => '正中',
            'right-middle' => '右中',

            'left-bottom' => '左下',
            'center-bottom' => '中下',
            'right-bottom' => '右下',

        ];

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '图片设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-photo'],
                    'help' => [507],
                ]
            ),
            'page' => $page,
            'data' => $data['watermark'],
            'image' => $data['image_reduce'],
            'form' => dr_form_hidden(['page' => $page]),
            'locate' => $locate,
            'waterfont' => dr_file_map(ROOTPATH.'config/font/', 1),
            'waterfile' => dr_file_map(ROOTPATH.'config/watermark/', 1),
        ]);
        \Phpcmf\Service::V()->display('site_image.html');
	}

	
}
