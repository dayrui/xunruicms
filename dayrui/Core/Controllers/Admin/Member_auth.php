<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Member_auth extends \Phpcmf\Common
{

    public function index() {

        // 用户组
        $group = [
            0 => dr_lang('游客')
        ];

        // 用户组等级
        $level = [
            0 => [
                'use' => 1,
                'name' => dr_lang('游客'),
                'space' => ' style="text-align: left;padding-left:10px;"',
            ]
        ];

        foreach ($this->member_cache['group'] as $t) {
            $group[$t['id']] = dr_lang($t['name']);
            $level[$t['id']] = [
                'use' => 1,
                'name' => dr_lang($t['name']),
                'space' => ' style="text-align: left;padding-left:10px;"',
            ];
            if ($t['level']) {
                foreach ($t['level'] as $lv) {
                    $level[$t['id'].'-'.$lv['id']] = [
                        'use' => 1,
                        'name' => dr_lang($lv['name']),
                        'space' => ' style="text-align: left;padding-left:30px;"'
                    ];
                    $level[$t['id']]['use'] = 0;
                }
            }
        }

        $v = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth_type')->get()->getRowArray();

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户权限' => ['member_auth/index', 'fa fa-cog'],
                    'help' => [801],
                ]
            ),
            'group' => $group,
            'level' => $level,
            'auth_type' => $v['value'],
        ]);
        \Phpcmf\Service::V()->display('member_auth.html');
    }

    // 存储模式值
    public function save_edit() {

        $value = intval(\Phpcmf\Service::L('input')->get('value'));
        if (!$value) {
            $msg = dr_lang('已切换至按全局配置模式');
        } elseif ($value == 1) {
            $msg = dr_lang('已切换至按用户组配置模式');
        } elseif ($value == 2) {
            $msg = dr_lang('已切换至按用户组等级配置模式');
        } else {
            $this->_json(0, dr_lang('未知模式'));
        }

        \Phpcmf\Service::M()->db->table('member_setting')->replace([
            'name' => 'auth_type',
            'value' => $value
        ]);
        \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存

        $this->_json(1, $msg);
    }

    // 初始化组权限
    public function init_edit() {

        $v = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth2')->get()->getRowArray();
        $aid = dr_safe_filename(\Phpcmf\Service::L('input')->get('aid'));
        $value = dr_string2array($v['value']);
        $value[SITE_ID][$aid] = [];

        \Phpcmf\Service::M()->db->table('member_setting')->replace([
            'name' => 'auth2',
            'value' => dr_array2string($value)
        ]);
        \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存

        $this->_json(1, dr_lang('本组权限初始化完成'));
    }

    // 权限设置列表
    public function add() {

        $aid = \Phpcmf\Service::L('input')->get('aid');
        if ($aid == 'public') {
            $name = dr_lang('全局');
        } elseif (!$aid) {
            $aid = 0;
            $name = dr_lang('游客');
        } elseif (strpos($aid, '-') !== false) {
            list($gid, $lid) = explode('-', $aid);
            if (!$this->member_cache['group'][$gid]) {
                $this->_admin_msg(0, dr_lang('此用户组不存在'));
            } elseif (!$this->member_cache['group'][$gid]['level'][$lid]) {
                $this->_admin_msg(0, dr_lang('此用户组等级不存在'));
            }
            $name = $this->member_cache['group'][$gid]['name'].'-'.$this->member_cache['group'][$gid]['level'][$lid]['name'];
        } else {
            if (!$this->member_cache['group'][$aid]) {
                $this->_admin_msg(0, dr_lang('此用户组不存在'));
            }
            $name = $this->member_cache['group'][$aid]['name'];
        }

        // 共享栏目
        $share_module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share');
        $share_categroy = [];
        if ($share_module['category']) {
            foreach($share_module['category'] as $t) {
                if ($t['tid'] != 2) {
                    $t['is_post'] = 0;
                    if (!$t['child'] && $t['tid'] == 1) {
                        $t['is_post'] = 1;
                    }
                    $share_categroy[$t['id']] = $t;
                }
            }
        }

        // 网站表单
        $form = \Phpcmf\Service::M()->table(SITE_ID.'_form')->getAll();

        // 模块部分
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                $module[$dir]['category'] = \Phpcmf\Service::L('tree')->init(\Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir, 'category'))->html_icon()->get_tree_array(0);
                $module[$dir]['mform'] = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir, 'form');
            }
        }

        // 读取权限存储值
        $v = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth2')->get()->getRowArray();
        $value = dr_string2array($v['value']);

        // 取当前站点当前模式的值
        $data = $value[SITE_ID][$aid];
        $auth = [
            'share_category' => $data['share_category_public'],
            'form' => $data['form_public'],
            'category' => $data['category_public'],
            'mform' => $data['mform_public'],
        ];

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $post['form_public'] = \Phpcmf\Service::L('input')->post('form');
            $post['share_category_public'] = \Phpcmf\Service::L('input')->post('share_category');
            $post['category_public'] = \Phpcmf\Service::L('input')->post('category');
            $post['mform_public'] = \Phpcmf\Service::L('input')->post('mform');

            // 这部分是独立设置的不管他，直接存储
            $post['form'] = $data['form'];
            $post['share_category'] = $data['share_category'];
            $post['category'] = $data['category'];
            $post['mform'] = $data['mform'];

            $value[SITE_ID][$aid] = $post;
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'auth2',
                'value' => dr_array2string($value)
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'aid' => $aid,
            'diy' => $this->_get_diy(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '返回模式选择' => ['member_auth/index', 'fa fa-cog'],
                    dr_lang('%s：用户权限设置', $name) => ['member_auth/add{aid='.$aid.'}', 'fa fa-user'],
                    'help' => [801],
                ]
            ),
            'page' => $page,
            'form' => $form,
            'data' => $data,
            'auth' => $auth,
            'module' => $module,
            'verify' => \Phpcmf\Service::M()->table('admin_verify')->getAll(),
            'is_ajax_edit' => 0,
            'share_categroy' => \Phpcmf\Service::L('tree')->init($share_categroy)->html_icon()->get_tree_array(0),
        ]);
        \Phpcmf\Service::V()->display('member_auth_setting.html');
    }

    // 弹出设置单独权限
    public function edit() {

        $at = dr_safe_filename(\Phpcmf\Service::L('input')->get('at'));
        if (!$at) {
            $this->_json(0, dr_lang('at参数错误'));
        }

        $v = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth2')->get()->getRowArray();
        $aid = dr_safe_filename(\Phpcmf\Service::L('input')->get('aid'));
        !$aid && $aid = 0;
        $value = dr_string2array($v['value']);

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!$id) {
            $this->_json(0, dr_lang('id参数错误'));
        }

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));

        if (IS_AJAX_POST) {

            if ($at == 'category' || $at == 'mform') {
                $post = \Phpcmf\Service::L('input')->post($at);
                $value[SITE_ID][$aid][$at][$mid][$id] = $post[$mid];
            } else {
                $value[SITE_ID][$aid][$at][$id] = \Phpcmf\Service::L('input')->post($at);
            }

            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'auth2',
                'value' => dr_array2string($value)
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
            $this->_json(1, dr_lang('操作成功'));
        }

        if ($at == 'category' || $at == 'mform') {
            $auth = [$at => [$mid => $value[SITE_ID][$aid][$at][$mid][$id ]]];
        } else {
            $auth = [$at => $value[SITE_ID][$aid][$at][$id]];
        }

        \Phpcmf\Service::V()->assign([
            'mid' => $mid,
            'diy' => $this->_get_diy(),
            'auth' => $auth,
            'verify' => \Phpcmf\Service::M()->table('admin_verify')->getAll(),
            'is_ajax_edit' => 1,
        ]);
        \Phpcmf\Service::V()->display('member_auth_'.$at.'.html');
    }

    // 复制动作
    public function copy_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $at = dr_safe_filename(\Phpcmf\Service::L('input')->get('at'));
        if (!$at) {
            $this->_json(0, dr_lang('at参数错误'));
        }

        $v = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'auth2')->get()->getRowArray();
        $aid = dr_safe_filename(\Phpcmf\Service::L('input')->get('aid'));
        !$aid && $aid = 0;
        $value = dr_string2array($v['value']);

        switch ($at) {

            case 'level':
                // 复制用户组等级
                // 用户组
                $group = [
                    0 => [
                        'id' => 0,
                        'name' => dr_lang('游客'),
                    ],
                ];
                foreach ($this->member_cache['group'] as $t) {
                    $group[$t['id']] = [
                        'id' => $t['id'],
                        'name' => dr_lang($t['name']),
                    ];
                    if ($t['level']) {
                        foreach ($t['level'] as $lv) {
                            $group[$t['id'].'-'.$lv['id']] = [
                                'id' => $t['id'].'-'.$lv['id'],
                                'name' => '  └  '.dr_lang($lv['name']),
                            ];
                        }
                    }
                }

                if (IS_AJAX_POST) {

                    $auth = $value[SITE_ID][$aid];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前用户组没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择用户组呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($group as $id => $t) {
                            $c ++;
                            $value[SITE_ID][$id] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个用户组', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $group,
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        '',
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_group.html');exit;

            case 'group':
                // 复制用户组
                // 用户组
                $group = [
                    0 => [
                        'id' => 0,
                        'name' => dr_lang('游客'),
                    ],
                ];
                foreach ($this->member_cache['group'] as $t) {
                    $group[$t['id']] = $t;
                }

                if (IS_AJAX_POST) {

                    $auth = $value[SITE_ID][$aid];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前用户组没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择用户组呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($group as $id => $t) {
                            $c ++;
                            $value[SITE_ID][$id] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个用户组', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $group,
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        '',
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_group.html');exit;
            case 'form':
                // 网站表单
                $form = \Phpcmf\Service::M()->table(SITE_ID.'_form')->get_all();
                if (IS_AJAX_POST) {

                    $auth = $value[SITE_ID][$aid]['form'][$id];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前表单没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择表单呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($form as $t) {
                            $c ++;
                            $value[SITE_ID][$aid]['form'][$t['id']] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$aid]['form'][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个表单', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $form,
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        dr_lang('全部表单'),
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_form.html');exit;

                break;
            case 'share_category':
                // 共享栏目
                $share_module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share');
                if (IS_AJAX_POST) {

                    $auth = $value[SITE_ID][$aid]['share_category'][$id];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前栏目没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择栏目呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($share_module['category'] as $id => $t) {
                            $c ++;
                            $value[SITE_ID][$aid]['share_category'][$id] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$aid]['share_category'][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个栏目', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $share_module['category'],
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        dr_lang('全部栏目'),
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_category.html');exit;
                break;

            case 'category':
                // 独立栏目
                $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
                $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$mid);
                if (IS_AJAX_POST) {

                    $auth = $value[SITE_ID][$aid]['category'][$mid][$id];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前栏目没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择栏目呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($module['category'] as $id => $t) {
                            $c ++;
                            $value[SITE_ID][$aid]['category'][$mid][$id] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$aid]['category'][$mid][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个栏目', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $module['category'],
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        dr_lang('全部栏目'),
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_category.html');exit;
                break;

            case 'mform':
                // 模块表单
                $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
                $form = \Phpcmf\Service::M()->table('module_form')->where('module', $mid)->where('disabled', 0)->order_by('id ASC')->getAll();
                if (IS_AJAX_POST) {
                    $auth = $value[SITE_ID][$aid]['mform'][$mid][$id];
                    if (!$auth) {
                        $this->_json(0, dr_lang('当前表单没有配置权限规则'));
                    }

                    $catids = \Phpcmf\Service::L('input')->post('catid');
                    if (!$catids) {
                        $this->_json(0, dr_lang('你还没有选择表单呢'));
                    }

                    $c = 0;
                    if (isset($catids[0]) && $catids[0] == 0) {
                        foreach ($form as $id => $t) {
                            $c ++;
                            $value[SITE_ID][$aid]['mform'][$mid][$id] = $auth;
                        }
                    } else {
                        foreach ($catids as $id) {
                            $c ++;
                            $value[SITE_ID][$aid]['mform'][$mid][$id] = $auth;
                        }
                    }

                    \Phpcmf\Service::M()->db->table('member_setting')->replace([
                        'name' => 'auth2',
                        'value' => dr_array2string($value)
                    ]);
                    \Phpcmf\Service::M('cache')->sync_cache('member');
                    $this->_json(1, dr_lang('共复制%s个表单', $c));
                    exit;
                }

                \Phpcmf\Service::V()->assign([
                    'form' =>  dr_form_hidden(),
                    'select' => \Phpcmf\Service::L('tree')->select_category(
                        $form,
                        0,
                        'id=\'dr_catid\' name=\'catid[]\' multiple="multiple" style="height:200px"',
                        dr_lang('全部表单'),
                        0,
                        0
                    ),
                ]);
                \Phpcmf\Service::V()->display('member_auth_copy_form.html');exit;
                break;

        }

        $this->_json(0, dr_lang('未知类型'));
    }

    // 获取自定义权限组
    private function _get_diy() {

        // 默认的
        $diy = [
            'member' => [],
            'module' => [],
            'app' => [],
            'form' => [],
            'mform' => [],
            'category' => [],
        ];

        // 执行插件自己的缓存程序
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock')
                && is_file($path.'Config/Auth.php')) {
                $_data = require $path.'Config/Auth.php';
                if ($_data) {
                    foreach ($_data as $key => $value) {
                        if ($value && isset($diy[$key])) {
                            foreach ($value as $file) {
                                if (is_file($path.'Views/auth/'.$file)) {
                                    $diy[$key][] = [
                                        'app' => $dir,
                                        'file' => $path.'Views/auth/'.$file,
                                    ];
                                } else {
                                    log_message('error', '应用插件['.$dir.']权限模板文件不存在：'.$path.'Views/auth/'.$file);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $diy;
    }

}
