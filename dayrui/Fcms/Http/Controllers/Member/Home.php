<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Home extends \Phpcmf\Common
{

    /**
     * 用户中心首页
     */
    public function index() {

        // 接口请求时返回会员数据
        IS_API_HTTP && $this->_json(1, dr_lang('认证成功'), $this->member);
        
        \Phpcmf\Service::V()->assign([
            'meta_title' => dr_lang('用户中心')
        ]);
        \Phpcmf\Service::V()->display('index.html');
    }

}
