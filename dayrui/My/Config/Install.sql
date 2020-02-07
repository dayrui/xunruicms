REPLACE INTO `{dbprefix}urlrule` VALUES(0, 3, '共享栏目测试规则', '{"list":"list-{dirname}.html","list_page":"list-{dirname}-{page}.html","show":"show-{id}.html","show_page":"show-{id}-{page}.html","catjoin":"\\/"}');
REPLACE INTO `{dbprefix}urlrule` VALUES(0, 2, '共享模块测试规则', '{"search":"{modname}\\/search.html","search_page":"{modname}\\/search\\/{param}.html","catjoin":"\\/"}');
REPLACE INTO `{dbprefix}urlrule` VALUES(0, 1, '独立模块测试规则', '{"module":"{modname}.html","list":"{modname}\\/list\\/{id}.html","list_page":"{modname}\\/list\\/{id}\\/{page}.html","show":"{modname}\\/show\\/{id}.html","show_page":"{modname}\\/show\\/{id}\\/{page}.html","search":"{modname}\\/search.html","search_page":"{modname}\\/search\\/{param}.html","catjoin":"\\/"}');

REPLACE INTO `{dbprefix}member_group` VALUES(1, '注册用户', 0.00, 0, 0, 1, 1, '{\"level\":{\"auto\":\"0\",\"unit\":\"0\",\"price\":\"0\"},\"verify\":\"0\"}', 0);
REPLACE INTO `{dbprefix}member_group_index` VALUES(1, 1, 1, 0, 0, 0);


REPLACE INTO `{dbprefix}member_setting` VALUES('config', '{\"edit_name\":\"1\",\"edit_mobile\":\"1\",\"logintime\":\"\",\"verify_msg\":\"\",\"pagesize\":\"\",\"pagesize_mobile\":\"\",\"pagesize_api\":\"\"}');
REPLACE INTO `{dbprefix}member_setting` VALUES('login', '{\"code\":\"1\"}');
REPLACE INTO `{dbprefix}member_setting` VALUES('register', '{\"close\":\"0\",\"groupid\":\"1\",\"field\":[\"username\",\"email\"],\"cutname\":\"0\",\"unprefix\":\"\",\"code\":\"1\",\"verify\":\"\",\"preg\":\"\",\"notallow\":\"\"}');
