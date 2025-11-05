<?php

declare(strict_types=1);

namespace Frame;

require CMSPATH.'Core/Phpcmf.php';

class Run
{

    /**
     * App startup time.
     *
     * @var float|null
     */
    protected $startTime;

    /**
     * Total app execution time
     *
     * @var float
     */
    protected $totalTime;

    /**
     * Main application configuration
     *
     * @var App
     */
    protected $config;

    /**
     * Timer instance.
     *
     * @var Timer
     */
    protected $benchmark;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->startTime = microtime(true);

    }

    /**
     * Start the Benchmark
     *
     * The timer is used to display total script execution both in the
     * debug toolbar, and potentially on the displayed page.
     *
     * @return void
     */
    protected function startBenchmark()
    {
        if ($this->startTime === null) {
            $this->startTime = microtime(true);
        }


        $this->benchmark = new \CodeIgniter\Debug\Timer();
        $this->benchmark->start('total_execution', $this->startTime);
        $this->benchmark->start('bootstrap');
    }


    /**
     * Returns an array with our basic performance stats collected.
     */
    public function getPerformanceStats(): array
    {
        // After filter debug toolbar requires 'total_execution'.
        $this->totalTime = $this->benchmark->getElapsedTime('total_execution');

        return [
            'startTime' => $this->startTime,
            'totalTime' => $this->totalTime,
        ];
    }

    public function bootWeb()
    {
        
        if (CI_DEBUG) {
            $this->startBenchmark();
            \CodeIgniter\Events\Events::trigger('pre_system');
        }

        $controller = 'Home';
        $method = 'index';

        if (IS_ADMIN) {
            $namespace = '\\Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Admin';
        } elseif (IS_MEMBER) {
            $namespace = '\\Phpcmf\\'.(APP_DIR == 'member' ? 'Controllers' : 'Controllers\\Member');
        } elseif (IS_API) {
            $namespace = '\\Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Api';
        } else {
            $namespace = '\\Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control');
        }

        isset($_GET['c']) && $_GET['c'] && is_string($_GET['c']) && $controller = (ucfirst(dr_safe_filename($_GET['c'])));
        isset($_GET['m']) && $_GET['m'] && is_string($_GET['m']) && $method = (dr_safe_filename($_GET['m']));

        $class = $namespace.'\\'.$controller;
        if (! class_exists($class)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forControllerNotFound($class);
            exit('<font color=red>控制器不存在</font>');
        }

        $app = new $class;

        if (! method_exists($app, $method)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forMethodNotFound($class, $method);
            exit('<font color=red>方法不存在</font>');
        }

        $app->$method();

        if (CI_DEBUG) {
            $tool = new \CodeIgniter\Debug\Toolbar(config(\Config\Toolbar::class));
            $tool->prepare($this);
        }
        
       
    }


    


}
