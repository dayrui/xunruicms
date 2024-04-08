<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use Config\Paths;
use Config\Services;
use Throwable;


/**
 * 继承异常类，用于Services.php
 */

class Exceptions extends \CodeIgniter\Debug\Exceptions {

    private $_is_404 = 0;

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

        $message = $this->_cn_msg($exception->getMessage());

        list($statusCode, $exitCode) = $this->determineCodes($exception);

        // Log it
        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes))
        {
            // 传入对象到日志中
            log_message('critical', $exception);
        }

        $this->request  = Services::request();
        $this->response = Services::response();

		 // ajax 返回
        if (IS_AJAX || IS_API) {
			// 调试模式不屏蔽敏感信息
            $file = $exception->getFile();
            if (strpos($file, WRITEPATH.'template') !== false) {
                $file = $this->_rp_file($file);
                $message = '模板标签写法错误：'.$message;
                $arr = \Phpcmf\Service::V()->get_view_files();
                if ($arr) {
                    $one = current($arr);
                    $message.= '（'.CI_DEBUG ? $one['path'] : basename($one['path']).'）';
                }
            }
            if (CI_DEBUG) {
                $message.= '<br>错误文件：'.$file.'（'.$exception->getLine().'）';
                $message.= '<br>访问地址：'.\Phpcmf\Service::V()->now_php_url();
                $trace = $exception->getTrace();
                if ($trace) {
                    foreach ($trace as $t) {
                        if (strpos($t['file'], FRAMEPATH) === false) {
                            $message.= '<br>'.$t['function'].'：'.$t['file'].'（'.$t['line'].'）';
                        }
                    }
                }
            } else {
                $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
            }
            dr_exit_msg(0, $message);
        }

        if (! is_cli())
        {
            if (!$statusCode) {
                $statusCode = 201;
            }
            $this->response->setStatusCode($statusCode);
            $header = "HTTP/{$this->request->getProtocolVersion()} {$this->response->getStatusCode()} {$this->response->getReason()}";
            header($header, true, $statusCode);

            if (strpos($this->request->getHeaderLine('accept'), 'text/html') === false)
            {
                $this->respond(IS_DEV ? $this->collectVars($exception, $statusCode) : '', $statusCode)->send();

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

        $message = $this->_cn_msg($exception->getMessage());
        if (empty($message)) {
            $message = '(null)';
        }


        // 调试模式不屏蔽敏感信息
        if (CI_DEBUG) {
            if ($this->_is_404) {
                //,404页面不显示路径
            } else {
                $message.= '<br>'.$this->_rp_file($exception->getFile()).'（'.$exception->getLine().'）';
            }
        } else {
            $message = str_replace([FCPATH, WEBPATH], ['/', '/'], $message);
        }

        if (strpos($message, 'The action you requested is not allowed') !== false) {
            dr_exit_msg(0, '提交CSRF验证超时，请重试', 'CSRFVerify');
        }

        // ajax 返回
        if (IS_AJAX || IS_API) {
            dr_exit_msg(0, $message);
        }

        $path = $this->viewPath = is_file(MYPATH.'View/errors/html/production.php') ? MYPATH.'View/errors/' : FRAMEPATH.'View/errors/';
        // Determine possible directories of error views
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
            $file = $this->_rp_file($exception->getFile());
            $is_template = false;
            $line_template = 0;
            if (strpos($file, WRITEPATH.'template') !== false) {
                list($a, $b) = explode('on line ', $this->_cn_msg($exception->getMessage()));
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

    /**
     * 中文翻译输出的错误信息
     */
    private function _cn_msg($message) {

        if (!$message) {
            return $message;
        }

        if (strpos($message, 'Unable to connect to the database') !== false) {
            $message.= '<br>无法连接到数据库，检查数据库是否启动或者数据库配置文件不对，config/database.php';
        } elseif (strpos($message, 'Unclosed \'{\'') !== false) {
            $message.= '<br>循环体或者if语句，缺少结束语句，{ }没有成对出现';
        } elseif (strpos($message, 'Cannot access offset of type string on string') !== false) {
            $message.= '<br>此变量是字符串，不能使用数组的方式调用他，检查下代码语法';
        } elseif (strpos($message, 'Call to undefined function') !== false) {
            $message.= '<br>'.str_replace('Call to undefined function', '函数没有定义', $message);
        } elseif (strpos($message, 'open_basedir restriction in effect') !== false) {
            $message.= '<br>目录被限制读取，需要设置.users.ini文件中的目录白名单';
        } elseif (strpos($message, 'Undefined constant') !== false) {
            $message.= '<br>'.str_replace('Undefined constant', '变量或者常量没有定义', $message);
        } elseif (preg_match("/Table '(.+)' doesn't exist/", $message, $mt)) {
            $message.= '<br>数据库表'.$mt[1].'不存在，表丢失或者表没有创建成功';
        } elseif (preg_match("/Unknown column '(.+)' in 'field list'/", $message, $mt)) {
            $message.= '<br>表中没有字段'.$mt[1].'，字段没有被创建';
        } elseif (preg_match("/Access level to (.+) must be protected \(as in class (.+)\) or weaker/U", $message, $mt)) {
            $message.= '<br>'.$mt[1].'在类'.$mt[2].'中已经被定义过更高级别的权限，请删除本文件的定义代码';
        } elseif (preg_match("/Creation of dynamic property (.+) is deprecated/", $message, $mt)) {
            $message.= '<br>动态属性被废除'.$mt[1].'，请预先定义';
        } elseif (preg_match("/Failed opening required '(.+)'/", $message, $mt)) {
            $message.= '<br>文件'.$mt[1].'不存在，文件丢失或者文件没有创建成功';
        } elseif (preg_match("/syntax error, unexpected token (.+)/", $message, $mt)) {
            $message.= '<br>PHP语法错误 或者 模板标签语法错误，检查上下行代码是否写对';
        } elseif (preg_match("/Cannot declare class (.+), because the name is already in use/", $message, $mt)) {
            $message.= '<br>类名'.$mt[1].'重复，全文搜索下哪个地方被重复命名了';
        } elseif (preg_match("/Controller method is not found: (.+)/", $message, $mt)) {
            $message.= '<br>检查此文件中是否有'.$mt[1].'方法名：'.$this->_get_file();
            $this->_is_404 = 1;
        } elseif (preg_match("/Controller or its method is not found:(.+)/", $message, $mt)) {
            $message.= '<br>检查此文件是否存在：'.$this->_get_file().'，检查地址是否正确，注意控制器文件首字母要大写';
            $this->_is_404 = 1;
        } elseif (preg_match("/count\(\): Argument #1 \((.+)\) must be of type Countable\|array/", $message, $mt)) {
            $message.= '<br>需要将count函数改为dr_count';
        } elseif (IS_XRDEV) {
            echo '需要入库cn_msg<br>';
            var_dump($message);
        }

        return $message;
    }

    /**
     * 替换模板文件显示完整路径
     */
    private function _rp_file($file) {

        if (strpos((string)$file, '.cache.php') !== false && strpos((string)$file, '_DS_') !== false) {
            $file = str_replace([WRITEPATH.'template/', '_DS_', '.cache.php'], ['', '/', ''], $file);
        }

        return $file;
    }

    /**
     * 获取控制器地址
     */
    private function _get_file() {
        $file = APPPATH;
        if ($file == FRAMEPATH) {
            $file = CMSPATH.'Control';
        } else {
            $file.= 'Controllers';
        }

        if (IS_ADMIN) {
            $file.= '/Admin';
        } elseif (IS_MEMBER) {
            $file.= '/Member';
        } elseif (IS_API) {
            $file.= '/Api';
        }

        return $file.'/'.ucfirst(\Phpcmf\Service::L('Router')->class).'.php';
    }

    /**
     * 显示完整路径
     */
    public static function cleanPath(string $file): string
    {
        return $file;
    }
}