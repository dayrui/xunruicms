<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 文件操作
class File extends \Phpcmf\Common
{

    private $siteid;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->siteid = max(intval($_GET['siteid']), 1);
    }

    // 验证上传权限，并获取上传参数
    private function _get_upload_params() {

        // 验证用户权限
        $rt = \Phpcmf\Service::M('Attachment')->check($this->member, $this->siteid);
        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }

        $fid = (int)\Phpcmf\Service::L('input')->get('fid');
        $field = \Phpcmf\Service::C()->get_cache('table-field', $fid);
        if (!$field) {
            $p = dr_string2array(dr_authcode(\Phpcmf\Service::L('input')->get('p'), 'DECODE'));
            if (!$p) {
                $this->_json(0, dr_lang('字段参数有误'));
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
    public function _check_upload_auth($editor = 0) {

        $error = '';
        if (!IS_API_HTTP && defined('SYS_CSRF') && SYS_CSRF && dr_get_csrf_token() != (string)$_GET['token']) {
            $error = '跨站验证禁止上传文件';
            if (strpos(FC_NOW_URL, 'ueditor') !== false) {
                $error = '本图标功能已禁用，请使用截图软件截图后，再粘贴进编辑器中';
            }
        } elseif ($this->member && $this->member['is_admin']) {
            return;
        } elseif (IS_USE_MEMBER && !\Phpcmf\Service::L('member_auth', 'member')->member_auth('uploadfile', $this->member)) {
            $error = '您的用户组不允许上传文件';
        } elseif (!$this->member && !IS_USE_MEMBER && (!defined('SYS_ATTACHMENT_GUEST') || !SYS_ATTACHMENT_GUEST)) {
            return dr_return_data(0, dr_lang('游客不允许上传文件'));
        } elseif (dr_is_app('mfile') && \Phpcmf\Service::M('mfile', 'mfile')->check_upload($this->uid)) {
            $error = '用户存储空间已满';
        }

        if ($error) {
            if ($editor) {
                return dr_lang($error);
            } else {
                $this->_json(0, dr_lang($error));
            }
        }

        return;
    }

    /**
     * 文件上传
     */
    public function upload() {

        // 验证上传权限
        $this->_check_upload_auth();
        $p = $this->_get_upload_params();
        $rt = \Phpcmf\Service::L('upload')->upload_file([
            'path' => '',
            'form_name' => 'file_data',
            'file_exts' => explode(',', strtolower(str_replace('，', ',', $p['exts']))),
            'file_size' => $p['size'] * 1024 * 1024,
            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment'], (int)$p['image_reduce']),
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
                $rt['data'] = $this->get_attachment($att['id']);
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

        $p = $this->_get_upload_params();

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            if (empty($post['url'])) {
                $this->_json(0, dr_lang('文件地址不能为空'));
            }

            if ($post['down']) {
                if (strpos($post['url'], 'http') !== 0 ) {
                    $this->_json(0, dr_lang('下载文件地址必须是https或者http开头'));
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
                        $this->_json(0, dr_lang('无法获取到文件扩展名，请在URL后方加入扩展名字符串，例如：#jpg'));
                    }
                }
                // 验证上传权限
                $this->_check_upload_auth();
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

            $this->_json(1, dr_lang('上传成功'), $data);
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
            $this->_json(0, dr_lang('游客无法浏览文件'));
        }

        $p = $this->_get_upload_params();
        $c = (int)\Phpcmf\Service::L('input')->get('ct'); // 当已有数量
        $ct = max(1, (int)$p['count']); // 可上传数量

        // 判断管理员
        $is_admin = $this->member['is_admin'] && \Phpcmf\Service::M()->table('admin_role_index')->where('uid', $this->uid)->where('roleid', 1)->counts();

        if (IS_AJAX_POST) {
            $p = (int)\Phpcmf\Service::L('input')->post('is_page');
            $ids = \Phpcmf\Service::L('input')->get_post_ids($p ? 'ids'.$p : 'ids0');
            if (!$ids) {
                $this->_json(0, dr_lang('没有选择文件'));
            } elseif (dr_count($ids) > $ct - $c) {
                $this->_json(0, dr_lang('只能选择%s个文件，当前已选择%s个', $ct - $c, dr_count($ids)));
            }
            $list = [];
            if ($p == 2) {
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
                $db = \Phpcmf\Service::M()->table($p ? 'attachment_data' : 'attachment_unused');
                !$is_admin && $db->where('uid', $this->uid);
                $temp = $db->where_in('id', $ids)->getAll();
                foreach ($temp as $t) {
                    $list[] = [
                        'id' => $t['id'],
                        'name' => $t['filename'],
                        'file' => $t['attachment'],
                        'url' => dr_get_file($t['id']),
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
            $this->_json(1, dr_lang('已选择%s个文件', $data['count']), $data);
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
                $where[] = $name.' LIKE "%'.$value.'%"';
            }
            $list['used'] = urlencode(implode(' AND ', $where));
        } else {
            !$is_admin && $list['used'] = urlencode('uid='.$this->uid);
        }

        $exts = dr_safe_replace($p['exts']);
        $unused = \Phpcmf\Service::M()->table('attachment_unused')->where(urldecode($list['unused']))
            ->where_in('fileext', explode(',', strtolower(str_replace('，', ',', $exts))))->counts();

        $url = dr_web_prefix('index.php?is_ajax=1&s=api&c=file&m=input_file_list')
            .'&fid='.\Phpcmf\Service::L('input')->get('fid')
            .'&ct='.$ct
            .'&p='.\Phpcmf\Service::L('input')->get('p');
        $pp = intval($_GET['pp']);
        $pp = $unused ? $pp : ($pp == 2 ? 2 : 1);

        $listurl = dr_web_prefix('index.php?is_ajax=1&s=api&c=file&m=file_list')
            .'&fid='.\Phpcmf\Service::L('input')->get('fid')
            .'&p='.\Phpcmf\Service::L('input')->get('p');

        // 快捷上传字段参数
        $field = [
            'url' => dr_web_prefix('index.php?s=api&c=file&token='.dr_get_csrf_token()).'&siteid='.SITE_ID.'&m=upload&p='.dr_authcode($p, 'ENCODE').'&fid='.\Phpcmf\Service::L('input')->get('fid'),
            'tips' => dr_lang('上传格式要求：%s，最大允许上传：%s', str_replace(',', '、', $p['exts']), ($p['size']).'MB'),
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
            'psize' => 36,
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
            $this->_json(0, dr_lang('游客无法浏览文件'));
        }

        $p = $this->_get_upload_params();
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
                    $file_data[] = [
                        'file' => $dir.'/'.$file,
                        'icon' => '<i class="fa fa-file-text"></i>',
                        'name' => '<a href="javascript:dr_preview_image(\''.SYS_UPLOAD_URL.$dir.'/'.$file.'\');">'.$file.'</a>',
                    ];
                }
            }
            closedir($fp);
        }

        return $file_data ? array_merge($dir_data, $file_data) : $dir_data;
    }

    /**
     * 删除文件
     */
    public function file_delete() {

        $rt = \Phpcmf\Service::M('Attachment')->file_delete(
            $this->member,
            (int)\Phpcmf\Service::L('input')->get('id')
        );

        $this->_json($rt['code'], $rt['msg']);
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
                $this->_msg(0, dr_lang('您的用户组不允许下载附件'));
            }
        }

        // 读取附件信息
        $id = \Phpcmf\Service::L('input')->get('id');
        if (is_numeric($id)) {
            $rt = [
                'id' => $id,
                'name' => dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
            ];
        } else {
            $rt = \Phpcmf\Service::L('cache')->get_auth_data('down-file-'.$id);
            if (!$rt) {
                $this->_msg(0, dr_lang('此附件下载链接已经失效'));
            }
        }

        $id = trim($rt['id']);

        // 下载文件钩子
        \Phpcmf\Hooks::trigger('down_file', $id);

        // 执行下载
        if (is_numeric($id)) {
            // 表示附件id
            $info = $this->get_attachment($id);
            if (!$info) {
                // 不存在
                $this->_msg(0, dr_lang('附件[%s]不存在', $id));
            }

            if (is_file($info['file'])) {
                //大文件在读取内容未结束时会被超时处理，导致下载文件不全。
                set_time_limit(0);
                $handle = fopen($info['file'],"rb");
                if (FALSE === $handle) {
                    $this->_msg(0, dr_lang('文件已经损坏'));
                }

                $filesize = filesize($info['file']);
				if ($filesize > 1024 * 1024 * 50) {
					// 大文件转向
					dr_redirect($info['url']);exit;
				}
				
				header('Content-Type: application/octet-stream');
                header("Accept-Ranges:bytes");
                header("Accept-Length:".$filesize);
                header("Content-Disposition: attachment; filename=".urlencode((isset($rt['name']) && $rt['name'] ? $rt['name'] : $info['filename']).'.'.$info['fileext']));

                while (!feof($handle)) {
                    $contents = fread($handle, 4096);
                    echo $contents;
                    ob_flush();  //把数据从PHP的缓冲中释放出来
                    flush();      //把被释放出来的数据发送到浏览器
                }
				
                fclose($handle);
                exit;
            } else {
                // 其他附件就转向地址
                dr_redirect($info['url']);
            }
        } else {
            $info = dr_file($id);
            if (!$info) {
                // 不存在
                $this->_msg(0, dr_lang('附件[%s]不存在', $id));
            }
            dr_redirect($info);
        }

        exit;
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
        $this->_check_upload_auth();
        $p = $this->_get_upload_params();

        $content = $_POST['file'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/i', $content, $result)) {
            $ext = strtolower($result[2]);
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $this->_json(0, dr_lang('图片格式不正确'));
            }
            $content = base64_decode(str_replace($result[1], '', $content));
            if (strlen($content) > 30000000) {
                $this->_json(0, dr_lang('图片太大了'));
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
            $this->_json(1, dr_lang('上传成功'), $data);
        } else {
            $this->_json(0, dr_lang('图片内容不规范'));
        }
    }

    /**
     * 图片编辑
     */
    public function image_edit() {

        if (!$this->uid) {
            $this->_json(0, dr_lang('无权限修改'));
        } elseif (!$this->member['is_admin']) {
            $this->_json(0, dr_lang('无权限修改'));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, dr_lang('附件id不能为空'));
        }

        $data = \Phpcmf\Service::M()->table('attachment')->get($id);
        if (!$data) {
            $this->_json(0, dr_lang('附件%s不存在', $id));
        }

        if ($data['related']) {
            $info = \Phpcmf\Service::M()->table('attachment_data')->get($id);
        } else {
            $info = \Phpcmf\Service::M()->table('attachment_unused')->get($id);
        }

        if (!dr_is_image($info['fileext'])) {
            $this->_json(0, dr_lang('此文件不属于图片'));
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
                    $this->_json(0, dr_lang('自定义附件（%s）的配置已经不存在', $info['remote']));
                } else {
                    $info['file'] = $remote['value']['path'].$info['attachment'];
                    if (!is_file($info['file'])) {
                        $this->_json(0, dr_lang('远程附件无法编辑'));
                    }
                }
            }

            $post = \Phpcmf\Service::L('input')->post('data');
            if (!$post['w']) {
                $this->_json(0, dr_lang('图形宽度不规范'));
            }
            try {
                $image = \Config\Services::image();
                $image->withFile($info['file']);
                $image->crop($post['w'], $post['h'], $post['x'], $post['y']);
                $image->save($info['file']);
            } catch (CodeIgniter\Images\ImageException $e) {
                $this->_json(0, $e->getMessage());
            }
            \Phpcmf\Service::M('attachment')->clear_data($info);
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'data' => $info,
        ]);
        \Phpcmf\Service::V()->display('attachment_image.html');exit;
    }

    /**
     * 附件改名
     */
    public function name_edit() {

        if (!$this->uid) {
            $this->_json(0, dr_lang('无权限修改'));
        } elseif (!$this->member['is_admin']) {
            $this->_json(0, dr_lang('无权限修改'));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, dr_lang('附件id不能为空'));
        }

        $data = \Phpcmf\Service::M()->table('attachment')->get($id);
        if (!$data) {
            $this->_json(0, dr_lang('附件%s不存在', $id));
        }

        if (IS_POST) {
            $name = \Phpcmf\Service::L('input')->post('name');
            if (!$name) {
                $this->_json(0, dr_lang('附件名称不能为空'));
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
            $this->_json(1, dr_lang('操作成功'));
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

}
