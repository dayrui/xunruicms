<?php namespace Phpcmf\Field;

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
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


class Pay extends \Phpcmf\Library\A_Field  {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['DECIMAL' => '10,2']; // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = 'DECIMAL'; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

        !$option['payfile'] && $option['payfile'] = 'buy.html';

	    $opt = '
	    <div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('模板文件').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="20" name="data[setting][option][payfile]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('模板位于./config/pay/模板文件名').'</span>
			</div>
		</div>
	    <div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('余额付款').'</label>
			<div class="col-md-9">
			<input type="checkbox" name="data[setting][option][is_finecms]" '.($option['is_finecms'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
			</div>
		</div>
	    ';

        $style = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['payfile'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
			</div>
		</div>
		';

        return [$opt, $style];
	}

    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = (float)\Phpcmf\Service::L('Field')->post[$field['fieldname']];
    }

    /**
     * 字段表单输入
     *
     * @return  string
     */
    public function input($field, $value = '', $html = []) {

        if (!defined('FC_PAY') && (IS_MEMBER || IS_ADMIN)) {
            // 字段显示名称
            $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];
            // 表单宽度设置
            $width = \Phpcmf\Service::_is_mobile() ? '100%' : ((int)$field['setting']['option']['width'] ? $field['setting']['option']['width'] : 250);

            // 表单附加参数
            $attr = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';" '.$field['setting']['validate']['formattr'];
            // 字段提示信息
            $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';
            // 当字段必填时，加入html5验证标签
            $required =  $field['setting']['validate']['required'] ? ' required="required"' : '';
            // 字段默认值
            $value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);
            // 字段默认值
            $value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);
            $ipt = '<input class="form-control '.$field['setting']['option']['css'].'" type="text" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$required.' '.$attr.' />';

            return $this->input_format($field['fieldname'], $text, $ipt.$tips);
        } else {

            // 付款金额
            $html['pay_value'] = $value ? $value : '';
            // 付款方式
            $html['pay_type'] = [];
            $html['pay_default'] = '';
            if (\Phpcmf\Service::C()->member
                && $field['setting']['option']['is_finecms']
                && is_file(ROOTPATH.'api/pay/finecms/config.php')) {
                // 余额支付
                $html['pay_default'] = 'finecms';
                $html['pay_type']['finecms'] = require ROOTPATH.'api/pay/finecms/config.php';
            }

            if (\Phpcmf\Service::C()->member_cache['payapi']) {
                foreach (\Phpcmf\Service::C()->member_cache['payapi'] as $name => $t) {
                    if (!is_file(ROOTPATH.'api/pay/'.$name.'/config.php')) {
                        continue; // 排除是否存在配置文件
                    }
                    !$html['pay_default'] && $html['pay_default'] = $name;
                    $html['pay_type'][$name] = require ROOTPATH.'api/pay/'.$name.'/config.php';
                }
            }

            // 付款界面模板
            $htmlfile = $field['setting']['option']['payfile'] && is_file(WEBPATH.'config/pay/'.$field['setting']['option']['payfile']) ? WEBPATH.'config/pay/'.$field['setting']['option']['payfile'] :  ROOTPATH.'config/pay/buy.html';
            if (!is_file($htmlfile)) {
                return '支付表单模板文件不存在：'.$htmlfile;
            }
            
            $member = \Phpcmf\Service::C()->member;
            $pay_url = \Phpcmf\Service::L('router')->member_url('pay/index');

            // 获取付款界面代码
            ob_start();
            $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
            require $file;
            $code = ob_get_clean();

            return $code;
        }
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $url = 'dr_iframe_show(\'cart\', \'/index.php?s=cart&c=show&m=index&fid='.$field['id'].'&id='.$this->id.'\')';
        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static"><a href="javascript:'.$url.'">¥'.$value.'元</a></div>');
    }
	
}