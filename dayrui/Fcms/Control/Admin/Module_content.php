<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Module_content extends \Phpcmf\Common
{

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 不是超级管理员
        if (!dr_in_array(1, $this->admin['roleid'])) {
            $this->_admin_msg(0, dr_lang('需要超级管理员账号操作'));
        }
    }

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        $bm = [];
        $tables = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();
        foreach ($tables as $t) {
            $t['Name'] = str_replace('_data_0', '_data_[tableid]', $t['Name']);
            $bm[$t['Name']] = $t;
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' =>  dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容维护工具' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                    'help' => [1142],
                ]
            ),
            'table' => \Phpcmf\Service::L('input')->get('table'),
            'field' => \Phpcmf\Service::L('input')->get('field'),
            'tables' => $bm,
            'module' => $module,
            'sql_cache' => \Phpcmf\Service::L('File')->get_sql_cache(),
        ]);

        if (\Phpcmf\Service::L('input')->get('p')) {
            \Phpcmf\Service::V()->assign([
                'page' => 1,
                'menu' =>\Phpcmf\Service::M('auth')->_admin_menu(
                    [
                        '数据结构' => ['db/index', 'fa fa-database'],
                        '执行SQL' => [\Phpcmf\Service::L('Router')->class.'/index{p=1}', 'fa fa-code'],
                    ]
                )
            ]);
        }

        \Phpcmf\Service::V()->display('module_content.html');
    }

    // 联动加载字段
    public function field_index() {

        $table = dr_safe_replace(\Phpcmf\Service::L('input')->get('table'));
        $table = str_replace('_data_[tableid]', '_data_0', $table);
        if (!$table) {
            $this->_json(0, dr_lang('表参数不能为空'));
        } elseif (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $this->_json(0, dr_lang('表[%s]不存在', $table));
        }

        $fields = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$table.'`')->getResultArray();
        if (!$fields) {
            $this->_json(0, dr_lang('表[%s]没有可用字段', $table));
        }

        $msg = '<select name="fd" class="form-control">';
        $field = \Phpcmf\Service::L('input')->get('field');
        foreach ($fields as $t) {
            if ($t['Field'] != 'id') {
                $msg.= '<option value="'.$t['Field'].'" '.($field && $field == $t['Field'] ? ' selected' : '').'>'.$t['Field'].($t['Comment'] ? '（'.$t['Comment'].'）' : '').'</option>';
            }
        }
        $msg.= '</select>';

        $this->_json(1, $msg);
    }

    // 内容维护替换
    public function edit() {
        $bm = \Phpcmf\Service::L('input')->post('bm');
        if (!$bm) {
            $this->_json(0, dr_lang('表名不能为空'));
        }
        $tables = [];
        if (strpos($bm, '[tableid]')) {
            for ($i = 0; $i < 200; $i ++) {
                $table = str_replace('[tableid]', $i, $bm);
                if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                    break;
                }
                $tables[$table] = $this->_get_field($table);
            }
        } else {
            $tables[$bm] = $this->_get_field($bm);
        }

        $t1 = \Phpcmf\Service::L('input')->post('t1');
        $t2 = \Phpcmf\Service::L('input')->post('t2');
        $fd = dr_safe_replace(\Phpcmf\Service::L('input')->post('fd'));

        if (!$fd) {
            $this->_json(0, dr_lang('待替换字段必须填写'));
        } elseif (!$t1) {
            $this->_json(0, dr_lang('被替换内容必须填写'));
        } elseif (!$tables) {
            $this->_json(0, dr_lang('表名称必须填写'));
        } elseif ($fd == 'id') {
            $this->_json(0, dr_lang('ID主键不支持替换'));
        }

        $count = 0;
        $replace = '`'.$fd.'`=REPLACE(`'.$fd.'`, \''.addslashes($t1).'\', \''.addslashes($t2).'\')';

        foreach ($tables as $table => $fields) {

            if (!dr_in_array($fd, $fields)) {
                $this->_json(0, dr_lang('表[%s]字段[%s]不存在', $table, $fd));
            }

            \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace);
            $count = \Phpcmf\Service::M()->db->affectedRows();
        }

        if ($count < 0) {
            $this->_json(0, dr_lang('执行错误'));
        }

        $this->_json(1, dr_lang('本次替换%s条数据', $count));
    }

    // 内容批量修改
    public function all_edit() {
        $bm = \Phpcmf\Service::L('input')->post('bm');
        if (!$bm) {
            $this->_json(0, dr_lang('表名不能为空'));
        }
        $tables = [];
        if (strpos($bm, '[tableid]')) {
            for ($i = 0; $i < 200; $i ++) {
                $table = str_replace('[tableid]', $i, $bm);
                if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                    break;
                }
                $tables[$table] = $this->_get_field($table);
            }
        } else {
            $tables[$bm] = $this->_get_field($bm);
        }

        $t1 = \Phpcmf\Service::L('input')->post('t1');
        $t2 = \Phpcmf\Service::L('input')->post('t2');
        $ms = (int)\Phpcmf\Service::L('input')->post('ms');
        $fd = dr_safe_replace(\Phpcmf\Service::L('input')->post('fd'));

        if (!$fd) {
            $this->_json(0, dr_lang('待修改字段必须填写'));
        } elseif (!$tables) {
            $this->_json(0, dr_lang('表名称必须填写'));
        } elseif ($fd == 'id') {
            $this->_json(0, dr_lang('ID主键不支持替换'));
        }

        $count = 0;

        $where = '';
        if ($t1) {
            // 防范sql注入后期需要加强
            foreach (['outfile', 'dumpfile', '.php', 'union', ';'] as $kw) {
                if (strpos(strtolower($t1), $kw) !== false) {
                    $this->_json(0, dr_lang('存在非法SQL关键词：%s', $kw));
                }
            }
            $where = ' WHERE '.addslashes($t1);
        }

        if ($ms == 1) {
            // 之前
            $replace = '`'.$fd.'`=CONCAT(\''.addslashes($t2).'\', `'.$fd.'`)';
        } elseif ($ms == 2) {
            // 之后
            $replace = '`'.$fd.'`=CONCAT(`'.$fd.'`, \''.addslashes($t2).'\')';
        } else {
            // 替换
            $replace = '`'.$fd.'`=\''.addslashes($t2).'\'';
        }


        foreach ($tables as $table => $fields) {

            if (!dr_in_array($fd, $fields)) {
                $this->_json(0, dr_lang('表[%s]字段[%s]不存在', $table, $fd));
            }

            \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace . $where);
            $count = \Phpcmf\Service::M()->db->affectedRows();
        }

        if ($count < 0) {
            $this->_json(0, dr_lang('执行错误'));
        }

        $this->_json(1, dr_lang('本次替换%s条数据', $count));
    }

    // 执行sql
    public function sql_edit() {

        $msg = '';
        $sqls = trim(\Phpcmf\Service::L('input')->post('sql'));
        $replace = [];
        $replace[0][] = '{dbprefix}';
        $replace[1][] = \Phpcmf\Service::M()->db->DBPrefix;
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', str_replace($replace[0], $replace[1], $sqls))));

        if ($sql_data) {
            foreach($sql_data as $query){
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach($queries as $query) {
                    $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                }
                $sql = trim($ret);
                if (!$sql) {
                    continue;
                }
                $ck = 0;
                foreach (['select', 'create', 'drop', 'alter', 'insert', 'replace', 'update', 'delete'] as $key) {
                    if (strpos(strtolower($sql), $key) === 0) {
                        if (!IS_DEV && in_array($key, ['create', 'drop', 'delete', 'alter'])) {
                            $this->_json(0, dr_lang('为了安全起见，在开发者模式下才能运行%s语句', $key), -1);
                        }
                        $ck = 1;
                        break;
                    }
                }
                if (!$ck) {
                    $this->_json(0, dr_lang('存在不允许执行的SQL语句：%s', dr_strcut($sql, 20)));
                }
                foreach (['outfile', 'dumpfile', '.php', 'union'] as $kw) {
                    if (strpos(strtolower($sql), $kw) !== false) {
                        $this->_json(0, dr_lang('存在非法SQL关键词：%s', $kw));
                    }
                }
                if (stripos($sql, 'select') === 0) {
                    // 查询语句
                    $db = \Phpcmf\Service::M()->db->query($sql);
                    !$db && $this->_json(0, dr_lang('查询出错'));
                    $rt = $db->getResultArray();
                    if ($rt) {
                        $msg.= var_export($rt, true);
                    } else {
                        $rt = \Phpcmf\Service::M()->db->error();
                        \Phpcmf\Service::L('File')->add_sql_cache($sql);
                        $this->_json(0, $rt['message']);
                    }
                } else {
                    // 执行语句
                    $db = \Phpcmf\Service::M()->db->query($sql);
                    if (!$db) {
                        $rt = \Phpcmf\Service::M()->db->error();
                        $this->_json(0, '查询错误：'.$rt['message']);
                    }
                }
            }
            $this->_json(1, $msg ? $msg : dr_lang('执行完成'));
        } else {
            $this->_json(0, dr_lang('不存在的SQL语句'));
        }
    }

    // 获取可用字段
    private function _get_field($bm) {

        $fields = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$bm.'`')->getResultArray();
        if (!$fields) {
            $this->_json(0, dr_lang('表[%s]没有可用字段', $bm));
        }

        $rt = [];
        foreach ($fields as $t) {
            $rt[] = $t['Field'];
        }

        return $rt;
    }

    //////////////////////////////


    public function tag_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 10; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');

        $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    if ($this->module['category'][$i]['child']) {
                        $cat = dr_array2array($cat, explode(',', $this->module['category'][$i]['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $keyword = \Phpcmf\Service::L('input')->get('keyword');
        $keyword && $where.= ' AND keywords=""';
        $url.= '&keyword='.$keyword;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page=1', dr_lang('在使用网络分词接口时可能会很慢'));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            $tag = dr_get_keywords($t['title'].' '.$t['description']);
            if ($tag) {
                \Phpcmf\Service::M()->db->table($table)->where('id', $t['id'])->update(array(
                    'keywords' => $tag
                ));
            }
        }

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1), dr_lang('在使用网络分词接口时可能会很慢'));

    }

    public function tag_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);


        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url('module_content/tag_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_tag.html');
    }

    // 提取描述信息
    public function desc_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');

        $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    if ($this->module['category'][$i]['child']) {
                        $cat = dr_array2array($cat, explode(',', $this->module['category'][$i]['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $nums = max(1, \Phpcmf\Service::L('input')->get('nums'));
        $keyword = \Phpcmf\Service::L('input')->get('keyword');
        $keyword && $where.= ' AND description=""';
        $url.= '&nums='.$nums;
        $url.= '&keyword='.$keyword;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $row) {
            if (!$this->module['field']['content']['ismain']) {
                $row = \Phpcmf\Service::M()->db->table($table.'_data_'.$row['tableid'])->select('content,id')->where('id', $row['id'])->get()->getRowArray();
            }
            if ($row && $row['content']) {
                \Phpcmf\Service::M()->db->table($table)->where('id', $row['id'])->update(array(
                    'description' => dr_get_description(dr_code2html($row['content']), $nums)
                ));
            }
        }

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));

    }
    public function desc_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);


        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url('module_content/desc_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_desc.html');
    }

    public function thumb_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');

        $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    if ($this->module['category'][$i]['child']) {
                        $cat = dr_array2array($cat, explode(',', $this->module['category'][$i]['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $thumb = \Phpcmf\Service::L('input')->get('thumb');
        $thumb && $where.= ' AND thumb=""';
        $url.= '&thumb='.$thumb;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $row) {
            if (!$this->module['field']['content']['ismain']) {
                $row = \Phpcmf\Service::M()->db->table($table.'_data_'.$row['tableid'])->select('content,id')->where('id', $row['id'])->get()->getRowArray();
            }
            if ($row && $row['content'] && preg_match("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|png))\\2/i", htmlspecialchars_decode($row['content']), $m)) {
                \Phpcmf\Service::M()->db->table($table)->where('id', (int)$row['id'])->update(array(
                    'thumb' => str_replace(['"', '\''], '', $m[3])
                ));
            }
        }

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function thumb_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);


        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url('module_content/thumb_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_thumb.html');
    }
    public function del() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = [];
        $catid = \Phpcmf\Service::L('input')->get('catid');

        $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    if ($this->module['category'][$i]['child']) {
                        $cat = dr_array2array($cat, explode(',', $this->module['category'][$i]['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where[] = 'catid IN ('.implode(',', $cat).')';
        }

        $author = \Phpcmf\Service::L('input')->get('author');
        if ($author) {
            $where[] = 'author="'.dr_safe_replace($author).'"';
            $url.= '&author='.$author;
        }

        $uid = (int)\Phpcmf\Service::L('input')->get('uid');
        if ($uid) {
            $where[] = 'uid='.$uid;
            $url.= '&uid='.$uid;
        }

        $id1 = (int)\Phpcmf\Service::L('input')->get('id1');
        $id2 = (int)\Phpcmf\Service::L('input')->get('id2');
        if ($id1 || $id2) {
            if (!$id2) {
                $where[] = 'id>'.$id1;
            } else {
                $where[] = '`id` BETWEEN '.$id1.' AND '.$id2;
            }
            $url.= '&id1='.$id1.'&id2='.$id2;
        }

        $sql = \Phpcmf\Service::L('input')->get('sql', true);
        if ($sql) {
            // 防范sql注入后期需要加强
            foreach (['outfile', 'dumpfile', '.php', 'union', ';'] as $kw) {
                if (strpos(strtolower($sql), $kw) !== false) {
                    $this->_html_msg(0, dr_lang('存在非法SQL关键词：%s', $kw));
                }
            }
            $where[] = addslashes($sql);
            $url.= '&sql='.$sql;
        }

        if (!$where) {
            $this->_html_msg(0, dr_lang('没有设置条件'));
        }

        $where = implode(' AND ', $where);
        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容'));
            }

            $this->_html_msg(1, dr_lang('正在删除中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('删除完成'));
        }

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $row) {
            $this->content_model->delete_content($row['id']);
        }

        $this->_html_msg(1, dr_lang('正在删除中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function del_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url('module_content/del', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_del.html');
    }

    public function cat_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $toid = (int)\Phpcmf\Service::L('input')->get('toid');
        if (!$toid) {
            $this->_html_msg(0, dr_lang('目标栏目必须选择'));
        }

        $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);
        $url.= '&toid='.$toid;
        $where = '';

        // 获取生成栏目
        $catid = \Phpcmf\Service::L('input')->get('catid');
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    if ($this->module['category'][$i]['child']) {
                        $cat = dr_array2array($cat, explode(',', $this->module['category'][$i]['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where = 'catid IN ('.implode(',', $cat).')';
        }

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table($table.'_index')->where($where)->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $db = \Phpcmf\Service::M()->db->table($table.'_index');
        $where && $db->where($where);
        $data = $db->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = $row['id'];
        }

        $this->content_model->move_category($ids, $toid);

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function cat_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url('module_content/cat_edit', ['mid' => $mid]),
            'select_post' => \Phpcmf\Service::L('Tree')->select_category(
                $this->module['category'],
                0,
                'name=\'toid\'',
                dr_lang('选择栏目'), 1, 1
            ),
        ]);
        \Phpcmf\Service::V()->display('module_content_cat.html');
    }

    // 更新url地址
    public function url_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 500; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($this->content_model->mytable.'_index')->where('status', 9)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $url = dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

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
            $this->content_model->update_url($t, $url);
        }

        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            dr_url('module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid,'total' => $total, 'page' => $page + 1])
        );

    }
}
