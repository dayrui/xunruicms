<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 附件
class Attachments extends \Phpcmf\Table {

    public function __construct(...$params) {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '已使用的附件' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '未使用的附件' => [\Phpcmf\Service::L('Router')->class.'/unused_index', 'fa fa-folder-o'],
                    'help' => [356],
                ]
            )
        ]);
    }

    // 已使用管理
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
        $remote && $where_list = ' and `remote`='.$remote;

        $this->_init([
            'table' => 'attachment_data',
            'field' => $field,
            'order_by' => 'id desc',
            'where_list' => $where_list,
            'date_field' => 'inputtime',
        ]);

        $this->_List();

        \Phpcmf\Service::V()->assign([
            'field' => $field,
            'table' => 'data',
            'remote' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(),
        ]);
        \Phpcmf\Service::V()->display('attachment_admin.html');
    }

    // 未使用的附件
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
        $remote && $where_list = ' and `remote`='.$remote;

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
            'remote' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(),
        ]);
        \Phpcmf\Service::V()->display('attachment_admin.html');
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
            // 删除未使用附件
            \Phpcmf\Service::M()->table('attachment_unused')->delete($t['id']);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

}
