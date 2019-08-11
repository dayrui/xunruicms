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



// Http接口处理
class Http extends \Phpcmf\Common
{

    /**
     * 调用接口
     */
    public function index() {

        $this->_api_auth();

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!$id) {
            $this->_json(0, '未获取到接口id');
        }

        $data = $this->get_cache('api_http', $id);
        if (!$data) {
            $this->_json(0, '接口数据【'.$id.'】不存在');
        }

        $rt = \Phpcmf\Service::M('api')->get_api_data($data);
        $this->_json($rt['code'], $rt['msg'], $rt['data']);

        exit;
    }

    /**
     * 接口测试
     */
    public function test() {
        $this->_api_auth();
        $this->_json(1, 'ok');
    }

}
