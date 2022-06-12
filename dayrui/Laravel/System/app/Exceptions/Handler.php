<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // 记录日志
            $message = $e->getMessage();

            if ($message && strpos($message, 'Unresolvable dependency resolving [Parameter #0 [ <optional> ...$params ]]') !== false) {
                dr_exit_msg(0, '控制器文件中含有字符（...$params），请手动删除：https://www.xunruicms.com/doc/1246.html');
            }

            log_message('error', $e);
            // ajax 返回
            if (IS_AJAX || IS_API) {
                // 调试模式不屏蔽敏感信息
                if (CI_DEBUG) {
                    $message.= '<br>'.$e->getFile().'（'.$e->getLine().'）';
                } else {
                    $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
                }
                dr_exit_msg(0, $message);
            }

        });


    }

}
