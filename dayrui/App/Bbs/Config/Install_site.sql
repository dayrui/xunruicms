DROP TABLE IF EXISTS `{dbprefix}bbs_cat_count`;
CREATE TABLE IF NOT EXISTS `{dbprefix}bbs_cat_count` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catid` int(10) NOT NULL,
  `today_subjects` int(10) NOT NULL,
  `today_replys` int(10) NOT NULL,
  `subjects` int(10) NOT NULL,
  `replys` int(10) NOT NULL,
  `last_title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `last_url` varchar(255) NOT NULL,
  `last_cid` int(10) NOT NULL,
  `last_username` varchar(100) NOT NULL,
  `last_uid` int(10) NOT NULL,
  `last_time` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='板块统计';