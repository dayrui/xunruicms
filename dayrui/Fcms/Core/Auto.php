<?php namespace Phpcmf;

/**
 * 自动加载识别程序
 */

class Auto
{
    /**
     * Stores namespaces as key, and path as values.
     *
     * @var array<string, array<string>>
     */
    protected $prefixes = [];

    /**
     * Stores class name as key, and path as values.
     *
     * @var array<string, string>
     */
    protected $classmap = [];

    /**
     * Stores files as a list.
     *
     * @var array<int, string>
     */
    protected $files = [];

    /**
     * Reads in the configuration array (described above) and stores
     * the valid parts that we'll need.
     *
     * @return $this
     */
    public function initialize($config)
    {

        if (empty($config->psr4) && empty($config->classmap)) {
            return $this;
        }

        if (isset($config->psr4)) {
            $this->addNamespace($config->psr4);
        }

        if (isset($config->classmap)) {
            $this->classmap = $config->classmap;
        }

        if (isset($config->files)) {
            $this->files = $config->files;
        }

        return $this;
    }

    /**
     * Register the loader with the SPL autoloader stack.
     */
    public function register()
    {
        // Prepend the PSR4  autoloader for maximum performance.
        spl_autoload_register([$this, 'loadClass'], true, true);

        // Now prepend another loader for the files in our class map.
        spl_autoload_register([$this, 'loadClassmap'], true, true);

        // Load our non-class files
        foreach ($this->files as $file) {
            if (is_string($file)) {
                $this->includeFile($file);
            }
        }
    }

    /**
     * Registers namespaces with the autoloader.
     *
     * @param array|string $namespace
     *
     * @return $this
     */
    public function addNamespace($namespace, ?string $path = null)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $namespacedPath) {
                $prefix = trim($prefix, '\\');

                if (is_array($namespacedPath)) {
                    foreach ($namespacedPath as $dir) {
                        $this->prefixes[$prefix][] = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR;
                    }

                    continue;
                }

                $this->prefixes[$prefix][] = rtrim($namespacedPath, '\\/') . DIRECTORY_SEPARATOR;
            }
        } else {
            $this->prefixes[trim($namespace, '\\')][] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
        }

        return $this;
    }

    /**
     * Get namespaces with prefixes as keys and paths as values.
     *
     * If a prefix param is set, returns only paths to the given prefix.
     *
     * @return array
     */
    public function getNamespace(?string $prefix = null)
    {
        if ($prefix === null) {
            return $this->prefixes;
        }

        return $this->prefixes[trim($prefix, '\\')] ?? [];
    }

    /**
     * Removes a single namespace from the psr4 settings.
     *
     * @return $this
     */
    public function removeNamespace(string $namespace)
    {
        if (isset($this->prefixes[trim($namespace, '\\')])) {
            unset($this->prefixes[trim($namespace, '\\')]);
        }

        return $this;
    }

    /**
     * Load a class using available class mapping.
     *
     * @return false|string
     */
    public function loadClassmap(string $class)
    {
        $file = $this->classmap[$class] ?? '';

        if (is_string($file) && $file !== '') {
            return $this->includeFile($file);
        }

        return false;
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully qualified class name.
     *
     * @return false|string The mapped file on success, or boolean false
     *                      on failure.
     */
    public function loadClass(string $class)
    {
        $class = trim($class, '\\');
        $class = str_ireplace('.php', '', $class);

        return $this->loadInNamespace($class);
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name
     *
     * @return false|string The mapped file name on success, or boolean false on fail
     */
    protected function loadInNamespace(string $class)
    {
        if (strpos($class, '\\') === false) {
            return false;
        }

        foreach ($this->prefixes as $namespace => $directories) {
            foreach ($directories as $directory) {
                $directory = rtrim($directory, '\\/');

                if (strpos($class, $namespace) === 0) {
                    $filePath = $directory . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace))) . '.php';
                    $filename = $this->includeFile($filePath);

                    if ($filename) {
                        return $filename;
                    }
                }
            }
        }

        // never found a mapped file
        return false;
    }

    /**
     * A central way to include a file. Split out primarily for testing purposes.
     *
     * @return false|string The filename on success, false if the file is not loaded
     */
    protected function includeFile(string $file)
    {
        $file = $this->sanitizeFilename($file);

        if (is_file($file)) {
            include_once $file;

            return $file;
        }

        return false;
    }

    /**
     * Sanitizes a filename, replacing spaces with dashes.
     *
     * Removes special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trim period, dash and underscore from beginning
     * and end of filename.
     *
     * @return string The sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Only allow characters deemed safe for POSIX portable filenames.
        // Plus the forward slash for directory separators since this might be a path.
        // http://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap03.html#tag_03_278
        // Modified to allow backslash and colons for on Windows machines.
        $filename = preg_replace('/[^0-9\p{L}\s\/\-\_\.\:\\\\]/u', '', $filename);

        // Clean up our filename edges.
        return trim($filename, '.-_');
    }

}


/**
 * 自动加载配置文件
 */
class AutoConfig
{

    public $psr4 = [];

    public $classmap = [];
}
