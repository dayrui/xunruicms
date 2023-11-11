<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Site extends \Phpcmf\Model {

    // 设置风格
    public function set_theme($name, $siteid) {
        return \Phpcmf\Service::M('site', 'module')->set_theme($name, $siteid);
    }

    // 设置模板
    public function set_template($name, $siteid) {
        return \Phpcmf\Service::M('site', 'module')->set_template($name, $siteid);
    }

    // 获取网站配置
    public function config($siteid, $name = '', $data = []) {

        !$siteid && $siteid = SITE_ID;
        $site = $this->table('site')->get($siteid);
        if (!$site) {
            return [];
        }

        $site['setting'] = dr_string2array($site['setting']);
        $site['setting']['config']['SITE_NAME'] = $site['name'];
        $site['setting']['config']['SITE_DOMAIN'] = strtolower((string)$site['domain']);

        if ($name && $data) {
            // 更新数据
            if ($data['SITE_NAME']) {
                $site['name'] = $data['SITE_NAME'];
            }
            if ($data['SITE_DOMAIN']) {
                $site['domain'] = $data['SITE_DOMAIN'];
            }
            $site['setting'][$name] = $data;
            $this->table('site')->update($siteid, [
                'name' => $site['name'],
                'domain' => strtolower((string)$site['domain']),
                'setting' => dr_array2string($site['setting']),
            ]);
        }

        return $site['setting'];
    }

    // 存储网站配置
    public function save_config($siteid, $name, $data) {

        !$siteid && $siteid = SITE_ID;
        $site = $this->table('site')->get($siteid);
        if (!$site) {
            return [];
        }

        $site['setting'] = dr_string2array($site['setting']);
        $site['setting']['config']['SITE_NAME'] = $site['name'];
        $site['setting']['config']['SITE_DOMAIN'] = strtolower((string)$site['domain']);

        // 更新数据
        if ($data['SITE_NAME']) {
            $site['name'] = $data['SITE_NAME'];
        }
        if ($data['SITE_DOMAIN']) {
            $site['domain'] = $data['SITE_DOMAIN'];
        }
        $site['setting'][$name] = $data;
        $this->table('site')->update($siteid, [
            'name' => $site['name'],
            'domain' => strtolower((string)$site['domain']),
            'setting' => dr_array2string($site['setting']),
        ]);

        return $site['setting'];
    }

    // 设置网站单个配置
    public function config_value($siteid, $group, $value) {

        !$siteid && $siteid = SITE_ID;
        $site = $this->table('site')->get($siteid);
        if (!$site || !$value) {
            return;
        }

        $site['setting'] = dr_string2array($site['setting']);

        foreach ($value as $n => $v) {
            $site['setting'][$group][$n] = $v;
        }

        $this->table('site')->update($siteid, [
            'setting' => dr_array2string($site['setting']),
        ]);

        return;
    }

    // 新增
    public function create($data) {
        return \Phpcmf\Service::M('site', 'module')->create($data);
    }

    // 变更主域名
    public function edit_domain($value) {
        return \Phpcmf\Service::M('site', 'module')->edit_domain($value);
    }

    // 设置域名
    public function domain($value = []) {
        return \Phpcmf\Service::M('site', 'module')->domain($value);
    }

    // 站点缓存缓存
    public function cache($siteid = null, $data = null, $module = null) {
        return \Phpcmf\Service::M('site', 'module')->cache($siteid, $data, $module);
    }

}