<?php namespace Config;

use phpDocumentor\Reflection\DocBlock\Tag\VarTag;

/**
 * Database Configuration
 *
 * @package Config
 */
class Database extends \CodeIgniter\Database\Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     * @var string
     */
    public $filesPath = WRITEPATH.'database/';

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     *
     * @var string
     */
    public $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array
     */
    public $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'     => true,
        'cacheOn'     => true,
        'cacheDir'     => WRITEPATH.'database/',
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => []
    ];

    //--------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        $db = [];
        require ROOTPATH.'config/database.php';

        foreach ($this->default as $p => $t) {
            foreach ($db as $name => $v) {
                $this->$name[$p] = isset($v[$p]) ? $v[$p] : $t;
            }
        }

        // Thinkphp数据库配置
        if (is_file(dirname(COMPOSER_PATH).'/topthink/think-orm/src/DbManager.php')) {
            \think\facade\Db::setConfig([
                // 默认数据连接标识
                'default'     => 'mysql',
                // 数据库连接信息
                'connections' => [
                    'mysql' => [
                        // 数据库类型
                        'type'     => 'mysql',
                        // 主机地址
                        'hostname' => $db['default']['hostname'],
                        // 用户名
                        'username' => $db['default']['username'],
                        'password' => $db['default']['password'],
                        // 数据库名
                        'database' => $db['default']['database'],
                        // 数据库编码默认采用utf8
                        'charset'  => 'utf8',
                        // 数据库表前缀
                        'prefix'   => $db['default']['DBPrefix'],
                        // 数据库调试模式
                        'debug'    => true,
                    ],
                ],
            ]);
        }

    }

    //--------------------------------------------------------------------


}

