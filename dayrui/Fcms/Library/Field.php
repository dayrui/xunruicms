<?php namespace Phpcmf\Library {
    /**
     * www.xunruicms.com
     * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
     * 迅睿内容管理框架系统
     **/

    /**
     * 自定义字段
     */

    class Field {

        public $post; // 当前post值
        public $old; // 修改时的老数据
        public $data; // 格式化后的post值
        public $value; // 默认值
        public $fields; // 可用字段
        public $merge; // 可用merge字段

        private $is_hide_merge_group = 0;
        private $app;
        private $objects = [];

        // 格式化字段输入表单
        private $format;

        /**
         * 设置应用
         */
        public function app($name = '') {
            $this->app = $name;
            $this->merge = [];
            return $this;
        }

        // 格式化字段输入表单
        public function get_field_format() {

            if ($this->format) {
                return $this->format;
            }

            if (is_file(CONFIGPATH.'field.php')) {
                $field = require CONFIGPATH.'field.php';
                if (IS_ADMIN) {
                    isset($field['admin']) && $field['admin'] && $this->format = $field['admin'];
                } elseif (IS_MEMBER) {
                    isset($field['member']) && $field['member'] && $this->format = $field['member'];
                } elseif (isset($field['home']) && $field['home']) {
                    $this->format = $field['home'];
                }
            }

            if (!$this->format) {
                $this->format = '
<div class="form-group" id="dr_row_{name}">
    <label class="control-label col-md-2">{text}</label>
    <div class="col-md-10">{value}</div>
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
            $this->fields = $field;

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
                            //$field[$v]['displayorder']+=1;
                            //$field[$t['fieldname']]['displayorder']+=1;
                            if (!in_array($t['fieldname'], $this->merge)) {
                                $this->merge[] = $t['fieldname'];
                            }
                        }
                    }
                }
                uasort($field, function($a, $b){
                    if($a['displayorder'] == $b['displayorder']){
                        return 0;
                    }
                    return($a['displayorder']<$b['displayorder']) ? -1 : 1;
                });
            }

            // 主字段
            foreach ($field as $t) {

                // 显示权限
                if (!IS_ADMIN) {
                    if (!$t['ismember']) {
                        continue; // 非后台时跳过用户中心显示
                    } elseif ($t['setting']['show_member']
                        && dr_array_intersect(\Phpcmf\Service::C()->member['groupid'], $t['setting']['show_member'])) {
                        continue; // 非后台时 判断用户权限
                    }
                } elseif (IS_ADMIN && $t['setting']['show_admin'] && !dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])
                    && dr_array_intersect(\Phpcmf\Service::C()->admin['roleid'], $t['setting']['show_admin'])) {
                    continue; // 后台时 判断管理员权限
                }

                // 字段对象
                $obj = $this->get($t['fieldtype'], $id);

                if (is_object($obj)) {
                    $obj->remove_div = 0;
                    // 百度地图特殊字段
                    if (strpos($t['fieldtype'], 'map') !== false
                        && isset($data[$t['fieldname'].'_lng']) && isset($data[$t['fieldname'].'_lat'])) {
                        $value = ($data[$t['fieldname'].'_lng'] && $data[$t['fieldname'].'_lat'] ? $data[$t['fieldname'].'_lng'].','.$data[$t['fieldname'].'_lat'] : $data[$t['fieldname']]);
                    } else {
                        $value = $obj->get_value($t['fieldname'], $data);
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
                <div class="portlet light bordered" id="dr_row_{name}">
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
                    $myfield = str_replace('{|'.$name.'|}', $t, $myfield);
                }
            }

            return $myfield;
        }

        /**
         * 会员内置字段
         */
        public function member_list_field() {

            return [
                'id' => [
                    'name' => dr_lang('Uid'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'id',
                    'setting' => []
                ],
                'group' => [
                    'name' => dr_lang('用户组'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'group',
                    'setting' => []
                ],
                'username' => [
                    'name' => dr_lang('账号'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'username',
                    'setting' => []
                ],
                'name' => [
                    'name' => dr_lang('姓名'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'name',
                    'setting' => []
                ],
                'email' => [
                    'name' => dr_lang('邮箱'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'email',
                    'setting' => []
                ],
                'phone' => [
                    'name' => dr_lang('手机'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'phone',
                    'setting' => []
                ],
                'money' => [
                    'name' => dr_lang('余额'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'money',
                    'setting' => []
                ],
                'score' => [
                    'name' => dr_lang('积分'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'score',
                    'setting' => []
                ],
                'experience' => [
                    'name' => dr_lang('经验值'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'experience',
                    'setting' => []
                ],
                'regip' => [
                    'name' => dr_lang('注册IP'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'regip',
                    'setting' => []
                ],
                'regtime' => [
                    'name' => dr_lang('注册时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'regtime',
                    'setting' => []
                ],
                'is_avatar' => [
                    'name' => dr_lang('头像认证'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_avatar',
                    'setting' => []
                ],
                'is_lock' => [
                    'name' => dr_lang('账号锁定'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_lock',
                    'setting' => []
                ],
                'is_verify' => [
                    'name' => dr_lang('审核状态'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_verify',
                    'setting' => []
                ],
                'is_mobile' => [
                    'name' => dr_lang('手机认证'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_mobile',
                    'setting' => []
                ],
                'is_email' => [
                    'name' => dr_lang('邮件认证'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_email',
                    'setting' => []
                ],
                'is_complete' => [
                    'name' => dr_lang('资料完善'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'is_complete',
                    'setting' => []
                ],

            ];
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
                            'ext' => 'jpg,gif,png,jpeg,webp',
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
                'uid' => array(
                    'name' => dr_lang('账号'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Uid',
                    'fieldname' => 'uid',
                    'setting' => array(
                        'option' => array(
                            'width' => '200px',
                        ),
                        'validate' => array(
                            'check' => '_check_member',
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
                            'width' => '100%',
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
                            'width' => '100%',
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
                            'width' => '100%',
                            'name' => '查看',
                            'icon' => 'fa fa-arrow-right',
                            'func' => 'dr_show_ip',
                            'value' => \Phpcmf\Service::L('input')->ip_info()
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
                            'width' => '100%',
                            'max' => '',
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
                            'width' => '100%',
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
                            'options' => dr_lang('待审核').'|0'.PHP_EOL.dr_lang('已通过').'|1'.PHP_EOL.dr_lang('未通过').'|2',
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
                $class = '';
                $file1 = MYPATH.'Field/'.$name.'.php';
                $file2 = CMSPATH.'Field/'.$name.'.php';
                if (is_file($file1)) {
                    $class = '\\My\\Field\\'.$name; // my目录继承类别
                } elseif (is_file($file2)) {
                    $class = '\\Phpcmf\\Field\\'.$name; // 系统类别
                } else {
                    if ($name == 'Ueditor') {
                        // 表示引用的百度编辑器字段
                        $class = '\\Phpcmf\\Field\\Editor'; // 系统类别
                    } else {
                        // 加载插件的字段
                        if (strpos($name, '::') !== false) {
                            list($app, $fname) = explode('::', $name);
                        } elseif ($this->app) {
                            $app = $this->app;
                            $fname = $name;
                        }
                        if ($app && !dr_is_app($app)) {
                            log_message('error', '字段类别['.$name.']所属插件['.$app.']未安装');
                            return;
                        } elseif (!$app) {
                            log_message('error', '字段类别['.$name.']不存在-'.$file1.'-'.$file2);
                            return;
                        }
                        $file = dr_get_app_dir($app).'Fields/'.ucfirst($fname).'.php';
                        if (is_file($file)) {
                            $class = '\\My\\Field\\'.$app.'\\'.ucfirst($fname);
                            require $file;
                        } else {
                            log_message('error', '字段类别['.$name.']所属插件['.$app.']的字段文件不存在-'.$file.'-'.$file1.'-'.$file2);
                            return;
                        }
                    }
                }

                if (!$class || !class_exists($class)) {
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
         * @return  array
         */
        public function option($name, $option = NULL, $field = NULL) {

            if (!$name) {
                return ['', ''];
            }

            $obj = $this->get($name);
            if (!$obj) {
                return ['', ''];
            }

            $obj->init($field);
            list($a, $b) = $obj->option($option, $field);

            if ($obj->is_validate) {
                $a.= '<script>$(\'.dr_is_validate\').show()</script>';
            } else {
                $a.= '<script>$(\'.dr_is_validate\').hide()</script>';
            }

            return [$a, $b];
        }

        /**
         * 获取可用字段类别
         *
         * @return  array
         */
        public function type($name) {

            $type = require CMSPATH.'Field/Field.php';
            if (is_file(CMSPATH.'Field/Ueditor.php')) {
                $type[] = [
                    'id' => 'Ueditor',
                    'name' => '百度编辑器',
                ];
            }
            // cms自定义字段类别
            if (is_file(MYPATH.'Field/Field.php')) {
                $my = require MYPATH.'Field/Field.php';
                $my && $type = dr_array2array($type, $my);
            }
            // 加载全部插件的字段
            $local = \Phpcmf\Service::Apps(1);
            foreach ($local as $dir => $path) {
                // 加载
                if (is_file($path.'Fields/Field.php')) {
                    $my = require $path.'Fields/Field.php';
                    $my && $type = dr_array2array($type, $my);
                }
            }
            // 组合组装
            $my = [];
            foreach ($type as $i => $t) {
                $my[] = $t['id'];
                if (isset($t['used']) && is_array($t['used']) && !dr_in_array($name, $t['used'])) {
                    unset($type[$i]);
                } elseif (isset($t['namespace']) && $t['namespace'] && $t['namespace'] != $this->app) {
                    unset($type[$i]);
                }
                $type[$i]['id'] = $t['id'];
                $type[$i]['used'] = $t['used'];
                $type[$i]['name'] = dr_lang($t['name']);
                $type[$i]['namespace'] = $t['namespace'];
            }
            // 扫描没有定义的字段类别
            $path = dr_file_map(MYPATH.'Field');
            if ($path) {
                foreach ($path as $file) {
                    $name = substr($file, 0, -4);
                    if (!dr_in_array($name, $my)
                        && strpos(file_get_contents(MYPATH.'Field/'.$file), '<?php namespace My\Field;') !== false) {
                        $type[] = [
                            'id' => $name,
                            'name' => dr_lang($name),
                            'used' => '',
                            'namespace' => '',
                        ];
                    }
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

            $this->data = $data;
            foreach ($data as $n => $value) {
                if (isset($fields[$n]) && $fields[$n] && isset($fields[$n]['fieldtype']) && $fields[$n]['fieldtype']) {
                    if ($n == 'content' && stripos($fields[$n]['fieldtype'], 'editor') !== false) {
                        // 编辑器
                        $value = dr_text_rel($this->get_value($fields[$n]['fieldtype'], $value));
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
                    } elseif ($fields[$n]['fieldtype'] == 'Pays') {
                        $data[$n.'_sku'] = dr_string2array($data[$n.'_sku']);
                        $data[$n.'_quantity'] = intval($data[$n.'_quantity']);
                    } else {
                        $data[$n] = $format = $this->get_value($fields[$n]['fieldtype'], $value);
                        $format !== $value && $data['_'.$n] = $value;
                    }
                } elseif (strpos($n, '_lng') !== FALSE) {
                    // 地图
                    $name = str_replace('_lng', '', $n);
                    if (isset($data[$name.'_lat'])) {
                        $data[$name] = isset($data[$name.'_lat']) && ($data[$name.'_lng'] > 0 || $data[$name.'_lat'] > 0) ? $data[$name.'_lng'].','.$data[$name.'_lat'] : '';
                    }
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
        public $close_xss; // 强制关闭xss
        public $field; // 当前字段信息
        public $use_xss; // 强制开启xss
        public $is_edit = true; // 是否允许修改字段类别
        public $is_validate = true; // 是否允许字段验证
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
         * 设置字段信息
         */
        public function init($field) {
            $this->field = $field;
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
            $value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

            $str = '<div class="form-control-static"> '.htmlspecialchars_decode((string)$value).' </div>';

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
        public function _not_edit($field, $value) {
            if (defined('IS_MODULE_VERIFY')) {
                // 内容审核时
                if (defined('IS_MODULE_VERIFY_NEW') && IS_MODULE_VERIFY_NEW) {
                    return 0;
                }
            }
            return !IS_ADMIN
                && $this->id
                && dr_strlen($value)
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
            $value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
            if (is_array($value)) {
                $value = dr_array2string($value);
            } elseif (dr_strlen($value) == 1 && $value == '0') {
                $value = '0';
            } else {
                $value = htmlspecialchars((string)$value);
            }
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value;
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
         * 验证必填字段值
         *
         * @param	string	$field	字段类型
         * @param	string	$value	字段值
         * @return
         */
        public function check_required($field, $value) {
            if (dr_is_empty($value)) {
                // 验证值为空
                return dr_lang('%s不能为空', $field['name']);
            }
            return '';
        }

        /**
         * 验证加载js
         */
        public function is_load_js($name) {
            if (isset(\Phpcmf\Service::C()->loadjs[$name]) && \Phpcmf\Service::C()->loadjs[$name]) {
                return true;
            }
            return false;
        }

        /**
         * 验证加载变量设置
         */
        public function set_load_js($name, $value) {
            \Phpcmf\Service::C()->loadjs[$name] = $value;
        }

        // 获取select搜索框的js代码
        public function get_select_search_code() {

            if ($this->is_load_js('Select')) {
                return '';
            }

            $this->set_load_js('Select', 1);

            return '<script type="text/javascript"> var bs_selectAllText = \''.dr_lang('全选').'\';var bs_deselectAllText = \''.dr_lang('全删').'\';var bs_noneSelectedText = \''.dr_lang('没有选择').'\'; var bs_noneResultsText = \''.dr_lang('没有找到 %s', '{0}').'\';</script>
<link href="'.THEME_PATH.'assets/global/plugins/bootstrap-select/css/bootstrap-select'.(CI_DEBUG ? '':'.min').'.css" rel="stylesheet" type="text/css" />
<script src="'.THEME_PATH.'assets/global/plugins/bootstrap-select/js/bootstrap-select'.(CI_DEBUG ? '':'.min').'.js" type="text/javascript"></script>
<script type="text/javascript"> jQuery(document).ready(function() { $(\'.bs-select\').selectpicker();  }); </script>';
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
         * 验证字段属性
         */
        public function edit_config($post) {
            return dr_return_data(1, 'ok');
        }


        /**
         * 会员字段选择（用于字段默认值设定）
         *
         * @return  string
         */
        public function member_field_select() {
            $str = '<select  class="form-control" onchange="$(\'#field_default_value\').val(\'{\'+this.value+\'}\')" name="_member_field"><option value=""> -- </option>';
            $str.= '<option value="username"> '.dr_lang('账号').' </option>';
            $str.= '<option value="email"> '.dr_lang('邮箱').' </option>';
            $str.= '<option value="groupid"> '.dr_lang('用户组ID').' </option>';
            $str.= '<option value="levelid"> '.dr_lang('用户组等级ID').' </option>';
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

            $str = '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-colorpicker/css/colorpicker.css" rel="stylesheet" type="text/css" />
        	<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-minicolors/jquery.minicolors.css" rel="stylesheet" type="text/css" />
			';
            $str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.js?v='.CMF_UPDATE_TIME.'"></script>';
            $str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-minicolors/jquery.minicolors.min.js?v='.CMF_UPDATE_TIME.'"></script>';
            $str.= '
		    <input type="text" class="form-control color " data-control="brightness" name="data[setting][option]['.$name.']" id="dr_'.$name.'" value="'.$color.'" >';
            $str.= '
		<script type="text/javascript">
		$(function(){
			$("#dr_'.$name.'").minicolors({
                control: $("#dr_'.$name.'").attr("data-control") || "hue",
                defaultValue: $("#dr_'.$name.'").attr("data-defaultValue") || "",
                inline: "true" === $("#dr_'.$name.'").attr("data-inline"),
                letterCase: $("#dr_'.$name.'").attr("data-letterCase") || "lowercase",
                opacity: $("#dr_'.$name.'").attr("data-opacity"),
                position: $("#dr_'.$name.'").attr("data-position") || "bottom left",
                change: function(t, o) {
                    t && (o && (t += ", " + o), "object" == typeof console && console.log(t));
                },
                theme: "bootstrap"
            });
		});
		</script>';

            return $str;
        }

        // 是否可以作为搜索字段
        public function _search_field() {
            return '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('字段优化').' </label>
			<div class="col-md-9"><label class="form-control-static" style="color: red">'.dr_lang('本类型字段不建议作为条件筛选字段').'</label></div>
		</div>';
        }

        /**
         * 获取会员默认值
         *
         * @param	string	$name
         * @return  string
         */
        public function get_default_value($value) {

            if (dr_is_empty($value)) {
                // 没设置时返回空
                return '';
            }

            $uid = 0;
            if (isset(\Phpcmf\Service::L('Field')->value['uid']) && \Phpcmf\Service::L('Field')->value['uid']) {
                $uid = \Phpcmf\Service::L('Field')->value['uid'];
            }

            $member = \Phpcmf\Service::C()->member;
            if ($member && $member['id'] != $uid && $uid) {
                $member = dr_member_info($uid);
            }

            if (preg_match('/\{(\w+)\}/', $value, $match)) {
                $rt = isset($member[$match[1]]) ? $member[$match[1]] : '';
                if ($match[1] == 'name' && !$rt) {
                    $rt = isset($member['username']) ? $member['username'] : '';
                }
                return $rt;
            } elseif (strpos((string)$value, '()') !== false) {
                $func = str_replace('()', '', trim((string)$value));
                if (function_exists($func)) {
                    return call_user_func($func);
                }
            }

            return $value;
        }

        // 数字默认值
        public function _default_value($type) {
            if (in_array($type, array('INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'))) {
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
				<option value="BIGINT" '.($name == 'BIGINT' ? 'selected' : '').'>BIGINT</option>
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

            return str_replace(['{name}', '{text}', '{value}'], [$name, dr_lang($text), $value], $fomart);
        }

        /**
         * 附件存储策略
         * @return  string
         */
        public function attachment($option, $image_reduce = 1) {

            $id = isset($option['attachment']) ? $option['attachment'] : 0;
            $remote = \Phpcmf\Service::C()->get_cache('attachment');

            $html = '<label><select class="form-control" name="data[setting][option][attachment]">';
            if (SYS_ATTACHMENT_SAVE_ID && isset($remote[SYS_ATTACHMENT_SAVE_ID])) {
                $html.= '<option value="0"> '.dr_lang($remote[SYS_ATTACHMENT_SAVE_ID]['name']).' </option>';
            } else {
                $html.= '<option value="0"> '.dr_lang('默认存储').' </option>';
            }

            if ($remote) {
                foreach ($remote as $i => $t) {
                    if (SYS_ATTACHMENT_SAVE_ID && $t['id'] == SYS_ATTACHMENT_SAVE_ID) {
                        continue;
                    }
                    $html.= '<option value="'.$i.'" '.($i == $id ? 'selected' : '').'> '.dr_lang($t['name']).' </option>';
                }
            }

            $html.= '</select></label>';

            $str = '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('附件存储策略').' </label>
			<div class="col-md-9">
				'.$html.'
                <span class="help-block">'.dr_lang('远程附件存储建议设置小文件存储，推荐10MB内，大文件会导致数据传输失败').'</span>
			</div>
		</div>';
            if ($image_reduce) {
                $str.= '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('图片压缩大小').' </label>
			<div class="col-md-9">
                <label><input type="text" class="form-control" value="'.$option['image_reduce'].'" name="data[setting][option][image_reduce]"></label>
                <span class="help-block">'.dr_lang('填写图片宽度，例如1000，表示图片大于1000px时进行压缩图片').'</span>
			</div>
		</div>';
            }
            return $str;
        }
    }
}

