<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 快捷登录接口
class Oauth extends \Phpcmf\Common
{

    /**
     * 快捷登录
     */
    public function index() {

        $name = dr_safe_filename(\Phpcmf\Service::L('input')->get('name'));
        $type = dr_safe_replace(\Phpcmf\Service::L('input')->get('type'));
        $back = dr_safe_replace(\Phpcmf\Service::L('input')->get('back'));
        $action = dr_safe_replace(\Phpcmf\Service::L('input')->get('action'));

        // 非授权登录时必须验证登录状态
        if ($type != 'login' && !$this->uid) {
            $this->_msg(0, dr_lang('你还没有登录'));
        } elseif (!$name) {
            $this->_msg(0, dr_lang('未知接入商'));
        }

        // 请求参数
        $appid = $this->member_cache['oauth'][$name]['id'];
        $appkey = $this->member_cache['oauth'][$name]['value'];
        $callback_url = OAUTH_URL.'index.php?s=api&c=oauth&m=index&action=callback&name='.$name.'&type='.$type;
        if ($back) {
            $callback_url.= '&back='.urlencode(dr_redirect_safe_check($back));
        }

        $file = FCPATH.'ThirdParty/OAuth/'.ucfirst($name).'/Run.php';
        if (is_file($file)) {
            require $file;
        } else {
            $this->_msg(0, IS_DEV ? dr_lang('没有找到接入商（%s）执行程序', $file) : dr_lang('没有找到接入商的执行程序'));
        }
    }

}
