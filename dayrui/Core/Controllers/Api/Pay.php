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



// 付款
class Pay extends \Phpcmf\Common
{

	// 付款
	public function index() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$data = \Phpcmf\Service::M('pay')->table('member_paylog')->get($id);
		if (!$data) {
			$this->_msg(0, dr_lang('该账单不存在'));exit;
		} elseif ($data['status'] == 1) {
			$this->_msg(0, dr_lang('该账单已被支付'));exit;
		}

		$apifile = ROOTPATH.'api/pay/'.$data['type'].'/pay.php';
		if (!is_file($apifile)) {
			$this->_msg(0, dr_lang('支付接口文件（%s）不存在', $data['type']));exit;
		}

		// 发起支付
		$rt = \Phpcmf\Service::M('pay')->dopay($apifile, $data);
		if (!$rt['code']) {
			$this->_msg(0, $rt['msg'], $rt['data']['url']);
			exit;
		} elseif (strlen($rt['data']['rturl']) > 10) {
			$this->_msg(1, $rt['msg'], $rt['data']['rturl']);
			exit;
		}
		
		$data['html'] = $rt['data'];
		if (SITE_IS_MOBILE && $this->_is_mobile()) {
		    // 开启了移动端时，支付判断模板是否是移动端的
            \Phpcmf\Service::V()->init('mobile');
        }

		\Phpcmf\Service::V()->assign([
			'pay' => $data,
			'pay_name' => dr_pay_type_html($data['type']),
			'meta_title' => $data['title']
		]);
        \Phpcmf\Service::V()->module('api');
		\Phpcmf\Service::V()->display('pay.html');exit;
	}

	/**
	 * 支付接口js-ajax回调
	 */
	public function ajax() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$data = \Phpcmf\Service::M()->table('member_paylog')->get($id);
		!$data && $this->_jsonp(0, dr_lang('支付记录不存在'));
		$data['status'] && $this->_jsonp(1, dr_lang('已经支付完成'));

		// 调用接口
		$apifile = ROOTPATH.'api/pay/'.$data['type'].'/notify_js.php';
		!is_file($apifile) && $this->_jsonp(0, dr_lang('支付接口文件不存在'));

		$return = [];
		$result = dr_string2array($data['result']);

		// 接口配置参数
		$config = $this->member_cache['payapi'][$data['type']];

		require $apifile;

		$this->_jsonp($return['code'], $return['msg']);
		exit;
	}

    /**
     * 支付接口返回
     */
    public function call() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M()->table('member_paylog')->get($id);
        !$data && $this->_msg(0, dr_lang('支付记录不存在'));

        // 支付回调钩子
        \Phpcmf\Hooks::trigger('pay_call', $data);

        if (!$this->uid) {
            $this->_msg(1, dr_lang('支付成功'));
        }


        if (SITE_IS_MOBILE && $this->_is_mobile()) {
            // 开启了移动端时，支付判断模板是否是移动端的
            \Phpcmf\Service::V()->init('mobile');
        }

        // 获取支付回调地址
        $url = \Phpcmf\Service::M('pay')->paycall_url($data);

        $this->_msg(1, dr_lang('支付成功'), $url);
    }
}
