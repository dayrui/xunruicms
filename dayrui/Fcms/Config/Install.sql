DROP TABLE IF EXISTS `{dbprefix}cron`;
CREATE TABLE IF NOT EXISTS `{dbprefix}cron` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site` int(10) NOT NULL COMMENT '站点',
  `type` varchar(100) NOT NULL COMMENT '类型',
  `value` text NOT NULL COMMENT '参数值',
  `status` tinyint(1) unsigned NOT NULL COMMENT '状态',
  `error` text DEFAULT NULL COMMENT '错误信息',
  `updatetime` int(10) unsigned NOT NULL COMMENT '执行时间',
  `inputtime` int(10) unsigned NOT NULL COMMENT '写入时间',
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `updatetime` (`updatetime`),
  KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='任务管理';

DROP TABLE IF EXISTS `{dbprefix}admin`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '管理员uid',
  `setting` mediumtext DEFAULT NULL COMMENT '相关配置',
  `usermenu` text COMMENT '自定义面板菜单，序列化数组格式',
  `history` text COMMENT '历史菜单，序列化数组格式',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='管理员表';

DROP TABLE IF EXISTS `{dbprefix}admin_notice`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_notice` (
  `id` int(10) NOT NULL COMMENT 'id' AUTO_INCREMENT,
  `site` int(5) NOT NULL COMMENT '站点id',
  `type` varchar(20) NOT NULL COMMENT '提醒类型：系统、内容、会员、应用',
  `msg` text NOT NULL COMMENT '提醒内容说明',
  `uri` varchar(100) NOT NULL COMMENT '对应的URI',
  `to_rid` varchar(100) NOT NULL COMMENT '指定角色组',
  `to_uid` varchar(100) NOT NULL COMMENT '指定管理员',
  `status` tinyint(1) NOT NULL COMMENT '未处理0，1已查看，2处理中，3处理完成',
  `uid` int(10) NOT NULL COMMENT '申请人',
  `username` varchar(100) NOT NULL COMMENT '申请人',
  `op_uid` int(10) NOT NULL COMMENT '处理人',
  `op_username` varchar(100) NOT NULL COMMENT '处理人',
  `updatetime` int(10) NOT NULL COMMENT '处理时间',
  `inputtime` int(10) NOT NULL COMMENT '提醒时间',
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `site` (`site`),
  KEY `status` (`status`),
  KEY `uid` (`uid`),
  KEY `op_uid` (`op_uid`),
  KEY `to_uid` (`to_uid`),
  KEY `to_rid` (`to_rid`),
  KEY `updatetime` (`updatetime`),
  KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='后台提醒表';

DROP TABLE IF EXISTS `{dbprefix}admin_login`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_login` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned DEFAULT NULL COMMENT '会员uid',
  `loginip` varchar(50) NOT NULL COMMENT '登录Ip',
  `logintime` int(10) unsigned NOT NULL COMMENT '登录时间',
  `useragent` varchar(255) NOT NULL COMMENT '客户端信息',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `loginip` (`loginip`),
  KEY `logintime` (`logintime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='登录日志记录';

DROP TABLE IF EXISTS `{dbprefix}admin_menu`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) unsigned NOT NULL COMMENT '上级菜单id',
  `name` text NOT NULL COMMENT '菜单语言名称',
  `site` text NOT NULL COMMENT '站点归属',
  `uri` varchar(255) DEFAULT NULL COMMENT 'uri字符串',
  `url` varchar(255) DEFAULT NULL COMMENT '外链地址',
  `mark` varchar(255) DEFAULT NULL COMMENT '菜单标识',
  `hidden` tinyint(1) unsigned DEFAULT NULL COMMENT '是否隐藏',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标标示',
  `displayorder` int(5) DEFAULT NULL COMMENT '排序值',
  PRIMARY KEY (`id`),
  KEY `list` (`pid`),
  KEY `displayorder` (`displayorder`),
  KEY `mark` (`mark`),
  KEY `hidden` (`hidden`),
  KEY `uri` (`uri`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='后台菜单表';

DROP TABLE IF EXISTS `{dbprefix}admin_min_menu`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_min_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) unsigned NOT NULL COMMENT '上级菜单id',
  `name` text NOT NULL COMMENT '菜单语言名称',
  `site` text NOT NULL COMMENT '站点归属',
  `uri` varchar(255) DEFAULT NULL COMMENT 'uri字符串',
  `url` varchar(255) DEFAULT NULL COMMENT '外链地址',
  `mark` varchar(255) DEFAULT NULL COMMENT '菜单标识',
  `hidden` tinyint(1) unsigned DEFAULT NULL COMMENT '是否隐藏',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标标示',
  `displayorder` int(5) DEFAULT NULL COMMENT '排序值',
  PRIMARY KEY (`id`),
  KEY `list` (`pid`),
  KEY `displayorder` (`displayorder`),
  KEY `mark` (`mark`),
  KEY `hidden` (`hidden`),
  KEY `uri` (`uri`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='后台简化菜单表';

DROP TABLE IF EXISTS `{dbprefix}admin_role`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_role` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `site` text NOT NULL COMMENT '允许管理的站点，序列化数组格式',
  `name` text NOT NULL COMMENT '角色组语言名称',
  `system` mediumtext NOT NULL COMMENT '系统权限',
  `module` mediumtext NOT NULL COMMENT '模块权限',
  `application` mediumtext NOT NULL COMMENT '应用权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='后台角色权限表';

DROP TABLE IF EXISTS `{dbprefix}admin_role_index`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_role_index` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned DEFAULT NULL COMMENT '会员uid',
  `roleid` mediumint(8) unsigned DEFAULT NULL COMMENT '角色组id',
  PRIMARY KEY (`id`),
  KEY (`uid`),
  KEY (`roleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='后台角色组分配表';


DROP TABLE IF EXISTS `{dbprefix}admin_setting`;
CREATE TABLE IF NOT EXISTS `{dbprefix}admin_setting` (
  `name` varchar(50) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='系统属性参数表';

DROP TABLE IF EXISTS `{dbprefix}mail_smtp`;
CREATE TABLE IF NOT EXISTS `{dbprefix}mail_smtp` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `port` mediumint(8) unsigned NOT NULL,
  `displayorder` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY (`displayorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='邮件账户表';

DROP TABLE IF EXISTS `{dbprefix}attachment`;
CREATE TABLE IF NOT EXISTS `{dbprefix}attachment` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL COMMENT '会员id',
  `author` varchar(50) NOT NULL COMMENT '会员',
  `siteid` mediumint(5) unsigned NOT NULL COMMENT '站点id',
  `related` varchar(50) NOT NULL COMMENT '相关表标识',
  `tableid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '附件副表id',
  `download` mediumint(8) NOT NULL DEFAULT '0' COMMENT '无用保留',
  `filesize` int(10) unsigned NOT NULL COMMENT '文件大小',
  `fileext` varchar(20) NOT NULL COMMENT '文件扩展名',
  `filemd5` varchar(50) NOT NULL COMMENT '文件md5值',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `author` (`author`),
  KEY `relatedtid` (`related`),
  KEY `fileext` (`fileext`),
  KEY `filemd5` (`filemd5`),
  KEY `siteid` (`siteid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='附件表';

DROP TABLE IF EXISTS `{dbprefix}attachment_remote`;
CREATE TABLE IF NOT EXISTS `{dbprefix}attachment_remote` (
  `id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) NOT NULL COMMENT '类型',
  `name` varchar(50) NOT NULL COMMENT '名称',
  `url` varchar(255) NOT NULL COMMENT '访问地址',
  `value` text NOT NULL COMMENT '参数值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='远程附件表';

DROP TABLE IF EXISTS `{dbprefix}attachment_data`;
CREATE TABLE IF NOT EXISTS `{dbprefix}attachment_data` (
  `id` mediumint(8) unsigned NOT NULL COMMENT '附件id',
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '会员id',
  `author` varchar(50) NOT NULL COMMENT '会员',
  `related` varchar(50) NOT NULL COMMENT '相关表标识',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '原文件名',
  `fileext` varchar(20) NOT NULL COMMENT '文件扩展名',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `attachment` varchar(255) NOT NULL DEFAULT '' COMMENT '服务器路径',
  `remote` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '远程附件id',
  `attachinfo` text NOT NULL COMMENT '附件信息',
  `inputtime` int(10) unsigned NOT NULL COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `inputtime` (`inputtime`),
  KEY `fileext` (`fileext`),
  KEY `remote` (`remote`),
  KEY `author` (`author`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='附件已归档表';

DROP TABLE IF EXISTS `{dbprefix}attachment_unused`;
CREATE TABLE IF NOT EXISTS `{dbprefix}attachment_unused` (
  `id` mediumint(8) unsigned NOT NULL COMMENT '附件id',
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '会员id',
  `author` varchar(50) NOT NULL COMMENT '会员',
  `siteid` mediumint(5) unsigned NOT NULL COMMENT '站点id',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '原文件名',
  `fileext` varchar(20) NOT NULL COMMENT '文件扩展名',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `attachment` varchar(255) NOT NULL DEFAULT '' COMMENT '服务器路径',
  `remote` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '远程附件id',
  `attachinfo` text NOT NULL COMMENT '附件信息',
  `inputtime` int(10) unsigned NOT NULL COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `inputtime` (`inputtime`),
  KEY `fileext` (`fileext`),
  KEY `remote` (`remote`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='未使用的附件表';

DROP TABLE IF EXISTS `{dbprefix}field`;
CREATE TABLE IF NOT EXISTS `{dbprefix}field` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT '字段别名语言',
  `fieldname` varchar(50) NOT NULL COMMENT '字段名称',
  `fieldtype` varchar(50) NOT NULL COMMENT '字段类型',
  `relatedid` smallint(5) unsigned NOT NULL COMMENT '相关id',
  `relatedname` varchar(50) NOT NULL COMMENT '相关表',
  `isedit` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否可修改',
  `ismain` tinyint(1) unsigned NOT NULL COMMENT '是否主表',
  `issystem` tinyint(1) unsigned NOT NULL COMMENT '是否系统表',
  `ismember` tinyint(1) unsigned NOT NULL COMMENT '是否会员可见',
  `issearch` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否可搜索',
  `disabled` tinyint(1) unsigned NOT NULL COMMENT '禁用？',
  `setting` mediumtext NOT NULL COMMENT '配置信息',
  `displayorder` int(5) NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `list` (`relatedid`,`disabled`,`issystem`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='字段表';

DROP TABLE IF EXISTS `{dbprefix}linkage`;
CREATE TABLE IF NOT EXISTS `{dbprefix}linkage` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '菜单名称',
  `type` tinyint(1) unsigned NOT NULL,
  `code` char(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `module` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='联动菜单表';

DROP TABLE IF EXISTS `{dbprefix}linkage_data_1`;
CREATE TABLE IF NOT EXISTS `{dbprefix}linkage_data_1` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site` mediumint(5) unsigned NOT NULL COMMENT '站点id',
  `pid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
  `pids` varchar(255) DEFAULT NULL COMMENT '所有上级id',
  `name` varchar(30) NOT NULL COMMENT '栏目名称',
  `cname` varchar(30) NOT NULL COMMENT '别名',
  `child` tinyint(1) unsigned DEFAULT NULL DEFAULT '0' COMMENT '是否有下级',
  `hidden` tinyint(1) unsigned DEFAULT NULL DEFAULT '0' COMMENT '前端隐藏',
  `childids` text DEFAULT NULL COMMENT '下级所有id',
  `displayorder` mediumint(8) DEFAULT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cname` (`cname`),
  KEY `hidden` (`hidden`),
  KEY `list` (`site`,`displayorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='联动菜单数据表';

DROP TABLE IF EXISTS `{dbprefix}site`;
CREATE TABLE IF NOT EXISTS `{dbprefix}site` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '站点名称',
  `domain` varchar(50) NOT NULL COMMENT '站点域名',
  `setting` mediumtext NOT NULL COMMENT '站点配置',
  `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '禁用？',
  `displayorder` smallint(5) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `disabled` (`disabled`),
  KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='站点表';

DROP TABLE IF EXISTS `{dbprefix}member`;
CREATE TABLE IF NOT EXISTS `{dbprefix}member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `phone` varchar(20) NOT NULL COMMENT '手机号码',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(50) NOT NULL DEFAULT '' COMMENT '加密密码',
  `login_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '登录附加验证字符',
  `salt` varchar(50) NOT NULL COMMENT '随机加密码',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `money` decimal(10,2) unsigned NOT NULL COMMENT 'RMB',
  `freeze` decimal(10,2) unsigned NOT NULL COMMENT '冻结RMB',
  `spend` decimal(10,2) unsigned NOT NULL COMMENT '消费RMB总额',
  `score` int(10) unsigned NOT NULL COMMENT '积分',
  `experience` int(10) unsigned NOT NULL COMMENT '经验值',
  `regip` varchar(200) NOT NULL COMMENT '注册ip',
  `regtime` int(10) unsigned NOT NULL COMMENT '注册时间',
  `randcode` mediumint(6) unsigned NOT NULL COMMENT '随机验证码',
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='会员表';

DROP TABLE IF EXISTS `{dbprefix}member_data`;
CREATE TABLE IF NOT EXISTS `{dbprefix}member_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_admin` tinyint(1) unsigned DEFAULT 0 COMMENT '是否是管理员',
  `is_lock` tinyint(1) unsigned DEFAULT 0 COMMENT '账号锁定标识',
  `is_verify` tinyint(1) unsigned DEFAULT 0 COMMENT '审核标识',
  `is_mobile` tinyint(1) unsigned DEFAULT 0 COMMENT '手机认证标识',
  `is_email` tinyint(1) unsigned DEFAULT 0 COMMENT '邮箱认证标识',
  `is_avatar` tinyint(1) unsigned DEFAULT 0 COMMENT '头像上传标识',
  `is_complete` tinyint(1) unsigned DEFAULT 0 COMMENT '完善资料标识',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='会员表';

REPLACE INTO `{dbprefix}admin_role` VALUES(1, '', '超级管理员', '', '', '');
REPLACE INTO `{dbprefix}admin_role` VALUES(2, '', '编辑员', '', '', '');
REPLACE INTO `{dbprefix}linkage` VALUES(1, '中国地区', 0, 'address');
REPLACE INTO `{dbprefix}linkage_data_1` VALUES(1, 1, 0, '0', '北京', 'bj', 0, 0, '1', 0);
REPLACE INTO `{dbprefix}linkage_data_1` VALUES(2, 1, 0, '0', '成都', 'cd', 0, 0, '2', 0);
