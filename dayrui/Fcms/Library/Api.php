<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 公共调用的动作
 */

class Api {

    protected $uid;
    protected $site;
    protected $admin;
    protected $member;
    protected $siteid;

    /**
     * 构造函数,初始化变量
     */
    public function __construct() {
        $this->uid = \Phpcmf\Service::C()->uid;
        $this->site = \Phpcmf\Service::C()->site;
        $this->admin = \Phpcmf\Service::C()->admin;
        $this->member = \Phpcmf\Service::C()->member;
        $this->siteid = defined('SITE_ID') ? SITE_ID : 0;
    }

    /**
     * 附件改名
     */
    public function name_edit() {

        if (!$this->uid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('无权限修改'));
        } elseif (!$this->member['is_admin']) {
            \Phpcmf\Service::C()->_json(0, dr_lang('无权限修改'));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            \Phpcmf\Service::C()->_json(0, dr_lang('附件id不能为空'));
        }

        $data = \Phpcmf\Service::M()->table('attachment')->get($id);
        if (!$data) {
            \Phpcmf\Service::C()->_json(0, dr_lang('附件%s不存在', $id));
        }

        if (IS_POST) {
            $name = dr_rp(strtolower((string)\Phpcmf\Service::L('input')->post('name')), '.php'. '');
            if (!$name) {
                \Phpcmf\Service::C()->_json(0, dr_lang('附件名称不能为空'));
            }
            if ($data['related']) {
                \Phpcmf\Service::M()->table('attachment_data')->update($id, [
                    'filename' => $name,
                ]);
            } else {
                \Phpcmf\Service::M()->table('attachment_unused')->update($id, [
                    'filename' => $name,
                ]);
            }
            \Phpcmf\Service::M('attachment')->clear_data($data);
            \Phpcmf\Service::C()->_json(1, dr_lang('操作成功'));
        }

        if ($data['related']) {
            $data2 = \Phpcmf\Service::M()->table('attachment_data')->get($id);
        } else {
            $data2 = \Phpcmf\Service::M()->table('attachment_unused')->get($id);
        }

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'name' => $data2['filename'],
        ]);
        \Phpcmf\Service::V()->display('attachment_edit.html');exit;
    }

    /**
     * 图片编辑
     */
    public function image_edit() {

        if (!$this->uid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('无权限修改'));
        } elseif (!$this->member['is_admin']) {
            \Phpcmf\Service::C()->_json(0, dr_lang('无权限修改'));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            \Phpcmf\Service::C()->_json(0, dr_lang('附件id不能为空'));
        }

        $data = \Phpcmf\Service::M()->table('attachment')->get($id);
        if (!$data) {
            \Phpcmf\Service::C()->_json(0, dr_lang('附件%s不存在', $id));
        }

        if ($data['related']) {
            $info = \Phpcmf\Service::M()->table('attachment_data')->get($id);
        } else {
            $info = \Phpcmf\Service::M()->table('attachment_unused')->get($id);
        }

        if (!dr_is_image($info['fileext'])) {
            \Phpcmf\Service::C()->_json(0, dr_lang('此文件不属于图片'));
        }

        $info['file'] = SYS_UPLOAD_PATH.$info['attachment'];
        $info['url'] = dr_get_file_url($info);

        // 修改图片的钩子
        \Phpcmf\Hooks::trigger('image_edit', $info);

        if (IS_POST) {

            // 文件真实地址
            if ($info['remote']) {
                $remote = $this->get_cache('attachment', $info['remote']);
                if (!$remote) {
                    // 远程地址无效
                    \Phpcmf\Service::C()->_json(0, dr_lang('自定义附件（%s）的配置已经不存在', $info['remote']));
                } else {
                    $info['file'] = $remote['value']['path'].$info['attachment'];
                    if (!is_file($info['file'])) {
                        \Phpcmf\Service::C()->_json(0, dr_lang('远程附件无法编辑'));
                    }
                }
            }

            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['w']) {
                \Phpcmf\Service::C()->_json(0, dr_lang('图形宽度不规范'));
            }

            $config = [];
            $config['source_image'] = $info['file'];
            $config['maintain_ratio'] = false;
            $config['width'] = $post['w'];
            $config['height'] = $post['h'];
            $config['x_axis'] = $post['x'];
            $config['y_axis'] = $post['y'];
            $image_lib = \Phpcmf\Service::L('image');
            $image_lib->initialize($config);

            if (!$image_lib->crop()) {
                $err = $image_lib->display_errors();
                \Phpcmf\Service::C()->_json(0, $err ? $err : dr_lang('剪切失败'));
            }


            \Phpcmf\Service::M('attachment')->clear_data($info);
            \Phpcmf\Service::C()->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'data' => $info,
        ]);
        \Phpcmf\Service::V()->display('attachment_image.html');exit;
    }

    /**
     * Ajax调用字段属性表单
     */
    public function field() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $app = dr_safe_replace(\Phpcmf\Service::L('input')->get('app'));
        $type = dr_safe_replace(\Phpcmf\Service::L('input')->get('type'));

        // 关联表
        \Phpcmf\Service::M('field')->relatedid = dr_safe_replace(\Phpcmf\Service::L('input')->get('relatedid'));
        \Phpcmf\Service::M('field')->relatedname = dr_safe_replace(\Phpcmf\Service::L('input')->get('relatedname'));

        // 获取全部字段
        $all = \Phpcmf\Service::M('field')->get_all_field();
        $data = $id ? $all[$id] : null;
        $value = $data ? $data['setting']['option'] : []; // 当前字段属性信息

        $obj = \Phpcmf\Service::L('field')->app($app);
        if (!$obj) {
            exit(json_encode(['option' => '', 'style' => '']));
        }

        list($option, $style) = $obj->option($type, $value, $all);

        exit(json_encode(['option' => $option, 'style' => $style], JSON_UNESCAPED_UNICODE));
    }

    /////////


    // 验证上传权限，并获取上传参数
    public function get_upload_params() {

        $siteid = max(intval($_GET['siteid']), 1);

        // 验证用户权限
        $rt = \Phpcmf\Service::M('Attachment')->check($this->member, $siteid);
        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }

        $fid = (int)\Phpcmf\Service::L('input')->get('fid');
        $field = \Phpcmf\Service::C()->get_cache('table-field', $fid);
        if (!$field) {
            $p = dr_string2array(dr_authcode(\Phpcmf\Service::L('input')->get('p'), 'DECODE'));
            if (!$p) {
                \Phpcmf\Service::C()->_json(0, dr_lang('字段参数有误'));
            }
            return $p;
        }

        return [
            'size' => floatval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'count' => max(1, (int)$field['setting']['option']['count']),
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
            'chunk' => $field['setting']['option']['chunk'] ? 20 * 1024 * 1024 : 0,
        ];
    }

    // 验证权限脚本
    public function check_upload_auth($editor = 0) {

        $error = '';
        if (!IS_API_HTTP && defined('SYS_CSRF') && SYS_CSRF && dr_get_csrf_token() != (string)$_GET['token']) {
            $error = '跨站验证禁止上传文件';
            if (strpos(FC_NOW_URL, 'ueditor') !== false) {
                $error = '本图标功能已禁用，请使用截图软件截图后，再粘贴进编辑器中';
            }
        } elseif (($this->member && $this->member['is_admin']) || IS_ADMIN) {
            // 后台跳过权限
            return;
        } elseif (IS_USE_MEMBER && !\Phpcmf\Service::L('member_auth', 'member')->member_auth('uploadfile', $this->member)) {
            $error = '您的用户组不允许上传文件';
        } elseif (!$this->member && !IS_USE_MEMBER && (!defined('SYS_ATTACHMENT_GUEST') || !SYS_ATTACHMENT_GUEST)) {
            return dr_return_data(0, dr_lang('游客不允许上传文件'));
        } elseif (dr_is_app('mfile') && \Phpcmf\Service::M('mfile', 'mfile')->check_upload($this->uid)) {
            $error = '用户存储空间已满';
        }

        // 挂钩点 验证格式
        $rt2 = \Phpcmf\Hooks::trigger_callback('check_upload_auth', $this->member, $error);
        if ($rt2 && isset($rt2['code'])) {
            $error = $rt2['code'] ? '' : $rt2['msg'];
        }

        if ($error) {
            if ($editor) {
                return dr_lang($error);
            } else {
                \Phpcmf\Service::C()->_json(0, dr_lang($error));
            }
        }

        return;
    }

    /**
     * 文件上传
     */
    public function upload() {

        // 验证上传权限
        $this->check_upload_auth();
        $p = $this->get_upload_params();
        $rt = \Phpcmf\Service::L('upload')->upload_file([
            'path' => '',
            'form_name' => 'file_data',
            'file_exts' => explode(',', strtolower(str_replace('，', ',', $p['exts']))),
            'file_size' => $p['size'] * 1024 * 1024,
            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment'], (int)$p['image_reduce']),
            'watermark' => isset($_GET['is_wm']) && $_GET['is_wm'] ? 1 : 0,
        ]);
        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }
        $data = [];
        if (defined('SYS_ATTACHMENT_CF') && SYS_ATTACHMENT_CF && $rt['data']['md5']) {
            $att = \Phpcmf\Service::M()->table('attachment')
                ->where('uid', $this->uid)
                ->where('filemd5', $rt['data']['md5'])
                ->where('fileext', $rt['data']['ext'])
                ->where('filesize', $rt['data']['size'])
                ->getRow();
            if ($att) {
                $data = dr_return_data($att['id'], 'ok');
                // 删除现有附件
                // 开始删除文件
                $storage = new \Phpcmf\Library\Storage($this);
                $storage->delete(\Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment']), $rt['data']['file']);
                $rt['data'] = \Phpcmf\Service::C()->get_attachment($att['id']);
                if ($rt['data']) {
                    $rt['data']['name'] = $rt['data']['filename'];
                }
            }
        }

        // 附件归档
        if (!$data) {
            $data = \Phpcmf\Service::M('Attachment')->save_data($rt['data']);
            if (!$data['code']) {
                exit(dr_array2string($data));
            }
        }

        // 上传成功
        if (IS_API_HTTP) {
            $data['data'] = [
                'id' => $data['code'],
                'url' => $rt['data']['url'],
            ];
            exit(dr_array2string($data));
        } else {
            $rt['data']['preview'] = dr_file_preview_html($rt['data']['url'], $data['code']);
            exit(dr_array2string(['code' => 1, 'msg' => dr_lang('上传成功'), 'id' => $data['code'], 'info' => $rt['data']]));
        }
    }

    /**
     * 输入一个附件
     */
    public function input_file_url() {

        $p = $this->get_upload_params();

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            if (empty($post['url'])) {
                \Phpcmf\Service::C()->_json(0, dr_lang('文件地址不能为空'));
            }
            $post['url'] = trim($post['url']);
            if ($post['down']) {
                if (strpos($post['url'], 'http') !== 0 ) {
                    \Phpcmf\Service::C()->_json(0, dr_lang('下载文件地址必须是https或者http开头'));
                }
                // 获取扩展名
                $ext = str_replace('.', '', trim(strtolower(strrchr($post['url'], '.')), '.'));
                if (strlen($ext) > 6) {
                    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $i) {
                        if (strpos($post['url'], $i) !== false) {
                            $ext = $i;
                            break;
                        }
                    }
                    if (strlen($ext) > 6) {
                        $ext2 = str_replace('#', '', trim(strtolower(strrchr($post['url'], '#')), '#'));
                        if ($ext2) {
                            $ext = $ext2;
                            $post['url'] = substr($post['url'], 0, strlen($post['url'])-strlen($ext2)-1);
                        }
                    }
                    if (strlen($ext) > 6 || !$ext) {
                        \Phpcmf\Service::C()->_json(0, dr_lang('无法获取到文件扩展名，请在URL后方加入扩展名字符串，例如：#jpg'));
                    }
                }
                // 验证上传权限
                $this->check_upload_auth();
                // 下载远程文件
                $rt = \Phpcmf\Service::L('upload')->down_file([
                    'url' => $post['url'],
                    'file_ext' => $ext,
                    'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment'], (int)$p['image_reduce']),
                ]);
                if (!$rt['code']) {
                    exit(dr_array2string($rt));
                }

                // 附件归档
                $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data']);
                if (!$att['code']) {
                    exit(dr_array2string($att));
                }

                $data = [
                    'id' => $att['code'],
                    'name' => htmlspecialchars((string)$post['name']),
                    'file' => htmlspecialchars($rt['data']['file']),
                    'preview' => dr_file_preview_html($rt['data']['url'], $att['code']),
                    'upload' => '<input type="file" name="file_data"></button>',
                ];
            } else {
                $data = [
                    'id' => $post['url'],
                    'name' => $post['name'] ? htmlspecialchars($post['name']) : '',
                    'file' => htmlspecialchars($post['url']),
                    'preview' => dr_file_preview_html($post['url']),
                    'upload' => '',
                ];
            }

            \Phpcmf\Service::C()->_json(1, dr_lang('上传成功'), $data);
            exit;
        }

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'one' => \Phpcmf\Service::L('input')->get('one'),
            'file' => htmlspecialchars(\Phpcmf\Service::L('input')->get('file')),
            'name' => htmlspecialchars(\Phpcmf\Service::L('input')->get('name')),
            'form' => dr_form_hidden()
        ]);
        \Phpcmf\Service::V()->display('api_upload_url.html');
        exit;
    }

    /**
     * 浏览文件
     */
    public function input_file_list() {

        if (!$this->uid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('游客无法浏览文件'));
        }

        $p = $this->get_upload_params();
        $c = (int)\Phpcmf\Service::L('input')->get('ct'); // 当已有数量
        $ct = max(1, (int)$p['count']); // 可上传数量

        // 判断管理员
        $is_admin = $this->member['is_admin'] && \Phpcmf\Service::M()->table('admin_role_index')->where('uid', $this->uid)->where('roleid', 1)->counts();

        if (IS_AJAX_POST) {
            $p = (int)\Phpcmf\Service::L('input')->post('is_page');
            $ids = \Phpcmf\Service::L('input')->get_post_ids($p ? 'ids'.$p : 'ids0');
            if (!$ids) {
                \Phpcmf\Service::C()->_json(0, dr_lang('没有选择文件'));
            } elseif (dr_count($ids) > $ct - $c) {
                \Phpcmf\Service::C()->_json(0, dr_lang('只能选择%s个文件，当前已选择%s个', $ct - $c, dr_count($ids)));
            }
            $list = [];
            if ($p == 2) {
                // 文件模式
                $ids = \Phpcmf\Service::L('input')->post('ids2');
                foreach ($ids as $t) {
                    $file = trim(str_replace('..', '', $t));
                    $url = dr_get_file($file);
                    $ext = trim(strtolower(strrchr($file, '.')), '.');
                    $list[] = [
                        'id' => $url,
                        'name' => basename($t),
                        'file' => $url,
                        'url' => $url,
                        'exturl' => is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png') ? ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png' : '',
                        'preview' => dr_file_preview_html($file, 0),
                        'upload' => '<input type="file" name="file_data"></button>',
                    ];
                }
            } else {
                // 归档附件
                $db = \Phpcmf\Service::M()->table($p ? 'attachment_data' : 'attachment_unused');
                !$is_admin && $db->where('uid', $this->uid);
                $temp = $db->where_in('id', $ids)->getAll();
                foreach ($temp as $t) {
                    $url = dr_get_file($t['id']);
                    if ($t['remote'])  {
                        $remote = \Phpcmf\Service::C()->get_cache('attachment', $t['remote']);
                        if ($remote && $remote['value']['image']) {
                            $url.= $remote['value']['image'];
                        }
                    }
                    $list[] = [
                        'id' => $t['id'],
                        'name' => $t['filename'],
                        'file' => $t['attachment'],
                        'url' => $url,
                        'exturl' => is_file(ROOTPATH.'static/assets/images/ext/'.$t['fileext'].'.png') ? ROOT_THEME_PATH.'assets/images/ext/'.$t['fileext'].'.png' : '',
                        'preview' => dr_file_preview_html(dr_get_file_url($t), $t['id']),
                        'upload' => '<input type="file" name="file_data"></button>',
                    ];
                }
            }

            $data = [
                'count' => dr_count($ids),
                'result' => $list,
            ];
            \Phpcmf\Service::C()->_json(1, dr_lang('已选择%s个文件', $data['count']), $data);
        }

        $list = [
            'used' => '',
            'unused' => urlencode('uid='.$this->uid),
        ];

        $sfield = [
            'id' => dr_lang('附件ID'),
            'filename' => dr_lang('附件名称'),
            'attachment' => dr_lang('附件路径'),
        ];

        // 搜索附件
        $name = \Phpcmf\Service::L('input')->get('name');
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));
        if ($name && isset($sfield[$name]) && $value) {
            $where = [];
            !$is_admin && $where[] = 'uid = '.$this->uid;
            if ($name == 'id') {
                $where[] = 'id='.intval($value);
            } else {
                $where[] = $name.' LIKE \'%'.$value.'%\'';
            }
            $list['used'] = urlencode(implode(' AND ', $where));
        } else {
            !$is_admin && $list['used'] = urlencode('uid='.$this->uid);
        }

        $exts = dr_safe_replace($p['exts']);
        $unused = SYS_ATTACHMENT_DB ? \Phpcmf\Service::M()->table('attachment_unused')->where(urldecode($list['unused']))
            ->where_in('fileext', explode(',', strtolower(str_replace('，', ',', $exts))))->counts() : [];

        $url = dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&is_iframe=1&m=input_file_list')
            .'&fid='.\Phpcmf\Service::L('input')->get('fid')
            .'&is_wm='.\Phpcmf\Service::L('input')->get('is_wm')
            .'&ct='.$ct
            .'&p='.\Phpcmf\Service::L('input')->get('p');
        $pp = intval($_GET['pp']);
        $pp = $unused ? $pp : ($pp == 2 ? 2 : 1);

        $listurl = dr_web_prefix('index.php?is_iframe=1&s=api&c=file&m=file_list')
            .'&fid='.\Phpcmf\Service::L('input')->get('fid')
            .'&is_wm='.\Phpcmf\Service::L('input')->get('is_wm')
            .'&p='.\Phpcmf\Service::L('input')->get('p');

        // 快捷上传字段参数
        $field = [
            'url' => dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&token='.dr_get_csrf_token())
                .'&siteid='.SITE_ID.'&m=upload&p='.dr_authcode($p, 'ENCODE')
                .'&is_wm='.\Phpcmf\Service::L('input')->get('is_wm')
                .'&fid='.\Phpcmf\Service::L('input')->get('fid'),
            'tips' => dr_lang('上传格式要求：%s，最大允许上传：%s', str_replace(',', '、', (string)$p['exts']), ($p['size']).'MB'),
            'param' => $p,
            'back' => $url.'&pp=0',
        ];

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'pp' => $pp,
            'form' => dr_form_hidden(),
            'list' => $list,
            'page' => intval($_GET['page']),
            'field' => $field,
            'psize' => defined('SYS_ATTACHMENT_PAGESIZE') && SYS_ATTACHMENT_PAGESIZE ? SYS_ATTACHMENT_PAGESIZE : 36,
            'param' => [
                'used' => $unused,
                'name' => $name,
                'value' => $value,
            ],
            'sfield' => $sfield,
            'unused' => $unused,
            'urlrule' => $url.'&page=[page]'.'&pp='.$pp,
            'tab_url' => $url,
            'fileext' => $exts,
            'listurl' => $listurl,
            'search_url' => $url.'&pp='.$pp,
        ]);
        \Phpcmf\Service::V()->display('api_upload_list.html');
    }

    /**
     * 浏览文件
     */
    public function file_list() {

        if (!$this->uid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('游客无法浏览文件'));
        }

        $p = $this->get_upload_params();
        $dir = trim(str_replace('..', '', str_replace(
            [".//.", '\\', ' ', '<', '>', "{", '}', '..', "//"],
            '',
            \Phpcmf\Service::L('input')->get('dir')
        )), '/');
        $exts = explode(',', strtolower(str_replace('，', ',', $p['exts'])));

        $list = $this->_map_file_list($dir, $exts);

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'list' => $list,
        ]);
        \Phpcmf\Service::V()->display('api_upload_filelist.html');
    }

    /**
     * 目录扫描
     */
    private function _map_file_list($dir, $exts) {

        $file_data = $dir_data = [];
        if ($dir) {
            $arr = explode(DIRECTORY_SEPARATOR, $dir);
            array_pop($arr);
            $pdir = $arr ? implode('/', $arr) : '';
            $dir_data = [
                [
                    'file' => 0,
                    'icon' => '<i class="fa fa-folder"></i>',
                    'name' => '<a href="javascript:dr_filelist(\''.$pdir.'\');">..</a>',
                ]
            ];
        }

        $root_path = SYS_UPLOAD_PATH;
        $source_dir	= dr_rp($root_path.($dir ? $dir : trim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), ['//', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR], ['/', DIRECTORY_SEPARATOR]);

        if ($fp = @opendir($source_dir)) {
            while (FALSE !== ($file = readdir($fp))) {
                if (in_array($file, ['.', '..', '.DS_Store', 'config.ini', 'thumb.jpg'])) {
                    continue;
                } elseif (strtolower(strrchr($file, '.')) == '.php') {
                    continue;
                }
                if (is_dir($source_dir.'/'.$file)) {
                    $dir_data[] = [
                        'file' => 0,
                        'icon' => '<i class="fa fa-folder"></i>',
                        'name' => '<a href="javascript:dr_filelist(\''.$dir.'/'.$file.'\');">'.$file.'</a>',
                    ];
                } else {
                    $ext = trim(strtolower(strrchr($file, '.')), '.');
                    if (!in_array($ext, $exts)) {
                        continue;
                    }
                    $url = SYS_UPLOAD_URL.$dir.'/'.$file;
                    $icon = '<i class="fa fa-file-text"></i>';
                    if (in_array($ext, ['jpg', 'gif', 'png', 'jpeg', 'webp'])) {
                        $icon = '<img src="'.$url.'" width=30>';
                    }
                    $file_data[] = [
                        'file' => $dir.'/'.$file,
                        'icon' => $icon,
                        'name' => '<a href="javascript:dr_preview_image(\''.$url.'\');">'.$file.'</a>',
                    ];
                }
            }
            closedir($fp);
        }

        return $file_data ? array_merge($dir_data, $file_data) : $dir_data;
    }


    /**
     * 下载文件
     */
    public function down() {

        // 判断下载权限
        if (IS_USE_MEMBER && !\Phpcmf\Service::L('member_auth', 'member')->member_auth('downfile', $this->member)) {
            if ($this->member && $this->member['is_admin']) {
                // 管理员
            } else {
                \Phpcmf\Service::C()->_msg(0, dr_lang('您的用户组不允许下载附件'));
            }
        }

        // 读取附件信息
        $id = \Phpcmf\Service::L('input')->get('id');
        if (is_numeric($id)) {
            $rt = [
                'id' => $id,
                'name' => dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
            ];
        } elseif (strlen((string)$id) == 32) {
            $rt = \Phpcmf\Service::L('cache')->get_auth_data('down-file-'.$id);
            if (!$rt) {
                \Phpcmf\Service::C()->_msg(0, dr_lang('此附件下载链接已经失效'));
            }
        } else {
            $rt = [
                'id' => dr_safe_replace(urldecode($id)),
                'name' => dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
            ];
        }

        $id = trim($rt['id']);

        // 下载文件钩子
        \Phpcmf\Hooks::trigger('down_file', $id);

        // 执行下载
        if (is_numeric($id)) {
            // 表示附件id
            $info = \Phpcmf\Service::C()->get_attachment($id);
            if (!$info) {
                // 不存在
                \Phpcmf\Service::C()->_msg(0, dr_lang('附件[%s]不存在', $id));
            }
            if ($info['remote'] && !is_file($info['file'])
                && defined('SYS_ATTACHMENT_DOWN_REMOTE') && SYS_ATTACHMENT_DOWN_REMOTE) {
                // 远程附件，文件不存在，下载到本地缓存
                $info['file'] = WRITEPATH.'temp/remote_down_'.md5($id).'.'.$info['fileext'];
                if (!is_file($info['file'])) {
                    $rs = dr_catcher_data($info['url'], 20);
                    if ($rs) {
                        file_put_contents($info['file'], $rs);
                    }
                }
            }
            if (is_file($info['file'])) {
                \Phpcmf\Service::L('file')->down(
                    $info['file'],
                    $info['url'],
                    (isset($rt['name']) && $rt['name'] ? $rt['name'] : $info['filename']).'.'.$info['fileext']
                );
            } else {
                // 其他附件就转向地址
                $this->_redirect_url($info['url']);
            }
            exit;
        } else {
            $info = dr_file($id, 1);
            if (!$info) {
                // 不存在
                \Phpcmf\Service::C()->_msg(0, dr_lang('附件[%s]不存在', $id));
            }
            if (defined('SYS_ATTACHMENT_DOWN_REMOTE') && SYS_ATTACHMENT_DOWN_REMOTE) {
                // 远程附件，文件不存在，下载到本地缓存
                $filename = \Phpcmf\Service::L('upload')->file_name($info);
                $fileext = \Phpcmf\Service::L('upload')->file_ext($info);
                $ifile = WRITEPATH.'temp/remote_down_url_'.md5($id).'.'.$fileext;
                if (!is_file($ifile)) {
                    $rs = dr_catcher_data($info, 20);
                    if ($rs) {
                        file_put_contents($ifile, $rs);
                    }
                }
                if (is_file($ifile)) {
                    \Phpcmf\Service::L('file')->down(
                        $ifile,
                        $info,
                        (isset($rt['name']) && $rt['name'] ? $rt['name'] : $filename).'.'.$fileext
                    );
                    exit;
                }
            }
            $this->_redirect_url($info);
        }

        exit;
    }

    /**
     * 跳转外链提示
     */
    private function _redirect_url($url) {

        if (is_file(\Phpcmf\Service::V()->get_dir().'down_file_msg.html')) {
            \Phpcmf\Service::V()->assign('url', $url);
            \Phpcmf\Service::V()->display('down_file_msg.html');
        } else {
            \Phpcmf\Service::C()->_msg(1, dr_lang('正在为你下载附件'), $url);
        }
    }

    /**
     * 百度编辑器处理接口
     */
    public function ueditor() {
        require ROOTPATH.'api/ueditor/php/controller.php';exit;
    }

    /**
     * base64图片上传
     */
    public function upload_base64_image() {

        // 验证上传权限
        $this->check_upload_auth();
        $p = $this->get_upload_params();

        $content = $_POST['file'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/i', $content, $result)) {
            $ext = strtolower($result[2]);
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                \Phpcmf\Service::C()->_json(0, dr_lang('图片格式不正确'));
            }
            $content = base64_decode(str_replace($result[1], '', $content));
            if (strlen($content) > 30000000) {
                \Phpcmf\Service::C()->_json(0, dr_lang('图片太大了'));
            }

            $rt = \Phpcmf\Service::L('upload')->base64_image([
                'ext' => $ext,
                'content' => $content,
                'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment'], (int)$p['image_reduce']),
            ]);
            if (!$rt['code']) {
                exit(dr_array2string($rt));
            }

            // 附件归档
            $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data']);
            if (!$att['code']) {
                exit(dr_array2string($att));
            }

            $data = [
                'id' => $att['code'],
                'url' => $rt['data']['url'],
            ];
            \Phpcmf\Service::C()->_json(1, dr_lang('上传成功'), $data);
        } else {
            \Phpcmf\Service::C()->_json(0, dr_lang('图片内容不规范'));
        }
    }

    /**
     * 下载远程图片
     */
    public function down_img_list() {

        $vid = XR_L('input')->get('vid');
        if (!$vid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('vid参数不能为空'));
        }

        $rt = \Phpcmf\Service::L('cache')->get_auth_data('down_img_'.$vid);
        if (!$rt) {
            \Phpcmf\Service::C()->_json(0, dr_lang('数据读取失败，请重试'));
        }

        if (IS_POST) {

            $post = XR_L('input')->post('data');
            if (!$post) {
                \Phpcmf\Service::C()->_json(0, dr_lang('还没有下载完毕'));
            }

            $err = $ct = 0;
            foreach ($post as $id => $aid) {
                if ($aid) {
                    $rt['value'] = str_replace($rt['url'][$id], dr_get_file($aid), $rt['value']);
                    $ct++;
                } else {
                    $err++;
                }
            }
            \Phpcmf\Service::L('cache')->del_auth_data('down_img_'.$vid);
            \Phpcmf\Service::C()->_json(1, dr_lang('共下载成功%s张，失败%s张', $ct, $err), $rt['value']);
        }

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'list' => $rt['url'],
            'down_url' => dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=down_img_url&vid='.$vid.'&token='.dr_get_csrf_token()),
        ]);
        \Phpcmf\Service::V()->display('api_down_img.html');exit;
    }

    /**
     * 下载远程图片
     */
    public function down_img_url() {

        // 验证上传权限
        $this->check_upload_auth();

        $vid = XR_L('input')->get('vid');
        if (!$vid) {
            \Phpcmf\Service::C()->_json(0, dr_lang('vid参数不能为空'));
        }

        $rt = \Phpcmf\Service::L('cache')->get_auth_data('down_img_'.$vid);
        if (!$rt) {
            \Phpcmf\Service::C()->_json(0, dr_lang('数据读取失败，请重试'));
        }

        $id = XR_L('input')->get('id');
        if (!isset($rt['ext'][$id]) || !$rt['ext'][$id]) {
            \Phpcmf\Service::C()->_json(0, dr_lang('扩展名识别失败，请重试'));
        }

        // 下载图片
        if (dr_is_app('mfile') && \Phpcmf\Service::M('mfile', 'mfile')->check_upload(\Phpcmf\Service::C()->uid)) {
            //用户存储空间已满
            \Phpcmf\Service::C()->_json(0, dr_lang('用户存储空间已满'));
        } else {
            // 正常下载
            // 下载远程文件
            $rs = \Phpcmf\Service::L('upload')->down_file([
                'url' => html_entity_decode((string)$rt['url'][$id]),
                'timeout' => 5,
                'watermark' => $rt['watermark'],
                'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info(intval($rt['attachment']), $rt['image_reduce']),
                'file_ext' => $rt['ext'][$id],
            ]);
            if ($rs['code']) {
                $att = \Phpcmf\Service::M('Attachment')->save_data($rs['data'], 'ueditor:'.$rt['rid']);
                if ($att['code']) {
                    // 归档成功
                    // 标记附件
                    \Phpcmf\Service::M('Attachment')->save_ueditor_aid($rt['rid'], $att['code']);
                    \Phpcmf\Service::C()->_json(1, $att['code']);
                } else {
                    \Phpcmf\Service::C()->_json(0, $att['msg']);
                }
            } else {
                \Phpcmf\Service::C()->_json(0, $rs['msg']);
            }
        }
    }

    /**
     * 下载远程图片
     */
    public function down_img() {

        // 验证上传权限
        $this->check_upload_auth();

        $value = XR_L('input')->post('value');
        if (!$value) {
            \Phpcmf\Service::C()->_json(0, dr_lang('内容不能为空'));
        }

        // 找远程图片
        $exts = $arrs = [];
        $temp = preg_replace('/<pre(.*)<\/pre>/siU', '', $value);
        $temp = preg_replace('/<code(.*)<\/code>/siU', '', $temp);
        if (preg_match_all("/(src)=([\"|']?)([^ \"'>]+)\\2/i", $temp, $imgs)) {
            $reps = array_unique($imgs[3]);
            usort($reps, function ($a, $b) {
                return dr_strlen($b) - dr_strlen($a);
            });
            foreach ($reps as $img) {
                $arr = parse_url($img);
                $domain = $arr['host'];
                if ($domain) {
                    $sites = \Phpcmf\Service::R(WRITEPATH . 'config/domain_site.php');
                    if (isset($sites[$domain])) {
                        // 过滤站点域名
                    } elseif (strpos(SYS_UPLOAD_URL, $domain) !== false) {
                        // 过滤附件白名单
                    } else {
                        $zj = 0;
                        $remote = \Phpcmf\Service::C()->get_cache('attachment');
                        if ($remote) {
                            foreach ($remote as $t) {
                                if (strpos($t['url'], $domain) !== false) {
                                    $zj = 1;
                                    break;
                                }
                            }
                        }
                        if ($zj == 0) {
                            $ext = XR_L('upload')->get_image_ext($img);
                            if (!$ext) {
                                continue;
                            }
                            $arrs[] = $img;
                            $exts[] = $ext;
                        }
                    }
                }
            }
        }

        if (!$arrs){
            \Phpcmf\Service::C()->_json(0, dr_lang('没有分析出远程图片'));
        }

        $p = $this->get_upload_params();

        // 储存缓存
        $vid = md5($value);
        \Phpcmf\Service::L('cache')->set_auth_data('down_img_'.$vid, [
            'url' => $arrs,
            'ext' => $exts,
            'value' => $value,
            'attachment' => $p['attachment'],
            'image_reduce' => $p['image_reduce'],
            'watermark' => \Phpcmf\Service::L('input')->get('is_wm'),
            'rid' => \Phpcmf\Service::L('input')->get('rid'),
        ]);

        \Phpcmf\Service::C()->_json(1, dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=down_img_list&vid='.$vid));
    }


}