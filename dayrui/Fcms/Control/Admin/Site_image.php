<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Site_image extends \Phpcmf\Common {


	public function index() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M('Site')->config(
                SITE_ID,
                'watermark',
                \Phpcmf\Service::L('input')->post('data')
            );
            /*
            \Phpcmf\Service::M('Site')->config(
                SITE_ID,
                'image_reduce',
                \Phpcmf\Service::L('input')->post('image')
            );*/

            $image = \Phpcmf\Service::L('input')->post('image');
            unset($image['avatar_url'], $image['avatar_path']);
            \Phpcmf\Service::M('site')->config(SITE_ID, 'image', $image);

            \Phpcmf\Service::M('cache')->sync_cache('');
            \Phpcmf\Service::L('input')->system_log('设置图片参数');
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
                    '图片设置' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-photo'],
                    'help' => [507],
                ]
            ),
            'page' => $page,
            'data' => $data['watermark'],
            'image' => $data['image'],
            //'image' => $data['image_reduce'],
            'form' => dr_form_hidden(['page' => $page]),
            'locate' => $locate,
            'waterfile' => dr_file_map(WRITEPATH.'watermark/', 1),
        ]);
        \Phpcmf\Service::V()->admin(COREPATH.'View/');
        \Phpcmf\Service::V()->display('site_image.html');
	}

	// 上传字体文件或图片
    public function upload_index() {

        $at = dr_safe_filename($_GET['at']);
        if ($at == 'font') {
            $rt = \Phpcmf\Service::L('upload')->upload_file([
                'save_name' => 'null',
                'save_path' => WRITEPATH.'watermark/',
                'form_name' => 'file_data',
                'file_exts' => ['ttf'],
                'file_size' => 50 * 1024 * 1024,
                'attachment' => [
                    'value' => [
                        'path' => 'null'
                    ]
                ],
            ]);
        } else {
            $rt = \Phpcmf\Service::L('upload')->upload_file([
                'save_name' => 'null',
                'save_path' => WRITEPATH.'watermark/',
                'form_name' => 'file_data',
                'file_exts' => ['png', 'jpg', 'jpeg'],
                'file_size' => 10 * 1024 * 1024,
                'attachment' => [
                    'value' => [
                        'path' => 'null'
                    ]
                ],
            ]);
        }

        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }

        $this->_json(1, dr_lang('上传成功'));
    }
	
}
