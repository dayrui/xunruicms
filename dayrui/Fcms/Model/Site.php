<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Site extends \Phpcmf\Model {

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

        $save = [
            'name' => $data['name'],
            'domain' => (string)$data['domain'],
            'setting' => dr_array2string([
                'webpath' => $data['webpath'],
            ]),
            'disabled' => 0,
            'displayorder' => 0,
        ];
        if (defined('IS_INSTALL')) {
            $save['id'] = 1;
        }
        $this->db->table('site')->replace($save);

        $siteid = $this->db->insertID();

        if ($siteid == 1) {
            return; // 安装不执行后面操作
        }

        if (dr_is_app('sites')) {
            $obj = \Phpcmf\Service::M('sites', 'sites');
            if (method_exists($obj, 'create')) {
                $obj->create($siteid, $data);
            }
        }
    }

    // 变更主域名
    public function edit_domain($value) {

        $site = $this->config(1);
        $value = trim(strtolower($value), '/');
        $this->db->table('site')->where('id', 1)->update([
            'domain' => $value,
            'setting' => dr_array2string($site),
        ]);
        // 替换栏目编辑器域名
        $this->db->query('UPDATE `'.$this->dbprefix(SITE_ID.'_share_category').'` SET `content`=REPLACE(`content`, \''.$site['config']['SITE_DOMAIN'].'\', \''.$value.'\')');
    }

    // 设置域名
    public function domain($value = []) {

        $data = [];
        $site = $this->config(SITE_ID);
        if ($value) {
            $site['webpath'] = $value['webpath'];
            $this->db->table('site')->where('id', SITE_ID)->update([
                'domain' => trim(strtolower($value['site_domain'] ? $value['site_domain'] : $site['domain']), '/'),
                'setting' => dr_array2string($site),
            ]);
        }

        $data['webpath'] = $site['webpath'];
        $data['site_domain'] = strtolower((string)$site['config']['SITE_DOMAIN']);

        // 识别手机域名
        if (isset($site['mobile']['mode']) && $site['mobile']['mode'] != -1) {
            if (!$site['mobile']['mode']) {
                $data['mobile_domain'] = $site['mobile']['domain'];
            } else {
                $data['mobile_domain'] = $site['config']['SITE_DOMAIN'].'/'.trim($site['mobile']['dirname'] ? $site['mobile']['dirname'] : 'mobile');
            }
        } else {
            $data['mobile_domain'] = $site['mobile']['domain'];
        }

        if ($site['client']) {
            foreach ($site['client'] as $c) {
                if ($c['name'] && $c['domain']) {
                    $data['client_'.$c['name']] = $c['domain'];
                }
            }
        }

        // 模块域名
        $my = [];
        if (IS_USE_MODULE) {
            list($my, $data) = \Phpcmf\Service::M('module', 'module')->domian($value, $my, $data);
        }

        return [$my, $data];
    }

    // 站点缓存缓存
    public function cache($siteid = null, $data = null, $module = null) {

        !$data && $data = $this->table('site')->where('disabled', 0)->order_by('displayorder ASC,id ASC')->getAll();
        $sso_domain = $client_name = $client_domain = $webpath = $app_domain = $site_domain = $config = $cache = [];
        if ($data) {
            $module_cache_file = []; // 删除多余的模块缓存文件
            foreach ($data as $t) {
                if ($t['id'] > 1 && !dr_is_app('sites')) {
                    break;
                }

                $t['setting'] = dr_string2array($t['setting']);
                $mobile_dirname = 'mobile';

                // 识别手机域名
                if (isset($t['setting']['mobile']['mode']) && $t['setting']['mobile']['mode'] != -1) {
                    if (!$t['setting']['mobile']['mode']) {
                        $mobile_domain = (string)$t['setting']['mobile']['domain'];
                    } else {
                        $mobile_dirname = trim($t['setting']['mobile']['dirname'] ? $t['setting']['mobile']['dirname'] : 'mobile');
                        $mobile_domain = $t['domain'].'/'.$mobile_dirname;
                    }
                } else {
                    $mobile_domain = (string)$t['setting']['mobile']['domain'];
                }

                $config[$t['id']] = [
                    'SITE_NAME' => $t['name'],
                    'SITE_DOMAIN' => strtolower($t['domain']),
                    'SITE_LOGO' => $t['setting']['config']['logo'] ? dr_get_file($t['setting']['config']['logo']) : ROOT_THEME_PATH.'assets/logo-web.png',
                    'SITE_MOBILE' => $mobile_domain,
                    'SITE_MOBILE_DIR' => $mobile_dirname,
                    'SITE_AUTO' => (string)$t['setting']['mobile']['auto'],
                    'SITE_IS_MOBILE_HTML' => (string)$t['setting']['mobile']['tohtml'],
                    'SITE_MOBILE_NOT_PAD' => (string)$t['setting']['mobile']['not_pad'],
                    'SITE_CLOSE' => $t['setting']['config']['SITE_CLOSE'],
                    'SITE_THEME' => $t['setting']['config']['SITE_THEME'],
                    'SITE_TEMPLATE' => $t['setting']['config']['SITE_TEMPLATE'],
                    'SITE_REWRITE' => $t['setting']['seo']['SITE_REWRITE'],
                    'SITE_SEOJOIN' => $t['setting']['seo']['SITE_SEOJOIN'],
                    'SITE_LANGUAGE' => $t['setting']['config']['SITE_LANGUAGE'],
                    'SITE_TIMEZONE' => $t['setting']['config']['SITE_TIMEZONE'],
                    'SITE_TIME_FORMAT' => $t['setting']['config']['SITE_TIME_FORMAT'],
                    'SITE_INDEX_HTML' => (string)$t['setting']['config']['SITE_INDEX_HTML'],
                    'SITE_THUMB_WATERMARK' => (int)$t['setting']['watermark']['thumb'],
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
                    $cache[$t['id']]['client'] = $t['setting']['client'] = $_save;
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

                // 自定义站点字段
                $field = \Phpcmf\Service::M('field')->get_mysite_field($t['id']);
                if ($field && $t['setting']['param']) {
                    $t['setting']['param'] = \Phpcmf\Service::L('Field')->app('')->format_value($field, $t['setting']['param'], 1);
                }

                // 删除首页静态文件
                //unlink($webpath[$t['id']]['site'].'index.html');
                //unlink($webpath[$t['id']]['site'].$mobile_dirname.'/index.html');

                $module_cache_file[] = 'module-'.$t['id'].'-content.cache'; // 删除多余的模块缓存文件
                $module_cache_file[] = 'module-'.$t['id'].'-share.cache'; // 删除多余的模块缓存文件
                $module_cache_file[] = 'module-'.$t['id'].'.cache'; // 删除多余的模块缓存文件
                $cache[$t['id']] = $t['setting'];
            }

            if (IS_USE_MODULE) {
                list($webpath, $site_domain, $app_domain, $sso_domain, $client_domain) = \Phpcmf\Service::M('module', 'module')->sync_site_cache($module, $webpath, $site_domain, $app_domain, $sso_domain, $client_domain, $module_cache_file);
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