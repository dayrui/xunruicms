<?php
/**
 * 运行目录
 */

// PHP版本检测
$min = '8.1';
$max = '8.5';
if (version_compare(PHP_VERSION, $min) < 0 || version_compare(PHP_VERSION, $max) > 0) {
    exit("<font color=red>PHP版本要求大于".$min.".0且小于".$max."，当前".PHP_VERSION."不满足运行环境</font>");
}

define('FRAME_PHP_VERSION', $min);
define('FRAME_NAME', 'CodeIgniter');
define('FRAME_VERSION', '4.2');

defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_LOW instead.
 */
define('EVENT_PRIORITY_LOW', 200);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_NORMAL instead.
 */
define('EVENT_PRIORITY_NORMAL', 100);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_HIGH instead.
 */
define('EVENT_PRIORITY_HIGH', 10);


require CMSPATH . 'Core/Auto.php';
require CMSPATH . 'Core/Service.php';
require CMSPATH . 'Core/Hooks.php';
require FRAMEPATH . 'Extend/Run.php';

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_token()
    {
        return \Phpcmf\Service::L('Security')->csrf_token();
    }
}

if (! function_exists('csrf_hash')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_hash()
    {
        return \Phpcmf\Service::L('Security')->csrf_hash();
    }
}

if (! function_exists('lang')) {
  function lang(...$param) {

        if (empty($param)) {
            return '';
        }

        // 取第一个作为语言名称
        $string = $param[0];
        unset($param[0]);

        // 调用语言包内容
        //$string = \Phpcmf\Service::L('lang')->text($string);
        if ($param) {
            //foreach ($param as $k => $t) {
                //$param[$k] = \Phpcmf\Service::L('lang')->text($t);
            //}
            return vsprintf($string, $param);
        }

        return $string;
    }
}


if (! function_exists('config')) {
    function config(string $name, bool $getShared = true)
    {
        if ($getShared) {
            return \CodeIgniter\Config\Factories::get('config', $name);
        }

        return \CodeIgniter\Config\Factories::config($name, ['getShared' => $getShared]);
    }
}

if (! function_exists('is_really_writable')) {
    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @see https://bugs.php.net/bug.php?id=54709
     *
     * @throws Exception
     *
     * @codeCoverageIgnore Not practical to test, as travis runs on linux
     */
    function is_really_writable(string $file): bool
    {
        // If we're on a Unix server we call is_writable
        if (! is_windows()) {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . bin2hex(random_bytes(16));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);

            return true;
        }

        if (! is_file($file) || ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);

        return true;
    }
}

if (! function_exists('is_windows')) {
    /**
     * Detect if platform is running in Windows.
     */
    function is_windows(?bool $mock = null): bool
    {
        static $mocked;

        if (func_num_args() === 1) {
            $mocked = $mock;
        }

        return $mocked ?? DIRECTORY_SEPARATOR === '\\';
    }
}


if (! function_exists('helper')) {
    /**
     * Loads a helper file into memory. Supports namespaced helpers,
     * both in and out of the 'Helpers' directory of a namespaced directory.
     *
     * Will load ALL helpers of the matching name, in the following order:
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     *
     * @param array|string $filenames
     *
     * @throws FileNotFoundException
     */
    function helper($filenames): void
    {
        static $loaded = [];


        if (! is_array($filenames)) {
            $filenames = [$filenames];
        }

        // Store a list of all files to include...
        $includes = [];

        foreach ($filenames as $filename) {
            // Store our system and application helper
            // versions so that we can control the load ordering.
            $systemHelper  = '';
            $appHelper     = '';
            $localIncludes = [];

            if (! str_contains($filename, '_helper')) {
                $filename .= '_helper';
            }

            // Check if this helper has already been loaded
            if (in_array($filename, $loaded, true)) {
                continue;
            }
                $path = FRAMEPATH.'Helpers/'.$filename.'.php';
                $includes[] = $path;
                $loaded[]   = $filename;
        }

        // Now actually include all of the files
        foreach ($includes as $path) {
            include_once $path;
        }
    }
}

if (! function_exists('service')) {
    /**
     * Allows cleaner access to the Services Config file.
     * Always returns a SHARED instance of the class, so
     * calling the function multiple times should always
     * return the same instance.
     *
     * These are equal:
     *  - $timer = service('timer')
     *  - $timer = \CodeIgniter\Config\Services::timer();
     *
     * @param array|bool|float|int|object|string|null ...$params
     */
    function service(string $name, ...$params): ?object
    {
        if ($params === []) {
            return \CodeIgniter\Config\Services::get($name);
        }

        return \CodeIgniter\Config\Services::$name(...$params);
    }
}

function site_url() {
    return FC_NOW_HOST;
}


if (! function_exists('clean_path')) {
    function clean_path(string $path): string
    {
   
        return $path;
    }
}

if (!class_exists('Locale')) {
    class Locale {

        static private $locale;

        public static function getDefault() {
            return self::$locale;
        }

        public static function setDefault($locale) {
            self::$locale = $locale;
        }

    }
}

if (! function_exists('env')) {
    /**
     * Allows user to retrieve values from the environment
     * variables that have been set. Especially useful for
     * retrieving values set from the .env file for
     * use in config files.
     *
     * @param array<int|string, mixed>|bool|float|int|object|string|null $default
     *
     * @return array<int|string, mixed>|bool|float|int|object|string|null
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        // Not found? Return the default value
        if ($value === false) {
            return $default;
        }

        // Handle any boolean values
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'empty' => '',
            'null'  => null,
            default => $value,
        };
    }
}

if (! function_exists('esc')) {
    /**
     * Performs simple auto-escaping of data for security reasons.
     * Might consider making this more complex at a later date.
     *
     * If $data is a string, then it simply escapes and returns it.
     * If $data is an array, then it loops over it, escaping each
     * 'value' of the key/value pairs.
     *
     * @param array|string                         $data
     * @param 'attr'|'css'|'html'|'js'|'raw'|'url' $context
     * @param string|null                          $encoding Current encoding for escaping.
     *                                                       If not UTF-8, we convert strings from this encoding
     *                                                       pre-escaping and back to this encoding post-escaping.
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    function esc($data, string $context = 'html', ?string $encoding = null)
    {
        $context = strtolower($context);

        // Provide a way to NOT escape data since
        // this could be called automatically by
        // the View library.
        if ($context === 'raw') {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = esc($value, $context);
            }
        }

        if (is_string($data)) {
            if (! in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
                throw new InvalidArgumentException('Invalid escape context provided.');
            }

            $method = $context === 'attr' ? 'escapeHtmlAttr' : 'escape' . ucfirst($context);

       
        }

        return $data;
    }
}


// 应用插件的自动识别
$loader = new \Phpcmf\Auto();
$myloader = new \Phpcmf\AutoConfig();
$myloader->psr4 = [
   'CodeIgniter' => FRAMEPATH,
   'Config' => FRAMEPATH.'Config',
];
$loader->initialize(\Phpcmf\Service::Auto($myloader));
$loader->register();


// 初始化异常处理
//\CodeIgniter\Exceptions\FrameworkException::initialize();
\CodeIgniter\Config\Services::exceptions()->initialize();

if (CI_DEBUG) {
    \CodeIgniter\Events\Events::on('pre_system', function () {

        /*
        * --------------------------------------------------------------------
        * Debug Toolbar Listeners.
        * --------------------------------------------------------------------
        * If you delete, they will no longer be collected.
        */

        \CodeIgniter\Events\Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');

        $config = config(\Config\Toolbar::class);

        $tool =  new \CodeIgniter\Debug\Toolbar($config);

        $tool->respond();
    });
}


// 启动框架
$run = new \Frame\Run();
$run->bootWeb();

