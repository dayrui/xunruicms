<?php namespace Phpcmf\Control\Api;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
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
        if ($this->dirname == 'MOD_DIR') {
            $this->_msg(0, dr_lang('app参数存在问题'));
        } elseif (!$this->dirname || !dr_is_app_dir(($this->dirname))) {
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

        $rt = $this->content_model->update_hits($id);

        $this->_jsonp($rt['code'], $rt['msg'], $rt['data']);
    }

    /**
     * 收藏模块内容
     */
    public function favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装'));
        }

        $rt = \Phpcmf\Service::M('op', 'favorite')->run($this->tablename, $this->dirname);

        $this->_json($rt['code'], $rt['msg'], $rt['data']);
    }

    /**
     * 是否收藏模块内容
     */
    public function is_favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('应用[模块内容收藏]未安装'));
        }

        $rt = \Phpcmf\Service::M('is_favorite', 'favorite')->run($this->tablename, $this->dirname);

        $this->_json($rt['code'], $rt['msg'], $rt['data']);
    }

    /**
     * 模块内容支持与反对
     */
    public function digg() {

        if (!dr_is_app('zan')) {
            $this->_json(0, dr_lang('应用[模块内容点赞]未安装'));
        }

        $rt = \Phpcmf\Service::M('op', 'zan')->run($this->tablename, $this->dirname);

        $this->_json($rt['code'], $rt['msg'], $rt['data']);
    }
}
