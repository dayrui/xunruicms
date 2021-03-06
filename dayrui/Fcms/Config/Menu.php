<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 菜单配置
 */


return [

    'admin' => [

        'home' => [
            'name' => '首页',
            'icon' => 'fa fa-home',
            'left' => [
                'home-my' => [
                    'name' => '我的面板',
                    'icon' => 'fa fa-home',
                    'link' => [
                        [
                            'name' => '后台首页',
                            'icon' => 'fa fa-home',
                            'uri' => 'home/main',
                        ],
                        [
                            'name' => '资料修改',
                            'icon' => 'fa fa-user',
                            'uri' => 'api/my',
                        ],
                        [
                            'name' => '系统更新',
                            'icon' => 'fa fa-refresh',
                            'uri' => 'cache/index',
                        ],
                        [
                            'name' => '应用市场',
                            'icon' => 'fa fa-puzzle-piece',
                            'uri' => 'api/app',
                        ],
                    ]
                ],
            ],
        ],


        'system' => [
            'name' => '系统',
            'icon' => 'fa fa-globe',
            'left' => [
                'system-wh' => [
                    'name' => '系统维护',
                    'icon' => 'fa fa-cog',
                    'link' => [
                        [
                            'name' => '系统环境',
                            'icon' => 'fa fa-cog',
                            'uri' => 'system/index',
                        ],
                        [
                            'name' => '系统缓存',
                            'icon' => 'fa fa-clock-o',
                            'uri' => 'system_cache/index',
                        ],
                        [
                            'name' => '附件设置',
                            'icon' => 'fa fa-folder',
                            'uri' => 'attachment/index',
                        ],
                        [
                            'name' => '短信设置',
                            'icon' => 'fa fa-envelope',
                            'uri' => 'sms/index',
                        ],
                        [
                            'name' => '邮件设置',
                            'icon' => 'fa fa-envelope-open',
                            'uri' => 'email/index',
                        ],
                        [
                            'name' => '系统提醒',
                            'icon' => 'fa fa-bell',
                            'uri' => 'notice/index',
                        ],
                        [
                            'name' => '系统体检',
                            'icon' => 'fa fa-wrench',
                            'uri' => 'check/index',
                        ],
                    ]
                ],
                'system-log' => [
                    'name' => '日志管理',
                    'icon' => 'fa fa-calendar',
                    'link' => [
                        [
                            'name' => '系统日志',
                            'icon' => 'fa fa-shield',
                            'uri' => 'error/index',
                        ],
                        [
                            'name' => '操作日志',
                            'icon' => 'fa fa-calendar',
                            'uri' => 'system_log/index',
                        ],
                        [
                            'name' => '短信日志',
                            'icon' => 'fa fa-envelope',
                            'uri' => 'sms_log/index',
                        ],
                        [
                            'name' => '邮件日志',
                            'icon' => 'fa fa-envelope-open',
                            'uri' => 'email_log/index',
                        ],
                    ]
                ],
            ],
        ],

        'config' => [
            'name' => '设置',
            'icon' => 'fa fa-cogs',
            'left' => [
                'config-web' => [
                    'name' => '网站设置',
                    'icon' => 'fa fa-cog',
                    'link' => [
                        [
                            'name' => '网站设置',
                            'icon' => 'fa fa-cog',
                            'uri' => 'site_config/index',
                        ],
                        [
                            'name' => '网站信息',
                            'icon' => 'fa fa-edit',
                            'uri' => 'site_param/index',
                        ],
                        [
                            'name' => '终端设置',
                            'icon' => 'fa fa-cogs',
                            'uri' => 'site_client/index',
                        ],
                        [
                            'name' => '手机设置',
                            'icon' => 'fa fa-mobile',
                            'uri' => 'site_mobile/index',
                        ],
                        [
                            'name' => '域名绑定',
                            'icon' => 'fa fa-globe',
                            'uri' => 'site_domain/index',
                        ],
                        [
                            'name' => '图片设置',
                            'icon' => 'fa fa-photo',
                            'uri' => 'site_image/index',
                        ],
                    ]
                ],
                'config-content' => [
                    'name' => '内容设置',
                    'icon' => 'fa fa-navicon',
                    'link' => [
                        [
                            'name' => '创建模块',
                            'icon' => 'fa fa-plus',
                            'uri' => 'module_create/index',
                        ],
                        [
                            'name' => '模块管理',
                            'icon' => 'fa fa-gears',
                            'uri' => 'module/index',
                        ],
                        [
                            'name' => '模块搜索',
                            'icon' => 'fa fa-search',
                            'uri' => 'module_search/index',
                        ],
                    ]
                ],

                'config-seo' => [
                    'name' => 'SEO设置',
                    'icon' => 'fa fa-internet-explorer',
                    'link' => [
                        [
                            'name' => '站点SEO',
                            'icon' => 'fa fa-cog',
                            'uri' => 'seo_site/index',
                        ],
                        [
                            'name' => '模块SEO',
                            'icon' => 'fa fa-th-large',
                            'uri' => 'seo_module/index',
                        ],
                        [
                            'name' => '栏目SEO',
                            'icon' => 'fa fa-reorder',
                            'uri' => 'seo_category/index',
                        ],
                        [
                            'name' => 'URL规则',
                            'icon' => 'fa fa-link',
                            'uri' => 'urlrule/index',
                        ],
                    ]
                ],
            ],
        ],

        'auth' => [
            'name' => '权限',
            'icon' => 'fa fa-user-circle',
            'left' => [
                'auth-admin' => [
                    'name' => '后台权限',
                    'icon' => 'fa fa-cog',
                    'link' => [
                        [
                            'name' => '后台菜单',
                            'icon' => 'fa fa-list-alt',
                            'uri' => 'menu/index',
                        ],
                        [
                            'name' => '简化菜单',
                            'icon' => 'fa fa-list',
                            'uri' => 'min_menu/index',
                        ],
                        [
                            'name' => '角色权限',
                            'icon' => 'fa fa-users',
                            'uri' => 'role/index',
                        ],
                        [
                            'name' => '角色账号',
                            'icon' => 'fa fa-user',
                            'uri' => 'root/index',
                        ],
                    ]
                ],
            ],
        ],

        'content' => [
            'name' => '内容',
            'icon' => 'fa fa-th-large',
            'left' => [
                'content-module' => [
                    'name' => '内容管理',
                    'icon' => 'fa fa-th-large',
                    'link' => [
                        [
                            'name' => '栏目管理',
                            'icon' => 'fa fa-reorder',
                            'uri' => 'module_category/index',
                        ],
                    ]
                ],
                'content-verify' => [
                    'name' => '内容审核',
                    'icon' => 'fa fa-edit',
                    'link' => [
                    ]
                ],
            ],
        ],

        'code' => [
            'name' => '界面',
            'icon' => 'fa fa-html5',
            'left' => [
                'code-html' => [
                    'name' => '模板管理',
                    'icon' => 'fa fa-home',
                    'link' => [
                        [
                            'name' => '电脑模板',
                            'icon' => 'fa fa-desktop',
                            'uri' => 'tpl_pc/index',
                        ],
                        [
                            'name' => '手机模板',
                            'icon' => 'fa fa-mobile',
                            'uri' => 'tpl_mobile/index',
                        ],
                        [
                            'name' => '终端模板',
                            'icon' => 'fa fa-cogs',
                            'uri' => 'tpl_client/index',
                        ],
                    ]
                ],
                'code-css' => [
                    'name' => '风格管理',
                    'icon' => 'fa fa-css3',
                    'link' => [
                        [
                            'name' => '系统文件',
                            'icon' => 'fa fa-chrome',
                            'uri' => 'system_theme/index',
                        ],
                        [
                            'name' => '网站风格',
                            'icon' => 'fa fa-photo',
                            'uri' => 'theme/index',
                        ],
                    ],
                    'displayorder' => 99
                ],
            ],
        ],

        'app' => [
            'name' => '应用',
            'icon' => 'fa fa-puzzle-piece',
            'left' => [
                'app-plugin' => [
                    'name' => '应用插件',
                    'icon' => 'fa fa-puzzle-piece',
                    'link' => [
                        [
                            'name' => '应用管理',
                            'icon' => 'fa fa-folder',
                            'uri' => 'cloud/local',
                        ],
                        [
                            'name' => '联动菜单',
                            'icon' => 'fa fa-columns',
                            'uri' => 'linkage/index',
                        ],
                        [
                            'name' => '任务队列',
                            'icon' => 'fa fa-indent',
                            'uri' => 'cron/index',
                        ],
                        [
                            'name' => '附件管理',
                            'icon' => 'fa fa-folder',
                            'uri' => 'attachments/index',
                        ],
                    ]
                ],
            ],
        ],

        'cloud' => [
			'name' => '服务',
            'icon' => 'fa fa-cloud',
            'displayorder' => 99,
            'left' => [
                'cloud-dayrui' => [
                    'name' => '网站管理',
                    'icon' => 'fa fa-cloud',
                    'link' => [
                        [
                            'name' => '我的网站',
                            'icon' => 'fa fa-cog',
                            'uri' => 'cloud/index',
                        ],
                        [
                            'name' => '服务工单',
                            'icon' => 'fa fa-user-md',
                            'uri' => 'cloud/service',
                        ],
                        [
                            'name' => '应用商城',
                            'icon' => 'fa fa-puzzle-piece',
                            'uri' => 'cloud/app',
                        ],
                        [
                            'name' => '模板商城',
                            'icon' => 'fa fa-html5',
                            'uri' => 'cloud/template',
                        ],
                        [
                            'name' => '版本升级',
                            'icon' => 'fa fa-refresh',
                            'uri' => 'cloud/update',
                        ],
                        [
                            'name' => '文件对比',
                            'icon' => 'fa fa-code',
                            'uri' => 'cloud/bf',
                        ],
                    ]
                ],
            ],
        ],

    ],


    'admin_min' => [

        'home' => [
            'name' => '我的面板',
            'icon' => 'fa fa-home',
            'link' => [
                [
                    'name' => '后台首页',
                    'icon' => 'fa fa-home',
                    'uri' => 'home/main',
                ],
                [
                    'name' => '资料修改',
                    'icon' => 'fa fa-user',
                    'uri' => 'api/my',
                ],
                [
                    'name' => '网站设置',
                    'icon' => 'fa fa-cog',
                    'uri' => 'site_param/index',
                ],
                [
                    'name' => '附件设置',
                    'icon' => 'fa fa-folder',
                    'uri' => 'attachment/index',
                ],
                [
                    'name' => '图片设置',
                    'icon' => 'fa fa-photo',
                    'uri' => 'site_image/index',
                ],
            ]
        ],


        'config-seo' => [
            'name' => 'SEO设置',
            'icon' => 'fa fa-internet-explorer',
            'link' => [
                [
                    'name' => '站点SEO',
                    'icon' => 'fa fa-cog',
                    'uri' => 'seo_site/index',
                ],
                [
                    'name' => '模块SEO',
                    'icon' => 'fa fa-gears',
                    'uri' => 'seo_module/index',
                ],
                [
                    'name' => '栏目SEO',
                    'icon' => 'fa fa-reorder',
                    'uri' => 'seo_category/index',
                ],
                [
                    'name' => 'URL规则',
                    'icon' => 'fa fa-link',
                    'uri' => 'urlrule/index',
                ],
            ]
        ],

        'content-module' => [
            'name' => '内容管理',
            'icon' => 'fa fa-th-large',
            'link' => [
                [
                    'name' => '栏目管理',
                    'icon' => 'fa fa-reorder',
                    'uri' => 'module_category/index',
                ],
            ]
        ],


        'app-plugin' => [
            'name' => '应用插件',
            'icon' => 'fa fa-puzzle-piece',
            'link' => [
                [
                    'name' => '联动菜单',
                    'icon' => 'fa fa-columns',
                    'uri' => 'linkage/index',
                ],
                [
                    'name' => '附件管理',
                    'icon' => 'fa fa-folder',
                    'uri' => 'attachments/index',
                ],
            ]
        ],

    ],


];