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



// 交易下单
class Buy extends \Phpcmf\Common {

    public function index() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $fid = (int)\Phpcmf\Service::L('input')->get('fid');
        (!$fid || !$id) && exit($this->_msg(0, dr_lang('支付参数不完整')));

        $num = max(1, (int)\Phpcmf\Service::L('input')->get('num'));
        $sku = dr_safe_replace(\Phpcmf\Service::L('input')->get('sku'), 'undefined');

        $field = $this->get_cache('table-field', $fid);
        !$field && exit($this->_msg(0, dr_lang('支付字段不存在')));

        // 获取付款价格
        $rt = \Phpcmf\Service::M('pay')->get_pay_info($id, $field, $num, $sku);
        isset($rt['code']) && !$rt['code'] && exit($this->_msg(0, $rt['msg']));

        // 挂钩点 购买商品之前
        \Phpcmf\Hooks::trigger('member_buy', $rt);

        \Phpcmf\Service::V()->assign($rt);
        \Phpcmf\Service::V()->assign([
            'num' => $rt['num'],
            'price' => $rt['price'],
            'total' => $rt['total'],
            'payform' => dr_payform($rt['mid'], $rt['total'], $rt['title'].$rt['sku_string'], $rt['url']),
            'meta_title' => dr_lang('在线付款').SITE_SEOJOIN.SITE_NAME,
            'meta_keywords' => $this->get_cache('site', SITE_ID, 'config', 'SITE_KEYWORDS'),
            'meta_description' => $this->get_cache('site', SITE_ID, 'config', 'SITE_DESCRIPTION')
        ]);
        \Phpcmf\Service::V()->display('buy.html');
    }
}
