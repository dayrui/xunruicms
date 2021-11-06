<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 数据表
class Table extends \Phpcmf\Model
{

    // 表结构缓存
    public function cache($siteid = SITE_ID, $module = null) {

        $cache = [];
        $paytable = []; // 付款表名
        // 生成模块表结构
        !$module && $module = $this->table('module')->getAll();
        if ($module) {
            $is_module_form = $this->is_table_exists('module_form');
            foreach ($module as $t) {
                // 模块主表
                $table = dr_module_table_prefix($t['dirname'], $siteid);
                $prefix = $this->dbprefix($table);
                // 判断是否存在表
                if (!$this->db->tableExists($prefix)) {
                    continue;
                }
                $main_field = $this->db->getFieldNames($prefix);
                if ($main_field) {
                    // 付款表
                    $paytable['module-'.$t['id']] = [
                        'table' => $table,
                        'name' => 'title',
                        'thumb' => 'thumb',
                        'url' => dr_web_prefix('index.php?s='.$t['dirname'].'&c=show&id='),
                        'username' => 'author',
                    ];
                    // 模块表
                    $cache[$prefix] = $main_field;
                    // 模块附表
                    $table = $prefix.'_data_0';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 栏目模型主表
                    $table = $prefix.'_category_data';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块点击量表
                    $table = $prefix.'_hits';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块评论表
                    $table = $prefix.'_comment';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块表单
                    if ($is_module_form) {
                        $form = $this->table('module_form')->where('module', $t['dirname'])->order_by('id ASC')->getAll();
                        if ($form) {
                            foreach ($form as $f) {
                                // 主表
                                $table = $prefix . '_form_' . $f['table'];
                                $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                                // 付款表
                                $paytable['mform-' . $t['dirname'] . '-' . $f['id']] = [
                                    'table' => $table,
                                    'name' => 'title',
                                    'thumb' => 'thumb',
                                    'url' => dr_web_prefix('index.php?s=' . $t['dirname'] . '&c=' . $f['table'] . '&m=show&id='),
                                    'username' => 'author',
                                ];
                            }
                        }
                    }
                }
            }
        }

        // 网站表单
        if ($this->is_table_exists($siteid.'_form')) {
            $form = $this->table($siteid.'_form')->getAll();
            if ($form) {
                foreach ($form as $t) {
                    // 主表
                    $table = $siteid.'_form_'.$t['table'];
                    $prefix = $this->dbprefix($table);
                    $cache[$prefix] = $this->db->getFieldNames($prefix);
                    // 付款表
                    $paytable['form-'.$siteid.'-'.$t['id']] = [
                        'table' => $table,
                        'name' => 'title',
                        'thumb' => 'thumb',
                        'url' => dr_web_prefix('index.php?s=form&c='.$t['table'].'&m=show&id='),
                        'username' => 'author',
                    ];
                }
            }
        }

        // 会员表
        $table = $this->dbprefix('member');
        $cache[$table] = $this->db->getFieldNames($table);

        // 会员附表
        $table = $this->dbprefix('member_data');
        $cache[$table] = $this->db->getFieldNames($table);

        // 缓存表结构
        \Phpcmf\Service::L('cache')->set_file('table-'.$siteid, $cache);

        // 缓存的字段类型
        //$type = ['Select', 'Checkbox', 'Radio', 'Pay', 'Pays', 'File', 'Files', 'Image', 'Images', 'Ftable'];
        $cache = [];
        $field = $this->db->table('field')->where('disabled', 0)->orderBy('id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                $f['setting'] = dr_string2array($f['setting']);
                $cache[$f['id']] = $f;
            }
        }
        \Phpcmf\Service::L('cache')->set_file('table-field', $cache);

        // 缓存付款表
        \Phpcmf\Service::L('cache')->set_file('table-pay-'.$siteid, $paytable);
        /*
         * $paytable字段主键为 自定义字段rname-rid
         * */
    }

    // 获取字段结构
    public function get_field($table) {

        return $this->db->getFieldNames($this->dbprefix($table));
    }
    // 获取缓存的字段结构
    public function get_cache_field($table) {

        $tableinfo = \Phpcmf\Service::L('cache')->get('table-'.SITE_ID);
        if (!$tableinfo) {
            // 没有表结构缓存时返回空
            return [];
        }

        return isset($tableinfo[$this->dbprefix($table)]) ? $tableinfo[$this->dbprefix($table)] : [];
    }
    
    // 执行批量sql
    public function _query($sql, $replace = []) {

        $replace[0][] = '{dbprefix}';
        $replace[1][] = $this->db->DBPrefix;

        $todo = [];
        $count = 0;
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', str_replace($replace[0], $replace[1], $sql))));
        if ($sql_data) {
            foreach($sql_data as $query){
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach($queries as $query) {
                    $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                }
                $ret = trim($ret);
                if (!$ret) {
                    continue;
                }
                if ($this->db->simpleQuery(dr_format_create_sql($ret))) {
                    $todo[] = $ret;
                    $count++;
                } else {
                    $rt = $this->db->error();
                    return dr_return_data(0, $rt['message'].'<br> '.$ret);
                }
            }
        }
        
        return dr_return_data(1, '', [$count, $todo]);
    }


    // 网站表单--------------------------------------------------------------------

    // 创建
    public function create_form($data) {
        if (dr_is_app('form')) {
            \Phpcmf\Service::M('form', 'form')->create_form($data);
        }
    }

    // 删除表单
    public function delete_form($data) {
        if (dr_is_app('form')) {
            \Phpcmf\Service::M('form', 'form')->delete_form_table($data);
        }

    }

    // 模块表单--------------------------------------------------------------------

    // 创建
    public function create_module_form($data) {
        if (dr_is_app('mform')) {
            \Phpcmf\Service::M('mform', 'mform')->create_module_form($data);
        }
    }
    
    // 删除模块表单
    public function delete_module_form($data) {
        if (dr_is_app('mform')) {
            \Phpcmf\Service::M('mform', 'mform')->delete_module_form($data);
        }
    }

    // 站点--------------------------------------------------------------------

    // 创建站点
    public function create_site($siteid) {

        // 创建数据表
        if ($siteid > 1) {
            // 复制站点1的栏目结构
            $sql = $this->db->query("SHOW CREATE TABLE `".$this->dbprefix('1_share_category')."`")->getRowArray();
            $sql = str_replace(
                array($sql['Table'], 'CREATE TABLE'),
                array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                $sql['Create Table']
            );
            $this->db->simpleQuery(str_replace('{tablename}', $this->dbprefix($siteid.'_share_category'), dr_format_create_sql($sql)));
            $this->db->table($siteid.'_share_category')->truncate();
        } else {
            $this->db->simpleQuery("DROP TABLE IF EXISTS `".$this->dbprefix($siteid.'_share_category')."`");
            $this->db->simpleQuery(dr_format_create_sql("
        CREATE TABLE IF NOT EXISTS `".$this->dbprefix($siteid.'_share_category')."` (
          `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
          `tid` tinyint(1) NOT NULL COMMENT '栏目类型，0单页，1模块，2外链',
          `pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
          `mid` varchar(20) NOT NULL COMMENT '模块目录',
          `pids` varchar(255) NOT NULL COMMENT '所有上级id',
          `name` varchar(255) NOT NULL COMMENT '栏目名称',
          `dirname` varchar(255) NOT NULL COMMENT '栏目目录',
          `pdirname` varchar(255) NOT NULL COMMENT '上级目录',
          `child` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有下级',
          `disabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否禁用',
          `ismain` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否主栏目',
          `childids` text NOT NULL COMMENT '下级所有id',
          `thumb` varchar(255) NOT NULL COMMENT '栏目图片',
          `show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
          `content` mediumtext NOT NULL COMMENT '单页内容',
          `setting` text NOT NULL COMMENT '属性配置',
          `displayorder` smallint(5) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `mid` (`mid`),
          KEY `tid` (`tid`),
          KEY `show` (`show`),
          KEY `disabled` (`disabled`),
          KEY `ismain` (`ismain`),
          KEY `dirname` (`dirname`),
          KEY `module` (`pid`,`displayorder`,`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='共享模块栏目表';
        "));
        }

        $this->db->simpleQuery("DROP TABLE IF EXISTS `".$this->dbprefix($siteid.'_share_index')."`");
        $this->db->simpleQuery(dr_format_create_sql("
        CREATE TABLE IF NOT EXISTS `".$this->dbprefix($siteid.'_share_index')."` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `mid` varchar(20) NOT NULL COMMENT '模块目录',
          PRIMARY KEY (`id`),
          KEY `mid` (`mid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='共享模块内容索引表';
        "));

        // 执行应用插件的站点sql语句
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock') && is_file($path.'Config/Install_site.php')) {
                $sql = file_get_contents($path.'Config/Install_site.sql');
                $this->_query(str_replace('{dbprefix}',  $this->preifx.$siteid.'_', $sql));
            }
        }

    }
    
}