<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
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
                        'url' => '/index.php?s='.$t['dirname'].'&c=show&id=',
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
                    // 栏目模型附表
                    $table = $prefix.'_category_data_0';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块点击量表
                    $table = $prefix.'_hits';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块评论表
                    $table = $prefix.'_comment';
                    $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                    // 模块表单
                    $form = $this->table('module_form')->where('module', $t['dirname'])->order_by('id ASC')->getAll();
                    if ($form) {
                        foreach ($form as $f) {
                            // 主表
                            $table = $prefix.'_form_'.$f['table'];
                            $this->db->tableExists($table) && $cache[$table] = $this->db->getFieldNames($table);
                            // 付款表
                            $paytable['mform-'.$t['dirname'].'-'.$f['id']] = [
                                'table' => $table,
                                'name' => 'title',
                                'thumb' => 'thumb',
                                'url' => '/index.php?s='.$t['dirname'].'&c='.$f['table'].'&m=show&id=',
                                'username' => 'author',
                            ];
                        }
                    }
                }


            }
        }

        // 网站表单
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
                    'url' => '/index.php?s=form&c='.$t['table'].'&m=show&id=',
                    'username' => 'author',
                ];
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

        $data['name'] = dr_safe_filename($data['name']);

        $pre = $this->dbprefix(SITE_ID.'_form');
        $sql = [
            "
			CREATE TABLE IF NOT EXISTS `".$pre.'_'.$data['table']."` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `uid` int(10) unsigned DEFAULT 0 COMMENT '录入者uid',
			  `author` varchar(100) DEFAULT NULL COMMENT '录入者账号',
			  `title` varchar(255) DEFAULT NULL COMMENT '主题',
			  `inputip` varchar(50) DEFAULT NULL COMMENT '录入者ip',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
	          `status` tinyint(1) DEFAULT NULL COMMENT '状态值',
			  `displayorder` int(10) NOT NULL DEFAULT '0' COMMENT '排序值',
	          `tableid` smallint(5) unsigned NOT NULL COMMENT '附表id',
			  PRIMARY KEY `id` (`id`),
			  KEY `uid` (`uid`),
			  KEY `status` (`status`),
			  KEY `inputtime` (`inputtime`),
			  KEY `displayorder` (`displayorder`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='".$data['name']."表单表';"
            ,
            "CREATE TABLE IF NOT EXISTS `".$pre.'_'.$data['table']."_data_0` (
			  `id` int(10) unsigned NOT NULL,
			  `uid` int(10) unsigned DEFAULT 0 COMMENT '录入者uid',
			  UNIQUE KEY `id` (`id`),
			  KEY `uid` (`uid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='".$data['name']."表单附表';"
        ];

        foreach ($sql as $s) {
            $this->db->simpleQuery(dr_format_create_sql($s));
        }

        // 默认字段
        $this->db->table('field')->insert(array(
            'name' => '主题',
            'fieldname' => 'title',
            'fieldtype' => 'Text',
            'relatedid' => $data['id'],
            'relatedname' => 'form-'.SITE_ID,
            'isedit' => 1,
            'ismain' => 1,
            'ismember' => 1,
            'issystem' => 1,
            'issearch' => 1,
            'disabled' => 0,
            'setting' => dr_array2string(array(
                'option' => array(
                    'width' => 300, // 表单宽度
                    'fieldtype' => 'VARCHAR', // 字段类型
                    'fieldlength' => '255' // 字段长度
                ),
                'validate' => array(
                    'xss' => 1, // xss过滤
                    'required' => 1, // 表示必填
                )
            )),
            'displayorder' => 0,
        ));
    }
    
    // 系统字段
    public function sys_field_form() {
        
        return [
            1 => ['id', 'title', 'uid', 'inputip', 'inputtime', 'displayorder', 'tableid'],
            0 => ['id', 'uid'],
        ];
    }
    // 自定义字段
    public function my_field_form($form) {
        
        if (!$form) {
            return [];
        }

        
    }
    
    // 删除表单
    public function delete_form($data) {

        $id = intval($data['id']);
        $pre = $this->dbprefix(SITE_ID.'_form');
        
        // 删除字段
        $this->db->table('field')->where('relatedid', $id)->where('relatedname', 'form-'.SITE_ID)->delete();

        // 删除表
        $table = $pre.'_'.$data['table'];
        $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.'`');

        // 删除附表
        for ($i = 0; $i < 200; $i ++) {
            if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                break;
            }
            $this->db->simpleQuery('DROP TABLE IF EXISTS '.$table.'_data_'.$i);
        }

        // 删除菜单
        $this->db->table('admin_menu')->where('mark', 'form-'.$data['table'])->delete();
        $this->db->table('member_menu')->where('mark', 'form-'.$data['table'])->delete();
        
        // 删除记录
        $this->db->table(SITE_ID.'_form')->delete($id);
        
    }


    // 模块表单--------------------------------------------------------------------

    // 创建
    public function create_module_form($data) {

        $data['name'] = dr_safe_filename($data['name']);

        $sql = [
            "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
              `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者id',
			  `author` varchar(50) NOT NULL COMMENT '作者名称',
			  `inputip` varchar(30) DEFAULT NULL COMMENT '录入者ip',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
			  `title` varchar(255) DEFAULT NULL COMMENT '表单主题',
	          `status` tinyint(1) DEFAULT NULL COMMENT '状态值',
	          `tableid` smallint(5) unsigned NOT NULL COMMENT '附表id',
	          `displayorder` int(10) DEFAULT NULL COMMENT '排序值',
			  PRIMARY KEY `id` (`id`),
			  KEY `cid` (`cid`),
			  KEY `uid` (`uid`),
              KEY `catid` (`catid`),
			  KEY `author` (`author`),
			  KEY `status` (`status`),
			  KEY `displayorder` (`displayorder`),
			  KEY `inputtime` (`inputtime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模块表单".$data['name']."表';"
            ,
            "CREATE TABLE IF NOT EXISTS `{tablename}_data_0` (
			  `id` int(10) unsigned NOT NULL,
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
              `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者id',
			  UNIQUE KEY `id` (`id`),
			  KEY `cid` (`cid`),
              KEY `catid` (`catid`),
			  KEY `uid` (`uid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模块表单".$data['name']."附表';"
        ];

        // 为全部站点模块创建表单
        foreach (\Phpcmf\Service::C()->site_info as $sid => $v) {
            $par = $this->dbprefix(dr_module_table_prefix($data['module'], $sid)); // 父级表
            $pre = $par.'_form_'.$data['table']; // 当前表
            // 判断模块是否安装过
            if (!$this->is_table_exists($par)) {
                continue;
            }
            foreach ($sql as $s) {
                $this->db->simpleQuery(str_replace('{tablename}', $pre, dr_format_create_sql($s)));
            }
            // 加上统计字段
            if ($this->is_field_exists($par, $data['table']."_total")) {
                continue;
            }
            $this->db->simpleQuery("ALTER TABLE `{$par}` ADD `".$data['table']."_total` INT(10) UNSIGNED NULL DEFAULT '0' COMMENT '表单".$data['name']."统计' , ADD INDEX (`".$data['table']."_total`) ;");
        }
        
        // 删除原有的冗余字段
        $this->db->table('field')->where('relatedid', $data['id'])->where('relatedname', 'mform-'.$data['module'])->delete();

        // 默认字段
        $this->db->table('field')->insert(array(
            'name' => '主题',
            'fieldname' => 'title',
            'fieldtype' => 'Text',
            'relatedid' => $data['id'],
            'relatedname' => 'mform-'.$data['module'],
            'isedit' => 1,
            'ismain' => 1,
            'ismember' => 1,
            'issystem' => 1,
            'issearch' => 1,
            'disabled' => 0,
            'setting' => dr_array2string(array(
                'option' => array(
                    'width' => 300, // 表单宽度
                    'fieldtype' => 'VARCHAR', // 字段类型
                    'fieldlength' => '255' // 字段长度
                ),
                'validate' => array(
                    'xss' => 1, // xss过滤
                    'required' => 1, // 表示必填
                )
            )),
            'displayorder' => 0,
        ));

    }
    
    // 删除模块表单
    public function delete_module_form($data) {

        $id = intval($data['id']);
        $pre = $this->dbprefix(SITE_ID.'_'.$data['module'].'_form');

        // 判断模块是否安装过
        if (!$this->is_table_exists($pre)) {
            return;
        }

        // 删除字段
        $this->db->table('field')->where('relatedid', $id)->where('relatedname', 'mform-'.$data['module'])->delete();

        // 删除表
        $table = $pre.'_'.$data['table'];
        $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.'`');

        // 删除附表
        for ($i = 0; $i < 200; $i ++) {
            if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                break;
            }
            $this->db->simpleQuery('DROP TABLE IF EXISTS '.$table.'_data_'.$i);
        }


        // 模块表统计字段删除
        $par = $this->dbprefix(SITE_ID.'_'.$data['module']);

        if ($this->is_field_exists($par, $data['table']."_total")) {
            $this->db->simpleQuery("ALTER TABLE `{$par}` DROP `".$data['table']."_total` , DROP INDEX (`".$data['table']."_total`) ;");
        }

        // 删除记录
        $this->db->table('module_form')->delete($id);
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
          `name` varchar(30) NOT NULL COMMENT '栏目名称',
          `dirname` varchar(30) NOT NULL COMMENT '栏目目录',
          `pdirname` varchar(100) NOT NULL COMMENT '上级目录',
          `child` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有下级',
          `childids` text NOT NULL COMMENT '下级所有id',
          `domain` varchar(50) DEFAULT NULL COMMENT '绑定电脑域名',
          `mobile_domain` varchar(50) DEFAULT NULL COMMENT '绑定手机域名',
          `thumb` varchar(255) NOT NULL COMMENT '栏目图片',
          `show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
          `content` mediumtext NOT NULL COMMENT '单页内容',
          `setting` text NOT NULL COMMENT '属性配置',
          `displayorder` smallint(5) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `mid` (`mid`),
          KEY `tid` (`tid`),
          KEY `show` (`show`),
          KEY `dirname` (`dirname`),
          KEY `module` (`pid`,`displayorder`,`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='共享模块栏目表';
        "));
        }

        $this->db->simpleQuery("DROP TABLE IF EXISTS `".$this->dbprefix($siteid.'_form')."`");
        $this->db->simpleQuery(dr_format_create_sql("
		CREATE TABLE IF NOT EXISTS `".$this->dbprefix($siteid.'_form')."` (
		  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(50) NOT NULL COMMENT '名称',
		  `table` varchar(50) NOT NULL COMMENT '表名',
		  `setting` text DEFAULT NULL COMMENT '配置信息',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `table` (`table`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='表单模型表';
		"));




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
        $local = dr_dir_map(dr_get_app_list(), 1);
        foreach ($local as $dir) {
            $path = dr_get_app_dir($dir);
            if (is_file($path.'install.lock') && is_file($path.'Config/Install_site.php')) {
                $sql = file_get_contents($path.'Config/Install_site.sql');
                $this->_query(str_replace('{dbprefix}',  $this->preifx.$siteid.'_', $sql));
            }
        }

    }


    //--------------------------------------------------------------------
    
    

    //--------------------------------------------------------------------
    
    

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------

    //--------------------------------------------------------------------



    // 或导出数据表的字段名称及自定义配置信息
    public function get_export_field_name($table, $update = 0) {

        // 获取表配置
        $row = $this->db->table('export')->where('name', $table)->get()->getRowArray();
        !$row && $this->table('export')->replace([
            'name' => $table,
            'value' => ''
        ]);
        $value = $row['value'] ? dr_string2array($row['value']) : [];

        // 获取最新的字段信息
        if ($update) {
            $tables = explode(',', $table);
            foreach ($tables as $tt) {
                $field = $this->db->query('SHOW FULL COLUMNS FROM `'.$tt.'`')->getResultArray();
                if (!$field) {
                    return $value;
                }
                foreach ($field as $t) {
                    if (!isset($value[$t['Field']])) {
                        $value[$t['Field']] = [
                            'use' => 1,
                            'name' => $t['Comment'] ? $t['Comment'] : $t['Field'],
                            'func' => '',
                        ];
                    }
                }
            }
            $this->table('export')->replace([
                'name' => $table,
                'value' => dr_array2string($value)
            ]);
        }

        return $value;
    }

    /// 存储导出数据表的字段名称及自定义配置信息
    public function save_export_field_name($table, $data) {

        $this->table('export')->replace([
            'name' => $table,
            'value' => dr_array2string($data)
        ]);

    }
    
}