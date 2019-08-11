<?php
/**
 * 示例文件
 * 变量介绍
 * $name 字段英文名称
 * $field 字段信息（数组）
 * $value 当前字段的值
 * \Phpcmf\Service::C() 表示控制器方法
 * \Phpcmf\Service::M() 表示模型方法
 * 表单的name值格式是：data[$name]
 */
$code = ''; // 最终输出的代码
$code = '<select name="data['.$name.']" class="form-control"><option value=""> -- </option>';
$data = \Phpcmf\Service::M()->db->table(SITE_ID.'_news')->get()->getResultArray();
if ($data) {
    foreach ($data as $t) {
        $code.= '<option value="'.$t['id'].'" '.($value == $t['id'] ? 'selected' : '').'> '.$t['title'].' </option>';
    }
}
$code.= '</select>';