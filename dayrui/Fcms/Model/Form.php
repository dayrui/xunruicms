<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 模型类

class Form extends \Phpcmf\Model
{

    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->table = SITE_ID.'_form';
    }

    // 设置操作表
    public function table($name) {
        $this->table = SITE_ID.'_'.$name;
        return $this;
    }

    // 创建表单文件
    public function create_file($table, $call = 0) {

        $name = ucfirst($table);
        $files = [
            APPSPATH.'Form/Controllers/'.$name.'.php' => FCPATH.'Temp/Form/$NAME$.php',
            APPSPATH.'Form/Controllers/Member/'.$name.'.php' => FCPATH.'Temp/Form/Member$NAME$.php',
            APPSPATH.'Form/Controllers/Admin/'.$name.'.php' => FCPATH.'Temp/Form/Admin$NAME$.php',
            APPSPATH.'Form/Controllers/Admin/'.$name.'_verify.php' => FCPATH.'Temp/Form/Admin$NAME$_verify.php',
        ];

        $ok = 0;
        foreach ($files as $file => $form) {
            if (!is_file($file)) {
                if (!is_dir(dirname($file))) {
                    dr_mkdirs(dirname($file));
                }
                $c = @file_get_contents($form);
                $size = @file_put_contents($file, str_replace('$NAME$', $name, $c));
                if (!$size && $call) {
                    @unlink($file);
                    return dr_return_data(0, dr_lang('文件%s创建失败，无可写权限', str_replace(FCPATH, '', $file)));
                }
                $ok ++;
            }
        }
        
        return dr_return_data(1, $ok);
    }
    
    // 创建表单
    public function create($data) {

        $rt = $this->insert([
            'name' => $data['name'],
            'table' => $data['table'],
            'setting' => '',
        ]);
        if (!$rt['code']) {
            return $rt;
        }
        
        // 创建文件
        $this->create_file($data['table']);
        
        // 创建表
        \Phpcmf\Service::M('Table')->create_form([
            'id' => $rt['code'],
            'name' => $data['name'],
            'table' => $data['table'],
        ]);

        return $rt;
    }

    // 导入
    public function import($data) {

        if ($this->table('form')->is_exists(0, 'table', $data['table'])) {
            return dr_return_data(0, dr_lang('数据表名称已经存在'));
        }

        $rt = $this->insert([
            'name' => $data['name'],
            'table' => $data['table'],
            'setting' => dr_array2string($data['setting']),
        ]);
        if (!$rt['code']) {
            return $rt;
        }
        $id = $rt['code'];
        // 导入字段
        foreach ($data['field'] as $t) {
            unset($t['id']);
            $t['relatedid'] = $id;
            $t['relatedname'] = 'form-'.SITE_ID;
            $r = parent::table('field')->insert($t);
            if (!$r['code']) {
                $this->db->table(SITE_ID.'_form')->where('id', $id)->delete();
                $this->db->table('field')->where('relatedid', $t['relatedid'])->where('relatedname', $t['relatedname'])->delete();
                return $r;
            }
        }

        // 创建文件
        $this->create_file($data['table']);

        // 创建表
        $rt = \Phpcmf\Service::M('Table')->_query(str_replace('{table}', $this->dbprefix(SITE_ID.'_form_'.$data['table']), $data['sql']));

        if (!$rt['code']) {
            $this->db->table(SITE_ID.'_form')->where('id', $id)->delete();
            $this->db->table('field')->where('relatedid', $id)->where('relatedname', 'form-'.SITE_ID)->delete();
            return $rt;
        }

        return dr_return_data(1, 'ok');
    }

    // 批量删除
    public function delete_form($ids) {

        foreach ($ids as $id) {
            $row = $this->table('form')->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('数据不存在(id:%s)', $id));
            }
            $rt = $this->table('form')->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            $name = ucfirst($row['table']);
            unlink(APPSPATH.'Form/Controllers/'.$name.'.php');
            unlink(APPSPATH.'Form/Controllers/Admin/'.$name.'.php');
            unlink(APPSPATH.'Form/Controllers/Member/'.$name.'.php');
            unlink(APPPATH.'Controllers/Admin/'.$name.'_verify.php');
            // 删除表数据
            \Phpcmf\Service::M('Table')->delete_form($row);
        }

        return dr_return_data(1, '');
    }

    // 缓存
    public function cache($siteid = SITE_ID) {

        $data = $this->init(['table' => $siteid.'_form'])->getAll();
        if ($data) {
            foreach ($data as $t) {
                $t['field'] = [];
                $t['setting'] = dr_string2array($t['setting']);
                // 排列table字段顺序
                $t['setting']['list_field'] = dr_list_field_order($t['setting']['list_field']);
                // 当前表单的自定义字段
                $field = $this->db->table('field')
                                ->where('disabled', 0)
                                ->where('relatedname', 'form-'.$siteid)
                                ->where('relatedid', intval($t['id']))
                                ->orderBy('displayorder ASC,id ASC')
                                ->get()->getResultArray();
                if ($field) {
                    foreach ($field as $fv) {
                        $fv['setting'] = dr_string2array($fv['setting']);
                        $t['field'][$fv['fieldname']] = $fv;
                    }
                }
                $cache[$t['table']] = $t;
                if (!$t['setting']['dev']) {
                    \Phpcmf\Service::M('Menu')->form($t); // 更新菜单
                }

            }
        }

        \Phpcmf\Service::L('cache')->set_file('form-'.$siteid, $cache);

    }
}