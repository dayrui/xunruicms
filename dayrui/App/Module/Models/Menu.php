<?php namespace Phpcmf\Model\Module;

// 菜单控制模型

class Menu extends \Phpcmf\Model {

    // 变更模块名称
    public function update_module_name($mid, $old, $new, $icon) {

        $replace = '`icon`="'.$icon.'", `name`=REPLACE(`name`, \''.addslashes($old).'\', \''.addslashes($new).'\')';

        $this->is_table_exists('member_menu') && $this->db->query('UPDATE `'.$this->dbprefix('member_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/home/index"');

        $this->db->query('UPDATE `'.$this->dbprefix('admin_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/home/index"');
        $this->db->query('UPDATE `'.$this->dbprefix('admin_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/verify/index"');
        $this->db->query('UPDATE `'.$this->dbprefix('admin_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/comment_verify/index"');

        $this->db->query('UPDATE `'.$this->dbprefix('admin_min_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/home/index"');
        $this->db->query('UPDATE `'.$this->dbprefix('admin_min_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/verify/index"');
        $this->db->query('UPDATE `'.$this->dbprefix('admin_min_menu').'` SET '.$replace.' WHERE uri="'.$mid.'/comment_verify/index"');
    }

    // 从模块中更新菜单
    public function update_module($mdir, $config, $form) {

        // 作为应用模块时且不操作menu.php时,不需要菜单
        if (isset($config['ftpye']) && $config['ftpye'] == 'module'
            && is_file(dr_get_app_dir($mdir).'Config/Menu.php')) {
            return;
        }

        // 内容模块 入库后台菜单
        if ($config['system'] == 1) {
            foreach (['admin', 'admin_min'] as $table) {
                $left = $this->db->table($table.'_menu')->where('mark', 'content-module')->get()->getRowArray();
                if ($left) {
                    // 查询模块菜单
                    $menu = $this->db->table($table.'_menu')->where('mark', 'module-'.$mdir)->get()->getRowArray();
                    $save = [
                        'uri' => $mdir.'/home/index',
                        'mark' => 'module-'.$mdir,
                        'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s管理', $config['name']),
                        'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                        'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                    ];
                    $menu ? \Phpcmf\Service::M('menu')->_edit($table, $menu['id'], $save) : \Phpcmf\Service::M('menu')->_add($table, $left['id'], $save);
                }
                // 入库后台审核菜单
                $left = $this->db->table($table.'_menu')->where('mark', 'content-verify')->get()->getRowArray();
                if ($left) {
                    // 内容模块入库
                    if ($config['system'] == 1) {
                        $menu = $this->db->table($table.'_menu')->where('mark', 'verify-module-'.$mdir)->get()->getRowArray();
                        $save = [
                            'uri' => $mdir.'/verify/index',
                            'mark' => 'verify-module-'.$mdir,
                            'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s审核', $config['name']),
                            'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                            'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                        ];
                        $menu ? \Phpcmf\Service::M('menu')->_edit($table, $menu['id'], $save) : \Phpcmf\Service::M('menu')->_add($table, $left['id'], $save);
                    }
                    // 表单入库
                    if ($form && dr_is_app('mform')) {
                        \Phpcmf\Service::M('mform', 'mform')->link_menu($form, $table, $mdir, $config, $left);
                    }
                }
            }
        }

        // 内容模块入库用户菜单
        if ($config['system'] == 1 && $this->is_table_exists('member_menu')) {
            $left = $this->db->table('member_menu')->where('mark', 'content-module')->get()->getRowArray();
            if ($left) {
                // 查询模块菜单
                $menu = $this->db->table('member_menu')->where('mark', 'module-'.$mdir)->get()->getRowArray();
                $save = [
                    'uri' => $mdir.'/home/index',
                    'mark' => 'module-'.$mdir,
                    'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s管理', $config['name']),
                    'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                    'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                ];
                $menu ? \Phpcmf\Service::M('menu')->_edit('member', $menu['id'], $save) : \Phpcmf\Service::M('menu')->_add('member', $left['id'], $save);
            }
        }
    }
}