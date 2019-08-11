<?php
/**
 * 评论表结构
 */

return [

    '_comment' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
      `cid` int(10) unsigned NOT NULL COMMENT '关联id',
      `cuid` int(10) unsigned NOT NULL COMMENT '关联uid',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `orderid` BIGINT(18) unsigned NOT NULL COMMENT '订单id',
      `uid` mediumint(8) unsigned DEFAULT '0' COMMENT '评论者ID',
      `author` varchar(250) DEFAULT NULL COMMENT '评论者账号',
      `content` text COMMENT '评论内容',
      `support` int(10) unsigned DEFAULT '0' COMMENT '支持数',
      `oppose` int(10) unsigned DEFAULT '0' COMMENT '反对数',
      `avgsort` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均分',
      `sort1` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort2` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort3` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort4` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort5` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort6` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort7` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort8` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `sort9` tinyint(1) unsigned DEFAULT '0' COMMENT '评分值',
      `reply` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复id',
      `in_reply` tinyint(1) unsigned DEFAULT '0' COMMENT '是否存在回复',
      `status` smallint(1) unsigned DEFAULT '0' COMMENT '审核状态',
      `inputip` varchar(50) DEFAULT NULL COMMENT '录入者ip',
      `inputtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '录入时间',
      PRIMARY KEY (`id`),
      KEY `uid` (`uid`),
      KEY `cid` (`cid`),
      KEY `catid` (`catid`),
      KEY `orderid` (`orderid`),
      KEY `reply` (`reply`),
      KEY `support` (`support`),
      KEY `oppose` (`oppose`),
      KEY `avgsort` (`avgsort`),
      KEY `status` (`status`),
      KEY `aa` (`cid`,`status`,`inputtime`),
      KEY `inputtime` (`inputtime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='评论内容表';",

    '_comment_index' => "CREATE TABLE IF NOT EXISTS `{tablename}` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
      `cid` int(10) unsigned NOT NULL COMMENT '内容id',
      `catid` mediumint(8) unsigned NOT NULL COMMENT '栏目id',
      `support` int(10) unsigned DEFAULT '0' COMMENT '支持数',
      `oppose` int(10) unsigned DEFAULT '0' COMMENT '反对数',
      `comments` int(10) unsigned DEFAULT '0' COMMENT '评论数',
      `avgsort` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均分',
      `sort1` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort2` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort3` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort4` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort5` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort6` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort7` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort8` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `sort9` decimal(4,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '选项分数',
      `tableid` smallint(5) unsigned DEFAULT '0' COMMENT '附表id',
      PRIMARY KEY (`id`),
      KEY `cid` (`cid`),
      KEY `catid` (`catid`),
      KEY `support` (`support`),
      KEY `oppose` (`oppose`),
      KEY `comments` (`comments`),
      KEY `avgsort` (`avgsort`),
      KEY `tableid` (`tableid`)
    ) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='评论索引表';",

    
];