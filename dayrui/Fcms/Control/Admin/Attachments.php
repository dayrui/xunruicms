<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 附件
class Attachments extends \Phpcmf\Table {

    public function __construct() {
        parent::__construct();
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '已归档的附件' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '未归档的附件' => [\Phpcmf\Service::L('Router')->class.'/unused_index', 'fa fa-folder-o'],
                    '变更储存策略' => ['add:'.\Phpcmf\Service::L('Router')->class.'/remote_edit', 'fa fa-edit', '500px', '400px'],
                    'help' => [356],
                ]
            )
        ]);
    }

    // 已归档管理
    public function index() {

        $field = [
            'uid' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'uid',
                'name' => '账号',
            ],
            'related' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'related',
                'name' => dr_lang('附件归属'),
            ],
            'fileext' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'fileext',
                'name' => dr_lang('扩展名'),
            ],
            'filename' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'filename',
                'name' => dr_lang('附件名称'),
            ],
            'attachment' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'attachment',
                'name' => dr_lang('附件路径'),
            ],
        ];

        $remote = (int)$_GET['remote'];
        $where_list = 'id in (select id from '.\Phpcmf\Service::M()->dbprefix('attachment').' where siteid='.SITE_ID.')';
        $remote && $where_list.= ' and `remote`='.$remote;

        $this->_init([
            'table' => 'attachment_data',
            'field' => $field,
            'order_by' => 'id desc',
            'where_list' => $where_list,
            'date_field' => 'inputtime',
        ]);

        $this->_List();

        // 快捷上传字段参数
        $p = [
            'size' => 9999,
            'exts' => '*',
            'count' => 20,
            'attachment' => $remote,
            'image_reduce' => 0,
            'chunk' => 10 * 1024 * 1024,
        ];
        $upload = [
            'url' => dr_web_prefix(SELF.'?c=api&token='.dr_get_csrf_token())
                .'&siteid='.SITE_ID.'&m=upload&p='.dr_authcode($p, 'ENCODE'),
            'param' => $p,
            'back' => dr_now_url(),
        ];

        \Phpcmf\Service::V()->assign([
            'field' => $field,
            'table' => 'data',
            'remote' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(0, 'id'),
            'upload' => $upload,
        ]);
        \Phpcmf\Service::V()->display('attachment_admin.html');
    }

    // 未归档的附件
    public function unused_index() {

        $field = [
            'author' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'author',
                'name' => dr_lang('账号'),
            ],
            'fileext' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'fileext',
                'name' => dr_lang('扩展名'),
            ],
            'uid' => [
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'uid',
                'name' => 'uid',
            ],
        ];

        $remote = (int)$_GET['remote'];
        $where_list = 'siteid='.SITE_ID;
        $remote && $where_list.= ' and `remote`='.$remote;

        $this->_init([
            'table' => 'attachment_unused',
            'field' => $field,
            'order_by' => 'id desc',
            'where_list' => $where_list,
            'date_field' => 'inputtime',
        ]);

        $this->_List();

        \Phpcmf\Service::V()->assign([
            'field' => $field,
            'table' => 'unused',
            'remote' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(0, 'id'),
        ]);
        \Phpcmf\Service::V()->display('attachment_admin.html');
    }

    public function remote_edit() {

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            if ($post['o'] == $post['n']) {
                $this->_json(0, dr_lang('储存策略不能相同'));
            }

            \Phpcmf\Service::M()->table('attachment_unused')->where('remote', intval($post['o']))->update(0, [
                'remote' => intval($post['n'])
            ]);

            \Phpcmf\Service::M()->table('attachment_data')->where('remote', intval($post['o']))->update(0, [
                'remote' => intval($post['n'])
            ]);

            dr_dir_delete(WRITEPATH.'attach');
            dr_mkdirs(WRITEPATH.'attach');
            
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'remote' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(0, 'id'),
        ]);
        \Phpcmf\Service::V()->display('attachment_remote_edit.html');
    }

    public function del() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $table = \Phpcmf\Service::L('input')->post('table');
        $table != 'data' && $table = 'unused';

        $data = \Phpcmf\Service::M()->db->table('attachment_'.$table)->whereIn('id', $ids)->get()->getResultArray();
        if (!$data) {
            $this->_json(0, dr_lang('所选附件不存在'));
        }

        foreach ($data as $t) {
            $rt = \Phpcmf\Service::M('attachment')->_delete_file($t);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 强制归档
    public function edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $data = \Phpcmf\Service::M()->db->table('attachment_unused')->whereIn('id', $ids)->get()->getResultArray();
        if (!$data) {
            $this->_json(0, dr_lang('所选附件不存在'));
        }

        $related = 'Save';
        foreach ($data as $t) {
            // 更新主索引表
            \Phpcmf\Service::M()->table('attachment')->update($t['id'], array(
                'related' => $related
            ));
            \Phpcmf\Service::M()->table('attachment_data')->insert(array(
                'id' => $t['id'],
                'uid' => $t['uid'],
                'remote' => $t['remote'],
                'author' => $t['author'],
                'related' => $related,
                'fileext' => $t['fileext'],
                'filesize' => $t['filesize'],
                'filename' => $t['filename'],
                'inputtime' => $t['inputtime'],
                'attachment' => $t['attachment'],
                'attachinfo' => '',
            ));
            // 删除未归档附件
            \Phpcmf\Service::M()->table('attachment_unused')->delete($t['id']);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 变更储存策略
    public function type_edit() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $rid = intval(\Phpcmf\Service::L('input')->post('remote'));
        if ($rid < 0) {
            $this->_json(0, dr_lang('你还没有选择储存策略'));
        }

        $table = \Phpcmf\Service::L('input')->post('table');
        $table != 'data' && $table = 'unused';

        \Phpcmf\Service::M()->table('attachment_'.$table)->where_in('id', $ids)->update(0, [
            'remote' => $rid
        ]);

        dr_dir_delete(WRITEPATH.'attach');
        dr_mkdirs(WRITEPATH.'attach');

        $this->_json(1, dr_lang('操作成功'));
    }

    // 重新上传附件
    public function file_edit() {

        $id = \Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $data = $this->get_attachment($id, true);
        if (!$data) {
            $this->_json(0, dr_lang('附件信息不存在'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
        ]);
        \Phpcmf\Service::V()->display('attachment_upload.html');
    }

    // 重新上传附件
    public function upload_edit() {

        $id = \Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $data = $this->get_attachment($id, true);
        if (!$data) {
            $this->_json(0, dr_lang('附件信息不存在'));
        }

        $rt = \Phpcmf\Service::L('upload')->upload_file([
            'save_name' => str_replace('.'.$data['fileext'], '', basename($data['attachment'])),
            'path' => dirname($data['attachment']),
            'form_name' => 'file_data',
            'file_exts' => [$data['fileext']],
            'file_size' => 1000 * 1024 * 1024,
            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info($data['remote']),
        ]);
        if (!$rt['code']) {
            exit(dr_array2string($rt));
        }
        \Phpcmf\Service::M()->table('attachment_data')->update($id, ['filesize' => $rt['data']['size']]);
        \Phpcmf\Service::M()->table('attachment_unused')->update($id, ['filesize' => $rt['data']['size']]);
        $this->_json(1, dr_lang('上传成功'), $rt['data']);
    }
}
