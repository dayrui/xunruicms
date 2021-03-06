<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use Throwable;

/**
 * 继承异常类，用于Services.php
 */

class Exceptions extends \CodeIgniter\Debug\Exceptions
{

    /**
     * 排除部分错误提示
     */
    public function errorHandler( $severity,  $message,  $file = null,  $line = null, $context = null)
    {
        if (!in_array($severity, [E_NOTICE, E_WARNING])) { //E_WARNING
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }
    }

    /**
     * 错误日志增加最后执行的sql语句
     *
     * @param \Throwable $exception
     */
    public function exceptionHandler(Throwable $exception)
    {
        $codes      = $this->determineCodes($exception);
        $statusCode = $codes[0];
        $exitCode   = $codes[1];

        // Log it
        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes))
        {
            // 传入对象到日志中
            log_message('critical', $exception);
        }

		 // ajax 返回
        if (IS_AJAX || IS_API) {
			$message = $exception->getMessage();
			// 调试模式不屏蔽敏感信息
            if (CI_DEBUG) {
                $message.= '<br>'.$exception->getFile().'（'.$exception->getLine().'）';
            } else {
                $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
            }
            dr_exit_msg(0, $message);
        }

        if (! is_cli())
        {
            $this->response->setStatusCode($statusCode);
            $header = "HTTP/{$this->request->getProtocolVersion()} {$this->response->getStatusCode()} {$this->response->getReason()}";
            header($header, true, $statusCode);

            if (strpos($this->request->getHeaderLine('accept'), 'text/html') === false)
            {
                $this->respond(ENVIRONMENT === 'development' ? $this->collectVars($exception, $statusCode) : '', $statusCode)->send();

                exit($exitCode);
            }
        }

        $this->render($exception, $statusCode);

        exit($exitCode);
    }

    /**
     * 错误输出结果
     */
    protected function render(\Throwable $exception, int $statusCode)
    {

        $message = $exception->getMessage();

        // 调试模式不屏蔽敏感信息
        if (CI_DEBUG) {
            $message.= '<br>'.$exception->getFile().'（'.$exception->getLine().'）';
        } else {
            $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
        }

        if (empty($message)) {
            $message = '(null)';
        } elseif (strpos($message, 'The action you requested is not allowed') !== false) {
            dr_exit_msg(0, '提交验证超时，请重试', 'CSRFVerify');
        }

        // ajax 返回
        if (IS_AJAX || IS_API) {
            dr_exit_msg(0, $message);
        }

        $this->viewPath = is_file(MYPATH.'View/errors/html/production.php') ? MYPATH.'View/errors/' : COREPATH.'View/errors/';

        return parent::render($exception, $statusCode);
    }
}