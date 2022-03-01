<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 用户权限处理
class Member_auth extends \Phpcmf\Model {

    // 获取用户权限值
    public function member_auth($name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->member_auth($name, $member) : 1;
    }

    // 获取应用插件权限值
    public function app_auth($dir, $name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->app_auth($dir, $name, $member) : 0;
    }

    // 获取站点权限值
    public function home_auth($name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->home_auth($name, $member) : 0;
    }

    // 获取模块权限值
    public function module_auth($mid, $name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->module_auth($mid, $name, $member) : 0;
    }

    // 获取模块的栏目权限值
    public function category_auth($module, $catid, $name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->category_auth($module, $catid, $name, $member) : 1;
    }

    // 获取模块表单权限值
    public function mform_auth($mid, $fid, $name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->mform_auth($mid, $fid, $name, $member) : 1;
    }

    // 获取网站表单权限值
    public function form_auth($fid, $name, $member = []) {
        return IS_USE_MEMBER ? \Phpcmf\Service::L('member_auth', 'member')->form_auth($fid, $name, $member) : 1;
    }

}