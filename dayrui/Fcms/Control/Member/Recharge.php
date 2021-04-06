<?php namespace Phpcmf\Control\Member;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
 **/

class Recharge extends \Phpcmf\Common
{

    /**
     * 在线充值
     */
    public function index() {
        define('FC_PAY', 1);
        $value = max(floatval(\Phpcmf\Service::L('input')->get('value')), floatval($this->member_cache['pay']['min']));
        \Phpcmf\Service::V()->assign([
            'payfield' => dr_payform('recharge', $value ? $value : '', '', '', 1),
        ]);
        \Phpcmf\Service::V()->display('recharge_index.html');
    }

}
