<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Pays extends \Phpcmf\Library\A_Field  {

    protected $showfield = [
        'image' => '图片',
        'price' => '价格',
        'quantity' => '数量',
        'sn' => '编码',
    ];

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
	public function option($option, $field = null) {

        $myfield = $this->showfield;
        !$option['field'] && $option['field'] = [];
        !$option['payfile'] && $option['payfile'] = 'buy.html';
        foreach ($field as $t) {
            $t['fieldtype'] == 'Paystext' && $myfield[$t['fieldname']] = $t['name'];
        }
        $param_html = '';
        foreach ($myfield as $id => $t) {
            $param_html.= '<p style="margin-bottom:10px">';
            $param_html.= '<input type="checkbox" name="data[setting][option][field][]" '.(dr_in_array($id, $option['field']) ? 'checked' : '').' value="'.$id.'" data-on-text="'.dr_lang('%s已显示', dr_lang($t)).'" data-off-text="'.dr_lang('%s已禁用', dr_lang($t)).'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
		';
            $param_html.= '</p>';
        }

        $tpl_group = $this->_get_tpl_group('data[setting][option][sku]');
        $tpl_value = $this->_get_tpl_value('data[setting][option][sku]');
        $value = $option;

        // 显示字段

        $result = '';
        if (isset($value['sku']['group']) && $value['sku']['group']) {
            foreach ($value['sku']['group'] as $id => $name) {
                $html = '';
                if (isset($value['sku']['name'][$id]) && $value['sku']['name'][$id]) {
                    foreach ($value['sku']['name'][$id] as $iid => $vname) {
                        $html.= str_replace(
                            ['{id}', '{name}', '{iid}'],
                            [$id, $vname, $iid],
                            $tpl_value
                        );
                    }
                }
                $result.= str_replace(
                    ['{id}', '{name}', '{value}'],
                    [$id, $name, $html],
                    $tpl_group
                );
            }
        }


	    $opt = '
	    <div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('模板文件').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="20" name="data[setting][option][payfile]" value="'.$option['payfile'].'"></label>
				<span class="help-block">'.dr_lang('模板位于./config/pay/模板文件名').'</span>
			</div>
		</div>
		<div class="form-group">
            <label class="col-md-2 control-label">'.dr_lang('定价模式').'</label>
            <div class="col-md-9">
                <div class="mt-radio-inline">
                    <label class="mt-radio mt-radio-outline"><input  type="radio" value="0" name="data[setting][option][close_one]" '.(!$option['close_one'] ? 'checked' : '').' > '.dr_lang('单一定价+组合定价').' <span></span></label>
                 &nbsp; &nbsp;
                    <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][close_one]" '.($option['close_one'] ? 'checked' : '').' > '.dr_lang('组合定价').' <span></span></label>
                   
                </div>
                <span class="help-block">'.dr_lang('可以选择单一定价+组合定价模式、组合定价模式两种方式').'</span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-2 control-label">'.dr_lang('固定组合规格').'</label>
            <div class="col-md-9">
                <div class="mt-radio-inline">
                    <label class="mt-radio mt-radio-outline"><input onclick="$(\'.gdggb\').show()" type="radio" value="1" name="data[setting][option][is_sku]" '.($option['is_sku'] ? 'checked' : '').' > '.dr_lang('开启').' <span></span></label>
                    &nbsp; &nbsp;
                    <label class="mt-radio mt-radio-outline"><input onclick="$(\'.gdggb\').hide()" type="radio" value="0" name="data[setting][option][is_sku]" '.(!$option['is_sku'] ? 'checked' : '').' > '.dr_lang('关闭').' <span></span></label>
                   
                </div>
                <span class="help-block">'.dr_lang('固定规格会调用指定的规格表，用户不能修改和添加属性').'</span>
            </div>
        </div>
		<div class="form-group gdggb" style="display: '.($option['is_sku'] ? 'blank' : 'none').'" >
            <label class="col-md-2 control-label">'.dr_lang('指定规格表').'</label>
            <div class="col-md-9">
            <div id="dr_field_pays">
                <label><button type="button" class="btn blue btn-sm" onclick="dr_sku_add_group()"> <i class="fa fa-plus"></i> '.dr_lang('添加属性').'</button></label>
                <div class="portlet light bordered">
                    <div id="dr_sku_result">
                        '.$result.'
                    </div>
                </div>
                <script type="text/javascript">
                var arrayValue = new Array();
                var tpl_group = "'.$this->_js_var($tpl_group).'";
                var tpl_value = "'.$this->_js_var($tpl_value).'";
                var field_name = "_sku";
                var sku_field_name = "";
                var sku_field_id = "";
                </script>
                <script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/sku.js"></script>
                <script type="text/javascript">
                $(function(){
                    dr_sku_init();
                });
                </script>
            </div>
            </div>
        </div>
	    <div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('余额付款').'</label>
			<div class="col-md-9">
			<input type="checkbox" name="data[setting][option][is_finecms]" '.($option['is_finecms'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('已开启').'" data-off-text="'.dr_lang('已关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
			</div>
		</div>
	    <div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('显示参数').'</label>
			<div class="col-md-9">
			         '.$param_html.'
			</div>
		</div>
	    ';

        $style = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
			</div>
		</div>
		';

        return [$this->_search_field().$opt, $style];
	}

    /**
     * 创建sql语句
     */
    public function create_sql($name, $value, $cname = '') {
        $sql = 'ALTER TABLE `{tablename}` ADD `'.$name.'` DECIMAL(9,2) NULL , ADD `'.$name.'_sku` TEXT NULL , ADD `'.$name.'_quantity` INT(10) UNSIGNED NULL , ADD `'.$name.'_sn` VARCHAR(10) NULL';
        return $sql;
    }

    /**
     * 修改sql语句
     */
    public function alter_sql($name, $value, $cname = '') {
        return NULL;
    }

    /**
     * 删除sql语句
     */
    public function drop_sql($name) {
        $sql = 'ALTER TABLE `{tablename}` DROP `'.$name.'`, DROP `'.$name.'_sku`, DROP `'.$name.'_quantity`, DROP `'.$name.'_sn`';
        return $sql;
    }

    // 测试字段是否被创建成功，默认成功为0，需要继承开发
    public function test_sql($tables, $field) {

        if (!$tables) {
            return 0;
        }

        foreach ($tables as $table) {
            if (!\Phpcmf\Service::M()->db->fieldExists($field.'_sku', $table)) {
                return '给表['.$table.']创建字段['.$field.'_sku'.']失败';
            }
            if (!\Phpcmf\Service::M()->db->fieldExists($field.'_quantity', $table)) {
                return '给表['.$table.']创建字段['.$field.'_quantity'.']失败';
            }
            if (!\Phpcmf\Service::M()->db->fieldExists($field.'_sn', $table)) {
                return '给表['.$table.']创建字段['.$field.'_sn'.']失败';
            }
            if (!\Phpcmf\Service::M()->db->fieldExists($field, $table)) {
                return '给表['.$table.']创建字段['.$field.']失败';
            }
        }

        return 0;
    }

    // 显示字段
    protected function _get_myfield($field) {

        $my = [];
        $_field = \Phpcmf\Service::L('field')->fields;
        foreach ($field['setting']['option']['field'] as $ff) {
            if (isset($this->showfield[$ff])) {
                $my[$ff] = $this->showfield[$ff];
            } elseif (isset($_field[$ff])) {
                $my[$ff] = $_field[$ff]['name'];
            } else {
                continue;
            }
        }

        return $my;
    }

    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {

        if (isset($field['setting']['option']['close_one']) && $field['setting']['option']['close_one']) {
            $is_field_pay = 1;
        } else {
            $is_field_pay = (int)$_POST['is_field_pay'];
        }

        if ($is_field_pay) {
            // 组合
            $price = 0;
            $quantity = 0;
            if (isset($field['setting']['option']['is_sku']) && $field['setting']['option']['is_sku']) {
                $sku = $field['setting']['option']['sku'];
                $sku['value'] = $_POST['data'][$field['fieldname'].'_sku']['value'];
            } else {
                $sku = $_POST['data'][$field['fieldname'].'_sku'];
            }
            if ($sku['value']) {
                $price_array = [];
                foreach ($sku['value'] as $v) {
                    $quantity+= intval($v['quantity']);
                    $price_array[] = $v['price'];
                }
                $price = min($price_array);
            }
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = (float)$price;
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_sn'] = '';
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_sku'] = dr_array2string($sku);
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_quantity'] = abs((int)$quantity);
        } else {
            // 单一
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = (float)$_POST[$field['fieldname']]['price'];
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_sn'] = dr_safe_replace($_POST[$field['fieldname']]['sn']);
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_sku'] = '';
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_quantity'] = (int)$_POST[$field['fieldname']]['quantity'];
            $_field = \Phpcmf\Service::L('form')->fields;
            foreach ($field['setting']['option']['field'] as $ff) {
                if (isset($_field[$ff])) {
                    \Phpcmf\Service::L('Field')->data[$_field[$ff]['ismain']][$ff] = (string)$_POST[$field['fieldname']][$ff];
                }
            }
        }
    }


    /**
     * 获取附件id
     */
    public function get_attach_id($value) {

        if (!$this->field) {
            return NULL;
        } elseif (!dr_in_array('image', $this->field['setting']['option']['field'])) {
            return NULL;
        }

        $data = [];
        $value = dr_string2array($value);

        if ($value) {
            foreach ($value as $tt) {
                if (isset($tt['image']) && $tt['image']) {
                    $data[] = intval($tt['image']);
                }
            }
        }

        return $data;
    }

    /**
     * 附件处理
     */
    public function attach($data, $_data) {

        if (!$this->field) {
            return NULL;
        }

        $data = isset($_POST['data'][$this->field['fieldname'].'_sku']['value']) ? $_POST['data'][$this->field['fieldname'].'_sku']['value'] : [];
        if (isset(\Phpcmf\Service::L('Field')->old[$this->field['fieldname'].'_sku'])) {
            $old = dr_string2array(\Phpcmf\Service::L('Field')->old[$this->field['fieldname'].'_sku']);
            $_data = isset($old['value']) ? $old['value'] : [];
        }

        $data = $this->get_attach_id($data);
        $_data = $this->get_attach_id($_data);

        if (!isset($_data)) {
            $_data = [];
        }

        if (!isset($data)) {
            $data = [];
        }

        // 新旧数据都无附件就跳出
        if (!$data && !$_data) {
            return NULL;
        }

        // 新旧数据都一样时表示没做改变就跳出
        if (dr_diff($data, $_data)) {
            return NULL;
        }

        // 当无新数据且有旧数据表示删除旧附件
        if (!$data && $_data) {
            return [
                [],
                $_data
            ];
        }

        // 当无旧数据且有新数据表示增加新附件
        if ($data && !$_data) {
            return [
                $data,
                []
            ];
        }

        // 剩下的情况就是删除旧文件增加新文件

        // 新旧附件的交集，表示固定的
        $intersect = array_intersect($data, $_data);

        return [
            array_diff($data, $intersect), // 固有的与新文件中的差集表示新增的附件
            array_diff($_data, $intersect), // 固有的与旧文件中的差集表示待删除的附件
        ];
    }

    /**
     * 验证必填字段值
     *
     * @param	string	$field	字段类型
     * @param	string	$value	字段值
     * @return
     */
    public function check_required($field, $value) {

        if (isset($field['setting']['option']['close_one']) && $field['setting']['option']['close_one']) {
            $is_field_pay = 1;
        } else {
            $is_field_pay = (int)$_POST['is_field_pay'];
        }

        if ($is_field_pay) {
            if (isset($field['setting']['option']['is_sku']) && $field['setting']['option']['is_sku']) {
                $sku = $field['setting']['option']['sku'];
                $sku['value'] = $_POST['data'][$field['fieldname'].'_sku']['value'];
            } else {
                $sku = $_POST['data'][$field['fieldname'].'_sku'];
            }
            if ($sku['value']) {
                foreach ($sku['value'] as $v) {
                    if (strlen($v['price']) == 0) {
                        return dr_lang('%s不能为空', $field['name']);
                    }
                }
            }
        } else {
            if (strlen($_POST[$field['fieldname']]['price']) == 0) {
                return dr_lang('%s不能为空', $field['name']);
            }
        }

        return '';
    }

    /**
     * 字段值
     */
    public function get_value($name, $data) {

        $value = [
            'price' => $data[$name],
            'sku' => dr_string2array($data[$name.'_sku']),
            'sn' => $data[$name.'_sn'],
            'quantity' => $data[$name.'_quantity'],
        ];

        $field = \Phpcmf\Service::L('field')->fields;

        foreach ($field[$name]['setting']['option']['field'] as $ff ) {
            if ($field[$ff]['fieldtype'] == 'Paystext') {
                $value[$ff] = (string)$data[$ff];
            }
        }

        return $value;
    }

    /**
     * 支付时获取商品属性
     */
    public function get_pay_info($rt, $field, $sku) {

        $rt[$field['fieldname'].'_sku'] = dr_string2array($rt[$field['fieldname'].'_sku']);
        if (!$sku && $rt[$field['fieldname'].'_sku']) {
            return dr_return_data(0, dr_lang('没有选择商品属性'));
        } elseif (!isset($rt[$field['fieldname'].'_sku']['value'][$sku]) || !$rt[$field['fieldname'].'_sku']['value'][$sku]) {
            return dr_return_data(0, dr_lang('商品(#'.$rt['id'].')属性（#'.$sku.'）无效'));
        }

        $sn = (string)$rt[$field['fieldname'].'_sku']['value'][$sku]['sn'];
        $quantity = (int)$rt[$field['fieldname'].'_sku']['value'][$sku]['quantity'];
        list($sku_name, $sku_string) = dr_sku_name($sku, $rt[$field['fieldname'].'_sku'], 1);

        return dr_return_data(1, '', [$sn, $quantity, $sku_name, $sku_string]);
    }

    /**
     * 字段输出
     *
     * @param	array	$value	值
     * @return  string
     */
    public function output($value) {
        return (float)$value;
    }

    protected function _get_tpl_value($name) {
        return '
            <div class="fc-sku-group-value col-md-4" id="dr_sku_value_{id}_{iid}" did="{iid}">
		        <div class="input-group input-group-sm">
                    <input type="text" class="fc-sku-value-name-input form-control" onblur="dr_sku_init()" name="'.$name.'[name][{id}][{iid}]" fname="{id}_{iid}" value="{name}">
                    <span class="input-group-btn">
                        <button class="btn red" onclick="javascript:dr_sku_del_value(\'{id}\', \'{iid}\');" type="button"><i class="fa fa-trash"></i></button>
                    </span>
                </div>
            </div>
            ';
    }
    protected function _get_tpl_group($name) {

        return '
            <div class="portlet-body fc-sku-group" id="dr_sku_group_{id}" did="{id}">
                <input type="hidden" id="dr_sku_group_text_{id}" name="'.$name.'[group][{id}]" value="{name}">
		        <div class="row fc-sku-group-name">
                    <div class="col-md-6 fc-sku-group-name-input">{name}</div>
                    <div class="text-right col-md-6">
                        <button onclick="javascript:dr_sku_add_value(\'{id}\');" type="button" class="btn green btn-sm"> '.dr_lang('添加值').'</button>
                        <button onclick="javascript:dr_sku_edit_group(\'{id}\');" type="button" class="edit btn blue btn-sm"> '.dr_lang('修改').'</button>
                        <button onclick="javascript:dr_sku_save_group(\'{id}\');" type="button" class="save btn blue btn-sm" style="display:none"> '.dr_lang('保存').'</button>
                        <button onclick="javascript:dr_sku_del_group(\'{id}\');" type="button" class="btn red btn-sm"> '.dr_lang('删除').'</button>
                    </div>
                </div>
		        <div class="row fc-sku-group-body" id="dr_sku_value_{id}">
		        {value}
                </div>
            </div>
            ';
    }

    /**
     * 字段表单输入
     *
     * @return  string
     */
    public function input($field, $value = [], $html = []) {

        if (!defined('FC_PAY') && (IS_MEMBER || IS_ADMIN)) {

            if (!$field['setting']['option']['field']) {
                return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static">字段Pays没有设置显示参数</div>');
            }

            // 字段显示名称
            $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

            // 字段提示信息
            $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

            $tpl_group = $this->_get_tpl_group('data['.$field['fieldname'].'_sku]');

            $tpl_value = $this->_get_tpl_value('data['.$field['fieldname'].'_sku]');

            $image_url ='/'.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p='.dr_authcode([
                'size' => 10,
                'count' => 1,
                'exts' => 'jpg,gif,png',
                'attachment' => 0,
                'image_reduce' => 0,
            ], 'ENCODE').'&ct=0&one=1';
            // 显示字段
            $pay_html = ''; // 单一模式下的输出
            $myfield = $this->_get_myfield($field);
            $sku_field_name = '';
            $sku_field_id = [];
            foreach ($myfield as $ff => $name) {
                if ($ff == 'image') {

                } else {
                    $pay_html.= '<div class="form-group">
                            <label class="col-md-2 control-label">'.$name.'</label>
                            <div class="col-md-7">
                                <input type="text" name="'.$field['fieldname'].'['.$ff.']" value="'.(isset($value[$ff]) ? $value[$ff] : '').'" class="form-control input-inline input-medium">
                            </div>
                        </div>';
                }

                $sku_field_name.= '<th>'.$name.'</th>';
                $sku_field_id[] = $ff;
            }


            if (isset($field['setting']['option']['is_sku']) && $field['setting']['option']['is_sku']) {
                $value['sku']['name'] = $field['setting']['option']['sku']['name'];
                $value['sku']['group'] = $field['setting']['option']['sku']['group'];
            }

            $result = '';
            if (isset($value['sku']['group']) && $value['sku']['group']) {
                foreach ($value['sku']['group'] as $id => $name) {
                    $html = '';
                    if (isset($value['sku']['name'][$id]) && $value['sku']['name'][$id]) {
                        foreach ($value['sku']['name'][$id] as $iid => $vname) {
                            $html.= str_replace(
                                ['{id}', '{name}', '{iid}'],
                                [$id, $vname, $iid],
                                $tpl_value
                            );
                        }
                    }
                    $result.= str_replace(
                        ['{id}', '{name}', '{value}'],
                        [$id, $name, $html],
                        $tpl_group
                    );
                }
            }

            $ovalue = [];
            if (isset($value['sku']['value']) && $value['sku']['value']) {
                foreach ($value['sku']['value'] as $ii => $t) {
                    foreach ($sku_field_id as $if) {
                        $ovalue[$ii.'_'.$if] = $t[$if];
                        if ($if == 'image' && $t[$if]) {
                            $ovalue[$ii.'_'.$if.'_url'] = dr_get_file($t[$if]);
                        }
                    }
                }
            }

            $str = '';

            // 是否单一模式
            if (isset($field['setting']['option']['close_one']) && $field['setting']['option']['close_one']) {
                $is_field_pay = 1;
                $sku_html = '<p class="margin-bottom-20">
                    <label><button type="button" class="btn blue btn-sm" onclick="dr_sku_add_group()"> <i class="fa fa-plus"></i> '.dr_lang('添加属性').'</button></label>
                    <label><button type="button" class="btn green btn-sm" onclick="dr_sku_init()"> <i class="fa fa-refresh"></i> '.dr_lang('更新属性').'</button></label>
                </p>
                <div class="portlet light isbordered">
                    <div id="dr_sku_result">
                        '.$result.'
                    </div>
                </div>';
            } else {
                $is_field_pay = $result && $ovalue ? 1 : 0;
                $str.= '<div class="mt-radio-inline">
                <label class="mt-radio">
                    <input type="radio" onclick="$(\'#dr_field_pay\').show();$(\'#dr_field_pays\').hide();" name="is_field_pay" value="0" '.(!$is_field_pay ? 'checked' : '').'> '.dr_lang('单一价格').'
                    <span></span>
                </label>
                <label class="mt-radio">
                    <input type="radio" onclick="$(\'#dr_field_pays\').show();$(\'#dr_field_pay\').hide();" name="is_field_pay" value="1" '.($is_field_pay ? 'checked' : '').'> '.dr_lang('组合价格').'
                    <span></span>
                </label>
            </div>';
                $sku_html = '<p class="margin-bottom-20">
                    <label><button type="button" class="btn blue btn-sm" onclick="dr_sku_add_group()"> <i class="fa fa-plus"></i> '.dr_lang('添加属性').'</button></label>
                    <label><button type="button" class="btn green btn-sm" onclick="dr_sku_init()"> <i class="fa fa-refresh"></i> '.dr_lang('更新属性').'</button></label>
                </p>
                <div class="portlet light isbordered">
                    <div id="dr_sku_result">
                        '.$result.'
                    </div>
                </div>';
            }


            $str.= '
           
            <div id="dr_field_pay" style="display:'.(!$is_field_pay ? 'block' : 'none').';">
                <div class="portlet light isbordered">
                   <div class="form-body" style="padding:30px 0 10px 0">
                        '.$pay_html.'
                   </div>
                </div>
            </div>
            <div id="dr_field_pays" style="display:'.($is_field_pay ? 'block' : 'none').';">
                '.$sku_html.'
                <div id="dr_sku_table">
                </div>
                <script type="text/javascript">
                var arrayValue = new Array();
                var tpl_group = "'.$this->_js_var($tpl_group).'";
                var tpl_value = "'.$this->_js_var($tpl_value).'";
                var field_name = "'.$field['fieldname'].'_sku";
                var sku_field_name = "'.$this->_js_var($sku_field_name).'";
                var sku_image_url = "'.$image_url.'";
                var sku_field_id = '.dr_array2string($sku_field_id).';
                arrayValue = '.($ovalue ? dr_array2string($ovalue) : 'new Array()').';
                </script>
                <script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/sku.js"></script>
                <script type="text/javascript">
                $(function(){
                    dr_sku_init();
                });
                </script>
            </div>
            ';
            return $this->input_format($field['fieldname'], $text, $str.$tips);
        } else {

            // 付款金额
            $html['pay_value'] = $value ? $value : 0;
            if (!dr_is_app('pay')) {
                return '没有安装「支付系统」插件';
            }
            // 付款方式
            $html['pay_type'] = \Phpcmf\Service::M('pay', 'pay')->get_pay_type(\Phpcmf\Service::C()->member && $field['setting']['option']['is_finecms']  && is_file(ROOTPATH.'api/pay/finecms/config.php'));

            // 取默认第一个
            if ($html['pay_type']) {
                reset($html['pay_type']);
                $html['pay_default'] = key($html['pay_type']);
            }

            // 付款界面模板
            $htmlfile = CONFIGPATH.'pay/buy.html';
            if ($field['setting']['option']['payfile']) {
                if (is_file(WEBPATH.'config/pay/'.$field['setting']['option']['payfile'])) {
                    $htmlfile = WEBPATH.'config/pay/'.$field['setting']['option']['payfile'];
                } elseif (is_file(CONFIGPATH.'pay/'.$field['setting']['option']['payfile'])) {
                    $htmlfile = CONFIGPATH.'pay/'.$field['setting']['option']['payfile'];
                }
            }
            if (!is_file($htmlfile)) {
                return '支付表单模板文件不存在：'.$htmlfile;
            }

            $member = \Phpcmf\Service::C()->member;
            $pay_url = \Phpcmf\Service::L('router')->member_url('pay/pay/index');

            // 获取付款界面代码
            ob_start();
            $file = \Phpcmf\Service::V()->code2php(file_get_contents($htmlfile));
            require_once $file;
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

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        $tpl_group = '
            <div class="portlet-body fc-sku-group" id="dr_sku_group_{id}" did="{id}">
                <input type="hidden" id="dr_sku_group_text_{id}" name="data['.$field['fieldname'].'_sku][group][{id}]" value="{name}">
		        <div class="row fc-sku-group-name">
                    <div class="col-md-6 fc-sku-group-name-input">{name}</div>
                </div>
		        <div class="row fc-sku-group-body" id="dr_sku_value_{id}">
		        {value}
                </div>
            </div>
            ';

        $tpl_value = '
            <div class="fc-sku-group-value col-md-4" id="dr_sku_value_{id}_{iid}" did="{iid}">
		        <div class="input-group input-group-sm">
                    <input type="text" class="fc-sku-value-name-input form-control" onblur="dr_sku_init()" name="data['.$field['fieldname'].'_sku][name][{id}][{iid}]" fname="{id}_{iid}" value="{name}">
                    <span class="input-group-btn">
                        <button class="btn red" onclick="javascript:dr_sku_del_value(\'{id}\', \'{iid}\');" type="button"><i class="fa fa-trash"></i></button>
                    </span>
                </div>
            </div>
            ';

        // 显示字段
        $pay_html = '';
        $myfield = $this->_get_myfield($field);
        $sku_field_name = '';
        $sku_field_id = [];
        foreach ($myfield as $ff => $name) {
            $pay_html.= '<div class="form-group">
                            <label class="col-md-2 control-label">'.$name.'</label>
                            <div class="col-md-7">
                                <div class="form-control-static">'.$value[$ff].'</div>
                            </div>
                        </div>';
            $sku_field_name.= '<th>'.$name.'</th>';
            $sku_field_id[] = $ff;
        }

        $result = '';
        if (isset($value['sku']['group']) && $value['sku']['group']) {
            foreach ($value['sku']['group'] as $id => $name) {
                $html = '';
                if (isset($value['sku']['name'][$id]) && $value['sku']['name'][$id]) {
                    foreach ($value['sku']['name'][$id] as $iid => $vname) {
                        $html.= str_replace(
                            ['{id}', '{name}', '{iid}'],
                            [$id, $vname, $iid],
                            $tpl_value
                        );
                    }
                }
                $result.= str_replace(
                    ['{id}', '{name}', '{value}'],
                    [$id, $name, $html],
                    $tpl_group
                );
            }
        }

        $ovalue = $oimg = [];
        if (isset($value['sku']['value']) && $value['sku']['value']) {
            foreach ($value['sku']['value'] as $ii => $t) {
                foreach ($sku_field_id as $if) {
                    $ovalue[$ii.'_'.$if] = $t[$if];
                    if ($if == 'image') {
                        $ovalue[$ii.'_'.$if.'_url'] = dr_get_file($t[$if]);
                    }
                }
            }
        }

        // 是否单一模式
        $is_field_pay = $result && $ovalue ? 1 : 0;

        $str = '
      
            <div id="dr_field_pay" style="display:'.(!$is_field_pay ? 'block' : 'none').';">
                <div class="portlet light isbordered">
                    
                   <div class="form-body" style="padding:30px 0 10px 0">
                   
                        '.$pay_html.'
                   </div>
                </div>
            </div>
            <div id="dr_field_pays" style="display:'.($is_field_pay ? 'block' : 'none').';">
               
                <div class="hide">
                    
                    <div id="dr_sku_result">
                        '.$result.'
                    </div>
                    
                </div>
                
                
                <div id="dr_sku_table">
                        
                </div>
            
                <script type="text/javascript">
                var arrayValue = new Array();
                var tpl_group = "'.$this->_js_var($tpl_group).'";
                var tpl_value = "'.$this->_js_var($tpl_value).'";
                var field_name = "'.$field['fieldname'].'_sku";
                var sku_field_name = "'.$this->_js_var($sku_field_name).'";
                var sku_field_id = '.dr_array2string($sku_field_id).';
                arrayValue = '.dr_array2string($ovalue).';
                </script>
                <script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/sku.js"></script>
                <script type="text/javascript">
                $(function(){
                    dr_sku_init();
                });
                </script>
            </div>
            ';
        return $this->input_format($field['fieldname'], $text, $str);
    }

    // 格式化js变量
    protected function _js_var($html) {
        return str_replace([PHP_EOL, chr(13)], "", addslashes($html));
    }
}