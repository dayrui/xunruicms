REPLACE INTO `{dbprefix}urlrule` VALUES(0, 3, '共享栏目测试规则', '{"list":"list-{dirname}.html","list_page":"list-{dirname}-{page}.html","show":"show-{id}.html","show_page":"show-{id}-{page}.html","catjoin":"\\/"}');
REPLACE INTO `{dbprefix}urlrule` VALUES(0, 2, '共享模块测试规则', '{"search":"{modname}\\/search.html","search_page":"{modname}\\/search\\/{param}.html","catjoin":"\\/"}');
REPLACE INTO `{dbprefix}urlrule` VALUES(0, 1, '独立模块测试规则', '{"module":"{modname}.html","list":"{modname}\\/list\\/{id}.html","list_page":"{modname}\\/list\\/{id}\\/{page}.html","show":"{modname}\\/show\\/{id}.html","show_page":"{modname}\\/show\\/{id}\\/{page}.html","search":"{modname}\\/search.html","search_page":"{modname}\\/search\\/{param}.html","catjoin":"\\/"}');

