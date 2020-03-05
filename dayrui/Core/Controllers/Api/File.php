<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
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
            'size' => intval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'count' => max(1, (int)$field['setting']['option']['count']),
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ];
    }

    // 验证权限脚本
    public function _check_upload_auth($editor = 0) {

        $error = '';
        if (!IS_API_HTTP && defined('SYS_CSRF') && SYS_CSRF
            && dr_get_csrf_token() != (string)$_GET['token']) {
            $error = '跨站验证禁止此操作';
        } elseif ($this->member && $this->member['is_admin']) {
            return;
        } elseif (!$this->_member_auth_value($this->member_authid, 'uploadfile')) {
            $error = '您的用户组不允许上传文件';
        } elseif (dr_is_app('mfile') && \Phpcmf\Service::M('mfile', 'mfile')->check_upload($this->uid)) {
            $error = '用户存储空间已满';
        }

        if ($error) {
            if ($editor) {
                return $error;
            } else {
                $this->_json(0, $error);
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
            'file_exts' => @explode(',', $p['exts']),
            'file_size' => (int)$p['size'] * 1024 * 1024,
            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info((int)$p['attachment'], (int)$p['image_reduce']),
        ]);
        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }

        // 附件归档
        $data = \Phpcmf\Service::M('Attachment')->save_data($rt['data']);
        if (!$data['code']) {
            exit(dr_array2string($data));
        }

        // 上传成功
        if (IS_API_HTTP) {
            $data['data'] = [
                'id' => $data['code'],
                'url' => $rt['data']['url'],
            ];
            exit(dr_array2string($data));
        } else {
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
                } elseif (strpos($post['url'], '?') !== false) {
                    $this->_json(0, dr_lang('下载文件地址中不能包含？号'));
                } elseif (strpos($post['url'], '#') !== false) {
                    $this->_json(0, dr_lang('下载文件地址中不能包含#号'));
                }
                // 验证上传权限
                $this->_check_upload_auth();
                // 下载远程文件
                $rt = \Phpcmf\Service::L('upload')->down_file([
                    'url' => $post['url'],
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
                    'name' => htmlspecialchars($post['name']),
                    'file' => htmlspecialchars($rt['data']['file']),
                    'preview' => $rt['data']['preview'],
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

        $p = $this->_get_upload_params();

        $c = (int)\Phpcmf\Service::L('input')->get('ct'); // 当已有数量
        $ct = max(1, (int)$p['count']); // 可上传数量

        if (IS_AJAX_POST) {
            $p = (int)\Phpcmf\Service::L('input')->post('is_page');
            $ids = \Phpcmf\Service::L('input')->get_post_ids($p ? 'ids1' : 'ids0');
            if (!$ids) {
                $this->_json(0, dr_lang('至少要选择一个文件'));
            } elseif (dr_count($ids) > $ct - $c) {
                $this->_json(0, dr_lang('只能选择%s个文件，你已经选择%s个', $ct - $c, dr_count($ids)));
            }
            $list = [];
            $temp = \Phpcmf\Service::M()->table($p ? 'attachment_data' : 'attachment_unused')->where('uid', $this->uid)->where_in('id', $ids)->getAll();
            foreach ($temp as $t) {
                $list[] = [
                    'id' => $t['id'],
                    'name' => $t['filename'],
                    'file' => $t['attachment'],
                    'url' => dr_get_file($t['id']),
                    'preview' => dr_file_preview_html(dr_get_file_url($t)),
                    'upload' => '<input type="file" name="file_data"></button>',
                ];
            }

            $data = [
                'count' => dr_count($ids),
                'result' => $list,
            ];
            $this->_json(1, dr_lang('已选择%s个文件', $data['count']), $data);
        }

        $num = 30;
        $exts = dr_safe_replace($p['exts']);

        $list = [
            'used' => [],
            'unused' => $exts ? \Phpcmf\Service::M()->table('attachment_unused')->where('uid', $this->uid)->where_in('fileext', explode(',', $exts))->order_by('id desc')->getAll($num) : \Phpcmf\Service::M()->table('attachment_unused')->order_by('id desc')->getAll($num),
        ];

        $sfield = [
            'id' => dr_lang('附件ID'),
            'filename' => dr_lang('附件名称'),
        ];

        // 搜索附件
        $name = \Phpcmf\Service::L('input')->get('name');
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));
        if ($name && isset($sfield[$name]) && $value) {
            $db = \Phpcmf\Service::M()->table('attachment_data');
            $exts && $db->where_in('fileext', explode(',', $exts));
            $where = !$this->member['is_admin'] ? 'uid = '.$this->uid : '1';
            if ($name == 'id') {
                $where.= ' AND id='.intval($value);
            } else {
                $where.= ' AND '.$name.' LIKE "%'.$value.'%"';
            }
            $list['used'] = $db->where($where)->order_by('id desc')->getAll($num);
        } else {
            $list['used'] = $exts ? \Phpcmf\Service::M()->table('attachment_data')->where('uid', $this->uid)->where_in('fileext', explode(',', $exts))->order_by('id desc')->getAll($num) : \Phpcmf\Service::M()->table('attachment_data')->order_by('id desc')->getAll($num);
        }

        $search_url = '/index.php?is_ajax=1&s=api&c=file&m=input_file_list&p='.\Phpcmf\Service::L('input')->get('p')
            .'&fid='.\Phpcmf\Service::L('input')->get('fid').'&ct='.\Phpcmf\Service::L('input')->get('ct');

        \Phpcmf\Service::V()->admin();
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'list' => $list,
            'sfield' => $sfield,
            'param' => [
                'name' => $name,
                'value' => $value,
            ],
            'page' => intval($_GET['page']),
            'search_url' => $search_url,
        ]);
        \Phpcmf\Service::V()->display('api_upload_list.html');exit;
    }

    /**
     * 删除文件
     */
    public function file_delete() {

        $rt = \Phpcmf\Service::M('Attachment')->file_delete(
            (int)$this->member['id'],
            (int)\Phpcmf\Service::L('input')->get('id')
        );

        $this->_json($rt['code'], $rt['msg']);
    }

    /**
     * 下载文件
     */
    public function down() {

        // 判断下载权限
        if (!$this->_member_auth_value($this->member_authid, 'downfile')) {
            if ($this->member && $this->member['is_admin']) {
                // 管理员
            } else {
                $this->_msg(0, dr_lang('您的用户组不允许下载附件'));
            }
        }

        // 读取附件信息
        $id = urldecode(\Phpcmf\Service::L('input')->get('id'));
        if (is_numeric($id)) {
            // 表示附件id
            $info = $this->get_attachment($id);
            if (!$info) {
                // 不存在
                $this->_msg(0, dr_lang('附件[%s]不存在', $id));
            } elseif (is_file($info['file'])) {
                set_time_limit(0);  //大文件在读取内容未结束时会被超时处理，导致下载文件不全。
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
                header("Content-Disposition: attachment; filename=".urlencode($info['filename'].'.'.$info['fileext']));

                while (!feof($handle)) {
                    $contents = fread($handle, 4096);
                    echo $contents;
                    @ob_flush();  //把数据从PHP的缓冲中释放出来
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
     * 百度编辑器处理接口
     */
    public function umeditor() {
        require ROOTPATH.'api/umeditor/php/imageUp.php';exit;
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
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
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


}
