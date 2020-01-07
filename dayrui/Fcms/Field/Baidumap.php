<?php namespace Phpcmf\Field;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Baidumap extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = ['INT' => 10];
        $this->defaulttype = 'INT';
    }

    /**
     * 字段相关属性参数
     *
     * @param	array	$value	值
     * @return  string
     */
    public function option($option) {

        return [
            '
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('显示级层').'</label>
                    <div class="col-md-9">
						<label><input type="text" class="form-control" size="10" name="data[setting][option][level]" value="'.$option['level'].'"></label>
						<span class="help-block">'.dr_lang('值越大地图显示越详细').'</span>
                    </div>
                </div>',
            '<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件高度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][height]" value="'.$option['height'].'"></label>
					<label>px</label>
				</div>
			</div>'
        ];
    }

    /**
     * 创建sql语句
     */
    public function create_sql($name, $option, $cname) {
        $tips = $cname ? ' COMMENT \''.$cname.'\'' : '';
        return 'ALTER TABLE `{tablename}` ADD `'.$name.'_lng` DECIMAL(9,6) NULL '.$tips.', ADD `'.$name.'_lat` DECIMAL(9,6) NULL '.$tips;
    }

    /**
     * 修改sql语句
     */
    public function alter_sql($name, $option, $cname) {
        return NULL;
    }

    /**
     * 删除sql语句
     */
    public function drop_sql($name) {
        return 'ALTER TABLE `{tablename}` DROP `'.$name.'_lng`, DROP `'.$name.'_lat`';
    }

    // 测试字段是否被创建成功，默认成功为0，需要继承开发
    public function test_sql($tables, $field) {

        if (!$tables) {
            return 0;
        }

        foreach ($tables as $table) {
            if (!\Phpcmf\Service::M()->db->fieldExists($field.'_lat', $table)) {
                return '给表['.$table.']创建字段['.$field.'_lng'.']失败';
            }
            if (!\Phpcmf\Service::M()->db->fieldExists($field.'_lat', $table)) {
                return '给表['.$table.']创建字段['.$field.'_lng'.']失败';
            }
        }

        return 0;
    }

    /**
     * 字段入库值
     */
    public function insert_value($field) {

        if (\Phpcmf\Service::L('Field')->post[$field['fieldname']]) {
            $map = explode(',', \Phpcmf\Service::L('Field')->post[$field['fieldname']]);
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_lng'] = (double)$map[0];
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_lat'] = (double)$map[1];
        } else {
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_lng'] = 0;
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname'].'_lat'] = 0;
        }

    }

    /**
     * 字段值
     */
    public function get_value($name, $data) {
        return $data[$name.'_lng'] > 0 || $data[$name.'_lat'] > 0 ? $data[$name.'_lng'].','.$data[$name.'_lat'] : '';
    }

    /**
     * 字段输出
     *
     * @param	array	$value	值
     * @return  string
     */
    public function output($value) {
    }

    /**
     * 字段显示
     *
     * @return  string
     */
    public function show($field, $value = null) {
        return $this->input_format($field['fieldname'], $field['name'], dr_baidu_map(
            $value,
            (int)$field['setting']['option']['level'],
            \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 400),
            $field['setting']['option']['height'] ? $field['setting']['option']['height'] : 200,
            'form-control-static'
        ));
    }

    /**
     * 字段表单输入
     *
     * @return  string
     */
    public function input($field, $value = null) {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        // 表单宽度设置
        $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 400);
        $height = $field['setting']['option']['height'] ? $field['setting']['option']['height'] : 200;

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

        // 字段提示信息
        $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 当字段必填时，加入html5验证标签
        $required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

        // 地图默认值
        !$value && $value = $this->get_default_value($field['setting']['option']['value']);
        $value = ($value == '0,0' || $value == '0.000000,0.000000' || strlen($value) < 5) ? '' : $value;

        $city = \Phpcmf\Service::L('ip')->address(\Phpcmf\Service::L('input')->ip_address());
        $level = $field['setting']['option']['level'] ? $field['setting']['option']['level'] : 15;

        $str = '';
        if (!defined('PHPCMF_FIELD_BAIDUMAP')) {
            $str = '
		<script type="text/javascript" src="'.(strpos(FC_NOW_URL, 'https') === 0 ? 'https' : 'http').'://api.map.baidu.com/api?v=2.0&ak='.SYS_BDMAP_API.'"></script>
		<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/baidumap.js"></script>';
            define('PHPCMF_FIELD_BAIDUMAP', 1);
        }

        $str.= '
		<input name="data['.$name.']" id="dr_'.$name.'" type="hidden" '.$attr.' '.$required.' value="'.$value.'">
		
		<div style="width:'.$width.(is_numeric($width) ? 'px' : '').';height:50px">
			<div class="">
				<div class="pull-left" style="width:85%;padding-right:10px">
					<div class="input-group">
                        <input type="text" class="form-control" id="baidu_address_'.$name.'" placeholder="'.dr_lang('输入地址，需要精确到街道号').'...">
                        <span class="input-group-btn">
                            <a title="'.dr_lang('输入地址，需要精确到街道号').'" class="btn blue" href="javascript:baiduSearchAddress(mapObj_'.$name.', \''.$name.'\');">
                                <i class="fa fa-search"></i>
                            </a>
                        </span>
                    </div>
				</div>
				<div class="pull-left">
				<label>
					<a title="'.dr_lang('添加标注').'" href="javascript:addMarker(mapObj_'.$name.', \''.$name.'\');" class="btn btn-icon-only red">
						<i class="fa fa-map-marker"></i>
					</a></label>
				</div>
			</div>
		</div>
		<div style="width:'.$width.(is_numeric($width) ? 'px' : '').';height:'.$height.'px; clear:both;" id="baidumap_'.$name.'">
		
		</div>
		<script type="text/javascript">
		var mapObj_'.$name.' = new BMap.Map("baidumap_'.$name.'"); // 创建地图实例
		$(function(){
			dr_baidumap(mapObj_'.$name.', \''.$name.'\', \''.$city.'\', \''.$level.'\')
		});
		</script>
		';


        return $this->input_format($name, $text, $str.$tips);
    }

}