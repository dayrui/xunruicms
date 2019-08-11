<?php namespace Phpcmf\Controllers\Member;

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



class Pay extends \Phpcmf\Common
{

    /**
     * 提交支付账单
     */
    public function index() {

        if (IS_POST) {
            $pay = \Phpcmf\Service::L('input')->post('pay');
            $pay['uid'] = $this->member['uid'];
            $pay['username'] = $this->member['username'];
            $money = floatval($pay['money']);
            if (!$money) {
                $this->_msg(0, dr_lang('金额(%s)不正确', $money));
                exit;
            }
            $rt = \Phpcmf\Service::M('Pay')->post($pay);
            if (!$rt['code']) {
                $this->_msg(0, $rt['msg']);exit;
            }
            if (IS_API_HTTP) {
                // 回调页面
                $this->_json($rt['code'], $rt['msg'], $rt['data']);exit;
            } else {
                // 跳转到支付页面
                $url = ROOT_URL.'index.php?s=api&c=pay&id='.$rt['code'];
                dr_redirect($url, 'auto');
            }
            exit;
        } else {
            $this->_msg(0, dr_lang('请求错误'));
        }
    }

}
