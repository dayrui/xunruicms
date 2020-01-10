<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Site extends \Phpcmf\Model
{
	
	// 设置风格
	public function set_theme($name, $siteid) {
		
		$site = $this->table('site')->get($siteid);
        if (!$site) {
            return [];
        }

        $site['setting'] = dr_string2array($site['setting']);
        $site['setting']['config']['SITE_THEME'] = $name;
		
		$this->table('site')->update($siteid, [
			'setting' => dr_array2string($site['setting']),
		]);
	}
	
	// 设置模板
	public function set_template($name, $siteid) {
		
		$site = $this->table('site')->get($siteid);
        if (!$site) {
            return [];
        }

        $site['setting'] = dr_string2array($site['setting']);
        $site['setting']['config']['SITE_TEMPLATE'] = $name;
		
		$this->table('site')->update($siteid, [
			'setting' => dr_array2string($site['setting']),
		]);
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
        $site['setting']['config']['SITE_DOMAIN'] = $site['domain'];

        if ($name && $data) {
            // 更新数据
            $data['SITE_NAME'] && $site['name'] = $data['SITE_NAME'];
            $data['SITE_DOMAIN'] && $site['domain'] = $data['SITE_DOMAIN'];
            $site['setting'][$name] = $data;
            $this->table('site')->update($siteid, [
                'name' => $site['name'],
                'domain' => $site['domain'],
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
        $site['setting']['config']['SITE_DOMAIN'] = $site['domain'];

        // 更新数据
        $data['SITE_NAME'] && $site['name'] = $data['SITE_NAME'];
        $data['SITE_DOMAIN'] && $site['domain'] = $data['SITE_DOMAIN'];
        $site['setting'][$name] = $data;
        $this->table('site')->update($siteid, [
            'name' => $site['name'],
            'domain' => $site['domain'],
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

        $this->db->table('site')->replace([
            'name' => $data['name'],
            'domain' => (string)$data['domain'],
            'setting' => dr_array2string([
                'webpath' => $data['webpath'],
            ]),
            'disabled' => 0,
        ]);

        $siteid = $this->db->insertID();

        \Phpcmf\Service::M('Table')->create_site($siteid);

        if ($siteid == 1) {
            return; // 安装不执行后面操作
        }

        if (is_file(MYPATH.'Config/Install_site.sql')) {
            $s = file_get_contents(MYPATH.'Config/Install_site.sql');
            $sql = str_replace('{dbprefix}', $this->prefix.$siteid.'_', $s);
            $this->query_all($sql);
        }

        // 创建
        if (is_file(MYPATH.'Config/Install.php')) {
            require MYPATH.'Config/Install.php';
        }

        // 应用插件
        $local = dr_dir_map(APPSPATH, 1);
        foreach ($local as $dir) {
            if (is_file(APPSPATH.$dir.'/install.lock')
                && is_file(APPSPATH.$dir.'/Config/App.php')
                && is_file(APPSPATH.$dir.'/Config/Install_site.sql')) {
                $cfg = require APPSPATH.$dir.'/Config/App.php';
                if ($cfg['type'] != 'module') {
                    // 这是插件
                    $sql = file_get_contents(APPSPATH.$dir.'/Config/Install_site.sql');
                    $this->query_all(str_replace('{dbprefix}',  $this->dbprefix($siteid.'_'), $sql));
                } else {
                    // 这是模块

                }
            }

        }

        \Phpcmf\Service::M('cache')->update_webpath('Web', $data['webpath'], [
            'SITE_ID' => $siteid
        ]);
    }

    // 变更主域名
    public function edit_domain($value) {

        $site = $this->config(1);
        $site['config']['SITE_DOMAIN'] = $value;
        $this->db->table('site')->where('id', 1)->update([
            'domain' => $value,
            'setting' => dr_array2string($site),
        ]);

    }

    // 设置域名
    public function domain($value = []) {

        $data = [];
        $site = $this->config(SITE_ID);
        if ($value) {
            $site['config']['SITE_DOMAIN'] = $value['site_domain'] ? $value['site_domain'] : $site['config']['SITE_DOMAIN'];
            $site['config']['SITE_DOMAINS'] = $value['site_domains'];
            $site['mobile']['domain'] = $value['mobile_domain'];
            $site['webpath'] = $value['webpath'];
            $this->db->table('site')->where('id', SITE_ID)->update([
                'domain' => $site['config']['SITE_DOMAIN'],
                'setting' => dr_array2string($site),
            ]);
        }

        $data['webpath'] = $site['webpath'];
        $data['site_domain'] = $site['config']['SITE_DOMAIN'];
        $data['site_domains'] = $site['config']['SITE_DOMAINS'];
        $data['mobile_domain'] = $site['mobile']['domain'];

        if ($site['client']) {
            foreach ($site['client'] as $c) {
                if ($c['name'] && $c['domain']) {
                    $data['client_'.$c['name']] = $c['domain'];
                }
            }
        }

        // 用户中心域名
        /*
        if (dr_is_app('member')) {
            $member = $this->db->table('member_setting')->where('name', 'domain')->get()->getRowArray();
            $member && $member['value'] = dr_string2array($member['value']);
            if ($value) {
                $member['value'][SITE_ID]['domain'] = $value['member_domain'];
                $member['value'][SITE_ID]['mobile_domain'] = $value['member_mobile_domain'];
                $this->db->table('member_setting')->replace([
                    'name' => 'domain',
                    'value' => dr_array2string($member['value'])
                ]);
            }
            $data['member_domain'] = $member['value'][SITE_ID]['domain'];
            $data['member_mobile_domain'] = $member['value'][SITE_ID]['mobile_domain'];
        }*/

        // 模块域名
        $my = [];
        $module = $this->table('module')->getAll();
        foreach ($module as $t) {
            if (!is_file(APPSPATH.ucfirst($t['dirname']).'/Config/App.php')) {
                continue;
            }
            $cfg = require APPSPATH.ucfirst($t['dirname']).'/Config/App.php';
            $t['site'] = dr_string2array($t['site']);
            $my[$t['dirname']] = [
                'share' => $t['share'],
                'name' => dr_lang($cfg['name']),
                'error' => '',
            ];
            if ($t['share']) {
                $my[$t['dirname']]['error'] = dr_lang('共享模块不支持绑定');
                continue;
            }
            if ($t['site'][SITE_ID]) {
                if ($value) {
                    $t['site'][SITE_ID]['domain'] = $value['module_'.$t['dirname']];
                    $t['site'][SITE_ID]['mobile_domain'] = $value['module_mobile_'.$t['dirname']];
                    $t['site'][SITE_ID]['webpath'] = $value['webpath_'.$t['dirname']];
                    $this->db->table('module')->where('id', $t['id'])->update([
                        'site' => dr_array2string($t['site'])
                    ]);
                }
                $data['module_'.$t['dirname']] = $t['site'][SITE_ID]['domain'];
                $data['module_mobile_'.$t['dirname']] = $t['site'][SITE_ID]['mobile_domain'];
                $data['webpath_'.$t['dirname']] = $t['site'][SITE_ID]['webpath'];
            } else {
                $my[$t['dirname']]['error'] = dr_lang('当前站点未安装');
            }
        }

        return [$my, $data];
    }


    // 站点缓存缓存
    public function cache($siteid = null, $data = null, $module = null) {

        !$data && $data = $this->table('site')->getAll();
        $sso_domain = $client_name = $client_domain = $webpath = $app_domain = $site_domain = $config = $cache = [];
        if ($data) {
            foreach ($data as $t) {
                if ($t['id'] > 1 && !dr_is_app('sites')) {
                    break;
                }

                $t['setting'] = dr_string2array($t['setting']);
                if ($t['setting']['config']) {
                    foreach ($t['setting']['config'] as $i => $v) {
                        if ($i == 'SITE_DOMAINS' && $v) {
                            $v = explode(',', str_replace(',,', ',', str_replace([chr(13), PHP_EOL], ',', $v)));
                            if ($v) {
                                foreach ($v as $tt) {
                                    $tt && $site_domain[$tt] = $t['id'];
                                }
                            }
                            $t['setting']['config']['SITE_DOMAINS'] = $v;
                        }
                    }
                }
                $config[$t['id']] = [
                    'SITE_NAME' => $t['name'],
                    'SITE_DOMAIN' => $t['domain'],
                    'SITE_DOMAINS' => $t['setting']['config']['SITE_DOMAINS'],
                    'SITE_LOGO' => $t['setting']['config']['logo'] ? dr_get_file($t['setting']['config']['logo']) : ROOT_THEME_PATH.'assets/logo-web.png',
                    'SITE_MOBILE' => (string)$t['setting']['mobile']['domain'],
                    'SITE_AUTO' => (string)$t['setting']['mobile']['auto'],
                    'SITE_IS_MOBILE_HTML' => (string)$t['setting']['mobile']['tohtml'],
                    'SITE_CLOSE' => $t['setting']['config']['SITE_CLOSE'],
                    'SITE_THEME' => $t['setting']['config']['SITE_THEME'],
                    'SITE_TEMPLATE' => $t['setting']['config']['SITE_TEMPLATE'],
                    'SITE_REWRITE' => $t['setting']['seo']['SITE_REWRITE'],
                    'SITE_SEOJOIN' => $t['setting']['seo']['SITE_SEOJOIN'],
                    'SITE_LANGUAGE' => $t['setting']['config']['SITE_LANGUAGE'],
                    'SITE_TIMEZONE' => $t['setting']['config']['SITE_TIMEZONE'],
                    'SITE_TIME_FORMAT' => $t['setting']['config']['SITE_TIME_FORMAT'],
                    'SITE_INDEX_HTML' => (string)$t['setting']['config']['SITE_INDEX_HTML'],
                ];
                unset($t['setting']['mobile']['auto'],
                    $t['setting']['mobile']['domain'],
                    $t['setting']['seo']['SITE_REWRITE'],
                    $t['setting']['seo']['SITE_SEOJOIN'],
                    $t['setting']['config']['SITE_THEME'],
                    $t['setting']['config']['SITE_TEMPLATE'],
                    $t['setting']['config']['SITE_LANGUAGE'],
                    $t['setting']['config']['SITE_TIME_FORMAT'],
                    $t['setting']['config']['SITE_NAME'],
                    $t['setting']['config']['SITE_TIMEZONE'],
                    $t['setting']['config']['SITE_DOMAIN'],
                    $t['setting']['config']['SITE_CLOSE']
                );
                $cache[$t['id']] = $t['setting'];
                // 本站的全部域名归属
                $site_domain[$t['domain']] = $t['id'];
                $sso_domain[] = $t['domain'];
                if ($config[$t['id']]['SITE_MOBILE']) {
                    $site_domain[$config[$t['id']]['SITE_MOBILE']] = $t['id'];
                    $client_domain[$t['domain']] = $config[$t['id']]['SITE_MOBILE'];
                    $sso_domain[] = $config[$t['id']]['SITE_MOBILE'];
                }
                // 自定义终端
                if ($t['setting']['client']) {
                    $_save = [];
                    foreach ($t['setting']['client'] as $c) {
                        $site_domain[$c['domain']] = $t['id'];
                        $_save[$c['name']] = $sso_domain[] = $c['domain'];
                    }
                    $cache[$t['id']]['client'] = $_save;
                }

                // 网站路径
                $webpath[$t['id']] = [
                    'site' => ROOTPATH,
                ];
                if ($t['id'] > 1 && $t['setting']['webpath']) {
                    $webpath[$t['id']]['site'] = dr_get_dir_path($t['setting']['webpath']);
                    if (!is_dir($webpath[$t['id']]['site'])) {
                        log_message('error', '多站点：站点【'.$t['id'].'】目录【'.$webpath[$t['id']]['site'].'】不存在');
                        unset($cache[$t['id']]);
                        continue;
                    }
                }
				// 删除首页静态文件
				@unlink($webpath[$t['id']]['site'].'index.html');
				@unlink($webpath[$t['id']]['site'].'mobile/index.html');
            }

            /*
            // 用户中心域名
            $member = $this->db->table('member_setting')->where('name', 'domain')->get()->getRowArray();
            $value = $member ? dr_string2array($member['value']) : [];
            if ($value) {
                foreach ($value as $i => $t) {
                    if ($t['domain']) {
                        $site_domain[$t['domain']] = $i;
                        $app_domain[$t['domain']] = 'member';
                    }
                    if ($t['mobile_domain']) {
                        $site_domain[$t['mobile_domain']] = $i;
                        $app_domain[$t['mobile_domain']] = 'member';
                        $client_domain[$t['domain']] = $t['mobile_domain'];
                    }
                }
            }*/

            // 模块域名
            !$module && $module = $this->table('module')->getAll();
            if ($module) {
                foreach ($module as $t) {
                    // 删除模块缓存文件
                    foreach ($cache as $_sid => $v) {
                        \Phpcmf\Service::L('cache')->set_file('module-'.$_sid.'-'.$t['dirname'], []);
                        \Phpcmf\Service::L('cache')->del_file('module-'.$_sid.'-'.$t['dirname']);
                    }

                    if (!is_file(APPSPATH.ucfirst($t['dirname']).'/Config/App.php')) {
                        continue;
                    }
                    $t['site'] = dr_string2array($t['site']);
                    if (!$t['site'] || $t['share']) {
                        continue;
                    }
                    foreach ($t['site'] as $sid => $v) {
                        $webpath[$sid][$t['dirname']] = $webpath[$sid]['site'];
                        if ($v['domain']) {
                            $site_domain[$v['domain']] = $sid;
                            $app_domain[$v['domain']] = $t['dirname'];
                            $sso_domain[] = $v['domain'];
                            // 网站路径
                            if ($v['webpath']) {
                                $webpath[$sid][$t['dirname']] = dr_get_dir_path($v['webpath']);
                            }
                        }
                        if ($v['mobile_domain']) {
                            $site_domain[$v['mobile_domain']] = $sid;
                            $app_domain[$v['mobile_domain']] = $t['dirname'];
                            $client_domain[$v['domain']] = $v['mobile_domain'];
                            $sso_domain[] = $v['mobile_domain'];
                        }
                    }
                }
            }
        }

        \Phpcmf\Service::L('Cache')->set_file('site', $cache);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/site.php', '站点配置文件', 32)->to_require($config);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/domain_sso.php', '同步域名配置文件', 32)->to_require_one($sso_domain);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/domain_app.php', '项目域名配置文件', 32)->to_require_one($app_domain);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/domain_site.php', '站点域名配置文件', 32)->to_require_one($site_domain);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/domain_client.php', '客户端域名配置文件', 32)->to_require_one($client_domain);
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/webpath.php', '入口文件目录配置文件', 32)->to_require($webpath);
    }
}