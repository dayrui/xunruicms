<?php
namespace Phpcmf\Extend;

/* *
 *
 * Copyright [2018] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


/**
 * 继承主类，用于init.php
 */

class CodeIgniter extends \CodeIgniter\CodeIgniter
{
    /**
     * 初始化程序
     */
    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 自定义函数库
        if (is_file(WEBPATH.'config/custom.php'))
        {
            require WEBPATH.'config/custom.php';
        }
        if (is_file(MYPATH.'Helper.php'))
        {
            require MYPATH.'Helper.php';
        }
        require CMSPATH.'Core/Helper.php';
    }

    /**
     * 初始化方法
     */
    public function initialize()
    {
        parent::initialize();
        \Phpcmf\Hooks::trigger('init');
    }

    /**
     * 不执行此方法
     * 自定义错误显示和debug开关
     */
    protected function bootstrapEnvironment() {

    }

    /**
     * 页面缓存名称定义
     */
    protected function generateCacheName($config): string
    {
        return 'page-'.md5(FC_NOW_URL);
    }


}

/**
 * 全局返回消息
 */
function dr_exit_msg($code, $msg, $data = []) {


    ob_end_clean();

    $rt = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ];

    if (isset($_GET['callback'])) {
        // jsonp
        @header('HTTP/1.1 200 OK');
        echo ($_GET['callback'] ? $_GET['callback'] : 'callback').'('.json_encode($rt, JSON_UNESCAPED_UNICODE).')';
    } else if (($_GET['is_ajax'] || (defined('IS_API_HTTP') && IS_API_HTTP) || IS_AJAX)) {
        // json
        @header('HTTP/1.1 200 OK');
        echo json_encode($rt, JSON_UNESCAPED_UNICODE);
    } else {
        // html
        exit($msg);
    }
    exit;
}