<?php namespace Phpcmf\Controllers\Admin;

class Api extends \Phpcmf\Common
{


    // 统计
    public function mtotal() {

        $t1 = $t2 = $t3 = $t4 = $t5 = 0;
        $dir = dr_safe_filename(\Phpcmf\Service::L('input')->get('dir'));
        $prefix = dr_module_table_prefix($dir);
        if (is_dir(dr_get_app_dir($dir))) {
            $this->_module_init($dir);
            $where = $this->content_model->get_admin_list_where();
            $t1 = \Phpcmf\Service::M()->table($prefix)->where($where)->where('DATEDIFF(from_unixtime(inputtime),now())=0')->counts();
            $t2 = \Phpcmf\Service::M()->table($prefix)->where($where)->counts();
            $t3 = \Phpcmf\Service::M()->table($prefix.'_verify')->where($this->content_model->get_admin_list_verify_where($where))->counts();
            $t4 = \Phpcmf\Service::M()->table($prefix.'_recycle')->where($where)->counts();
            $t5 = \Phpcmf\Service::M()->table($prefix.'_time')->where($where)->counts();
        }
        echo '$("#'.$dir.'_today").html('.$t1.');';
        echo '$("#'.$dir.'_all").html('.$t2.');';
        echo '$("#'.$dir.'_verify").html('.$t3.');';
        echo '$("#'.$dir.'_recycle").html('.$t4.');';
        echo '$("#'.$dir.'_timing").html('.$t5.');';
        exit;
    }

    // 更新url
    public function update_url() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 200; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($this->content_model->mytable)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $url = dr_url('module/api/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            \Phpcmf\Service::M('cache')->update_data_cache();
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $update = [];
        $data = \Phpcmf\Service::M()->db->table($this->content_model->mytable)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            if ($t['link_id'] && $t['link_id'] >= 0) {
                // 同步栏目的数据
                $i = $t['id'];
                $t = \Phpcmf\Service::M()->db->table($this->content_model->mytable)->where('id', (int)$t['link_id'])->get()->getRowArray();
                if (!$t) {
                    continue;
                }
                $url = \Phpcmf\Service::L('Router')->show_url($this->module, $t);
                $t['id'] = $i; // 替换成当前id
            } else {
                $url = \Phpcmf\Service::L('Router')->show_url($this->module, $t);
            }
            $update[] = [
                'id' => (int)$t['id'],
                'url'=> $url,
            ];
        }
        $update && \Phpcmf\Service::M()->table($this->content_model->mytable)->update_batch($update);

        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            dr_url('module/api/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid,'total' => $total, 'page' => $page + 1])
        );
    }

    // 统计栏目
    public function ctotal() {

        $rt = '';
        if (IS_POST) {
            $ids = dr_string2array(\Phpcmf\Service::L('input')->post('cid'));
            if ($ids) {
                foreach ($ids as $t) {
                    list($id, $mid) = explode('-', $t);
                    if ($id && $mid && dr_is_module($mid) ) {
                        $db = \Phpcmf\Service::M()->table(dr_module_table_prefix($mid).'_index');
                        $mod = $this->get_cache('module-'.SITE_ID.'-'.$mid);
                        if ($mod['category'][$id]['childids']) {
                            $db->where('catid in ('.$mod['category'][$id]['childids'].')');
                        } else {
                            $db->where('catid', $id);
                        }
                        $num = $db->where('status=9')->counts();
                        if ($num) {
                            $rt.= '$(".cat-total-'.$id.'").html("'.dr_lang('（%s）', $num).'");';
                        }
                    }
                }
            }
        }

        $this->_json(1, $rt);
    }

    // 更新栏目缓存配置
    public function update_category_cache() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $cdir = 'share';
        } else {
            $cdir = $mid;
        }
        if (\Phpcmf\Service::M()->table(SITE_ID.'_'.$cdir.'_category')->counts() > MAX_CATEGORY) {
            \Phpcmf\Service::M('module')->update_category_cache(SITE_ID, $cdir);
        }

        \Phpcmf\Service::M('cache')->sync_cache();

        $this->_json(1, dr_lang('操作成功'));
    }

}
