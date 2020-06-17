<?php namespace Phpcmf\Controllers\Api;
use function Composer\Autoload\includeFile;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 快捷登录接口
class Oauth extends \Phpcmf\Common
{

    /**
     * 快捷登录
     */
    public function index() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        $type = dr_safe_replace(\Phpcmf\Service::L('input')->get('type'));
        $back = dr_safe_replace(\Phpcmf\Service::L('input')->get('back'));
        $action = dr_safe_replace(\Phpcmf\Service::L('input')->get('action'));

        // 非授权登录时必须验证登录状态
        if ($type != 'login' && !$this->uid) {
            $this->_msg(0, dr_lang('你还没有登录'));
        }

        // 请求参数
        $appid = $this->member_cache['oauth'][$name]['id'];
        $appkey = $this->member_cache['oauth'][$name]['value'];
        $callback_url = OAUTH_URL.'index.php?s=api&c=oauth&m=index&action=callback&name='.$name.'&type='.$type.'&back='.urlencode($back);

        if (is_file(FCPATH.'ThirdParty/OAuth/'.ucfirst($name).'/Run.php')) {
            require FCPATH.'ThirdParty/OAuth/'.ucfirst($name).'/Run.php';
        } else {
            $this->_msg(0, dr_lang('没有找到接入商（%s）执行程序', $name));
        }

    }



}
