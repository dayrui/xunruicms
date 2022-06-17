<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

$config = [
    // 默认缓存驱动
    'default' => SYS_CACHE_TYPE  == 1 ? 'memcached' : (SYS_CACHE_TYPE  == 2 ? 'redis' : 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => WRITEPATH.'file/',
            // 缓存前缀
            'prefix'     => SYS_KEY,
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 更多的缓存连接
        'memcached' => [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'       => '127.0.0.1',
            // 缓存前缀
            'prefix'     => SYS_KEY,
        ],
        'redis' => [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'       => '127.0.0.1',
            // 缓存前缀
            'prefix'     => SYS_KEY,
        ],
    ],
];

if (is_file(ROOTPATH.'config/memcached.php')) {
    $my = require ROOTPATH.'config/memcached.php';
    $config['stores']['memcached'] = [
        // 驱动方式
        'type'   => 'memcached',
        // 服务器地址
        'host'       => $my['host'],
        // 缓存前缀
        'prefix'     => SYS_KEY,
    ];
}

if (is_file(ROOTPATH.'config/redis.php')) {
    $my = require ROOTPATH.'config/redis.php';
    $config['stores']['redis'] = [
        // 驱动方式
        'type'   => 'redis',
        // 服务器地址
        'host'       => $my['host'],
        // 缓存前缀
        'prefix'     => SYS_KEY,
    ];
}

return $config;
