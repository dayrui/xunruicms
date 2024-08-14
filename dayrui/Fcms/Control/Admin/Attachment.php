<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Attachment extends \Phpcmf\Common {

    public $type;
    public $path;
    public $load_file;

	public function __construct() {
		parent::__construct();
        $this->type = [
            0 => [
                'name' => '本地磁盘',
            ],
        ];
        $this->path = FCPATH.'ThirdParty/Storage/';
        $local = dr_dir_map($this->path, 1);
        $this->load_file = [];
        foreach ($local as $dir) {
            if (is_file($this->path.$dir.'/App.php')) {
                $cfg = require $this->path.$dir.'/App.php';
                if ($cfg['id']) {
                    $this->load_file[] = $this->path.$dir.'/Config.html';
                    $this->type[$cfg['id']] = $cfg;
                }
            }
        }
	}

	public function index() {

        $data = is_file(WRITEPATH.'config/system.php') ? require WRITEPATH.'config/system.php' : [];

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $image = \Phpcmf\Service::L('input')->post('image');
            $save = [
                'SYS_ATTACHMENT_SAFE' => (int)$post['SYS_ATTACHMENT_SAFE'],
                'SYS_ATTACHMENT_GUEST' => (int)$post['SYS_ATTACHMENT_GUEST'],
                'SYS_ATTACHMENT_CF' => (int)$post['SYS_ATTACHMENT_CF'],
                'SYS_ATTACHMENT_REL' => (int)$post['SYS_ATTACHMENT_REL'],
                'SYS_ATTACHMENT_DB' => (int)$post['SYS_ATTACHMENT_DB'],
                'SYS_ATTACHMENT_PAGESIZE' => (int)$post['SYS_ATTACHMENT_PAGESIZE'],
                'SYS_ATTACHMENT_URL' => $post['SYS_ATTACHMENT_URL'],
                'SYS_ATTACHMENT_PATH' => addslashes($post['SYS_ATTACHMENT_PATH']),
                'SYS_ATTACHMENT_SAVE_TYPE' => intval($post['SYS_ATTACHMENT_SAVE_TYPE']),
                'SYS_ATTACHMENT_DOWN_REMOTE' => intval($post['SYS_ATTACHMENT_DOWN_REMOTE']),
                'SYS_ATTACHMENT_DOWN_SIZE' => intval($post['SYS_ATTACHMENT_DOWN_SIZE']),
                'SYS_ATTACHMENT_SAVE_DIR' => addslashes($post['SYS_ATTACHMENT_SAVE_DIR']),
                'SYS_ATTACHMENT_SAVE_ID' => intval($post['SYS_ATTACHMENT_SAVE_ID']),
                'SYS_AVATAR_URL' => $image['avatar_url'],
                'SYS_AVATAR_PATH' => addslashes($image['avatar_path']),
            ];
            if ($image['cache_path']) {
                if ($save['SYS_ATTACHMENT_PATH'] && $image['cache_path'] == $save['SYS_ATTACHMENT_PATH']) {
                    $this->_json(0, dr_lang('附件上传目录不能与缩略图存储目录相同'));
                } elseif ($save['SYS_AVATAR_PATH'] && $image['cache_path'] == $save['SYS_AVATAR_PATH']) {
                    $this->_json(0, dr_lang('头像存储目录不能与缩略图存储目录相同'));
                }
            }
            $save['cache_path'] = $image['cache_path'];
            foreach (['SYS_ATTACHMENT_PATH' => '附件上传', 'SYS_AVATAR_PATH' => '头像上传', 'cache_path' => '缩略图上传'] as $key => $name) {
                if (isset($save[$key]) && $save[$key] &&
                    (strpos($save[$key], 'config') !== false || strpos($save[$key], CONFIGPATH) !== false)) {
                    $this->_json(0, dr_lang('%s目录不能包含config目录', $name));
                }
            }
            unset($save['cache_path']);
            \Phpcmf\Service::M('System')->save_config($data, $save);
            unset($image['avatar_url'], $image['avatar_path']);
            \Phpcmf\Service::M('site')->config(SITE_ID, 'image', $image);
            \Phpcmf\Service::L('input')->system_log('设置附件参数');
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));


        $site = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $image = $site['image'];
        $image['avatar_url'] = defined('SYS_AVATAR_URL') ? SYS_AVATAR_URL : '';
        $image['avatar_path'] = defined('SYS_AVATAR_PATH') ? SYS_AVATAR_PATH : '';

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '存储策略' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    'help' => [359],
                ]
            ),
            'image' => $image,
            'remote' =>  \Phpcmf\Service::C()->get_cache('attachment'),
        ]);
        \Phpcmf\Service::V()->display('attachment_index.html');
	}

	public function remote_index() {

        \Phpcmf\Service::V()->assign([
            'list' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '存储策略' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_remote.html');
	}

	public function add() {

	    if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('input')->post('data', true);
            $rt = \Phpcmf\Service::M()->table('attachment_remote')->insert([
                'type' => intval($data['type']),
                'name' => (string)$data['name'],
                'url' => trim((string)$data['url']),
                'value' => dr_array2string($data['value']),
            ]);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache('attachment');
            $this->_json(1, dr_lang('操作成功'));
        }
	    
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '存储策略' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_add.html');
	}

	public function edit() {

	    $id = intval($_GET['id']);

	    if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('input')->post('data', true);
            $rt = \Phpcmf\Service::M()->table('attachment_remote')->update($id,
                [
                    'type' => intval($data['type']),
                    'name' => (string)$data['name'],
                    'url' => trim((string)$data['url']),
                    'value' => dr_array2string($data['value']),
                ]
            );
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache('attachment');
            $this->_json(1, dr_lang('操作成功'));
        }

        $data = \Phpcmf\Service::M()->table('attachment_remote')->get($id);
	    $data['value'] = dr_string2array($data['value']);
	    $data['value'] = $data['value'][intval($data['type'])];

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '存储策略' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_add.html');
	}

	public function del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        \Phpcmf\Service::M()->table('attachment_remote')->deleteAll($ids);
        \Phpcmf\Service::L('input')->system_log('批量删除远程附件策略: '. implode(',', $ids));

        // 自动更新缓存
        \Phpcmf\Service::M('cache')->sync_cache('attachment');

        $this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
    }
	
}
