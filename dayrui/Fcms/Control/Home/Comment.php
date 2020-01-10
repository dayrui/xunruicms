<?php namespace Phpcmf\Home;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/




// 用于前端模块评论显示
class Comment extends \Phpcmf\Common
{
    protected $cid;
    protected $index;
    
    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $this->_module_init();
        // 是否启用判断
        if (!$this->module['comment']) {
            if (!\Phpcmf\Service::L('input')->get('callback')) {
                $this->_msg(0, dr_lang('模块【%s】没有启用%s', MOD_DIR, dr_comment_cname($this->module['comment']['cname'])));
            }
            exit('未开启'.dr_comment_cname($this->module['comment']['cname'])); // jsonp请求时不输出
        }
        // 关联内容数据
        $this->cid = intval(\Phpcmf\Service::L('input')->get('id'));
        $this->index = \Phpcmf\Service::L('cache')->get_data('module_'.MOD_DIR.'_show_id_'.$this->cid);
        if (!$this->index) {
            $this->index = $this->content_model->get_data($this->cid);
            if (!$this->index) {
                $this->_msg(0, dr_lang('内容【id#%s】不存在',  $this->cid));
            }
            // 格式化输出自定义字段
            $fields = $this->module['category'][$this->index['catid']]['field'] ? array_merge($this->module['field'], $this->module['category'][$this->index['catid']]['field']) : $this->module['field'];
            $fields['inputtime'] = ['fieldtype' => 'Date'];
            $fields['updatetime'] = ['fieldtype' => 'Date'];
            $this->index = \Phpcmf\Service::L('Field')->app($this->module['dirname'])->format_value($fields, $this->index);
        }
        $this->index['url'] = dr_url_prefix($this->index['url'], MOD_DIR);
        \Phpcmf\Service::V()->module(MOD_DIR);
    }

    // 评论列表
    protected function _Index() {

        // 启用页面缓存
        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            $this->cachePage(SYS_CACHE_PAGE * 3600);
        }

        // 排序模式
        $type = (int)str_replace('#', '', \Phpcmf\Service::L('input')->get('type'));
        $order = 'inputtime desc';
        switch ($type) {
            case 1:
                $order = 'inputtime asc';
                break;
            case 2:
                $order = 'support asc';
                break;
            case 3:
                $order = 'avgsort desc';
                break;
            case 4:
                $order = 'image desc';
                break;
            default:
                $_GET['order'] && $order = strtolower(dr_get_order_string($_GET['order'], $order));
                break;
        }

        // 判断排序字段是否可用
        !in_array(trim(str_replace([' asc', ' desc'], '', $order)), \Phpcmf\Service::L('cache')->get('table-'.SITE_ID, \Phpcmf\Service::M()->dbprefix($this->content_model->mytable.'_comment'))) && $order = 'inputtime desc';

        // 获取评论数据
        $comment = $this->content_model->get_comment_index( $this->cid, $this->index['catid']);
        if (!$comment) {
            exit($this->_msg(0, dr_lang('内容【id#%s】索引数据读取失败',  $this->cid)));
        }

        $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (IS_API_HTTP) {
            $pagesize = (int)$this->module['comment']['pagesize_api'];
        } elseif (\Phpcmf\Service::IS_MOBILE()) {
            $pagesize = (int)$this->module['comment']['pagesize_mobile'];
        } else {
            $pagesize = (int)$this->module['comment']['pagesize'];
        }

        !$pagesize && $pagesize = 10;

        // 查询数据
        list($list, $total) = $this->content_model->get_comment_result($this->cid, $order, $page, $pagesize, $total, $this->module['comment']['field']);

        // ajax动态无刷新调用
        $js = 'dr_ajax_module_comment_'. $this->cid;
        $myfield = \Phpcmf\Service::L('Field')->toform(0, $this->module['comment']['field']);

        \Phpcmf\Service::V()->assign($this->index);
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->comment($this->module, $this->index));
        \Phpcmf\Service::V()->assign([
            'js' => $js,
            'type' => $type,
            'page' => $page,
            'list' => $list,
            'code' => (int)$this->module['comment']['code'],
            'index' => $this->index,
            'catid' => (int)$this->index['catid'],
            'review' => $this->module['comment']['review'],
            'emotion' => dr_comment_emotion(),
            'myfield' => $myfield,
            'comment' => $comment,
            'commnets' => $total,
            'pagesize' => $pagesize,
            'post_url' => '/index.php?s='.MOD_DIR.'&c=comment&id='. $this->cid,
            'page_url' => '/index.php?s='.MOD_DIR.'&c=comment&m=index&id='. $this->cid,
            'is_reply' => (int)$this->module['comment']['reply'],
            'ajax_pages' => $this->_get_pages('javascript:'.$js.'('.$type.', {page})', $total, $pagesize),
        ]);

        if (empty($_GET['callback'])) {
            \Phpcmf\Service::V()->display('comment.html');
        } else {
            ob_start();
            \Phpcmf\Service::V()->display('comment_ajax.html');
            $html = ob_get_contents();
            ob_clean();
            $this->_jsonp(1, $html);
        }
    }
    
    // 评论或者回复
    protected function _Post() {

        if (!IS_POST) {
            $this->_json(0, dr_lang('非法请求'));
        }

        // 挂钩点 评论完成之后
        \Phpcmf\Hooks::trigger('comment_before', $this->index);

        if ($this->module['comment']['my'] && $this->index['uid'] == $this->uid) {
            // 判断不能对自己评论
            $this->_json(0, dr_lang('系统禁止对自己提交'));
        } elseif (!dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID][MOD_DIR]['comment']['add'])) {
            // 判断用户评论权限
            $this->_json(0, dr_lang('您的用户组无权限提交'));
        } elseif ($this->index['is_comment'] == 1) {
            // 判断内容设置的评论权限
            $this->_json(0, dr_lang('该主题禁止提交'));
        } elseif ($this->module['comment']['buy']) {
            // 购买之后才能评论
            $this->_json(0, dr_lang('请到我的订单中操作'));
        } elseif ($this->module['comment']['num'] && \Phpcmf\Service::M()->db->table($this->content_model->mytable.'_comment')->where('cid',  $this->cid)->where('uid', $this->uid)->countAllResults()) {
            // 只允许评论一次
            $this->_json(0, dr_lang('请勿重复提交'));
        }

        $rid = (int)\Phpcmf\Service::L('input')->get('rid');
        if ($rid) {
            // 判断是否回复权限
            // 禁止回复
            if (!$this->module['comment']['reply']) {
                $this->_json(0, dr_lang('系统禁止回复功能'));
            }
            // 查询主题
            $row = $this->content_model->table($this->content_model->mytable.'_comment')->get($rid);
            if (!$row || $this->cid != $row['cid']) {
                $this->_json(0, dr_lang('您回复的主体不存在'));
            } elseif ($row['reply']) {
                $rid = $row['reply']; // 如果他本是就是回帖内容
            } elseif ($this->module['comment']['reply'] == 2 && !(($this->member['uid'] == $row['uid'] && $row['uid'] == $this->index['uid']) || $this->member['is_admin'])) {
                // 判断仅自己
                $this->_json(0, dr_lang('您无权限回复'));
            }
        }

        // 判断会员权限
        $this->member && $this->_member_option(1);

        // 验证操作间隔
        $name = 'module-comment-post-'.md5(dr_now_url().$this->uid);
        if ($this->session()->get($name)) {
            $this->_json(0, dr_lang('您动作太快了'));
        }

        // 获取评论数据
        $comment = $this->content_model->get_comment_index( $this->cid, $this->index['catid']);
        if (!$comment) {
            $this->_json(0, dr_lang('内容【id#%s】索引数据读取失败',  $this->cid));
        }

        // 判断评论内容
        $content = $this->_safe_replace(\Phpcmf\Service::L('input')->post('content', true));
        if (!$content) {
            $this->_json(0, dr_lang('%s内容不能为空', dr_comment_cname($this->module['comment']['cname'])));
        }

        // 开启点评功能时，判断各项点评数，回复不做点评
        $review = [];
        if (!$rid && $this->module['comment']['review'] && $this->module['comment']['review']['option']) {
            foreach ($this->module['comment']['review']['option'] as $i => $name) {
                $review[$i] = (int)$_POST['review'][$i];
                if (!$review[$i]) {
                    $this->_json(0, dr_lang('选项[%s]未评分', $name));
                }
            }
        }

        // 自定义字段
        if (!$rid && $this->module['comment']['field']) {
            \Phpcmf\Service::L('Field')->app(MOD_DIR);
            list($post, $return, $attach) = \Phpcmf\Service::L('Form')->validation(
                \Phpcmf\Service::L('input')->post('data'),
                [],
                $this->module['comment']['field']
            );
            // 输出错误
            if ($return) {
                $this->_json(0, $return['error']);
            }
            $my = $post[1];
        }

        // 评论状态
        $status = dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID][MOD_DIR]['comment']['verify']) ? 0 : 1;

        // 提交评论
        $rt = $this->content_model->insert_comment(
            [
                'index' => $this->index,
                'member' => $this->member,
                'reply_id' => $rid,
                'status' => $status,
            ],
            [
                'review' => $review,
                'content' => htmlspecialchars($content),
            ],
            $my
        );
        // 评论失败
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        // 附件归档
        !$rid && SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
            $this->member['id'],
            \Phpcmf\Service::M()->dbprefix($this->content_model->mytable.'_comment').'-'.$rt['code'],
            $attach
        );

        // 间隔30秒
        $this->session()->setTempdata($name, 1, 30);

        $status ? $this->_json(1, dr_lang('操作成功')) : $this->_json(1, dr_lang('操作成功，等待管理员审核'));
    }
    
    // 操作动作
    protected function _Op() {

        $op = \Phpcmf\Service::L('input')->get('t');
        $id = (int)\Phpcmf\Service::L('input')->get('rid');

        // 查询评论是否存在
        $data = $this->content_model->table($this->content_model->mytable.'_comment')->get($id);
        if (!$data) {
            $this->_json(0, dr_lang('主题不存在'));
        }

        // 获取评论索引数据
        $comment = $this->content_model->get_comment_index( $this->cid, $this->index['catid']);
        if (!$comment) {
            $this->_msg(0, dr_lang('内容【id#%s】索引数据读取失败',  $this->cid));
        }

        // 验证操作间隔
        $name = 'module-comment-op-'.md5(dr_now_url().$op.$id.$this->uid);
        if ($this->session()->get($name)) {
            $this->_json(0, dr_lang('您动作太快了'));
        }

        // 其他操作
        switch ($op) {
            case 'zc':
                $num = (int)$data['support'] + 1;
                $this->content_model->table($this->content_model->mytable.'_comment')->update($id, ['support' => $num]);
                $this->content_model->table($this->content_model->mytable.'_comment_index')->update($comment['id'], ['support' => (int)$comment['support'] + 1]);
                $this->session()->setTempdata($name, 1, 3600);
                $this->_json(1, $num);
                break;
            case 'fd':
                $num = (int)$data['oppose'] + 1;
                $this->content_model->table($this->content_model->mytable.'_comment')->update($id, ['oppose' => $num]);
                $this->content_model->table($this->content_model->mytable.'_comment_index')->update($comment['id'], ['oppose' => (int)$comment['oppose'] + 1]);
                $this->session()->setTempdata($name, 1, 3600);
                $this->_json(1, $num);
                break;

            case 'delete':
                if (!$this->uid) {
                    $this->_json(1, '无权限删除');
                } elseif (!$this->member['adminid']) {
                    $this->_json(1, '当前用户['.$this->member['username'].']无权限删除');
                }
                // 删除
                \Phpcmf\Service::M()->table($this->content_model->mytable.'_comment')->delete($data['id']);
                \Phpcmf\Service::M('member')->delete_admin_notice(MOD_DIR.'/comment_verify/edit:cid/'.$data['cid'].'/id/'.$data['id'], SITE_ID);
                // 重新统计评论数
                $this->content_model->comment_update_total($data);
                $this->content_model->comment_update_review($data);
                $this->_json(1, '删除成功');
                break;

            default:
                $this->_json(1, '未定义的动作('.$op.')');
                break;
        }
    }

    // 格式化评论内容，方便二次开发和重写
    protected function _safe_replace($data) {
        return dr_safe_replace($data);
    }
    
    /**
     * 评论ajax分页 方便二次开发和重写
     */
    protected function _get_pages($url, $total, $pagesize) {

        $config = [];

        $file = 'config/page/'.(\Phpcmf\Service::IS_PC() ? 'pc' : 'mobile').'/ajax.php';
        if (is_file(WEBPATH.$file)) {
            $config = require WEBPATH.$file;
        } elseif (is_file(ROOTPATH.$file)) {
            $config = require ROOTPATH.$file;
        } else {
            $config['next_link'] = '>';
            $config['prev_link'] = '<';
            $config['last_link'] = '>|';
            $config['first_link'] = '|<';
            $config['cur_tag_open'] = '<a class="ds-current">';
            $config['cur_tag_close'] = '</a>';
        }

        $config['base_url'] = $url;
        $config['per_page'] = $pagesize;
        $config['total_rows'] = $total;
        $config['use_page_numbers'] = TRUE;
        $config['query_string_segment'] = 'page';

        return \Phpcmf\Service::L('Page')->initialize($config)->create_links();
    }


}
