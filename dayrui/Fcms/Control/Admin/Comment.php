<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 评论操作类 基于 Ftable
class Comment extends \Phpcmf\Table
{
    public $cid; // 内容id
    public $module; // 模块信息
    public $module_menu; // 是否显示模块菜单
    public $is_verify; // 来自审核

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $this->_module_init(APP_DIR);
        if (!$this->module['comment']) {
            $this->_admin_msg(0, dr_lang('模块【%s】没有启用%s', APP_DIR, dr_comment_cname($this->module['comment']['cname'])));
        }
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'share_comment_';
        // 单独模板命名
        $this->tpl_name = 'comment_content';
        // 模块显示名称
        $this->name = dr_lang('内容模块[%s]%s', APP_DIR, dr_comment_cname($this->module['comment']['cname']));
        // 获取父级内容
         $this->cid = intval(\Phpcmf\Service::L('input')->get('cid'));
         $this->cid && $this->index = $this->content_model->get_row( $this->cid);
        // 自定义字段
        if (!$this->module['setting']['comment_list_field']) {
            $this->module['setting']['comment_list_field'] = [
                'content' => [
                    'use' => 1,
                    'name' => dr_lang('内容'),
                    'func' => 'comment',
                    'width' => 0,
                ],
            ];
        }
        $this->module['comment']['field'] = dr_array22array(
            [
                'content' => [
                    'name' => dr_lang('内容'),
                    'ismain' => 1,
                    'fieldtype' => 'Ueditor',
                    'fieldname' => 'content',
                    'setting' => array(
                        'option' => array(
                            'height' => 250,
                            'mode' => 1,
                            'width' => '100%'
                        )
                    ),
                ]
            ],
            $this->module['comment']['field']
        );
        // 判断是否来自审核控制器
        $this->is_verify = strpos(\Phpcmf\Service::L('Router')->class, '_verify') !== false;
        // 自定义条件
        $where = $this->is_verify ? 'status=0' : 'status=1';
        $this->cid && $where.= ' and cid='. $this->cid;
        $cwhere = $this->content_model->get_admin_list_where();
        $cwhere && $where.= ' AND '. $cwhere;
        $sysfield = ['inputtime', 'inputip', 'author'];
        $this->is_verify && $sysfield[] = 'status';
        // 初始化数据表
        $this->_init([
            'table' => dr_module_table_prefix(APP_DIR).'_comment',
            'field' => $this->module['comment']['field'],
            'sys_field' => $sysfield,
            'date_field' => 'inputtime',
            'show_field' => 'id',
            'list_field' => $this->module['setting']['comment_list_field'],
            'order_by' => 'inputtime desc',
            'where_list' => $where,
        ]);

        // 控制菜单
        $menu = $this->is_verify ? \Phpcmf\Service::M('auth')->_admin_menu([
                '审核管理' => [MOD_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-edit'],
            ]) : \Phpcmf\Service::M('auth')->_module_menu(
                $this->module,
                ' <i class="fa fa-comments"></i>  '.dr_lang('%s管理', dr_comment_cname($this->module['comment']['cname'])),
               \Phpcmf\Service::L('Router')->url(APP_DIR.'/comment/index', ['cid' =>  $this->cid]),
                 $this->cid ?\Phpcmf\Service::L('Router')->url(APP_DIR.'/comment/add', ['cid' =>  $this->cid]) : ''
            ).($this->module['comment']['review'] &&  $this->cid ? '<li> <a href="javascript:dr_iframe_show(\'show\', \''.\Phpcmf\Service::L('Router')->url(APP_DIR.'/comment/review_index', ['cid' =>  $this->cid]).'\', \'30%\', \'50%\');"> <i class="fa fa-thumbs-o-up"></i> '.dr_lang('查看评分').'</a> <i class="fa fa-circle"></i> </li>' : '');

        // 写入模板
        \Phpcmf\Service::V()->assign([
            'menu' => $menu,
            'field' => $this->init['field'],
            'index' => $this->index,
            'is_verify' => $this->is_verify,
            'comment_url' =>\Phpcmf\Service::L('Router')->url(APP_DIR.'/comment/index', ['cid' =>  $this->cid]),
        ]);
    }

    // ========================

    // 后台查看列表
    protected function _Admin_List() {

        list($tpl) = $this->_List(['cid' => $this->cid]);

        \Phpcmf\Service::V()->assign([
            'p' => ['cid' =>  $this->cid],
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台添加内容
    protected function _Admin_Add() {

        if (!$this->cid) {
            $this->_admin_msg(0, dr_lang('缺少cid参数'));
        }

        list($tpl, $data) = $this->_Post();

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'form' => dr_form_hidden(),
            'review' => $this->module['comment']['review'],
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
            'data' => $data,
            'form' => dr_form_hidden(),
            'review' => $this->module['comment']['review'],
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台查看评分信息
    protected function _Admin_Review() {
        \Phpcmf\Service::V()->assign([
            'review' => $this->module['comment']['review'],
            'comment' => $this->content_model->get_comment_index( $this->cid, $this->index['catid']),
        ]);
        \Phpcmf\Service::V()->display('share_comment_review.html');exit;
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
        }

        \Phpcmf\Service::V()->display($tpl);
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

        $rows = \Phpcmf\Service::M()->init($this->init)->where_in('id', $in)->getAll();
        if (!$rows) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        foreach ($rows as $row) {
            !$row['status'] && $this->content_model->verify_comment($row);
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 后台删除内容
    protected function _Admin_Del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            function ($rows) {
                // 对应删除提醒
                foreach ($rows as $t) {
                    \Phpcmf\Service::M('member')->delete_admin_notice(MOD_DIR.'/comment_verify/edit:cid/'.$t['cid'].'/id/'.$t['id'], SITE_ID);
                    // 重新统计评论数
                    $this->content_model->comment_update_total($t);
                    $this->content_model->comment_update_review($t);
                }

            },
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // ===========================

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {

        $row = $this->content_model->table($this->content_model->mytable.'_comment')->get($id);
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
        $data[1]['cid'] =  $this->cid;

        // 添加评论
        if (!$id) {
            $data[1]['status'] = 1;
            $data[1]['cuid'] = $this->index['uid'];
            $data[1]['catid'] = $this->index['catid'];
            $data[1]['orderid'] = 0;
        }

        $review = \Phpcmf\Service::L('input')->post('review');
        if ($review) {
            foreach ($review as $i => $v) {
                $data[1]['sort'.$i] = $v;
            }
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
                // 保存之后
                if ($this->is_verify && $data[1]['status']) {
					//审核通知
                    $this->content_model->verify_comment($old);
                } elseif (!$old) {
					// 后台新增
                    $this->content_model->verify_comment($data[1]);
                } else {
					// 修改数据
                    $this->content_model->comment_update_total($data[1]);
                    $this->content_model->comment_update_review($data[1]);
                }

            }
        );
    }

}
