<?php namespace Phpcmf\Library {

    /**
     * http://www.xunruicms.com
     * 本文件是框架系统文件，二次开发时不可以修改本文件
     **/


    /**
     * 自定义字段
     */

    class Field {

        public $post; // 当前post值
        public $data; // 格式化后的post值
        public $value; // 默认值

        private $myfields;
        private $is_hide_merge_group = 0;
        private $app;
        private $objects = [];

        // 格式化字段输入表单
        private $format;

        /**
         * 设置应用
         */
        public function app($name) {
            $this->app = $name;
            return $this;
        }

        public function get_myfields() {
            return $this->myfields;
        }

        // 格式化字段输入表单
        public function get_field_format() {

            if ($this->format) {
                return $this->format;
            }

            if (is_file(ROOTPATH.'config/field.php')) {
                $field = require ROOTPATH.'config/field.php';
                if (IS_ADMIN && isset($field['admin']) && $field['admin']) {
                    $this->format = $field['admin'];
                } elseif (IS_MEMBER && isset($field['member']) && $field['member']) {
                    $this->format = $field['member'];
                } elseif (isset($field['home']) && $field['home']) {
                    $this->format = $field['home'];
                }
            }

            if (!$this->format) {
                $this->format = '
<div class="form-group" id="dr_row_{name}">
    <label class="control-label col-md-2">{text}</label>
    <div class="col-md-9">{value}</div>
</div>';
            }

            return $this->format;
        }

        // 关闭分组字段
        public function is_hide_merge_group() {
            $this->is_hide_merge_group = 1;
        }

        /**
         * 字段输出表单
         *
         * @param	array	$id 	数据id
         * @param	array	$data	表单值
         * @return	string
         */
        public function toform($id, $field, $data = [], $show = 0) {

            if (!$field) {
                return '';
            }

            $myfield =  '';
            $mygroup = $mymerge = $merge = $group = [];
            $this->value = $data;
            $this->myfields = $field;

            if (!$this->is_hide_merge_group) {
                // 分组字段筛选
                foreach ($field as $t) {
                    if ($t['fieldtype'] == 'Group'
                        && preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                        foreach ($value[1] as $v) {
                            $group[$v] = $t['fieldname'];
                        }
                    }
                }

                // 字段合并分组筛选
                foreach ($field as $t) {
                    if ($t['fieldtype'] == 'Merge'
                        && preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                        foreach ($value[1] as $v) {
                            $merge[$v] = $t['fieldname'];
                        }
                    }
                }
            }

            // 主字段
            foreach ($field as $t) {

                // 显示权限
                if (!IS_ADMIN) {
                    if (!$t['ismember']) {
                        continue; // 非后台时跳过用户中心显示
                    } elseif ($t['setting']['show_member']
                        && array_intersect(\Phpcmf\Service::C()->member['groupid'], $t['setting']['show_member'])) {
                        continue; // 非后台时 判断用户权限
                    }
                } elseif (IS_ADMIN && $t['setting']['show_admin'] && !in_array(1, \Phpcmf\Service::C()->admin['roleid'])
                    && @array_intersect(\Phpcmf\Service::C()->admin['roleid'], $t['setting']['show_admin'])) {
                    continue; // 后台时 判断管理员权限
                }

                // 字段对象
                $obj = $this->get($t['fieldtype'], $id);

                if (is_object($obj)) {
                    $obj->remove_div = 0;
                    // 百度地图特殊字段
                    switch ($t['fieldtype']) {

                        case 'Baidumap':
                            $value = ($data[$t['fieldname'].'_lng'] && $data[$t['fieldname'].'_lat'] ? $data[$t['fieldname'].'_lng'].','.$data[$t['fieldname'].'_lat'] : $data[$t['fieldname']]);
                            break;

                        case 'Pays':
                            $value = [
                                'price' => $data[$t['fieldname']],
                                'sku' => dr_string2array($data[$t['fieldname'].'_sku']),
                                'sn' => $data[$t['fieldname'].'_sn'],
                                'quantity' => $data[$t['fieldname'].'_quantity'],
                            ];
                            foreach ($t['setting']['option']['field'] as $ff ) {
                                $field[$ff]['fieldtype'] == 'Paystext' && $value[$ff] = (string)$data[$ff];
                            }
                            break;

                        default:
                            $value = $data[$t['fieldname']];
                            break;
                    }
                    if (isset($group[$t['fieldname']])) {
                        // 属于分组字段,重新获取字段表单
                        if (!$this->is_hide_merge_group) {
                            $obj->remove_div = 1;
                            $mygroup[$t['fieldname']] = $show ? $obj->show($t, $value) : $obj->input($t, $value);
                        }
                    } elseif (isset($merge[$t['fieldname']])) {
                        // 属于合并字段
                        if (!$this->is_hide_merge_group) {
                            $input = $show ? $obj->show($t, $value) : $obj->input($t, $value);
                            $mymerge[$t['fieldname']] = $input;
                        }
                    } elseif ($t['fieldtype'] == 'Merge') {
                        if (!$this->is_hide_merge_group) {
                            $myfield.= '{merge_'.$t['fieldname'].'}';
                        }
                    } else {
                        $input = $show ? $obj->show($t, $value) : $obj->input($t, $value);
                        $myfield.= $input;
                    }
                }
            }

            if ($merge) {

                $html = '
					    </div>
					</div>
				</div>
                <div class="portlet light bordered" id="dr_{name}">
                    <div class="portlet-title mytitle">
                        <div class="caption"><span class="caption-subject font-green">{text}</span></div>
                    </div>
                    <div class="portlet-body">
                        <div class="form-body">
                        {value}
                ';


                $data = [];
                foreach ($merge as $fname => $mname) {
                    $data[$mname][] = $fname;
                }
                foreach ($data as $mname => $value) {
                    $code = '';
                    if ($value) {
                        foreach ($value as $fname) {
                            $mymerge[$fname] && $code.= $mymerge[$fname];
                        }
                        $myfield = $code ? str_replace(
                            '{merge_'.$mname.'}',
                            str_replace(
                                array('{text}', '{name}', '{value}'),
                                array($field[$mname]['name'], $mname, $code),
                                $html
                            ),
                            $myfield
                        ) : str_replace(
                            '{merge_'.$mname.'}',
                            '',
                            $myfield
                        );
                    }

                }
            }

            if ($mygroup) {
                foreach ($mygroup as $name => $t) {
                    $myfield = str_replace('{'.$name.'}', $t, $myfield);
                }
            }

            return $myfield;
        }

        /**
         * 系统内置字段
         */
        public function sys_field($field) {

            $system = [
                'id' => array(
                    'name' => dr_lang('Id'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'id',
                    'setting' => array()
                ),
                'content' => array(
                    'name' => dr_lang('内容'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'content',
                    'setting' => array()
                ),
                'title' => array(
                    'name' => dr_lang('主题'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'title',
                    'setting' => array()
                ),
                'thumb' => array(
                    'name' => dr_lang('缩略图'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'File',
                    'fieldname' => 'thumb',
                    'setting' => array(
                        'option' => array(
                            'ext' => 'jpg,gif,png,jpeg',
                            'size' => 10,
                            'input' => 1,
                        )
                    )
                ),
                'catid' => array(
                    'name' => dr_lang('栏目'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'catid',
                    'setting' => array()
                ),
                'author' => array(
                    'name' => dr_lang('账号'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Textbtn',
                    'fieldname' => 'author',
                    'setting' => array(
                        'option' => array(
                            'width' => 200,
                            'name' => '资料',
                            'icon' => 'fa fa-user',
                            'func' => 'dr_show_member',
                            'extend_field' => 'uid',
                            'extend_function' => 'member:uid',
                            'value'	=> \Phpcmf\Service::C()->member['username']
                        ),
                        'validate' => array(
                            'tips' => (IS_ADMIN ? '</span><span class="help-block"><input name="no_author" type="checkbox" value="1" /> '.dr_lang('不验证账号').'</label>' : ''),
                            'check' => '_check_member',
                            'required' => 1,
                        )
                    )
                ),
                'inputtime' => array(
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                    'setting' => array(
                        'option' => array(
                            'width' => 170,
                            'value' => 'SYS_TIME',
                            'is_left' => 1,
                        ),
                        'validate' => array(
                            'required' => 1,
                        )
                    )
                ),
                'updatetime' => array(
                    'name' => dr_lang('更新时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'updatetime',
                    'setting' => array(
                        'option' => array(
                            'width' => 170,
                            'value' => 'SYS_TIME',
                            'is_left' => 1,
                        ),
                        'validate' => array(
                            'required' => 1,
                        )
                    )
                ),
                'inputip' => array(
                    'name' => dr_lang('客户端IP'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Textbtn',
                    'fieldname' => 'inputip',
                    'setting' => array(
                        'option' => array(
                            'width' => 200,
                            'name' => '查看',
                            'icon' => 'fa fa-arrow-right',
                            'func' => 'dr_show_ip',
                            'value' => \Phpcmf\Service::L('input')->ip_address()
                        )
                    )
                ),
                'displayorder' => array(
                    'name' => dr_lang('排列值'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Touchspin',
                    'fieldname' => 'displayorder',
                    'setting' => array(
                        'option' => array(
                            'width' => 200,
                            'max' => '255',
                            'min' => '0',
                            'step' => '1',
                            'show' => '1',
                            'value' => 0
                        )
                    )
                ),
                'hits' => array(
                    'name' => dr_lang('浏览数'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Touchspin',
                    'fieldname' => 'hits',
                    'setting' => array(
                        'option' => array(
                            'width' => 200,
                            'max' => '9999999',
                            'min' => '1',
                            'step' => '1',
                            'show' => '1',
                            'value' => 1
                        )
                    )
                ),
                'status' => array(
                    'name' => dr_lang('审核状态'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Radio',
                    'fieldname' => 'status',
                    'setting' => array(
                        'option' => array(
                            'options' => '待审核|0'.PHP_EOL.'已通过|1',
                            'value' => 1
                        )
                    )
                ),
            ];

            if (is_file(MYPATH.'Config/Sys_field.php')) {
                $sys2 = require MYPATH.'Config/Sys_field.php';
                $system = dr_array22array($system, $sys2);
            }

            $system['username'] = $system['author'];
            $system['username']['fieldname'] = 'username';

            $rt = [];
            foreach ($field as $name) {
                $rt[$name] = $system[$name];
            }

            return $rt;

        }

        /**
         * 获取字段类别对象
         *
         * @param   string  $name    字段类别名称
         * @return  object
         */
        public function get($name, $id = 0, $post = []) {

            if (!$name || strpos($name, '.') !== FALSE) {
                return null;
            }

            $name = ucfirst(strtolower($name));
            if (!isset($this->objects[$name])) {
                if ($this->app && is_file(dr_get_app_dir($this->app).'Fields/'.$name.'.php')) {
                    $class = '\\My\\Field\\'.$this->app.'\\'.$name;
                    require dr_get_app_dir($this->app).'Fields/'.$name.'.php';
                } elseif (is_file(MYPATH.'Field/'.$name.'.php')) {
                    $class = '\\My\\Field\\'.$name;
                } else {
                    $class = '\\Phpcmf\\Field\\'.$name;
                }

                if (!class_exists($class)) {
                    log_message('error', '字段类别['.$class.']未定义');
                    return;
                }
                $this->objects[$name] = new $class();
            }

            $this->post = $post;
            $this->objects[$name]->id = $id;
            $this->objects[$name]->app = $this->app;

            return $this->objects[$name];
        }

        /**
         * 自定义字段选项信息
         *
         * @param   string	$name	字段类别名称
         * @param   array 	$option	选项值
         * @param	array	$field	字段集合
         * @return  string
         */
        public function option($name, $option = NULL, $field = NULL) {
            return $name ? $this->get($name)->option($option, $field) : NULL;
        }

        /**
         * 获取可用字段类别
         *
         * @return  array
         */
        public function type($name) {

            $type = require CMSPATH.'Field/Field.php';
            // cms自定义字段类别
            if (is_file(MYPATH.'Field/Field.php')) {
                $my = require MYPATH.'Field/Field.php';
                $my && $type = dr_array2array($type, $my);
            }
            // 应用目录自定义字段类别
            if ($this->app && is_file(dr_get_app_dir($this->app).'Fields/Field.php')) {
                $my = require dr_get_app_dir($this->app).'Fields/Field.php';
                $my && $type = dr_array2array($type, $my);
            }
            // 组合组装
            foreach ($type as $i => $t) {
                if (isset($t['used']) && is_array($t['used']) && !in_array($name, $t['used'])) {
                    unset($type[$i]);
                } elseif (isset($t['namespace']) && $t['namespace'] && $t['namespace'] != $this->app) {
                    unset($type[$i]);
                }
            }

            return $type;
        }

        /**
         * 格式化自定义字段内容
         *
         * @param	string	$field	字段类型
         * @param	string	$value	字段值
         * @return
         */
        function get_value($field, $value) {

            $obj = $this->get($field);
            if (!$obj) {
                return $value;
            }

            return $obj->output($value);
        }

        /**
         * 字段输出格式化
         *
         * @param	array	$fields 	可用字段集
         * @param	array	$data		数据
         * @param	intval	$curpage	分页id
         * @return	string
         */
        public function format_value($fields, $data, $curpage = 1) {

            if (!$fields || !$data || !is_array($data)) {
                return $data;
            }

            foreach ($data as $n => $value) {
                if (isset($fields[$n]) && $fields[$n]) {
                    if ($n == 'content' && $fields[$n]['fieldtype'] == 'Ueditor') {
                        $value = $this->get_value($fields[$n]['fieldtype'], $value);
                        if (strpos($value, '<hr class="pagebreak">') !== FALSE) {
                            // 编辑器分页
                            $page = 1;
                            $match = explode('<hr class="pagebreak">', $value);
                            $content = [];
                            foreach ($match as $i => $t) {
                                $content[$page] = $t;
                                $page ++;
                            }
                            $page = max(1, min($page, $curpage));
                            $data[$n] = $content[$page]; // 默认内容字段为当前页的内容
                            $data[$n.'_page'] = $content; // 全部分页
                        } else {
                            // 不分页
                            $data[$n] = $value;
                        }

                        /* 老版本吧的分页
                    } elseif ($fields[$n]['fieldtype'] == 'Ueditor'
                        && strpos($value, '<p class="pagebreak">') !== FALSE
                        && preg_match_all('/<p class="pagebreak">(.*)<\/p>/Us', $value, $match)
                        && preg_match('/(.*)<p class="pagebreak">/Us', $value, $frist)) {
                        // 编辑器分页 新版
                        $page = 1;
                        $content = $title = [];
                        $data['_'.$n] = $value;
                        $content[$page]['title'] = dr_lang('第%s页', $page);
                        $content[$page]['body'] = $frist[1];
                        foreach ($match[0] as $i => $t) {
                            $page ++;
                            $value = str_replace($content[$page - 1]['body'].$t, '', $value);
                            $body = preg_match('/(.*)<p class="pagebreak"/Us', $value, $match_body) ? $match_body[1] : $value;
                            $title[$page] = trim($match[1][$i]);
                            $content[$page]['title'] = trim($match[1][$i]) ? trim($match[1][$i]) : dr_lang('第%s页', $page);
                            $content[$page]['body'] = $body;
                        }
                        $page = max(1, min($page, $curpage));
                        $data[$n] = $content[$page]['body'];
                        $data[$n.'_page'] = $content;
                        $data[$n.'_title'] = $title[$page];*/
                    } elseif ($fields[$n]['fieldtype'] == 'Pays') {
                        $data[$n.'_sku'] = dr_string2array($data[$n.'_sku']);
                        $data[$n.'_quantity'] = intval($data[$n.'_quantity']);
                    } else {
                        $data[$n] = $format = $this->get_value($fields[$n]['fieldtype'], $value);
                        $format !== $value && $data['_'.$n] = $value;
                    }
                } elseif (strpos($n, '_lng') !== FALSE) {
                    // 百度地图
                    $name = str_replace('_lng', '', $n);
                    $data[$name] = isset($data[$name.'_lat']) && ($data[$name.'_lng'] > 0 || $data[$name.'_lat'] > 0) ? $data[$name.'_lng'].','.$data[$name.'_lat'] : '';
                }
            }

            return $data;
        }


    }


    /**
     * 自定义字段抽象类
     */

    abstract class A_Field  {

        public $id; // 当前数据id 存在id表示修改数据
        public $app; // 当前app目录，option可用
        public $close_xss; // 是否关闭xss
        public $remove_div; // 去掉div盒模块

        protected $fieldtype; // 可用字段类型
        protected $defaulttype;	// 默认字段类型

        // 内置可用字段及默认长度
        protected $fields = [
            'INT' => 10,
            'TINYINT' => 3,
            'SMALLINT' => 5,
            'MEDIUMINT' => 8,
            'DECIMAL' => '10,2',
            'FLOAT' => '8,2',
            'CHAR' => 100,
            'VARCHAR' => 255,
            'TEXT' => '',
            'MEDIUMTEXT' => ''

        ];

        /**
         * 构造函数
         */
        public function __construct(...$params) {

        }

        /**
         * 字段相关属性参数
         *
         * @param	array	$option
         * @return  string
         */
        abstract public function option($option);

        /**
         * 字段表单输入
         *
         * @param	string	$t  	字段数组
         * @param	array	$value	值
         * @return  string
         */
        abstract function input($t, $value = NULL);

        /**
         * 字段表单显示
         *
         * @param	string	$field	字段数组
         * @param	array	$value	值
         * @return  string
         */
        public function show($field, $value = null) {

            // 字段默认值
            $value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

            $str = '<div class="form-control-static"> '.htmlspecialchars_decode($value).' </div>';

            return $this->input_format($field['fieldname'], $field['name'], $str);
        }

        /**
         * 字段输出
         *
         * @param	array	$value	数据库值
         * @return  string
         */
        public function output($value) {
            return $value;
        }

        /**
         * 获取附件id
         *
         * @param	array	$value	数据库值
         * @return  array
         */
        public function get_attach_id($value) {

        }

        // 判断是否禁止修改
        protected function _not_edit($field, $value) {
            return !defined('IS_MODULE_VERIFY')
                && !IS_ADMIN
                && $this->id
                && strlen($value)
                && $field['setting']['validate']['isedit'];
        }

        /**
         * 附件处理
         *
         * @param	$data	当前的附件数据
         * @param	$_data	原来的附件数据
         * @return  返回当前字段使用的附件id集合与待删除的id集合
         */
        public function attach($data, $_data) {

        }

        /**
         * 字段入库值
         *
         * @param	array	$field	字段信息
         * @return  void
         */
        public function insert_value($field) {
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        }

        /**
         * 字段值
         *
         * @param	string	$name	字段名称
         * @param	array	$data	数据库中的值
         * @return  value
         */
        public function get_value($name, $data) {
            return isset($data[$name]) ? $data[$name] : '';
        }

        /**
         * 验证字段值
         *
         * @param	string	$field	字段类型
         * @param	string	$value	字段值
         * @return
         */
        public function check_value($field, $value) {
            return '';
        }

        /**
         * 创建字段的sql语句
         *
         * @param	英文字段名字
         * @param	选项数组
         * @param	中文别名
         * @return  string
         */
        public function create_sql($name, $option, $cname = '') {
            $tips = $cname ? ' COMMENT \''.$cname.'\'' : '';
            $fieldtype = $this->fieldtype === TRUE ? $this->fields : $this->fieldtype; // 可用字段类型
            $_fieldtype	= isset($option['fieldtype']) && isset($fieldtype[$option['fieldtype']]) ? $option['fieldtype'] : $this->defaulttype; // 字段类型
            $_length = isset($option['fieldlength']) && $option['fieldlength'] ? $option['fieldlength'] : $fieldtype[$_fieldtype]; // 字段长度
            return 'ALTER TABLE `{tablename}` ADD `'.$name.'` '.$_fieldtype.($_length ? '('.$_length.')' : '').' NULL DEFAULT '.$this->_default_value($_fieldtype).$tips;
        }

        /**
         * 修改字段的sql语句
         *
         * @param	英文字段名字
         * @param	选项数组
         * @param	中文别名
         * @return  string
         */
        public function alter_sql($name, $option, $cname = '') {
            $tips = $cname ? ' COMMENT \''.$cname.'\'' : '';
            $fieldtype = $this->fieldtype === TRUE ? $this->fields : $this->fieldtype; // 可用字段类型
            $_fieldtype	= isset($option['fieldtype']) && isset($fieldtype[$option['fieldtype']]) ? $option['fieldtype'] : $this->defaulttype; // 字段类型
            $_length = isset($option['fieldlength']) && $option['fieldlength'] ? $option['fieldlength'] : $fieldtype[$_fieldtype]; // 字段长度
            return 'ALTER TABLE `{tablename}` CHANGE `'.$name.'` `'.$name.'` '.$_fieldtype.($_length ? '('.$_length.')' : '').' NULL DEFAULT '.$this->_default_value($_fieldtype).$tips;
        }

        /**
         * 删除字段的sql语句
         *
         * @param	string	$name
         * @return  string
         */
        public function drop_sql($name) {
            //ALTER TABLE `{tablename}` DROP `{field}`
            $sql = 'ALTER TABLE `{tablename}` DROP `'.$name.'`';
            return $sql;
        }

        // 测试字段是否被创建成功，默认成功为0，需要继承开发
        public function test_sql($tables, $field) {

            if (!$tables) {
                return 0;
            }

            foreach ($tables as $table) {
                if (!\Phpcmf\Service::M()->db->fieldExists($field, $table)) {
                    return '给表['.$table.']创建字段['.$field.']失败';
                }
            }

            return 0;
        }


        /**
         * 会员字段选择（用于字段默认值设定）
         *
         * @return  string
         */
        public function member_field_select() {
            $str = '<select  class="form-control" onchange="$(\'#field_default_value\').val(\'{\'+this.value+\'}\')" name="_member_field"><option value=""> -- </option>';
            $str.= '<option value="username"> '.dr_lang('会员名称').' </option>';
            $str.= '<option value="email"> '.dr_lang('会员邮箱').' </option>';
            $str.= '<option value="groupid"> '.dr_lang('会员组ID').' </option>';
            $str.= '<option value="levelid"> '.dr_lang('会员等级ID').' </option>';
            $str.= '<option value="name"> '.dr_lang('姓名').' </option>';
            $str.= '<option value="phone"> '.dr_lang('电话').' </option>';
            // 这里要改的
            if (\Phpcmf\Service::C()->member_cache['field']) {
                foreach (\Phpcmf\Service::C()->member_cache['field'] as $field => $t) {
                    $str.= '<option value="'.$field.'"> '.$t['name'].' </option>';
                }
            }
            $str.= '</select>';
            return $str;
        }

        // 颜色选取
        public function _color_select($name, $color) {

            $select	= '<select class="form-control" name="data[setting][option]['.$name.']">';
            $select.= '<option value="">-</option>';
            foreach (['red', 'blue', 'green', 'default', 'yellow', 'dark'] as $t) {
                $select.= "<option value=\"{$t}\" ".($color == $t ? "selected" : "").">{$t}</option>";
            }
            $select.= '</select>';

            return $select;
        }

        /**
         * 获取会员默认值
         *
         * @param	string	$name
         * @return  string
         */
        public function get_default_value($value) {
            if (preg_match('/\{(\w+)\}/', $value, $match)) {
                return isset(\Phpcmf\Service::C()->member[$match[1]]) ? \Phpcmf\Service::C()->member[$match[1]] : '';
            }
            return $value;
        }

        // 数字默认值
        public function _default_value($type) {
            if (in_array($type, array('INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'))) {
                return '0';
            } else {
                return 'NULL';
            }
        }

        /**
         * 字段类型选择
         *
         * @param	string	$name
         * @param	string	$length
         * @return  string
         */
        public function field_type($name = NULL, $length = NULL) {
            if ($this->fieldtype === TRUE) {
                $select	= '<option value="">-</option>
				<option value="INT" '.($name == 'INT' ? 'selected' : '').'>INT</option>
				<option value="TINYINT" '.($name == 'TINYINT' ? 'selected' : '').'>TINYINT</option>
				<option value="SMALLINT" '.($name == 'SMALLINT' ? 'selected' : '').'>SMALLINT</option>
				<option value="MEDIUMINT" '.($name == 'MEDIUMINT' ? 'selected' : '').'>MEDIUMINT</option>
				<option value="">-</option>
				<option value="DECIMAL" '.($name == 'DECIMAL' ? 'selected' : '').'>DECIMAL</option>
				<option value="FLOAT" '.($name == 'FLOAT' ? 'selected' : '').'>FLOAT</option>
				<option value="">-</option>
				<option value="CHAR" '.($name == 'CHAR' ? 'selected' : '').'>CHAR</option>
				<option value="VARCHAR" '.($name == 'VARCHAR' ? 'selected' : '').'>VARCHAR</option>
				<option value="TEXT" '.($name == 'TEXT' ? 'selected' : '').'>TEXT</option>
				<option value="MEDIUMTEXT" '.($name == 'MEDIUMTEXT' ? 'selected' : '').'>MEDIUMTEXT</option>';
            } elseif (dr_count($this->fieldtype) > 1) {
                $select	= '<option value="">-</option>';
                foreach ($this->fieldtype as $t) {
                    $select.= "<option value=\"{$t}\" ".($name == $t ? "selected" : "").">{$t}</option>";
                }
            } else {
                return NULL;
            }

            $str = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('存储类型').' </label>
			<div class="col-md-9">
				<label><select class="form-control" name="data[setting][option][fieldtype]" onChange="setlength()" id="type">
					'.$select.'
				</select></label>
				<span class="help-block">'.dr_lang('根据你的实际情况选择字段类型，如果你不懂MySQL数据库知识就不要填写此项').'</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('存储长度/值').' </label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" value="'.$length.'" name="data[setting][option][fieldlength]"></label>
				<span class="help-block">'.dr_lang('如果你不懂MySQL数据库知识就不要填写此项').'</span>
			</div>
		</div>';

            return $str;
        }

        /**
         * 表单输入格式
         *
         * @param	string	$name	字段名称
         * @param	string	$text	字段别名
         * @param	string	$value	表单输入内容
         * @return  string
         */
        public function input_format($name, $text, $value) {

            if ($this->remove_div) {
                return $value;
            }

            $fomart = \Phpcmf\Service::L('field')->get_field_format();
            // 来自移动端替换div class
            if (\Phpcmf\Service::C()->is_mobile) {
                $fomart = str_replace(['control-label col-md-2', 'col-md-9'], ['control-label col-md-12', 'col-md-12'], $fomart);
            }
            return str_replace(['{name}', '{text}', '{value}'], [$name, $text, $value], $fomart);
        }

        /**
         * 附件存储策略
         * @return  string
         */
        public function attachment($option) {

            $id = isset($option['attachment']) ? $option['attachment'] : 0;

            $html = '<label><select class="form-control" name="data[setting][option][attachment]">';
            $html.= '<option value="0"> '.dr_lang('本地存储').' </option>';

            $remote = \Phpcmf\Service::C()->get_cache('attachment');
            if ($remote) {
                foreach ($remote as $i => $t) {
                    $html.= '<option value="'.$i.'" '.($i == $id ? 'selected' : '').'> '.dr_lang($t['name']).' </option>';
                }
            }

            $html.= '</select></label>';

            return '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('附件存储策略').' </label>
			<div class="col-md-9">
				'.$html.'
                <span class="help-block">远程附件存储建议设置小文件存储，推荐10MB内，大文件会导致数据传输失败</span>
			</div>
		</div><div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('图片压缩大小').' </label>
			<div class="col-md-9">
                <label><input type="text" class="form-control" value="'.$option['image_reduce'].'" name="data[setting][option][image_reduce]"></label>
                <span class="help-block">填写图片宽度，例如1000，表示图片大于1000px时进行压缩图片</span>
			</div>
		</div>';
        }
    }
}

