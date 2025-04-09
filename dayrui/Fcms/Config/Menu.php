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
            'displayorder' => '-2',
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
            'displayorder' => '-2',
            'left' => [
                'system-wh' => [
                    'name' => '系统维护',
                    'icon' => 'fa fa-cog',
                    'link' => [
                        [
                            'name' => '系统参数',
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
                            'name' => '存储策略',
                            'icon' => 'fa fa-cloud',
                            'uri' => 'attachment/remote_index',
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
                            'name' => '操作记录',
                            'icon' => 'fa fa-calendar',
                            'uri' => 'system_log/index',
                        ],
                        [
                            'name' => '密码错误',
                            'icon' => 'fa fa-unlock-alt',
                            'uri' => 'password_log/index',
                        ],
                        [
                            'name' => '短信错误',
                            'icon' => 'fa fa-envelope',
                            'uri' => 'sms_log/index',
                        ],
                        [
                            'name' => '邮件错误',
                            'icon' => 'fa fa-envelope-open',
                            'uri' => 'email_log/index',
                        ],
                        [
                            'name' => '慢查询日志',
                            'icon' => 'fa fa-database',
                            'uri' => 'sql_log/index',
                        ],
                    ]
                ],
            ],
        ],

        'config' => [
            'name' => '设置',
            'icon' => 'fa fa-cogs',
            'displayorder' => '-2',
            'left' => [
                'config-web' => [
                    'name' => '项目设置',
                    'icon' => 'fa fa-cog',
                    'link' => [
                        [
                            'name' => '项目信息',
                            'icon' => 'fa fa-cog',
                            'uri' => 'site_param/index',
                        ],
                    ]
                ]
            ]
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
                    'name' => '项目服务',
                    'icon' => 'fa fa-cloud',
                    'link' => [
                        [
                            'name' => '我的项目',
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