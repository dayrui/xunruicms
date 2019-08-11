<?php
/**
 * 系统模块表
 */

return [
    
    '_draft' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `cid` int(10) unsigned NOT NULL COMMENT '内容id',
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `content` mediumtext NOT NULL COMMENT '具体内容',
      `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
      PRIMARY KEY `id` (`id`),
      KEY `uid` (`uid`),
      KEY `cid` (`cid`),
      KEY `catid` (`catid`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容草稿表';",

    '_verify' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL,
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `isnew` TINYINT(1) unsigned NOT NULL COMMENT '是否新增',
      `author` varchar(50) NOT NULL COMMENT '作者',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `status` tinyint(2) NOT NULL COMMENT '审核状态',
      `content` mediumtext NOT NULL COMMENT '具体内容',
      `backuid` mediumint(8) unsigned NOT NULL COMMENT '操作人uid',
      `backinfo` text NOT NULL COMMENT '操作退回信息',
      `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
      UNIQUE KEY `id` (`id`),
      KEY `uid` (`uid`),
      KEY `catid` (`catid`),
      KEY `status` (`status`),
      KEY `inputtime` (`inputtime`),
      KEY `backuid` (`backuid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容审核表';",

    '_hits' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL COMMENT '文章id',
      `hits` int(10) unsigned NOT NULL COMMENT '总点击数',
      `day_hits` int(10) unsigned NOT NULL COMMENT '本日点击',
      `week_hits` int(10) unsigned NOT NULL COMMENT '本周点击',
      `month_hits` int(10) unsigned NOT NULL COMMENT '本月点击',
      `year_hits` int(10) unsigned NOT NULL COMMENT '年点击量',
      UNIQUE KEY `id` (`id`),
      KEY `day_hits` (`day_hits`),
      KEY `week_hits` (`week_hits`),
      KEY `month_hits` (`month_hits`),
      KEY `year_hits` (`year_hits`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='时段点击量统计';",

    '_index' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `status` tinyint(2) NOT NULL COMMENT '审核状态',
      `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `catid` (`catid`),
      KEY `status` (`status`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容索引表';",

    '_category' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
        `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
        `pid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
        `pids` varchar(255) NOT NULL COMMENT '所有上级id',
        `name` varchar(30) NOT NULL COMMENT '栏目名称',
        `dirname` varchar(30) NOT NULL COMMENT '栏目目录',
        `pdirname` varchar(100) NOT NULL COMMENT '上级目录',
        `child` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有下级',
        `childids` text NOT NULL COMMENT '下级所有id',
        `thumb` varchar(255) NOT NULL COMMENT '栏目图片',
        `show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
        `setting` text NOT NULL COMMENT '属性配置',
        `displayorder` mediumint(8) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `show` (`show`),
        KEY `module` (`pid`,`displayorder`,`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='栏目表';",

    '_category_data' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` int(3) unsigned NOT NULL COMMENT '栏目id',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `catid` (`catid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='栏目模型表';",

    '_category_data_0' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `catid` (`catid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='栏目模型表的附表';",
    
    '_flag' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `flag` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '文档标记id',
      `id` int(10) unsigned NOT NULL COMMENT '文档内容id',
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      KEY `flag` (`flag`,`id`,`uid`),
      KEY `catid` (`catid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='标记表';
    ",

    '_search' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` varchar(32) NOT NULL,
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `params` text NOT NULL COMMENT '参数数组',
      `keyword` varchar(255) NOT NULL COMMENT '关键字',
      `contentid` mediumtext NOT NULL COMMENT 'id集合',
      `inputtime` int(10) unsigned NOT NULL COMMENT '搜索时间',
      PRIMARY KEY (`id`),
      UNIQUE KEY `id` (`id`),
      KEY `catid` (`catid`),
      KEY `keyword` (`keyword`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='搜索表';
    ",


    '_recycle' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `cid` int(10) unsigned NOT NULL COMMENT '内容id',
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` tinyint(3) unsigned NOT NULL COMMENT '栏目id',
      `content` mediumtext NOT NULL COMMENT '具体内容',
      `result` text NOT NULL COMMENT '删除理由',
      `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `cid` (`cid`),
      KEY `catid` (`catid`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容回收站表';
    ",

    '_time' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `content` mediumtext NOT NULL COMMENT '具体内容',
      `result` text NOT NULL COMMENT '处理结果',
      `posttime` int(10) unsigned NOT NULL COMMENT '定时发布时间',
      `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `catid` (`catid`),
      KEY `posttime` (`posttime`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容定时发布表';
    ",
    
];