<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        $message = $exception->getMessage();
        if ($message == 'method param miss:params') {
            dr_exit_msg(0, '控制器文件中含有字符（...$params），请手动删除：https://www.xunruicms.com/doc/1246.html');
        }
        log_message('error', $exception);
        // ajax 返回
        if (IS_AJAX || IS_API) {
            // 调试模式不屏蔽敏感信息
            if (CI_DEBUG) {
                $message.= '<br>'.$exception->getFile().'（'.$exception->getLine().'）';
            } else {
                $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
            }
            dr_exit_msg(0, $message);
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
