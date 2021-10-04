<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Seo_site extends \Phpcmf\Common {

	public function index() {

		if (IS_AJAX_POST) {
			$rt = \Phpcmf\Service::M('Site')->config(
			    SITE_ID,
                'seo',
                \Phpcmf\Service::L('input')->post('data', true)
            );
            \Phpcmf\Service::M('Site')->config_value(SITE_ID, 'config', [
                'SITE_INDEX_HTML' => intval(\Phpcmf\Service::L('input')->post('SITE_INDEX_HTML'))
            ]);
            if (!is_array($rt)) {
                $this->_json(0, dr_lang('网站SEO(#%s)不存在', SITE_ID));
            }
			\Phpcmf\Service::L('input')->system_log('设置网站SEO');
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['seo'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '站点SEO' => ['seo_site/index', 'fa fa-cog'],
                    'help' => [494],
                ]
            ),
            'module' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
            'SITE_INDEX_HTML' => $data['config']['SITE_INDEX_HTML'],
		]);
		\Phpcmf\Service::V()->display('seo_site.html');
	}

	public function sync_index() {

        $ct = intval(\Phpcmf\Service::L('input')->get('ct'));
        $value = intval(\Phpcmf\Service::L('input')->get('value'));
        $url = dr_url(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, ['ct' => $ct, 'value' => $value]);
        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table(SITE_ID.'_share_category')->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用栏目更新'));
            }
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page=1');
        }

        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            \Phpcmf\Service::M('cache')->sync_cache('');
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $category = \Phpcmf\Service::M()->db->table(SITE_ID.'_share_category')->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        if ($category) {
            foreach ($category as $data) {
                $data['setting'] = dr_string2array($data['setting']);
                if ($ct == 1) {
                    $data['setting']['urlrule'] = $value;
                } elseif ($ct == 2) {
                    $data['setting']['template']['pagesize'] = $value;
                } elseif ($ct == 3) {
                    $data['setting']['template']['mpagesize'] = $value;
                }

                \Phpcmf\Service::M()->table_site('share_category')->update($data['id'], [
                    'setting' => dr_array2string($data['setting']),
                ]);
            }
        }

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }

}
