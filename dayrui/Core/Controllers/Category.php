<?php namespace Phpcmf\Controllers;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Category extends \Phpcmf\Home\Module
{

	public function index() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$dir = dr_safe_replace(\Phpcmf\Service::L('input')->get('dir'));
		$page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));

		$module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share');
		if (!$module) {
		    $this->_msg(0, dr_lang('共享栏目缓存不存在'));
            return;
        }

		if ($id) {
			$cat = $module['category'][$id];
			!$cat && exit($this->goto_404_page(dr_lang('栏目（%s）不存在', $id)));
		} elseif ($dir) {
			$id = intval($module['category_dir'][$dir]);
            $cat = $module['category'][$id];
			if (!$cat) {
				// 无法通过目录找到栏目时，尝试多及目录
				foreach ($module['category'] as $t) {
					if ($t['setting']['urlrule']) {
						$rule = \Phpcmf\Service::L('cache')->get('urlrule', $t['setting']['urlrule']);
						$rule['value']['catjoin'] = '/';
						if ($rule['value']['catjoin'] && strpos($dir, $rule['value']['catjoin'])) {
                            $dir = trim(strchr($dir, $rule['value']['catjoin']), $rule['value']['catjoin']);
							if (isset($module['category_dir'][$dir])) {
								$id = $module['category_dir'][$dir];
                                $cat = $module['category'][$id];
								break;
							}
						}
					}
				}
				// 返回无法找到栏目
				!$id && exit($this->goto_404_page(dr_lang('栏目（%s）不存在', $dir)));
			}
		} else {
            exit($this->goto_404_page(dr_lang('栏目参数不存在')));
		}

		// 初始化模块
        if ($cat['tid'] == 1) {
		    if ($cat['mid']) {
                $this->_module_init($cat['mid']);
            } else {
                exit($this->goto_404_page(dr_lang('栏目所属模块不存在')));
            }
        } else {
            $this->_module_init('share');
        }

		
		// 调用栏目方法
		$this->_Category($id, $dir, $page);
	}

}
