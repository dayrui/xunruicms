<?php namespace Config;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
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
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'foreignKeys'   => true,
        'failover'     => []
    ];

    //--------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        if (!is_file(CONFIGPATH.'database.php')) {
            return $this;
        }

        $db = [];
        require CONFIGPATH.'database.php';

        if (isset($db['failover']) && $db['failover']) {
            // 备用库
            $this->default['failover'] = $db['failover'];
            unset($db['failover']);
        }

        foreach ($this->default as $p => $t) {
            foreach ($db as $name => $v) {
                $this->$name[$p] = isset($v[$p]) ? $v[$p] : $t;
            }
        }

        // 判断数据库名称的规范性
        if (is_numeric($this->default['database'])) {
            exit('数据库名称不能是数字');
        } elseif (strpos($this->default['database'], '.') !== false) {
            exit('数据库名称不能存在.号');
        }

    }

    //--------------------------------------------------------------------


}

