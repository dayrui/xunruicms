<?php
// 屏蔽error_reporting设置，由xunruicms统一配置
// /dayrui/ThinkPHP/System/vendor/topthink/framework/src/think/initializer/Error.php


namespace think\initializer;

use think\App;
use think\console\Output as ConsoleOutput;
use think\exception\ErrorException;
use think\exception\Handle;
use Throwable;

/**
 * 错误和异常处理
 */
class Error
{
    /** @var App */
    protected $app;

    /**
     * 注册异常处理
     * @access public
     * @param App $app
     * @return void
     */
    public function init(App $app)
    {
        $this->app = $app;
        //error_reporting(E_ALL);
        set_error_handler([$this, 'appError']);
        set_exception_handler([$this, 'appException']);
        register_shutdown_function([$this, 'appShutdown']);
    }

    /**
     * Exception Handler
     * @access public
     * @param \Throwable $e
     */
    public function appException(Throwable $e): void
    {
        $handler = $this->getExceptionHandler();

        $handler->report($e);

        if ($this->app->runningInConsole()) {
            $handler->renderForConsole(new ConsoleOutput, $e);
        } else {
            $handler->render($this->app->request, $e)->send();
        }
    }

    /**
     * Error Handler
     * @access public
     * @param integer $errno 错误编号
     * @param string $errstr 详细错误信息
     * @param string $errfile 出错的文件
     * @param integer $errline 出错行号
     * @throws ErrorException
     */
    public function appError(int $errno, string $errstr, string $errfile = '', int $errline = 0): void
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        if (error_reporting() & $errno) {
            // 将错误信息托管至 think\exception\ErrorException
            throw $exception;
        }
    }

    /**
     * Shutdown Handler
     * @access public
     */
    public function appShutdown(): void
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            // 将错误信息托管至think\ErrorException
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);

            $this->appException($exception);
        }
    }

    /**
     * 确定错误类型是否致命
     *
     * @access protected
     * @param int $type
     * @return bool
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @access protected
     * @return Handle
     */
    protected function getExceptionHandler()
    {
        return $this->app->make(Handle::class);
    }
}
