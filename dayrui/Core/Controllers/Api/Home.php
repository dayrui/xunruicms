<?php namespace Phpcmf\Controllers\Api;

/* *
 *
 * Copyright [2019] [李睿]
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
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



// 接口处理
class Home extends \Phpcmf\Common
{

	public function index() {


	    if (IS_API === 'pay') {
	        // 支付接口部分

            $info = pathinfo($_SERVER['PHP_SELF']);
            $name = basename($info['dirname']);
            $path = trim($info['dirname'], '/');
            $file = str_replace('_url.php', '_api.php', $info['basename']);
            $apifile = WEBPATH.$path.'/'.$file;

            !is_file($apifile) && exit('支付接口文件不存在');

            // 接口配置参数
            $config = $this->member_cache['payapi'][$name];

            require $apifile;

            exit;
        }

        $myfile = MYPATH.'Api/'.ucfirst(IS_API).'.php';

		!is_file($myfile) && exit('api file is error');

		require $myfile;
		exit;
	}

}
