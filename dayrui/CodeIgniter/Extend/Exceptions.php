<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use Config\Paths;
use Throwable;


/**
 * 继承异常类，用于Services.php
 */

class Exceptions extends \CodeIgniter\Debug\Exceptions {

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

        $message = $exception->getMessage();

        list($statusCode, $exitCode) = $this->determineCodes($exception);

        // Log it
        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes))
        {
            // 传入对象到日志中
            log_message('critical', $exception);
        }

		 // ajax 返回
        if (IS_AJAX || IS_API) {
			// 调试模式不屏蔽敏感信息
            if (strpos($message, 'Unable to connect to the database') !== false) {
                $message = '无法连接到数据库<br>'.$message;
            }
            $file = $exception->getFile();
            if (strpos($file, WRITEPATH.'template') !== false) {
                $message = '模板标签写法错误：'.$message;
                $arr = \Phpcmf\Service::V()->get_view_files();
                if ($arr) {
                    $one = current($arr);
                    $message.= '（'.CI_DEBUG ? $one['path'] : basename($one['path']).'）';
                }
            }
            if (CI_DEBUG) {
                $message.= '<br>模板标签解析文件：'.$file.'（'.$exception->getLine().'）';
                $message.= '<br>错误页面访问地址：'.\Phpcmf\Service::V()->now_php_url();
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
        if (empty($message)) {
            $message = '(null)';
        }

        if (strpos($message, 'Unable to connect to the database') !== false) {
            $message = '无法连接到数据库<br>'.$message;
        }

        // 调试模式不屏蔽敏感信息
        if (CI_DEBUG) {
            $message.= '<br>'.$exception->getFile().'（'.$exception->getLine().'）';
        } else {
            $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
        }

        if (strpos($message, 'The action you requested is not allowed') !== false) {
            dr_exit_msg(0, '提交验证超时，请重试', 'CSRFVerify');
        }

        // ajax 返回
        if (IS_AJAX || IS_API) {
            dr_exit_msg(0, $message);
        }

        $this->viewPath = is_file(MYPATH.'View/errors/html/production.php') ? MYPATH.'View/errors/' : FRAMEPATH.'View/errors/';
        // Determine possible directories of error views
        $path    = $this->viewPath;
        $altPath = rtrim((new Paths())->viewDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR;

        $path    .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;
        $altPath .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;

        // Determine the views
        $view    = $this->determineView($exception, $path);
        $altView = $this->determineView($exception, $altPath);

        // Check if the view exists
        if (is_file($path . $view)) {
            $viewFile = $path . $view;
        } elseif (is_file($altPath . $altView)) {
            $viewFile = $altPath . $altView;
        }

        if (! isset($viewFile)) {
            echo 'The error view files were not found. Cannot render exception trace.';

            exit(1);
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_clean();
        }

        echo(function () use ($exception, $statusCode, $viewFile, $message): string {
            $vars = $this->collectVars($exception, $statusCode);
            extract($vars, EXTR_SKIP);
            $file = $exception->getFile();
            $is_template = false;
            $line_template = 0;
            if (strpos($file, WRITEPATH.'template') !== false) {
                list($a, $b) = explode('on line ', $exception->getMessage());
                if (is_numeric($b)) {
                    $line_template = $b;
                }
                $message = '模板标签写法错误：'.$message;
                $is_template = \Phpcmf\Service::V()->get_view_files();
            }
            ob_start();
            include $viewFile;

            return ob_get_clean();
        })();
    }

}