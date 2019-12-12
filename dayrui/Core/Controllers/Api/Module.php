<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



// 模块ajax操作接口
class Module extends \Phpcmf\Common
{
    private $siteid;
    private $dirname;
    private $tablename;

    protected $content_model;

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $this->siteid = (int)\Phpcmf\Service::L('input')->get('siteid');
        !$this->siteid && $this->siteid = SITE_ID;
        $this->dirname = dr_safe_replace(\Phpcmf\Service::L('input')->get('app'));
        if (!$this->dirname || !dr_is_app_dir(($this->dirname))) {
            $this->_msg(0, dr_lang('模块目录[%s]不存在', $this->dirname));
            exit;
        }
        $this->tablename = $this->siteid.'_'.$this->dirname;
        $this->content_model = \Phpcmf\Service::M('Content', $this->dirname);
        $this->_module_init($this->dirname, $this->siteid);
    }

    public function index() {
        exit('module api');
    }

    /**
     * 阅读数统计
     */
    public function hits() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_jsonp(0, dr_lang('阅读统计: id参数不完整'));
        }

        $data = \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->select('hits,updatetime')->get()->getRowArray();
        if (!$data) {
            $this->_jsonp(0, dr_lang('阅读统计: 模块内容不存在'));
        }

        $plus = defined('IS_HITS_PLUS') && is_numeric(IS_HITS_PLUS) ? intval(IS_HITS_PLUS) : 1;
        if (!$plus) {
            // 增量为0时原样输出
            $this->_jsonp(1, $data['hits']);exit;
        }

        $name = 'module-'.md5($this->tablename).'-'.$id;
        if (\Phpcmf\Service::L('input')->get_cookie($name)) {
            $this->_jsonp(1, $data['hits']);
        }

        $hits = (int)$data['hits'] + $plus;

        // 更新主表
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set('hits', $hits)->update();

        // 获取统计数据
        $total = \Phpcmf\Service::M()->db->table($this->tablename.'_hits')->where('id', $id)->get()->getRowArray();
        if (!$total) {
            $total['day_hits'] = $total['week_hits'] = $total['month_hits'] = $total['year_hits'] = $plus;
        }

        // 更新到统计表
        \Phpcmf\Service::M()->table($this->tablename.'_hits')->replace([
            'id' => $id,
            'hits' => $hits,
            'day_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', SYS_TIME)) ? $hits : $plus,
            'week_hits' => (date('YW', $data['updatetime']) == date('YW', SYS_TIME)) ? ($total['week_hits'] + $plus) : $plus,
            'month_hits' => (date('Ym', $data['updatetime']) == date('Ym', SYS_TIME)) ? ($total['month_hits'] + $plus) : $plus,
            'year_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', strtotime('-1 day'))) ? $hits : $total['year_hits'],
        ]);

        //session()->save($name, $id, 300); 考虑并发性能还是不用session了
        \Phpcmf\Service::L('input')->set_cookie($name, $id, 300);

        // 输出
        $this->_jsonp(1, $hits);
    }

    /**
     * 收藏模块内容
     */
    public function favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装'));
        } elseif (!in_array('favorites', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$this->uid) {
            $this->_json(0, dr_lang('还没有登录'));
        } elseif (!$id) {
            $this->_json(0, dr_lang('id参数不完整'));
        }

        $data = \Phpcmf\Service::M()->db->table($this->tablename.'_index')->where('id', $id)->countAllResults();
        if (!$data) {
            $this->_json(0, dr_lang('模块内容不存在'));
        }

        $favorite = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->where('uid', $this->uid)->get()->getRowArray();
        if ($favorite) {
            // 已经收藏了,我们就删除它
            $msg = dr_lang('取消收藏');
            \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('id', intval($favorite['id']))->delete();
        } else {
            $msg = dr_lang('收藏成功');
            \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->insert(array(
                'cid' => $id,
                'uid' => $this->uid
            ));
        }

        // 更新数量
        $c = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->countAllResults();
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set('favorites', $c)->update();
        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

        // 返回结果
        $this->_json(1, $msg, $c);
    }

    /**
     * 是否收藏模块内容
     */
    public function is_favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装'));
        } elseif (!in_array('favorites', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');

        if (!$this->uid) {
            $this->_json(0, dr_lang('还没有登录'));
        } elseif (!$id) {
            $this->_json(0, dr_lang('id参数不完整'));
        }

        $favorite = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->where('uid', $this->uid)->countAllResults();
        if ($favorite) {
            $this->_json(1, '已经收藏');
        } else {
            $this->_json(0, '没有收藏');
        }
    }

    /**
     * 模块内容支持与反对
     */
    public function digg() {

        if (!dr_is_app('zan')) {
            $this->_json(0, dr_lang('应用[模块内容点赞]未安装'));
        } elseif (!in_array('support', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('应用[模块内容点赞]未安装到本模块[%s]', $this->dirname));
        } elseif (!in_array('oppose', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('应用[模块内容点赞]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, dr_lang('id参数不完整'));
        }

        $value = (int)\Phpcmf\Service::L('input')->get('value');
        $data = \Phpcmf\Service::M()->db->table($this->tablename.'_index')->where('id', $id)->countAllResults();
        if (!$data) {
            $this->_json(0, dr_lang('模块内容不存在'));
        }

        $field = $value ? 'support' : 'oppose';
        $table = $this->tablename.'_'.$field;
        if (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $this->_json(0, dr_lang('应用[模块内容点赞]未安装到本模块[%s]', $this->dirname));
        }

        $agent = md5(\Phpcmf\Service::L('input')->get_user_agent().\Phpcmf\Service::L('input')->ip_address());
        if (!$this->uid) {
            $result = \Phpcmf\Service::M()->db->table($table)->where('cid', $id)->where('uid', $this->uid)->where('agent', $agent)->get()->getRowArray();
        } else {
            $result = \Phpcmf\Service::M()->db->table($table)->where('cid', $id)->where('uid', $this->uid)->get()->getRowArray();
        }


        if ($result) {
            // 已经操作了,我们就删除它
            \Phpcmf\Service::M()->db->table($table)->where('id', intval($result['id']))->delete();
            $msg = dr_lang('操作取消');
        } else {
            \Phpcmf\Service::M()->db->table($table)->insert(array(
                'cid' => $id,
                'uid' => $this->uid,
                'agent' => $agent,
            ));
            $msg = dr_lang('操作成功');
        }

        // 更新数量
        $c = \Phpcmf\Service::M()->db->table($table)->where('cid', $id)->countAllResults();
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set($field, $c)->update();
        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

        // 返回结果
        $this->_json(1, $msg, $c);
    }



}
