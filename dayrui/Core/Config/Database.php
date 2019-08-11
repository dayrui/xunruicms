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

    }

    //--------------------------------------------------------------------


}

