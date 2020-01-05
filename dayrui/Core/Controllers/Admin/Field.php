<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Field extends \Phpcmf\Common
{
	public $name;
	public $data;
	
	public $ftype;

	public $backurl; // 返回链接
	public $cachename; // 缓存名称

	public $namespace;
	public $relatedid;
	public $relatedname;

	public function __construct(...$params) {
		parent::__construct(...$params);

		$this->name = '字段管理';
		$this->namespace = ''; // 设置应用目录
		
		// 字段来源相关表
        \Phpcmf\Service::M('Field')->relatedid = $this->relatedid = (int)\Phpcmf\Service::L('input')->get('rid');
		\Phpcmf\Service::M('Field')->relatedname = $this->relatedname = \Phpcmf\Service::L('input')->get('rname');

		list($ismain, $issearch, $iscategory) = $this->_set_init();

		// 可用字段类别
		$this->ftype = \Phpcmf\Service::L('Field')->app($this->namespace)->type(\Phpcmf\Service::M('Field')->func);

		// 判断类别权限

		\Phpcmf\Service::V()->assign([
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
				[
					'返回' => ['url:'.$this->backurl, 'fa fa-reply'],
					$this->name => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname'=>$this->relatedname, 'rid'=>$this->relatedid]), 'fa fa-code', 'field/index'],
					'添加' => ['url:'.\Phpcmf\Service::L('Router')->url('field/add', ['rname'=>$this->relatedname, 'rid'=>$this->relatedid]), 'fa fa-plus', 'field/add'],
					'修改' => ['hide:field/edit', 'fa fa-edit'],
				]
			),
			'rid' => $this->relatedid,
			'rname' => $this->relatedname,
			'ftype' => $this->ftype,
			'ismain' => $ismain,
			'issearch' => $issearch,
			'namespace' => $this->namespace,
			'iscategory' => $iscategory,
		]);
		
	}

	public function test() {

        \Phpcmf\Service::V()->assign(array(
            'list' => [],
        ));
        \Phpcmf\Service::V()->display('field_list.html');
    }

	public function index() {

        $field = \Phpcmf\Service::M('Field')->get_all();
        if ($field) {
            uasort($field, function($a, $b){
                if($a['displayorder'] == $b['displayorder']){
                    return 0;
                }
                return($a['displayorder']<$b['displayorder']) ? -1 : 1;
            });
            $group = [];
            $mygroup = [];
            // 分组和合并字段筛选
            foreach ($field as $t) {
                if ($t['fieldtype'] == 'Group' || $t['fieldtype'] == 'Merge') {
                    if (preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                        foreach ($value[1] as $v) {
                            $group[$t['fieldtype']][$v] = $t['fieldname'];
                        }
                    }
                    $mygroup[$t['fieldtype']][$t['fieldname']] = $t;
                }
            }

            $data = [];
            $group_data = [];

            // 主字段
            foreach ($field as $t) {

                if (isset($data[$t['fieldname']]) && $data[$t['fieldname']]) {
                    // 重复了 删除记录
                    \Phpcmf\Service::M()->table('field')->delete($t['id']);
                }
                if (isset($group['Merge'][$t['fieldname']])) {
                    $group_data['Merge'][$t['fieldname']] = $t;
                } elseif (isset($group['Group'][$t['fieldname']])) {
                    // 属于分组字段
                    $group_data['Group'][$t['fieldname']] = $t;
                } elseif ($t['fieldtype'] == 'Group') {
                    $data[$t['fieldname']] = '';
                } elseif ($t['fieldtype'] == 'Merge') {
                    $data[$t['fieldname']] = '';
                } else {
                    $data[$t['fieldname']] = $t;
                }

            }

            if ($mygroup['Merge']) {
                foreach ($mygroup['Merge'] as $m) {
                    $list = [];
                    foreach ($group['Merge'] as $fieldname => $t) {
                        $m['fieldname'] == $t && $list[] = $group_data['Merge'][$fieldname];
                    }
                    $list && $data[$m['fieldname']] = $list;
                }
            }
            if ($mygroup['Group']) {
                foreach ($mygroup['Group'] as $m) {
                    $list = [];
                    foreach ($group['Group'] as $fieldname => $t) {
                        $m['fieldname'] == $t && $list[] = $group_data['Group'][$fieldname];
                    }
                    $list && $data[$m['fieldname']] = $list;
                }
            }

            $list = [];
            foreach ($data as $fname => $t) {
                if (isset($t['id'])) {
                    $list[$t['fieldname']] = $t;
                } elseif ($mygroup['Group'][$fname])  {
                    $mygroup['Group'][$fname]['spacer'] = $group['Merge'][$fname] ? '<span class="tree-icon">└</span>' : '';
                    $list[$fname] = $mygroup['Group'][$fname];
                    foreach ($t as $f) {
                        $f['spacer'] = $group['Merge'][$fname] ? '<span class="tree-icon">└</span><span class="tree-icon">└</span>' :  '<span class="tree-icon">└</span>';
                        $f['id'] && $list[$f['fieldname']] = $f;
                    }
                } elseif ($mygroup['Merge'][$fname])  {
                    $list[] = $mygroup['Merge'][$fname];
                    foreach ($t as $f) {
                        $f['spacer'] = '<span class="tree-icon">└</span>';
                        if ($f['id']) {
                            $list[$f['fieldname']] = $f;
                            if ($mygroup['Group'][$f['fieldname']] && $data[$f['fieldname']]) {
                                foreach ($data[$f['fieldname']] as $ff) {
                                    $ff['spacer'] = '<span class="tree-icon">└</span><span class="tree-icon">└</span>';
                                    $ff['id'] && $list[$ff['fieldname']] = $ff;
                                }
                            }
                        }

                    }
                }
            }

            //print_r($data);
        } else {
            $list = [];
        }

		\Phpcmf\Service::V()->assign(array(
			'list' => $list,
			'role' => \Phpcmf\Service::C()->get_cache('auth'),
		));
		\Phpcmf\Service::V()->display('field_index.html');
	}

	public function add() {

		// 初始化部分值
        $data = [
            'setting' => [
                'validate' => [],
            ],
        ];
		$page = max((int)\Phpcmf\Service::L('input')->post('page'), 0);
		$data['fieldtype'] = $data['setting']['option'] = '';
		$data['setting']['validate']['required'] = $id = 0;
		$data['ismain'] = 1;

		// 提交表单
		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
			if (!$field) {
				$this->_json(0, dr_lang('字段类别不存在'));
			} elseif (empty($data['name'])) {
				$this->_json(0, dr_lang('字段显示名称不能为空'));
			} elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $data['fieldname'])) {
				$this->_json(0, dr_lang('字段名称不规范'));
			} elseif (strlen($data['fieldname']) > 20) {
				$this->_json(0, dr_lang('字段名称太长'));
			} elseif (\Phpcmf\Service::M('Field')->exitsts($data['fieldname'])) {
				$this->_json(0, dr_lang('字段已经存在'));
			} else {
				$rt = \Phpcmf\Service::M('Field')->add($data, $field);
				if (!$rt['code']) {
					$this->_json(0, dr_lang($rt['msg']));
				}
                $this->_cache(); // 自动更新缓存
				\Phpcmf\Service::L('input')->system_log('添加'.$this->name.'【'.$data['fieldname'].'】'.$data['name']); // 记录日志
				$this->_json(1, dr_lang('操作成功'));
			}
		}

		\Phpcmf\Service::V()->assign(array(
			'id' => $id,
			'page' => $page,
			'data' => $data,
			'form' => dr_form_hidden(['page' => $page]),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
		));
		\Phpcmf\Service::V()->display('field_add.html');
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$page = max((int)\Phpcmf\Service::L('input')->get('page'), 0);
		$data = \Phpcmf\Service::M()->table('field')->get($id);
		if (!$data) {
            $this->_json(0, dr_lang('数据#%s不存在', $id));
        }
		$data['setting'] = dr_string2array($data['setting']);

		if (IS_AJAX_POST) {
			$post = \Phpcmf\Service::L('input')->post('data');
			$field = \Phpcmf\Service::L('field')->get($data['fieldtype']);
			$rt = \Phpcmf\Service::M('Field')->edit(
				$data,
				$post,
				$field->alter_sql($data['fieldname'], $post['setting']['option'], $data['name'])
			);
			if (!$rt['code']) {
			    $this->_json(0, dr_lang($rt['msg']));
            }
            $this->_cache(); // 自动更新缓存
			\Phpcmf\Service::L('input')->system_log('修改'.$this->name.'【'.$data['fieldname'].'】'.$data['name']); // 记录日志
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'id' => $id,
			'data' => $data,
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
            'role' => \Phpcmf\Service::C()->get_cache('auth'),
		]);
		\Phpcmf\Service::V()->display('field_add.html');
	}


	/**
	 * 通用操作
	 */
	public function option() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$data = \Phpcmf\Service::M()->table('field')->get($id);
		if (!$data) {
			exit($this->_json(0, dr_lang('字段不存在')));
		}
		
		switch (\Phpcmf\Service::L('input')->get('op')) {
			case 'disabled':
				$value = $data['disabled'] == 1 ? 0 : 1;
				\Phpcmf\Service::M()->table('field')->save($id, 'disabled', $value);
                $this->_cache(); // 自动更新缓存
				\Phpcmf\Service::L('input')->system_log(($value ? '禁用' : '启用').$this->name.'【'.$data['fieldname'].'】'); // 记录日志
				exit($this->_json(1, dr_lang(($value ? '禁用' : '启用').'成功'), ['value' => $value]));
				break;
			case 'xss':
				$data['setting'] = dr_string2array($data['setting']);
				$data['setting']['validate']['xss'] = $value = $data['setting']['validate']['xss'] ? 0 : 1;
				\Phpcmf\Service::M()->table('field')->save($id, 'setting', dr_array2string($data['setting']));
                $this->_cache(); // 自动更新缓存
				\Phpcmf\Service::L('input')->system_log($this->name.'【'.$data['fieldname'].'】'.($value ? '开启XSS' : '关闭XSS')); // 记录日志
				exit($this->_json(1, dr_lang('操作成功'), ['value' => $value]));
				break;
			case 'member':
				$value = $data['ismember'] ? 0 : 1;
				\Phpcmf\Service::M()->table('field')->save($id, 'ismember', $value);
                $this->_cache(); // 自动更新缓存
				\Phpcmf\Service::L('input')->system_log($this->name.'【'.$data['fieldname'].'】'.($value ? '前端显示' : '前端隐藏')); // 记录日志
				exit($this->_json(1, dr_lang('操作成功'), ['value' => $value]));
				break;
			case 'save':
				\Phpcmf\Service::M()->table('field')->save($id, 'displayorder', dr_safe_replace(\Phpcmf\Service::L('input')->get('value')));
                $this->_cache(); // 自动更新缓存
				\Phpcmf\Service::L('input')->system_log('修改排序值: '. $this->name.'【'.$data['fieldname'].'】');
				exit($this->_json(1, dr_lang('操作成功')));
				break;
		}

		exit($this->_json(0, dr_lang('未知操作')));
	}

	// 删除字段
	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
			exit($this->_json(0, dr_lang('你还没有选择呢')));
		}

		$rt = \Phpcmf\Service::M('Field')->delete_field($ids);
		if (!$rt['code']) {
			exit($this->_json(0, $rt['msg']));
		} 

        $this->_cache(); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('删除字段'. $this->name.' '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	// 联动更新缓存
	private function _cache() {

        list($case_name, $a) = explode('-', $this->relatedname);

        switch ($case_name) {

            case 'form':
                // 网站表单 form-站点id, 表单id
                \Phpcmf\Service::M('cache')->sync_cache('form', '', 1); // 自动更新缓存
                break;

            case 'tag':
                // 网站tag
                \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1); // 自动更新缓存
                break;

            case 'linkage':
                // 联动菜单
                \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
                break;

            case 'member':
                // 用户主表
                \Phpcmf\Service::M('cache')->sync_cache('member'); // 自动更新缓存
                break;

            case 'navigator':
                // 导航链接
                \Phpcmf\Service::M('cache')->sync_cache('navigator', 'navigator', 1); // 自动更新缓存
                break;

            case 'order':
                // 订单插件
                break;

            case 'page':
                // 网站单页
                \Phpcmf\Service::M('cache')->sync_cache('page', 'page', 1); // 自动更新缓存
                break;

            case 'table':
                // 任意表
                break;

            case 'module':
                // 模块字段
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                break;

            case 'mform':
                // 模块表单
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                break;

            case 'category':
                // 栏目自定义字段
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                break;

            default:
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                break;
        }
    }

	// 初始化设置
	private function _set_init() {

        $ismain = $issearch = $iscategory = 0;

        list($case_name, $a) = explode('-', $this->relatedname);

        switch ($case_name) {

            case 'form':
                // 网站表单 form-站点id, 表单id
                list($a, $siteid) = explode('-', $this->relatedname);
                $this->data = \Phpcmf\Service::M()->init(['db' => $siteid, 'table' => $siteid.'_form'])->get($this->relatedid);
                if (!$this->data) {
					$this->_admin_msg(0, dr_lang('网站表单【%s】不存在', $this->relatedid));
				} 
                $this->name = '网站表单【'.$this->data['name'].'】字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('form/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'form'; // 重要标识: 函数和识别码
                \Phpcmf\Service::M('Field')->data = $this->data;
                break;

            case 'tag':
                // 网站tag
                $ismain = 1;
                $this->name = 'Tag字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('tag/home/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'tag'; // 重要标识: 函数和识别码
                break;

            case 'linkage':
                // 联动菜单
                $ismain = 1;
                $this->name = '联动菜单字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('linkage/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'linkage'; // 重要标识: 函数和识别码
                break;

            case 'member':
                // 用户主表
                $ismain = 1;
                $this->name = '用户信息字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('member_field/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'member'; // 重要标识: 函数和识别码
                break;

            case 'navigator':
                // 导航链接
                $ismain = 1;
                $this->name = '自定义链接字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('navigator/home/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'navigator'; // 重要标识: 函数和识别码
                break;

            case 'order':
                // 订单插件
                $ismain = 1;
                $this->name = '订单应用';
                $this->backurl =\Phpcmf\Service::L('Router')->url('order/field/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'order'; // 重要标识: 函数和识别码
                break;

            case 'page':
                // 网站单页
                $ismain = 1;
                $this->name = '自定义页面字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url('page/home/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'page'; // 重要标识: 函数和识别码
                break;

            case 'table':
                // 任意表
                $ismain = 1;
                $this->name = '数据表【'.\Phpcmf\Service::M()->dbprefix($a).'】';
                \Phpcmf\Service::M('Field')->data = $a;
                \Phpcmf\Service::M('Field')->func = 'table'; // 重要标识: 函数和识别码
                break;

            case 'module':
                // 模块字段
                $this->data = \Phpcmf\Service::M()->table('module')->get($this->relatedid);
                if (!$this->data) {
					$this->_admin_msg(0, dr_lang('模块【%s】不存在', $this->relatedid));
				}
                $this->backurl =\Phpcmf\Service::L('Router')->url('module/index'); // 返回uri地址
                $this->name = '模块【'.$this->data['dirname'].'】字段';
                \Phpcmf\Service::M('Field')->func = 'module'; // 重要标识: 函数和识别码
                \Phpcmf\Service::M('Field')->data = $this->data;
                $this->namespace = $this->data['dirname'];
                break;

            case 'mform':
                // 模块表单
                $this->data = \Phpcmf\Service::M()->table('module_form')->get($this->relatedid);
                if (!$this->data) {
					$this->_admin_msg(0, dr_lang('模块【%s】不存在', $this->relatedid));
				} 
                $this->backurl =\Phpcmf\Service::L('Router')->url('module/form_index', ['dir' => $a]); // 返回uri地址
                $this->name = '模块【'.$a.'】的表单【'.$this->data['name'].'】字段';
                \Phpcmf\Service::M('Field')->func = 'mform'; // 重要标识: 函数和识别码
                \Phpcmf\Service::M('Field')->data = $this->data;
                $this->namespace = $this->data['module'];
                break;

            case 'category':
                // 栏目自定义字段
                $ismain = 1;
                $this->name = '栏目自定义字段';
                $this->backurl =\Phpcmf\Service::L('Router')->url(($a == 'share' ? '' : $a).'/category/index'); // 返回uri地址
                \Phpcmf\Service::M('Field')->func = 'category'; // 重要标识: 函数和识别码
                \Phpcmf\Service::M('Field')->data = $a;
                break;

            default:
                if (strpos($this->relatedname, 'comment-module') !== false) {
                    // 模块评论字段
                    $ismain = 1;
                    list($a, $b, $module) = explode('-', $this->relatedname);
                    $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);
                    if (!$cache) {
						$this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $module));
					}
                    $this->name = '模块【'.$cache['dirname'].'】评论字段';
                    $this->data = $cache['dirname'];
                    $this->backurl =\Phpcmf\Service::L('Router')->url('module/index'); // 返回uri地址
                    \Phpcmf\Service::M('Field')->func = 'comment'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $cache['dirname'];
                    $this->namespace = $cache['dirname'];
                } else {
                    // 识别栏目模型字段
                    $issearch = 1;
                    $iscategory = 1;
                    list($module, $s) = explode('-', $this->relatedname);
                    $cache = \Phpcmf\Service::L('cache')->get('module-'.$s.'-'.$module);
                    if (!$cache) {
						$this->_admin_msg(0, dr_lang('模块【%s】缓存不存在', $module));
					} 
                    if ($this->relatedid) {
                        $this->data = $cache['category'][$this->relatedid];
                        if (!$this->data) {
							$this->_admin_msg(0, dr_lang('模块【%s】栏目【%s】缓存不存在', $module, $this->relatedid));
						} 
                        if ($module == 'share') {
                            $this->data['tid'] != 1 && $this->_admin_msg(0, dr_lang('模块栏目才支持创建'));
                            $this->data['dirname'] = $this->data['mid'];
                            $this->backurl =\Phpcmf\Service::L('Router')->url('category/index'); // 返回uri地址
                            $this->name = '模块【'.$this->data['mid'].'】栏目【#'.$this->relatedid.'】模型字段';
                        } else {
                            $this->data['dirname'] = $module;
                            $this->backurl =\Phpcmf\Service::L('Router')->url($module.'/category/index'); // 返回uri地址
                            $this->name = '模块【'.$module.'】栏目【#'.$this->relatedid.'】模型字段';
                        }
                    } else {
                        $this->data = [
                            'dirname' => $module,
                        ];
                        $this->name = '模块【'.$module.'】栏目公共模型字段';
                        $this->backurl = \Phpcmf\Service::L('Router')->url('module_category/field_index', ['dir' => $module]); // 返回uri地址
                    }

                    \Phpcmf\Service::M('Field')->func = 'category_data'; // 重要标识: 函数和识别码
                    \Phpcmf\Service::M('Field')->data = $this->data;
                    $this->namespace = $module;
                }
                break;
        }

        return [$ismain, $issearch, $iscategory];
    }

}
