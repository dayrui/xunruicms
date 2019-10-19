<?php namespace Phpcmf\Extend;

/**
 * http://www.xunruicms.com
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
            log_message('critical',$exception->getMessage()
			."\n# " . FC_NOW_URL
			."\n# " .\Phpcmf\Service::M()->get_sql_query()
			. "\n{trace}", [
                'trace' => $exception->getTraceAsString(),
            ]);
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


        $file = $exception->getFile();
        $line = $exception->getLine();
        $title = get_class($exception);
        $message = $exception->getMessage();

        // 前端访问屏蔽敏感信息
        !IS_ADMIN && $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);

        if (empty($message)) {
            $message = '(null)';
        } elseif (strpos($message, 'The action you requested is not allowed') !== false) {
            $this->_save_error_file($statusCode, $title, $file, $line, $message);
            dr_exit_msg(0, '提交验证超时，请重试', 'CSRFVerify');
        } else {
            $this->_save_error_file($statusCode, $title, $file, $line, $message);
        }

        // ajax 返回
        if (IS_AJAX) {
            dr_exit_msg(0, $message);
        }

        $this->viewPath = COREPATH.'Views/errors/';

        return parent::render($exception, $statusCode);
    }

    private function _save_error_file($statusCode, $title, $file, $line, $message, $is_kz = 0) {

        if ($statusCode == 404) {
            return;
        }

        // 写入错误日志
        $filepath = WRITEPATH.'error_php/'.date('Y-m-d').'.php';
        $newfile = 0;

        $msg = '';
        if (!is_file($filepath) ) {
            $newfile = true;
            $msg .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n\n";
        }

        if ( $fp = @fopen($filepath, 'ab')) {
            $msg .= date('Y-m-d H:i:s').' --> '.$title."\n";
            $msg .= '文件: '.$file."\n";
            $msg .= '行号: '.$line."\n";
            $msg .= '错误: '.str_replace(PHP_EOL, '<br>', $message)."\n";
            $msg .= json_encode(['html' => $is_kz ? var_export($_POST, true) : self::highlightFile($file, $line)], JSON_UNESCAPED_UNICODE)."\n";
            $msg .= '查询: '.\Phpcmf\Service::M()->get_sql_query()."\n";
            $msg .= '地址: '.FC_NOW_URL."\n";
            $msg .= '来源: '.$_SERVER['HTTP_REFERER']."\n";
            $msg .= "\n\n";
        } else {
            return;
        }

        flock($fp, LOCK_EX);

        for ($written = 0, $length = strlen($msg); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($msg, $written))) === false) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if ($newfile) {
            chmod($filepath, 0777);
        }

        return $msg;
    }
}