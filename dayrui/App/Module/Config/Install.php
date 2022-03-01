<?php

foreach ($this->site as $siteid) {
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
          `setting` mediumtext NOT NULL COMMENT '属性配置',
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

    $this->db->simpleQuery("DROP TABLE IF EXISTS `".$this->dbprefix($siteid.'_share_index')."`");
    $this->db->simpleQuery(dr_format_create_sql("
        CREATE TABLE IF NOT EXISTS `".$this->dbprefix($siteid.'_share_index')."` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `mid` varchar(20) NOT NULL COMMENT '模块目录',
          PRIMARY KEY (`id`),
          KEY `mid` (`mid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='共享模块内容索引表';
        "));
}
