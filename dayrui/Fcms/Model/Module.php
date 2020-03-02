<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Module extends \Phpcmf\Model
{

    protected $cat_share;
    protected $cat_share_lock;

    // 全部安装的模块
    public function All($is_file_name = 0) {
        $list = $this->table('module')->order_by('displayorder ASC,id ASC')->getAll();
        if ($list && $is_file_name) {
            foreach ($list as $i => $t) {
                if (is_file(dr_get_app_dir($t['dirname']).'Config/App.php')) {
                    $cfg = require dr_get_app_dir($t['dirname']).'Config/App.php';
                    if ($cfg['type'] == 'module' || $cfg['ftype'] == 'module') {
                        $list[$i]['name'] = $cfg['name'];
                    }
                }
            }
        }
        return $list;
    }

    // 存储配置信息
    public function config($data, $post, $value = []) {

        $update = $post ? [ 'setting' => $post['setting'] ] : [ 'setting' => $data['setting'] ];

        // 推荐位更新
        if ($value['flag']) {
            $update['setting']['flag'] = [];
            foreach ($value['flag'] as $i => $t) {
                $t['name'] && $update['setting']['flag'][$i] = $t;
            }
        } else {
            $update['setting']['flag'] = $data['setting']['flag'];
        }

        // 这里不更新站点
        $update['setting']['param'] = $data['setting']['param'];
        $update['setting']['search'] = $data['setting']['search'];

        // 站点更新
        if ($value['site']) {
            $site = $data['site'];
            $value['site']['use'] = 1;
            $site[SITE_ID] = $value['site'];
            $update['site'] = dr_array2string($site);
        }

        // 排列table字段顺序
        $update['setting']['list_field'] = dr_list_field_order($update['setting']['list_field']);

        $update['setting'] = dr_array2string($update['setting']);

        // 更新表
        return $this->table('module')->update(intval($data['id']), $update);
    }

    // 创建表单文件
    public function create_form_file($dir, $table, $call = 0) {

        $dir = ucfirst($dir);
        $path = dr_get_app_dir($dir);
        if (!is_dir($path)) {
            return dr_return_data(1, 'ok');
        }

        $name = ucfirst($table);
        $files = [
            $path.'Controllers/'.$name.'.php' => FCPATH.'Temp/Mform/$NAME$.php',
            $path.'Controllers/Member/'.$name.'.php' => FCPATH.'Temp/Mform/Member$NAME$.php',
            $path.'Controllers/Admin/'.$name.'.php' => FCPATH.'Temp/Mform/Admin$NAME$.php',
            $path.'Controllers/Admin/'.$name.'_verify.php' => FCPATH.'Temp/Mform/Admin$NAME$_verify.php',
        ];

        $ok = 0;
        foreach ($files as $file => $form) {
            if (!is_file($file)) {
                $c = @file_get_contents($form);
                $size = @file_put_contents($file, str_replace('$NAME$', $name, $c));
                if (!$size && $call) {
                    @unlink($file);
                    return dr_return_data(0, dr_lang('文件%s创建失败，无可写权限', str_replace(FCPATH, '', $file)));
                }
                $ok ++;
            }
        }

        return dr_return_data(1, $ok);
    }


    // 创建模块表单
    public function create_form($dir, $data) {

        // 插入表单数据
        $rt = $this->table('module_form')->insert(array(
            'name' => $data['name'],
            'table' => strtolower($data['table']),
            'module' => $dir,
            'setting' => '',
            'disabled' => 0,
        ));

        if (!$rt['code']) {
            return $rt;
        }

        $id = $data['id'] = $rt['code'];
        $data['module'] = $dir;

        // 创建文件
        $rt = $this->create_form_file($dir, strtolower($data['table']));
        if (!$rt['code']) {
            $this->table('module_form')->delete($id);
            return $rt;
        }

        // 创建表
        \Phpcmf\Service::M('Table')->create_module_form($data);

        return dr_return_data(1, 'ok');
    }

    // 删除模块表单
    public function delete_form($ids) {

        foreach ($ids as $id) {
            $row = $this->table('module_form')->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('模块表单不存在(id:%s)', $id));
            }
            $rt = $this->table('module_form')->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            $name = ucfirst($row['table']);
            @unlink(APPPATH.'Controllers/'.$name.'.php');
            @unlink(APPPATH.'Controllers/Admin/'.$name.'.php');
            @unlink(APPPATH.'Controllers/Admin/'.$name.'_verify.php');
            // 删除表数据
            \Phpcmf\Service::M('Table')->delete_module_form($row);
        }

        return dr_return_data(1, '');
    }

    // 获取归属于本模块的栏目关系
    private function _get_my_category($mdir, $CAT) {

        foreach ($CAT as $i => $t) {
            if (!$t['child'] && $t['tid'] == 1 && $t['mid'] != $mdir) {
                unset($CAT[$i]);
            }
            // 验证mid
            if ($CAT[$t['id']]['catids']) {
                $mid = [];
                foreach ($CAT[$t['id']]['catids'] as $c_catid) {
                    if ($CAT[$c_catid]['mid']) {
                        $mid[$CAT[$c_catid]['mid']] = 1;
                    }
                }
                if (!$mid) {
                    unset($CAT[$t['id']]);
                } elseif (!isset($mid[$mdir])) {
                    unset($CAT[$t['id']]);
                }
            }
        }

        return $CAT;
    }

    // 安装模块
    public function install($dir, $config = [], $is_app = 0, $is_share = 0) {

        $mpath = dr_get_app_dir($dir);
        if (!$config) {
            if (!is_file($mpath.'Config/App.php')) {
                return dr_return_data(0, dr_lang('模块配置文件不存在'));
            }
            $config = require $mpath.'Config/App.php';
        }

        $table = $this->dbprefix(dr_module_table_prefix($dir)); // 当前表前缀
        $system_table = require CMSPATH.'Config/SysTable.php';
        if (!$system_table ) {
            return dr_return_data(0, dr_lang('系统表配置文件不存在'));
        }

        // 判断是否强制独立模块或共享模块
        if ($is_share) {
            $config['share'] = $is_share == 1 ? 1 : 0; // 强制共享安装
        } elseif (isset($config['mtype']) && $config['mtype']) {
            $config['share'] = $config['mtype'] == 2 ? 0 : 1;
        }

        // 模块内容表结构和字段结构
        if (is_file($mpath.'Config/Content.php')) {
            $content_table = require $mpath.'Config/Content.php';
        } else {
            $content_table = require CMSPATH.'Config/Content.php';
        }

        $module = $this->db->table('module')->where('dirname', $dir)->get()->getRowArray();
        if (!$module) {
            if (isset($config['ftype']) && $config['ftype'] == 'module' && $is_app == 0) {
                // 首次安装模块时，验证应用模块
                return dr_return_data(0, dr_lang('此模块属于应用类型，请到[本地应用]中去安装'));
            }
            $module = [
                'site' => dr_array2string([
                    SITE_ID => [
                        'html' => 0,
                        'theme' => 'default',
                        'domain' => '',
                        'template' => 'default',
                    ]
                ]),
                'share' => intval($config['share']),
                'dirname' => $dir,
                'setting' => '{"order":"displayorder DESC,updatetime DESC","verify_msg":"","delete_msg":"","list_field":{"title":{"use":"1","order":"1","name":"主题","width":"","func":"title"},"catid":{"use":"1","order":"2","name":"栏目","width":"130","func":"catid"},"author":{"use":"1","order":"3","name":"作者","width":"120","func":"author"},"updatetime":{"use":"1","order":"4","name":"更新时间","width":"160","func":"datetime"}},"comment_list_field":{"content":{"use":"1","order":"1","name":"评论","width":"","func":"comment"},"author":{"use":"1","order":"3","name":"作者","width":"100","func":"author"},"inputtime":{"use":"1","order":"4","name":"评论时间","width":"160","func":"datetime"}},"flag":null,"param":null,"search":{"use":"1","field":"title,keywords","total":"500","length":"4","param_join":"-","param_rule":"0","param_field":"","param_join_field":["","","","","","",""],"param_join_default_value":"0"}}',
                'comment' => '{"use":"1","num":"0","my":"0","reply":"0","ct_reply":"0","pagesize":"","pagesize_mobile":"","pagesize_api":"","review":{"score":"10","point":"0","value":{"1":{"name":"1星评价"},"2":{"name":"2星评价"},"3":{"name":"3星评价"},"4":{"name":"4星评价"},"5":{"name":"5星评价"}},"option":{"1":{"name":"选项1"},"2":{"name":"选项2"},"3":{"name":"选项3"},"4":{"name":"选项4"},"5":{"name":"选项5"},"6":{"name":"选项6"},"7":{"name":"选项7"},"8":{"name":"选项8"},"9":{"name":"选项9"}}}}',
                'disabled' => 0,
                'displayorder' => 0,
            ];
            $rt = $this->table('module')->insert($module);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            $module['id'] = $rt['code'];
            $module['site'] = dr_string2array($module['site']);
        } else {
            $module['site'] = dr_string2array($module['site']);
            $module['site'][SITE_ID] = [
                'html' => 0,
                'theme' => 'default',
                'domain' => '',
                'template' => 'default',
            ];
            $this->table('module')->update($module['id'], [
                'site' => dr_array2string($module['site'])
            ]);
        }

        $siteid = 1;
        if (dr_count($module['site']) > 1) {
            // 多站点的情况下作为站点安装
            foreach ($module['site'] as $sid => $t) {
                if ($sid != SITE_ID) {
                    $siteid = $sid;
                    break;
                }
            }
            // 多站点时的复制站点表$siteid
        }


        // 创建内容表字段
        foreach ([1, 0] as $is_main) {
            $t = $content_table['field'][$is_main];
            if ($t) {
                foreach ($t as $field) {
                    $this->_add_field($field, $is_main, $module['id'], 'module');
                }
            }
        }

        $system_table[''] = $content_table['table'][1];
        $system_table['_data_0'] = $content_table['table'][0];
        // 创建系统表
        foreach ($system_table as $name => $sql) {
            if (dr_count($module['site']) == 1) {
                // 表示第一个站就创建
                $this->db->simpleQuery(str_replace('{tablename}', $table.$name, dr_format_create_sql($sql)));
            } else {
                // 表示已经在其他站创建过了,我们就复制它以前创建的表结构
                $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix(dr_module_table_prefix($dir, $siteid).$name)."`")->getRowArray();
                $sql = str_replace(
                    array($sql['Table'], 'CREATE TABLE'),
                    array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                    $sql['Create Table']
                );
                $this->db->simpleQuery(str_replace('{tablename}', $table.$name, dr_format_create_sql($sql)));
            }
        }
        // 创建相关栏目表字段
        if (isset($config['scategory']) && $config['scategory']) {
            if (!\Phpcmf\Service::M()->db->fieldExists('tid', $table.'_category')) {
                \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'_category` ADD `tid` tinyint(1) NOT NULL COMMENT \'栏目类型，0单页，1模块，2外链\'');
            }
            if (!\Phpcmf\Service::M()->db->fieldExists('content', $table.'_category')) {
                \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'_category` ADD `content` mediumtext NOT NULL COMMENT \'单页内容\'');
            }
        }
        // 第一次安装模块
        // system = 2 2菜单不出现在内容下，由开发者自定义
        if ($config['system'] == 2 && dr_count($module['site']) == 1 && $is_app == 0 && is_file($mpath.'Config/Menu.php')) {
            \Phpcmf\Service::M('Menu')->add_app($dir);
        }

        // =========== 这里是公共部分 ===========

        // 创建评论
        $comment = require CMSPATH.'Config/Comment.php';
        foreach ($comment as $name => $sql) {
            if (dr_count($module['site']) == 1) {
                // 表示第一个站就创建
                $this->db->simpleQuery(str_replace('{tablename}', $table.$name, dr_format_create_sql($sql)));
            } else {
                // 表示已经在其他站创建过了,我们就复制它以前创建的表结构
                $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix(dr_module_table_prefix($dir, 1).$name)."`")->getRowArray();
                $sql = str_replace(
                    array($sql['Table'], 'CREATE TABLE'),
                    array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                    $sql['Create Table']
                );
                $this->db->simpleQuery(str_replace('{tablename}', $table.$name, dr_format_create_sql($sql)));
            }
        }

        // 创建表单
        if (dr_count($module['site']) == 1) {
            // 表示第一个站就创建表单
            if (is_file($mpath.'Config/Form.php')) {
                $form = require $mpath.'Config/Form.php';
                if ($form) {
                    foreach ($form as $ftable => $t) {
                        // 插入表单数据
                        $rt = $this->table('module_form')->insert(array(
                            'name' => $t['form']['name'],
                            'table' => $ftable,
                            'module' => $dir,
                            'setting' => $t['form']['setting'],
                            'disabled' => 0,
                        ));
                        if ($rt['code']) {
                            // 插入sql
                            $this->db->simpleQuery(str_replace('{tablename}', $table.'_form_'.$ftable, dr_format_create_sql($t['table'][1])));
                            $this->db->simpleQuery(str_replace('{tablename}', $table.'_form_'.$ftable.'_data_0', dr_format_create_sql($t['table'][0])));
                            // 插入自定义字段
                            foreach ([1, 0] as $is_main) {
                                $f = $t['field'][$is_main];
                                if ($f) {
                                    foreach ($f as $field) {
                                        $this->_add_field($field, $is_main, $rt['code'], 'mform-'.$dir);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // 创建模块已经存在的表单
            $form = $this->db->table('module_form')->where('module', $dir)->get()->getResultArray();
            if ($form) {
                foreach ($form as $t) {
                    $mytable = $table.'_form_'.$t['table'];
                    // 主表
                    $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix('1_'.$dir).'_form_'.$t['table']."`")->getRowArray();
                    $sql = str_replace(
                        array($sql['Table'], 'CREATE TABLE'),
                        array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                        $sql['Create Table']
                    );
                    $sql = dr_format_create_sql(str_replace('{tablename}', $mytable, $sql));
                    $this->db->query($sql);
                    // 附表
                    $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix('1_'.$dir).'_form_'.$t['table']."_data_0`")->getRowArray();
                    $sql = str_replace(
                        array($sql['Table'], 'CREATE TABLE'),
                        array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                        $sql['Create Table']
                    );
                    $sql = dr_format_create_sql(str_replace('{tablename}', $mytable.'_data_0', $sql));
                    $this->db->query($sql);
                }
            }
        }

        if (is_file($mpath.'Config/Install.php')) {
            require $mpath.'Config/Install.php';
        }

        // 首次安装模块执行它
        if (dr_count($module['site']) == 1) {
            // 执行自定义sql
            if (is_file($mpath.'Config/Install.sql')) {
                $sql = file_get_contents($mpath.'Config/Install.sql');
                $sql && \Phpcmf\Service::M('table')->_query(
                    $sql,
                    [
                        [
                            '{moduleid}',
                            '{dbprefix}',
                            '{tablename}',
                            '{dirname}',
                            '{siteid}'
                        ],
                        [
                            $module['id'],
                            $this->dbprefix(),
                            $table,
                            $dir,
                            $siteid
                        ],
                    ]
                );
            }
        }

        // 执行站点sql语句
        if (is_file($mpath.'Config/Install_site.sql')) {
            $sql = file_get_contents($mpath.'Config/Install_site.sql');
            $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix($siteid.'_'), $sql));
            if ($rt) {
                return dr_return_data(0, $rt);
            }
        }

        return dr_return_data(1, dr_lang('操作成功，请刷新后台页面'), $module);
    }

    // 卸载模块
    public function uninstall($dir, $config = [], $is_app = 0) {

        $module = $this->db->table('module')->where('dirname', $dir)->get()->getRowArray();
        if (!$module) {
            return dr_return_data(0, dr_lang('模块尚未安装'));
        }

        $mpath = dr_get_app_dir($dir);
        $table = $this->dbprefix(dr_module_table_prefix($dir)); // 当前表前缀
        $system_table = require CMSPATH.'Config/SysTable.php';

        $site = dr_string2array($module['site']);
        if (count($site) == 1 && isset($config['ftype']) && $config['ftype'] == 'module' && $is_app == 0) {
            // 只有一个站点时，卸载需要 验证应用模块
            return dr_return_data(0, dr_lang('此模块属于应用类型，请到[本地应用]中去卸载'));
        }

        if (isset($site[SITE_ID]) && $site[SITE_ID]) {
            // 删除当前站点中的全部模块表

            // 系统模块
            // 删除系统表
            foreach ($system_table as $name => $sql) {
                $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.$name.'`');
            }
            // 删除主表
            $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.'`');
            // 删除全部子表
            for ($i = 0; $i < 200; $i ++) {
                if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                    break;
                }
                $this->db->query('DROP TABLE IF EXISTS '.$table.'_data_'.$i);
            }
            // 删除栏目模型子表
            for ($i = 0; $i < 200; $i ++) {
                if (!$this->db->query("SHOW TABLES LIKE '".$table.'_category_data_'.$i."'")->getRowArray()) {
                    break;
                }
                $this->db->query('DROP TABLE IF EXISTS '.$table.'_category_data_'.$i);
            }


            // 删除评论
            $comment = require CMSPATH.'Config/Comment.php';
            foreach ($comment as $name => $sql) {
                $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.$name.'`');
            }

            // 删除表单
            $form = $this->db->table('module_form')->where('module', $dir)->get()->getResultArray();
            if ($form) {
                foreach ($form as $t) {
                    $mytable = $table.'_form_'.$t['table'];
                    // 主表
                    $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$mytable.'`');
                    // 附表
                    for ($i = 0; $i < 200; $i ++) {
                        if (!$this->db->query("SHOW TABLES LIKE '".$mytable.'_data_'.$i."'")->getRowArray()) {
                            break;
                        }
                        $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$mytable.'_data_'.$i.'`');
                    }
                }
            }

            // 执行模块自己的卸载程序
            if (is_file($mpath.'Config/Uninstall.php')) {
                require $mpath.'Config/Uninstall.php';
            }

            // 执行自定义sql
            if (is_file($mpath.'Config/Uninstall.sql')) {
                $sql = file_get_contents($mpath.'Config/Uninstall.sql');
                $sql && \Phpcmf\Service::M('table')->_query(
                    $sql,
                    [
                        [
                            '{moduleid}',
                            '{dbprefix}',
                            '{tablename}',
                            '{dirname}',
                            '{siteid}'
                        ],
                        [
                            $module['id'],
                            $this->dbprefix(),
                            $table,
                            $dir,
                            SITE_ID
                        ],
                    ]
                );
            }

            // 执行站点sql语句
            if (is_file($mpath.'Config/Uninstall_site.sql')) {
                $sql = file_get_contents($mpath.'Config/Uninstall_site.sql');
                foreach ($this->site as $siteid) {
                    $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix($siteid.'_'), $sql));
                    if ($rt) {
                        return dr_return_data(0, $rt);
                    }
                }
            }

            // 删除栏目模型字段
            $this->db->table('field')->where('relatedname', $dir.'-'.SITE_ID)->delete();
            unset($site[SITE_ID]);
        }

        if (empty($site)) {
            // 没有站点了就删除这条模块记录
            $this->table('module')->delete($module['id']);
            // 删除字段
            $this->db->table('field')->where('relatedname', $dir.'-1')->delete();
            $this->db->table('field')->where('relatedname', 'category-'.$dir)->delete();
            $this->db->table('field')->where('relatedid', $module['id'])->where('relatedname', 'module')->delete();
            $this->db->table('field')->where('relatedid', $module['id'])->where('relatedname', 'mform-'.$dir)->delete();
            // 删除菜单
            $this->db->table('admin_menu')->where('mark', 'module-'.$dir)->delete();
            $this->db->table('member_menu')->where('mark', 'module-'.$dir)->delete();
            // 删除自定义菜单
            \Phpcmf\Service::M('Menu')->delete_app($dir);
            // 删除自定义表单
            $this->db->table('module_form')->where('module', $dir)->delete();
        } else {
            // 删除当前站点配置
            $this->table('module')->update($module['id'], ['site' => dr_array2string($site)]);
        }

        return dr_return_data(1, dr_lang('卸载成功'));
    }

    /**
     * 字段入库
     * @return	bool
     */
    private function _add_field($field, $ismain, $rid, $rname) {

        if ($this->db->table('field')->where('fieldname', $field['fieldname'])->where('relatedid', $rid)->where('relatedname', $rname)->countAllResults()) {
            return;
        }

        $this->db->table('field')->insert(array(
            'name' => (string)($field['name'] ? $field['name'] : $field['textname']),
            'ismain' => $ismain,
            'setting' => dr_array2string($field['setting']),
            'issystem' => isset($field['issystem']) ? (int)$field['issystem'] : 1,
            'ismember' => isset($field['ismember']) ? (int)$field['ismember'] : 1,
            'disabled' => isset($field['disabled']) ? (int)$field['disabled'] : 0,
            'fieldname' => $field['fieldname'],
            'fieldtype' => $field['fieldtype'],
            'relatedid' => $rid,
            'relatedname' => $rname,
            'displayorder' => (int)$field['displayorder'],
        ));
    }

    // 内容模块
    public function get_module_info($module = null) {

        $rt = [];
        !$module && $module = $this->table('module')->order_by('displayorder ASC,id ASC')->getAll();
        if ($module) {
            foreach ($module as $data) {
                $mdir = $data['dirname'];
                // 如果没有配置文件就不更新缓存
                if (!is_file(dr_get_app_dir($mdir).'Config/App.php') || $data['disabled']) {
                    continue;
                }
                $config = require dr_get_app_dir($mdir).'Config/App.php';
                $setting = dr_string2array($data['setting']);#print_r($setting);
                $setting['list_field'] = dr_list_field_order($setting['list_field']);
                $setting['comment_list_field'] = dr_list_field_order($setting['comment_list_field']);
                $rt[$mdir] = [
                    'id' => $data['id'],
                    'name' => $config['name'],
                    'icon' => $config['icon'],
                    'site' => dr_string2array($data['site']),
                    'config' => $config,
                    'share' => $data['share'],
                    'comment' => dr_string2array($data['comment']),
                    'setting' => $setting,
                    'dirname' => $mdir,
                ];
            }
        }

        return $rt;
    }

    // 模块的共享栏目数据
    private function _get_share_category($siteid, $dir = '') {

        !$this->cat_share[$siteid] && $this->cat_share[$siteid] = $this->db->table($siteid.'_share_category')->orderBy('displayorder ASC, id ASC')->get()->getResultArray();

        if (!$this->cat_share[$siteid]) {
            return array();
        }

        if (!$dir) {
            return $this->cat_share[$siteid];
        }

        $category = array();
        foreach ($this->cat_share[$siteid] as $i => $t) {
            if (!$t['child'] && $t['tid'] == 1 && $t['mid'] != $dir) {
                continue;
            }
            $category[$i] = $t;
        }

        return $category;
    }

    // 栏目缓存数据
    private function _get_category_cache($siteid, $cache) {

        if ($cache['share']) {
            $cdir = 'share';
            $category = $this->cat_share[$siteid] = $this->cat_share[$siteid] ? $this->cat_share[$siteid] : $this->db->table($siteid.'_share_category')->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
        } else {
            $cdir = $cache['dirname'];
            $category = $this->db->table($siteid.'_'.$cdir.'_category')->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
        }

        // 修复优化栏目
        $category = \Phpcmf\Service::M('category')->init(['table' => $siteid.'_'.$cdir.'_category'])->repair($category, $cdir);

        // 栏目开始
		$CAT = $CAT_DIR = $fenzhan = $level = [];
        if ($category) {
            // 栏目的定义字段
            $field = $this->db->table('field')->where('disabled', 0)->where('relatedname', 'category-'.$cdir)->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
            if ($field) {
                foreach ($field as $f) {
                    $f['setting'] = dr_string2array($f['setting']);
                    $cache['category_field'][$f['fieldname']] = $f;
                }
            }
            if (!isset($cache['category_field']['thumb'])) {
                $this->_add_field([
                    'name' => dr_lang('缩略图'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'File',
                    'fieldname' => 'thumb',
                    'setting' => array(
                        'option' => array(
                            'ext' => 'jpg,gif,png,jpeg',
                            'size' => 10,
                            'input' => 1,
                            'attachment' => 0,
                        )
                    )
                ], 1, 0, 'category-'.$cdir);
            }
            if ($cache['share'] && !isset($cache['category_field']['content'])) {
                $this->_add_field([
                    'name' => dr_lang('栏目内容'),
                    'ismain' => 1,
                    'fieldtype' => 'Ueditor',
                    'fieldname' => 'content',
                    'setting' => array(
                        'option' => array(
                            'mode' => 1,
                            'height' => 300,
                            'width' => '100%',
                            'attachment' => 0,
                        )
                    ),
                ], 1, 0, 'category-'.$cdir);
            }
            foreach ($category as $c) {
                $pid = explode(',', $c['pids']);
                $level[] = substr_count($c['pids'], ',');
                $c['mid'] = isset($c['mid']) ? $c['mid'] : $cache['dirname'];
                $c['topid'] = isset($pid[1]) ? $pid[1] : $c['id'];
                $c['domain'] = isset($c['domain']) ? $c['domain'] : $cache['domain'];
                $c['mobile_domain'] = isset($c['mobile_domain']) ? $c['mobile_domain'] : $cache['mobile_domain'];
                $c['catids'] = explode(',', $c['childids']);
                $c['setting'] = dr_string2array($c['setting']);
                if ($cache['share']) {
                    // 共享栏目时
                    //以本栏目为准
                    $c['setting']['html'] = intval($c['setting']['html']);
                    $c['setting']['urlrule'] = intval($c['setting']['urlrule']);
                } else {
                    // 独立模块栏目
                    //以站点为准
                    if (!isset($c['tid'])) {
                        $c['tid'] = $c['setting']['linkurl'] ? 2 : 1; // 判断栏目类型 2表示外链
                    }
                    $c['setting']['html'] = intval($cache['html']);
                    $c['setting']['urlrule'] = intval($cache['site'][$siteid]['urlrule']);
                }
                // 权限
                $c['permission'] = $c['child'] && !$cache['setting']['pcatpost'] ? '' : dr_string2array($c['permission']);
                // 获取栏目url
                $c['url'] = $c['tid'] == 2 && $c['setting']['linkurl'] ? $c['setting']['linkurl'] : \Phpcmf\Service::L('router')->category_url($cache, $c);
                //$c['furl'] = $c['setting']['linkurl'] ? $c['setting']['linkurl'] : \Phpcmf\Service::L('router')->category_url($cache, $c, 0, '{fid}');
                // 按分站生成url
                // 统计栏目文章数量
                $c['total'] = ($c['child'] || !$c['mid'] || !$this->db->tableExists($this->dbprefix($siteid.'_'.$c['mid'].'_index'))) ? 0 : $this->db->table($siteid.'_'.$c['mid'].'_index')->where('status', 9)->where('catid', intval($c['id']))->countAllResults();
                // 格式化栏目
                $CAT[$c['id']] = \Phpcmf\Service::L('Field')->app($cdir)->format_value($cache['category_field'], $c, 1);
                $CAT_DIR[$c['dirname']] = $c['id'];
            }
            // 更新父栏目数量
            foreach ($category as $c) {
                if ($c['child']) {
                    $arr = explode(',', $c['childids']);
                    $CAT[$c['id']]['total'] = 0;
                    foreach ($arr as $i) {
                        $CAT[$c['id']]['total']+= $CAT[$i]['total'];
                    }
                }
            }

            // 自定义栏目模型字段，把父级栏目的字段合并至当前栏目
            $like = [$cache['dirname'].'-'.$siteid];
            if ($cache['share']) {
                $like[] = 'share-'.$siteid;
            }
            $field = $this->db->table('field')->where('disabled', 0)->whereIn('relatedname', $like)->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
            if ($field) {
                foreach ($field as $f) {
                    $f['setting'] = dr_string2array($f['setting']);
                    //
                    if ($f['relatedid']) {
                        $f['setting']['diy']['cat_field_catids'][] = $f['relatedid'];
                    }
                    $fcatids = array_unique($f['setting']['diy']['cat_field_catids']);
                    if ($fcatids) {
                        foreach ($fcatids as $fcid) {
                            if (isset($CAT[$fcid]['childids']) && $CAT[$fcid]['childids']) {
                                // 将该字段同时归类至其子栏目
                                $child = explode(',', $CAT[$fcid]['childids']);
                                foreach ($child as $catid) {
                                    $CAT[$catid] && $CAT[$catid]['field'][$f['fieldname']] = $f;
                                }
                            }
                        }
                    }

                }
            }

            // 栏目结束
            if (!$cache['share']) {
                // 此变量说明本模块存在栏目模型字段
                $cache['category_data_field'] = $field ? 1 : 0;
                $cache['category'] = $CAT;
            } else {
                // 共享模块需要筛选出自己的模块的栏目
                $cache['category_data_field'] = 0;
                $cache['category'] = $this->_get_my_category($cache['dirname'], $CAT);
                foreach ($cache['category'] as $t) {
                    if ($t['field']) {
                        $cache['category_data_field'] = 1;
                        break;
                    }
                }

            }
            $cache['category_dir'] = $CAT_DIR;
            $cache['category_level'] = $level ? max($level) : 0;
        }

        // 共享模块
        if ($cache['share'] && $this->cat_share_lock[$siteid]) {
            // 删除缓存
            \Phpcmf\Service::L('cache')->clear('module-'.$siteid.'-share');
            // 写入缓存
            \Phpcmf\Service::L('cache')->set_file('module-'.$siteid.'-share', [
                'id' => 0,
                'name' => '共享',
                'share' => 1,
                'dirname' => 'share',
                'category' => $CAT,
                'category_dir' => $CAT_DIR,
                'category_field' => $cache['category_field'],
                'category_level' => $cache['category_level'],
                'category_data_field' => $cache['category_data_field'],
            ]);
            $this->cat_share_lock[$siteid] = 0;
        }

        return $cache;
    }

    // 缓存
    public function cache($siteid = SITE_ID, $module = null) {

        // 重置缓存
        $this->cat_share_lock[$siteid] = 1;
        \Phpcmf\Service::L('cache')->set_file('module-'.$siteid, []);
        \Phpcmf\Service::L('cache')->set_file('module-'.$siteid.'-share', []);
        \Phpcmf\Service::L('cache')->set_file('module-'.$siteid.'-content', []);

        $all = $content = [];
        $module = $this->get_module_info($module);
        $menu_model = \Phpcmf\Service::M('Menu');
        if ($module) {
            foreach ($module as $mdir => $data) {
                $cache = $data;
                $config = $data['config'];
                $cache['cname'] = isset($config['cname']) && $config['cname'] ? $config['cname'] : $cache['name'];
                //unset($cache['config']);
                // 当前站点安装过就缓存它
                if (isset($data['site'][$siteid]) && $data['site'][$siteid]) {
                    $cache['html'] = $data['site'][$siteid]['html'];
                    $cache['title'] = $data['site'][$siteid]['module_title'] ? $data['site'][$siteid]['module_title'] : $data['name'];
                    $cache['urlrule'] = $data['site'][$siteid]['urlrule'];
                    // 绑定的域名
                    $cache['domain'] = $data['site'][$siteid]['domain'] ? dr_http_prefix($data['site'][$siteid]['domain'].'/') : '';
                    $cache['mobile_domain'] = $data['site'][$siteid]['mobile_domain'] ? dr_http_prefix($data['site'][$siteid]['mobile_domain'].'/') : '';
                    // 补全url
                    $cache['url'] = \Phpcmf\Service::L('router')->module_url($cache, $siteid); // 模块的URL地址
                    $cache['murl'] = $data['site'][$siteid]['mobile_domain'] ? $cache['mobile_domain'] : $cache['url']; // 模块的URL地址
                    // 模块的自定义字段
                    $cache['field'] = [];
                    $field = $this->db->table('field')->where('disabled', 0)->where('relatedid', intval($data['id']))->where('relatedname', 'module')->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
                    if ($field) {
                        foreach ($field as $f) {
                            $f['setting'] = dr_string2array($f['setting']);
                            $cache['field'][$f['fieldname']] = $f;
                        }
                    }
                } else {
                    continue; // 当前站点没有安装模块
                }

                // 系统模块
                $cache['system'] = $config['system'];

                // 模块的栏目分类
                $cache['category'] = [];
                $cache['category_dir'] = [];
                $cache['category_field'] = [];
                $cache['category_level'] = 0;
                $cache['category_data_field'] = 0;

                if (isset($config['hcategory']) && $config['hcategory']) {
                    // 不使用栏目功能
                } else {
                    // 如果是共享共享栏目就查询share表
                    $cache = $this->_get_category_cache($siteid, $cache);
                }

                // 模块表单
                $cache['form'] = [];
                $form = $this->table('module_form')->where('module', $mdir)->where('disabled', 0)->order_by('id ASC')->getAll();
                if ($form) {
                    foreach ($form as $t) {
                        $t['field'] = [];
                        // 模块表单的自定义字段
                        $field = $this->db->table('field')->where('disabled', 0)->where('relatedid', intval($t['id']))->where('relatedname', 'mform-'.$mdir)->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
                        if ($field) {
                            foreach ($field as $f) {
                                $f['setting'] = dr_string2array($f['setting']);
                                $t['field'][$f['fieldname']] = $f;
                            }
                        }
                        $t['setting'] = dr_string2array($t['setting']);
                        // 排列table字段顺序
                        $t['setting']['list_field'] = dr_list_field_order($t['setting']['list_field']);
                        $cache['form'][$t['table']] = $t;
                    }
                }

                // 搜索验证
                !$cache['setting']['search']['use'] && $cache['setting']['search'] = [];

                // 评论验证
                if ($cache['comment']['use']) {
                    $cache['comment']['field'] = [];
                    // 模块表单的自定义字段
                    $field = $this->db->table('field')->where('disabled', 0)->where('relatedname', 'comment-module-'.$mdir)->orderBy('displayorder ASC, id ASC')->get()->getResultArray();
                    if ($field) {
                        foreach ($field as $f) {
                            $f['setting'] = dr_string2array($f['setting']);
                            $cache['comment']['field'][$f['fieldname']] = $f;
                        }
                    }
                    if ($cache['comment']['review']['use']) {
                        // 格式化点评
                        if ($cache['comment']['review']['value']) {
                            $tmp = [];
                            foreach ($cache['comment']['review']['value'] as $i => $op) {
                                $op['name'] && $tmp[$i] = $op['name'];
                            }
                            $cache['comment']['review']['value'] = $tmp;
                        }
                        if ($cache['comment']['review']['option']) {
                            $tmp = [];
                            foreach ($cache['comment']['review']['option'] as $i => $op) {
                                $op['use'] && $tmp[$i] = $op['name'];
                            }
                            $cache['comment']['review']['option'] = $tmp;
                        }
                        unset($cache['comment']['review']['use']);
                    } else {
                        $cache['comment']['review'] = [];
                    }
                } else {
                    $cache['comment'] = []; // 关闭状态时清除评论缓存
                }

                // 更新内容模块菜单
                $menu_model->update_module($mdir, $config, $cache['form'], $cache['comment']['cname']);
                !$cache['title'] && $cache['title'] = $cache['name'];

                // 执行模块自己的缓存程序
                if (is_file(dr_get_app_dir($mdir).'Config/Cache.php')) {
                    require dr_get_app_dir($mdir).'Config/Cache.php';
                }

                // 全部模块
                $all[$mdir] = [
                    'url' => $cache['url'],
                    'murl' => $cache['murl'],
                    'name' => $cache['name'],
                    'icon' => $cache['icon'],
                    'title' => $cache['title'],
                    'share' => $cache['share'],
                    'system' => $config['system'],
                    'hlist' => (int)$config['hlist'],
                    'hcategory' => (int)$config['hcategory'],
                    'scategory' => (int)$config['scategory'],
                    'search' => $cache['setting']['search']['use'] ? 1 : 0,
                    'dirname' => $mdir,
                    'comment' => $cache['comment'] ? 1 : 0,
                    'is_index_html' => $cache['setting']['module_index_html'] ? 1 : 0,
                ];

                // 内容模块
                in_array($config['system'], [1, 2]) && $content[$mdir] = $all[$mdir];

                // 删除缓存
                \Phpcmf\Service::L('cache')->clear('module-'.$siteid.'-'.$mdir);

                // 写入缓存
                \Phpcmf\Service::L('cache')->set_file('module-'.$siteid.'-'.$mdir, $cache);
            }
        }

        // 写入缓存
        \Phpcmf\Service::L('cache')->set_file('module-'.$siteid, $all);
        \Phpcmf\Service::L('cache')->set_file('module-'.$siteid.'-content', $content);

        return;
    }
}