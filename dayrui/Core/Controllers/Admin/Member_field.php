<?php namespace Phpcmf\Controllers\Admin;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



class Member_field extends \Phpcmf\Common
{

    public function __construct(...$params) {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '字段划分' => ['member_field/index', 'fa fa-cog'],
                    '自定义字段' => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname' => 'member']), 'fa fa-code'],
                ]
            ),
            'uriprefix' => 'member_field'
        ]);
    }

    public function index() {

        $color = $list = $group = [];

        // 字段配置
        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'field')->get()->getRowArray();
        $value = $data ? dr_string2array($data['value']) : [];

        // 注册配置
        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'register_field')->get()->getRowArray();
        $register = $data ? dr_string2array($data['value']) : [];

        // 字段查询
        $field = \Phpcmf\Service::M()->db->table('field')->where('disabled', 0)->where('relatedname', 'member')->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                $f['register'] = in_array($f['id'], $register);
                $f['group'] = $value[$f['id']];
                $list[$f['fieldname']] = $f;
            }
        }

        // 用户组
        $data = \Phpcmf\Service::M()->db->table('member_group')->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            $_color = ['blue', 'red', 'green', 'dark', 'yellow'];
            foreach ($data as $i => $t) {
                $group[$t['id']] = $t;
                $color[$t['id']] = $_color[$i];
            }
        }
        
        \Phpcmf\Service::V()->assign([
            'list' => $list,
            'group' => $group,
            'color' => $color,
        ]);
        \Phpcmf\Service::V()->display('member_field.html');
    }

    public function add() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        !$ids && $this->_json(0, dr_lang('你还没有选择呢'));

        $gid = (int)\Phpcmf\Service::L('input')->post('groupid');
        !$gid && $this->_json(0, dr_lang('你还没有选择用户组'));

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'field')->get()->getRowArray();
        $value = $data ? dr_string2array($data['value']) : [];

        foreach ($ids as $id) {
            $value[$id][$gid] = $gid;
        }

        \Phpcmf\Service::M()->db->table('member_setting')->replace([
            'name' => 'field',
            'value' => dr_array2string($value)
        ]);

        \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
        $this->_json(1, dr_lang('划分成功'));
    }

    public function reg_edit() {

        $fid = (int)\Phpcmf\Service::L('input')->get('id');
        !$fid && $this->_json(0, dr_lang('字段id不存在'));

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'register_field')->get()->getRowArray();
        $register = $data ? dr_string2array($data['value']) : [];

        if ($register[$fid]) {
            unset($register[$fid]);
            $rt = 1;
        } else {
            $register[$fid] = $fid;
            $rt = 0;
        }

        \Phpcmf\Service::M()->db->table('member_setting')->replace([
            'name' => 'register_field',
            'value' => dr_array2string($register)
        ]);

        \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
        $this->_json(1, dr_lang('操作成功'), ['value' => $rt]);
    }

    public function del() {

        $fid = (int)\Phpcmf\Service::L('input')->get('fid');
        !$fid && $this->_json(0, dr_lang('字段id不存在'));

        $gid = (int)\Phpcmf\Service::L('input')->get('gid');
        !$gid && $this->_json(0, dr_lang('用户组id不存在'));

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'field')->get()->getRowArray();
        $value = $data ? dr_string2array($data['value']) : [];
        !$value[$fid][$gid] && $this->_json(0, dr_lang('配置不存在'));

        unset($value[$fid][$gid]);
        
        \Phpcmf\Service::M()->db->table('member_setting')->replace([
            'name' => 'field',
            'value' => dr_array2string($value)
        ]);

        \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
        $this->_json(1, dr_lang('删除成功'));
    }


}
