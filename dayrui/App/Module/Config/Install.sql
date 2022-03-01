DROP TABLE IF EXISTS `{dbprefix}urlrule`;
CREATE TABLE IF NOT EXISTS `{dbprefix}urlrule` (
    `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `type` tinyint(1) unsigned NOT NULL COMMENT '规则类型',
    `name` varchar(50) NOT NULL COMMENT '规则名称',
    `value` text NOT NULL COMMENT '详细规则',
    PRIMARY KEY (`id`),
    KEY `type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='URL规则表' ;

DROP TABLE IF EXISTS `{dbprefix}module`;
CREATE TABLE IF NOT EXISTS `{dbprefix}module` (
    `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `site` text NULL COMMENT '站点划分',
    `dirname` varchar(50) NOT NULL COMMENT '目录名称',
    `share` tinyint(1) unsigned DEFAULT NULL COMMENT '是否共享模块',
    `setting` text NULL COMMENT '配置信息',
    `comment` text NULL COMMENT '评论信息',
    `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '禁用？',
    `displayorder` smallint(5) DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `dirname` (`dirname`),
    KEY `disabled` (`disabled`),
    KEY `displayorder` (`displayorder`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='模块表';