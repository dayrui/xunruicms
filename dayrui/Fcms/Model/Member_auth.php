<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 用户权限处理
class Member_auth extends \Phpcmf\Model
{
    public $auth;
    public $auth_type;

    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->auth = \Phpcmf\Service::C()->member_cache['auth2'][SITE_ID]; // 当前站点下面的权限值
        $this->auth_type = \Phpcmf\Service::C()->member_cache['auth_type']; // 是否按用户组来取权限模式
    }

    // 当前登录会员的groupid值
    protected function _get_groupid($member) {

        if ($this->auth_type == 1) {
            // 按用户组
            $groupid = $member ? $member['groupid'] : [0]; // 当前登录会员的groupid值
            if (!$groupid) {
                return [0];
            }
        } elseif ($this->auth_type == 2) {
            // 按用户组等级
            $groupid = $member ? $member['authid'] : [0]; // 当前登录会员的authid值
            if (!$groupid) {
                return [0];
            }
        } else {
            // 全局
            $groupid = ['public'];
        }

        return $groupid;
    }

    // 获取用户权限值
    public function member_auth($name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['member'][$name])) {
                $values[] = $this->auth[$gid]['member'][$name];
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取应用插件权限值
    public function app_auth($dir, $name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['app'][$dir][$name])) {
                $values[] = $this->auth[$gid]['app'][$dir][$name];
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取站点权限值
    public function home_auth($name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['home'][$name])) {
                $values[] = $this->auth[$gid]['home'][$name];
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取模块权限值
    public function module_auth($mid, $name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['module'][$mid][$name])) {
                $values[] = $this->auth[$gid]['module'][$mid][$name];
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取模块的栏目权限值
    public function category_auth($module, $catid, $name, $member = []) {

        $mid = $module['dirname'];
        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if ($module['share']) {
                // 共享
                if (isset($this->auth[$gid]['home']['is_category']) && $this->auth[$gid]['home']['is_category']) {
                    // 分开设置
                    $auth = $this->auth[$gid]['share_category'][$catid];
                } else {
                    // 统一设置
                    $auth = $this->auth[$gid]['share_category_public'];
                }
            } else {
                // 独立
                if (isset($this->auth[$gid]['module'][$mid]['is_category']) && $this->auth[$gid]['module'][$mid]['is_category']) {
                    // 分开设置
                    $auth = $this->auth[$gid]['category'][$mid][$catid];
                } else {
                    // 统一设置
                    $auth = $this->auth[$gid]['category_public'][$mid];
                }
            }
            if (isset($auth[$name])) {
                $values[] = $auth[$name];
            } elseif (!$auth) {
                if (in_array($name, ['show'])) {
                    $values[] = 1; // 默认没勾选是启用的权限
                }
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取模块表单权限值
    public function mform_auth($mid, $fid, $name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['module'][$mid]['is_mform']) && $this->auth[$gid]['module'][$mid]['is_mform']) {
                // 分开设置
                $auth = $this->auth[$gid]['mform'][$mid][$fid];
            } else {
                // 统一设置
                $auth = $this->auth[$gid]['mform_public'][$mid];
            }

            if (isset($auth[$name])) {
                $values[] = $auth[$name];
            } elseif (!$auth) {
                if (in_array($name, ['show', 'add', 'code', 'verify'])) {
                    $values[] = 1; // 默认没勾选是启用的权限
                }
            }
        }

        // 取最大值
        return $values ? max($values) : null;
    }

    // 获取网站表单权限值
    public function form_auth($fid, $name, $member = []) {

        $values = [];
        $groupid = $this->_get_groupid($member);

        foreach ($groupid as $gid) {
            if (isset($this->auth[$gid]['home']['is_form']) && $this->auth[$gid]['home']['is_form']) {
                // 分开设置
                $auth = $this->auth[$gid]['form'][$fid];
            } else {
                // 统一设置
                $auth = $this->auth[$gid]['form_public'];
            }

            if (isset($auth[$name])) {
                $values[] = $auth[$name];
            } elseif (!$auth) {
                if (in_array($name, ['show', 'add', 'code', 'verify'])) {
                    $values[] = 1; // 默认没勾选是启用的权限
                }
            }

        }

        // 取最大值
        return $values ? max($values) : null;
    }

}