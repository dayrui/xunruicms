<?php

/**
 * 菜单配置
 */


return [

    'admin' => [

        'config' => [

            'left' => [


                'config-content' => [
                    'name' => '内容设置',
                    'icon' => 'fa fa-navicon',
                    'link' => [
                        [
                            'name' => '创建模块',
                            'icon' => 'fa fa-plus',
                            'uri' => 'module/module_create/index',
                            'displayorder' => -1,
                        ],
                        [
                            'name' => '模块管理',
                            'icon' => 'fa fa-gears',
                            'uri' => 'module/module/index',
                            'displayorder' => -1,
                        ],
                        [
                            'name' => '模块搜索',
                            'icon' => 'fa fa-search',
                            'uri' => 'module/module_search/index',
                            'displayorder' => -1,
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
                            'uri' => 'module/seo_site/index',
                        ],
                        [
                            'name' => '模块SEO',
                            'icon' => 'fa fa-th-large',
                            'uri' => 'module/seo_module/index',
                        ],
                        [
                            'name' => '栏目SEO',
                            'icon' => 'fa fa-reorder',
                            'uri' => 'module/seo_category/index',
                        ],
                        [
                            'name' => 'URL规则',
                            'icon' => 'fa fa-link',
                            'uri' => 'module/urlrule/index',
                        ],
                        [
                            'name' => '伪静态解析',
                            'icon' => 'bi bi-code-square',
                            'uri' => 'module/urlrule/rewrite_index',
                        ],
                    ]
                ],


            ],



        ],


        'content' => [
            'name' => '内容',
            'icon' => 'fa fa-th-large',
            'displayorder' => '-1',
            'left' => [
                'content-module' => [
                    'name' => '内容管理',
                    'icon' => 'fa fa-th-large',
                    'link' => [
                        [
                            'name' => '共享栏目',
                            'icon' => 'fa fa-reorder',
                            'uri' => 'category/index',
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




    ],


    'admin_min' => [


        'config-seo' => [
            'name' => 'SEO设置',
            'icon' => 'fa fa-internet-explorer',
            'link' => [
                [
                    'name' => '站点SEO',
                    'icon' => 'fa fa-cog',
                    'uri' => 'module/seo_site/index',
                ],
                [
                    'name' => '模块SEO',
                    'icon' => 'fa fa-gears',
                    'uri' => 'module/seo_module/index',
                ],
                [
                    'name' => '栏目SEO',
                    'icon' => 'fa fa-reorder',
                    'uri' => 'module/seo_category/index',
                ],
                [
                    'name' => 'URL规则',
                    'icon' => 'fa fa-link',
                    'uri' => 'module/urlrule/index',
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
                    'uri' => 'module/module_category/index',
                ],
            ]
        ],

    ]

];