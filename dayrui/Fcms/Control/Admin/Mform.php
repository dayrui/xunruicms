<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



// 内容模块表单操作类 基于 Ftable
class Mform extends \Phpcmf\Table
{
    public $cid; // 内容id
    public $form; // 表单信息
    public $is_verify; // 判断是否来自审核控制器
    protected $is_add_menu = 1; //允许有添加菜单

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $this->_module_init(APP_DIR);
        // 判断是否来自审核控制器
        $this->is_verify = strpos(\Phpcmf\Service::L('Router')->class, '_verify') !== false;
        // 判断表单是否操作
        $this->form = $this->module['form'][str_replace('_verify', '',\Phpcmf\Service::L('Router')->class)];
        if (!$this->form) {
            $this->_admin_msg(0, dr_lang('模块表单【%s】不存在', str_replace('_verify', '',\Phpcmf\Service::L('Router')->class)));
        }
        // 支持附表存储
        $this->is_data = 1;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'share_mform_';
        // 单独模板命名
        $this->tpl_name = $this->form['table'];
        // 模块显示名称
        $this->name = dr_lang('内容模块[%s]表单（%s）', APP_DIR, $this->form['name']);
        // 获取父级内容
         $this->cid = intval(\Phpcmf\Service::L('input')->get('cid'));
         $this->cid && $this->index = $this->content_model->get_data( $this->cid);
        // 自定义条件
        $where = $this->is_verify ? 'status=0' : 'status=1';
        $this->cid && $where.= ' and cid='. $this->cid;
        $cwhere = $this->content_model->get_admin_list_where();
        $cwhere && $where.= ' AND '. $cwhere;
        $sysfield = ['inputtime', 'inputip', 'displayorder', 'author'];
        $this->is_verify && $sysfield[] = 'status';
        // 初始化数据表
        $this->_init([
            'field' => $this->form['field'],
            'table' => dr_module_table_prefix(APP_DIR).'_form_'.$this->form['table'],
            'sys_field' => $sysfield,
            'date_field' => 'inputtime',
            'show_field' => 'title',
            'list_field' => $this->form['setting']['list_field'],
            'order_by' => 'displayorder DESC,inputtime DESC',
            'where_list' => $where,
        ]);


        $menu = $this->is_verify ? \Phpcmf\Service::M('auth')->_admin_menu([
            '审核管理' => [MOD_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-edit'],
        ]) : \Phpcmf\Service::M('auth')->_module_menu(
            $this->module,
            ' <i class="'.dr_icon($this->form['setting']['icon']).'"></i>  '.dr_lang('%s管理', $this->form['name']),
           \Phpcmf\Service::L('Router')->url(APP_DIR.'/'.$this->form['table'].'/index', ['cid' =>  $this->cid]),
             $this->cid && $this->is_add_menu ? \Phpcmf\Service::L('Router')->url(APP_DIR.'/'.$this->form['table'].'/add', ['cid' =>  $this->cid]) : ''
        );
        
        // 写入模板
        \Phpcmf\Service::V()->assign([
            'menu' => $menu,
            'mform' => $this->form,
            'index' => $this->index,
            'field' => $this->init['field'],
            'form_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/'.$this->form['table'].'/index', ['cid' =>  $this->cid]),
            'is_verify' => $this->is_verify,
        ]);
    }

    // ========================

    // 后台查看列表
    protected function _Admin_List() {

        list($tpl) = $this->_List(['cid' =>  $this->cid]);

        \Phpcmf\Service::V()->assign([
            'p' => ['cid' =>  $this->cid],
        ]);
        return \Phpcmf\Service::V()->display($tpl);
    }

    // 后台添加内容
    protected function _Admin_Add() {

        if (!$this->cid) {
            $this->_admin_msg(0, dr_lang('缺少cid参数'));
        }

        list($tpl) = $this->_Post(0);

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台修改内容
    protected function _Admin_Edit() {

        if (!$this->cid) {
            $this->_admin_msg(0, dr_lang('缺少cid参数'));
        }

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        list($tpl, $data) = $this->_Post($id);

        if (!$data) {
            $this->_admin_msg(0, dr_lang('数据不存在: '.$id));
        } elseif ($this->cid != $data['cid']) {
            $this->_admin_msg(0, dr_lang('cid不匹配'));
        } elseif ($this->is_verify && $data['status']) {
            $this->_admin_msg(0, dr_lang('已经通过了审核'));
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台查看内容
    protected function _Admin_Show() {

        if (!$this->cid) {
            $this->_admin_msg(0, dr_lang('缺少cid参数'));
        }

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        list($tpl, $data) = $this->_Show($id);

        if (!$data) {
            $this->_admin_msg(0, dr_lang('数据不存在: '.$id));
        } elseif ($this->cid != $data['cid']) {
            $this->_admin_msg(0, dr_lang('cid不匹配'));
        } elseif ($this->is_verify && $data['status']) {
            $this->_admin_msg(0, dr_lang('已经通过了审核'));
        }

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台批量保存排序值
    protected function _Admin_Order() {
        $this->_Display_Order(
            intval(\Phpcmf\Service::L('input')->get('id')),
            intval(\Phpcmf\Service::L('input')->get('value'))
        );
    }

    // 后台删除内容
    protected function _Admin_Del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            function ($rows) {
                // 对应删除提醒
                foreach ($rows as $t) {
                    \Phpcmf\Service::M('member')->delete_admin_notice(MOD_DIR.'/'.$this->form['table'].'_verify/edit:cid/'.$t['cid'].'/id/'.$t['id'], SITE_ID);// clear
                    \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_from_'.$this->form['table'].'_show_id_'.$t['id']);
                    // 统计数量
                    $this->content_model->update_form_total($t['cid'], $this->form['table']);
                }
            },
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // 后台批量审核
    protected function _Admin_Status() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        // 格式化
        $in = [];
        foreach ($ids as $i) {
            $i && $in[] = intval($i);
        }
        if (!$in) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        $rows = \Phpcmf\Service::M()->db->table($this->init['table'])->whereIn('id', $in)->get()->getResultArray();
        if (!$rows) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        foreach ($rows as $row) {
            if (!$row['status']){
                $this->_verify($row);
                $this->content_model->update_form_total($row['cid'], $this->form['table']);
            }

        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // ===========================

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {

        $row = $this->content_model->get_form_row($id, $this->form['table']);
        if (!$row) {
            return [];
        }

        return $row;
    }

    // 格式化保存数据 保存之前
    protected function _Format_Data($id, $data, $old) {

        // 验证父数据
        if (!$this->index) {
            $this->_json(0, dr_lang('关联内容不存在'));
        }

        // 默认数据
        $data[0]['uid'] = (int)$data[1]['uid'];
        $data[1]['cid'] = $data[0]['cid'] =  $this->cid;
        $data[1]['catid'] = $data[0]['catid'] = (int)$this->index['catid'];

        // 后台添加时默认通过
        if (!$id) {
            // !$this->is_verify &&
            $data[1]['status'] = 1;
            $data[1]['tableid'] = 0;
        }

        return $data;
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        return parent::_Save($id, $data, $old, null,
            function ($id, $data, $old) {
                $data[1]['status'] && $this->is_verify && $this->_verify([
                    'id' => (int)$data[1]['id'],
                    'uid' => (int)$data[1]['uid'],
                    'status' => 0,
                ]);
                // 保存之后的更新total字段
                $this->content_model->update_form_total( $this->cid, $this->form['table']);
                \Phpcmf\Service::M('member')->todo_admin_notice(MOD_DIR.'/'.$this->form['table'].'_verify/edit:cid/'.$old['cid'].'/id/'.$old['id'], SITE_ID);
                // clear
                \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_from_'.$this->form['table'].'_show_id_'.$id);
            }
        );
    }

    // 审核表单
    protected function _verify($row) {

        if ($row['status']) {
            return;
        }

        // 增减金币
        $score = $this->_member_value(\Phpcmf\Service::M('member')->authid($row['uid']), $this->member_cache['auth_module'][SITE_ID][MOD_DIR]['form'][$this->form['table']]['score']);
        $score && \Phpcmf\Service::M('member')->add_score($row['uid'], $score, dr_lang('%s: %s发布', MODULE_NAME, $this->form['name']), $row['curl']);

        // 增减经验
        $exp = $this->_member_value(\Phpcmf\Service::M('member')->authid($row['uid']), $this->member_cache['auth_module'][SITE_ID][MOD_DIR]['form'][$this->form['table']]['exp']);
        $exp && \Phpcmf\Service::M('member')->add_experience($row['uid'], $exp, dr_lang('%s: %s发布', MODULE_NAME, $this->form['name']), $row['curl']);

        \Phpcmf\Service::M()->db->table($this->init['table'])->where('id', $row['id'])->update(['status' => 1]);

        \Phpcmf\Service::L('Notice')->send_notice('module_form_verify_1', $row);
        
    }
}
