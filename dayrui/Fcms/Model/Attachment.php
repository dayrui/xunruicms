<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 附件模型
 */

class Attachment extends \Phpcmf\Model {

    public function __construct() {
        parent::__construct();
        $this->siteid = SITE_ID;
    }

    // 验证用户权限
    public function check($member, $siteid) {

        if (IS_ADMIN) {
            // 后台不验证
            return dr_return_data(1);
        }

        $this->member = $member;
        $this->siteid = $siteid;

        $error = '';
        if ($member['is_admin']) {
            // 管理员不验证
        } elseif (IS_USE_MEMBER && !\Phpcmf\Service::L('member_auth', 'member')->member_auth('uploadfile', $this->member)) {
            $error = dr_lang('您的用户组不允许上传文件');
        } elseif (!IS_USE_MEMBER && (!defined('SYS_ATTACHMENT_GUEST') || !SYS_ATTACHMENT_GUEST)) {
            $error = dr_lang('游客不允许上传文件');
        }

        // 挂钩点 验证格式
        $rt2 = \Phpcmf\Hooks::trigger_callback('check_upload_auth', $this->member, $error);
        if ($rt2 && isset($rt2['code'])) {
            $error = $rt2['code'] ? '' : $rt2['msg'];
        }

        if ($error) {
            return dr_return_data(0, $error);
        }

        return dr_return_data(1);
    }
    
    // 附件归属
    public function handle($uid, $related, $data) {

        if (!SYS_ATTACHMENT_DB) {
            return;
        }

        $uid = intval($uid);
        $member = dr_member_info($uid);
        // 删除文件
        if ($data['del'] && $related && $related != 'rand') {
            $delete = $this->table('attachment')->where_in('id', $data['del'])->getAll();
            if ($delete) {
                foreach ($delete as $row) {
                    if ($related == $row['related']) {
                        $this->file_delete($member, $row['id']);
                    }
                }
            }
        }

        // 新增归档
        if ($data['add']) {
            foreach ($data['add'] as $id) {
                $id = is_numeric($id) ? intval($id) : 0;
                if (!$id) {
                    continue;
                }
                $t = $this->table('attachment_unused')->get($id);
                if ($t) {
                    // 更新主索引表
                    $this->table('attachment')->update($id, array(
                        'uid' => $t['uid'],
                        'author' => '',
                        'tableid' => 0,
                        'related' => $related
                    ));
                    $this->table('attachment_data')->insert(array(
                        'id' => $t['id'],
                        'uid' => $t['uid'],
                        'remote' => $t['remote'],
                        'author' => '',
                        'related' => $related,
                        'fileext' => $t['fileext'],
                        'filesize' => $t['filesize'],
                        'filename' => $t['filename'],
                        'inputtime' => $t['inputtime'],
                        'attachment' => $t['attachment'],
                        'attachinfo' => $t['attachinfo'],
                    ));
                    // 删除未使用附件
                    $this->table('attachment_unused')->delete($id);
                } else {
                    // 已使用的附件库
                    $t = $this->table('attachment_data')->get($id);
                    if ($t) {
                        if (strpos($t['related'], 'ueditor') !== false) {
                            // 编辑器字段归属
                            $this->table('attachment')->update($id, array(
                                'related' => $related
                            ));
                            $this->table('attachment_data')->update($id, array(
                                'related' => $related
                            ));
                        } elseif ($t['related'] != $related) {
                            // 表示多表复用
                            $this->table('attachment')->update($id, array(
                                'related' => 'rand'
                            ));
                            $this->table('attachment_data')->update($id, array(
                                'related' => 'rand'
                            ));
                        } else {
                            // 表示多表复用
                            $this->table('attachment')->update($id, array(
                                'related' => $related.'-rand'
                            ));
                            $this->table('attachment_data')->update($id, array(
                                'related' => $related.'-rand'
                            ));
                        }
                    }

                }
            }
        }
    }

    // 删除内容关联的文件
    public function cid_delete($member, $cid, $related) {

        if (!$cid) {
            return;
        } elseif (!IS_ADMIN && !$member) {
            return;
        }

        $indexs = $this->table('attachment')->where('related', $related.'-'.$cid)->getAll();
        if ($indexs) {
            foreach ($indexs as $i) {
                $this->file_delete($member, intval($i['id']));
            }
        }
    }

    // 删除用户的全部关联的文件
    public function uid_delete($uid) {

        if (!$uid ) {
            return;
        }

        $indexs = $this->table('attachment')->where('uid', $uid)->getAll();
        if ($indexs) {
            foreach ($indexs as $i) {
                $this->file_delete($uid, intval($i['id']));
            }
        }
    }

    // 删除内容关联的文件
    public function id_delete($member, $ids, $related) {

        if (!$member || !$ids) {
            return;
        }

        foreach ($ids as $id) {
            $indexs = $this->table('attachment')->where('related', $related.'-'.$id)->getAll();
            if ($indexs) {
                foreach ($indexs as $i) {
                    $this->file_delete($member, intval($i['id']));
                }
            }
        }
    }

    // related删除
    public function related_delete($related, $id = 0) {

        if ($id) {
            $indexs = $this->table('attachment')->where('related', $related.'-'.$id)->getAll();
        } else {
            $indexs = $this->table('attachment')->where('related LIKE "'.$related.'-%"')->getAll();
        }

        if ($indexs) {
            foreach ($indexs as $index) {
                $this->_delete_file($index);
            }
        }
    }
    
    // 删除文件判断
    public function file_delete($member, $id) {

        if (!$member) {
            return dr_return_data(0, dr_lang('必须登录之后才能删除文件'));
        } elseif (!$id) {
            return dr_return_data(0, dr_lang('文件id不存在'));
        } elseif (!isset($member['id'])) {
            return dr_return_data(0, dr_lang('必须登录之后才能删除文件'));
        }

        if (is_numeric($member)) {
            $member = $this->table('member')->get($member);
            if (!$member) {
                return dr_return_data(0, dr_lang('必须登录之后才能删除文件'));
            }
        }

        $index = $this->table('attachment')->get($id);
        if (!$index) {
            return dr_return_data(0, dr_lang('文件记录不存在'));
        }
        if (IS_ADMIN) {
            // 后台删除
            if (!\Phpcmf\Service::M('auth')->_is_admin_auth('attachments/del')) {
                return dr_return_data(0, dr_lang('无权限操作'));
            }
        } else {
            // 前端删除自己的
           if ($member['id'] != $index['uid']) {
                return dr_return_data(0, dr_lang('不能删除他人的文件'));
           }
        }

        return $this->_delete_file($index);
    }

    // 开始删除文件
    public function _delete_file($index) {

        $table = $index['related'] ? 'attachment_data' : 'attachment_unused';

        // 获取文件信息
        $info = $this->table($table)->get($index['id']);
        if (!$info) {
            $this->table('attachment')->delete($index['id']);
            return dr_return_data(0, dr_lang('文件数据不存在'));
        }

        $rt = $this->table('attachment')->delete($index['id']);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        // 删除记录
        $this->table($table)->delete($index['id']);

        // 开始删除文件
        $storage = new \Phpcmf\Library\Storage(\Phpcmf\Service::C());
        $storage->delete($this->get_attach_info($info['remote']), $info['attachment']);

        // 删除缓存
        $this->clear_data($index);

        // 删除附件进行记录
        \Phpcmf\Service::L('input')->system_log('删除附件（#'.$index['id'].'-'.dr_now_url().'）'.var_export($info, true));
        if (CI_DEBUG) {
            log_message('debug', '删除附件（#'.$index['id'].'）'.dr_get_file_url($info));
        }

        return dr_return_data(1, dr_lang('删除成功'));
    }

    // 删除附件缓存
    public function clear_data($index) {

        \Phpcmf\Service::L('cache')->del_file('attach-info-'.$index['id'], 'attach');

        // 删除缩略图的缓存
        if (in_array($index['fileext'], ['png', 'jpeg', 'jpg', 'gif'])) {
            list($cache_path, $a, $b, $path) = dr_thumb_path($index['id']);
            dr_dir_delete($cache_path.'/'.$path.'/', true);
        }
    }
    
    // 附件归档存储
    // $handle是否强制随机储存已使用
    public function save_data($data, $related = '', $handle = 0) {

        if (!$this->member) {
            $this->member = [
                'id'=> 0,
                'username' => 'guest',
            ];
        }

        // 按uid散列分表
        $tid = (int)substr((string)$this->member['id'], -1, 1);
        $related = $related ? $related : (SYS_ATTACHMENT_DB ? '' : 'rand');
        $data['name'] = dr_safe_replace($data['name'], ["/", '\\', '..']);

        // 入库索引表
        $rt = $this->table('attachment')->replace([
            'uid' => (int)$this->member['id'],
            'author' => '',
            'siteid' => $this->siteid,
            'related' => $related,
            'tableid' => $tid,
            'download' => 0,
            'filesize' => (int)$data['size'],
            'fileext' => $data['ext'],
            'filemd5' => $data['md5'] ? $data['md5'] : 0,
        ]);
        if (!$rt['code']) {
            // 入库失败
            unlink($data['path']);
            return $rt;
        }
        $id = $rt['code'];
        if ($handle == 0 && (strpos($related, 'ueditor') === 0 ? 0 : SYS_ATTACHMENT_DB)) {
            // 归档存储
            $rt = $this->table('attachment_unused')->replace([
                'id' => $id,
                'uid' => (int)$this->member['id'],
                'author' => '',
                'siteid' => $this->siteid,
                'remote' => $data['remote'],
                'fileext' => $data['ext'],
                'filename' => $data['name'],
                'filesize' => (int)$data['size'],
                'inputtime' => SYS_TIME,
                'attachment' => $data['file'],
                'attachinfo' => dr_safe_url($_SERVER['HTTP_REFERER']),
            ]);
        } else {
            // 随机存储
            $rt = $this->table('attachment_data')->replace([
                'id' => $id,
                'uid' => (int)$this->member['id'],
                'author' => '',
                'related' => $related,
                'filename' => $data['name'],
                'fileext' => $data['ext'],
                'filesize' => $data['size'],
                'remote' => $data['remote'],
                'attachinfo' => dr_array2string($data['info']),
                'attachment' => $data['file'],
                'inputtime' => SYS_TIME,
            ]);
        }
        
        // 入库失败 无主键id 返回msg为准
        if ($rt['msg']) {
            // 删除附件索引
            unlink($data['path']);
            $this->table('attachment')->delete($id);
            return dr_return_data(0, $rt['msg']);
        }

        // 统计附件插件
        if (dr_is_app('mfile')) {
            \Phpcmf\Service::M('mfile', 'mfile')->update_member($this->member, $data['size']);
        }

        return dr_return_data($id, 'ok');
    }

    // 附件存储信息
    public function get_attach_info($id = 0, $image_reduce = 0) {

        // 全局存储
        if ((!$id || $id == 'null') && SYS_ATTACHMENT_SAVE_ID) {
            $id = SYS_ATTACHMENT_SAVE_ID;
        }

        $remote = \Phpcmf\Service::C()->get_cache('attachment');
        if (isset($remote[$id]) && $remote[$id]) {
            $rt = $remote[$id];
            $rt['image_reduce'] = $image_reduce;
            return $rt;
        }

        return [
            'id' => 0,
            'url' => SYS_UPLOAD_URL,
            'type' => 0,
            'image_reduce' => $image_reduce,
            'value' => [
                'path' => SYS_UPLOAD_PATH
            ]
        ];
    }

    // 存储aid到内存中
    public function save_ueditor_aid($rid, $aid) {
        $name = 'ueditor_aid_'.$rid;
        $data = dr_string2array(\Phpcmf\Service::L('cache')->get_auth_data($name));
        if (!$data || !is_array($data)) {
            $data = [$aid];
        } else {
            $data[] = $aid;
        }
        \Phpcmf\Service::L('cache')->set_auth_data($name, $data);
    }

    // 读取aid
    public function get_ueditor_aid($rid, $is_del = false) {
        $name = 'ueditor_aid_'.$rid;
        $data = \Phpcmf\Service::L('cache')->get_auth_data($name);
        $is_del && \Phpcmf\Service::L('cache')->del_auth_data($name);
        return $data;
    }

    // 远程附件缓存
    public function cache($site = SITE_ID) {

        $data = $this->table('attachment_remote')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $t['url'] = trim($t['url'], '/').'/';
                $t['value'] = dr_string2array($t['value']);
                $t['value'] = $t['value'][intval($t['type'])];
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('attachment', $cache);
    }

}