<?php namespace Phpcmf\Home;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 用于前端模块内容显示
class Module extends \Phpcmf\Common {

    public $module; // 模块信息
    public $is_prev_next_page = 1; // 启用内容页上下页计算

    // 模块首页
    public function _Index($html = 0) {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        // 初始化模块
        $this->_module_init();

        // 执行自定义方法
        $this->content_model->_call_index();

        // 共享模块时禁止访问首页
        if ($this->module['share']) {
            exit($this->goto_404_page(dr_lang('共享模块没有首页功能')));
        }

        if ($this->module['setting']['search']['indexsync']) {
            // 集成搜索
            return $this->_Search(0);
        } else {
            // 判断URL重复问题
            if (!$html) {
                \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix(MODULE_URL, $this->module['dirname']));
            }

            // 模板变量
            \Phpcmf\Service::V()->assign([
                'indexm' => 1,
                'pageid' => max(1, max(1, (int)\Phpcmf\Service::L('input')->get('page'))),
                'fix_html_now_url' => defined('IS_MOBILE') && IS_MOBILE ? $this->module['murl'] : $this->module['url'],
            ]);
            \Phpcmf\Service::V()->assign($this->content_model->_format_home_seo($this->module));
            \Phpcmf\Hooks::trigger('module_index');

            // 系统开启静态首页
            if (!defined('SC_HTML_FILE') && $this->module['setting']['module_index_html']) {

                ob_start();
                \Phpcmf\Service::V()->display('index.html');
                $html = ob_get_clean();

                if ($this->module['domain']) {
                    // 绑定域名时
                    $file = 'index.html';
                } else {
                    $file = ltrim(\Phpcmf\Service::L('Router')->remove_domain(MODULE_URL), '/'); // 从地址中获取要生成的文件名;
                }

                if (!$file) {
                    // 静态文件失败就输出当前页面
                    echo $html;exit;
                }

                if (IS_CLIENT) {
                    // 终端下不生成
                } elseif (defined('IS_MOBILE') && IS_MOBILE) {
                    // 移动端访问
                    if (SITE_IS_MOBILE || $this->module['mobile_domain']) {
                        file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], SITE_MOBILE_DIR.'/'.$file), $html);
                    }
                } else {
                    // 电脑端访问
                    file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], $file), $html);
                }
                echo $html;
            } else {
                \Phpcmf\Service::V()->display('index.html');
            }
        }
    }

    // 模块搜索
    protected function _Search($_catid = 0) {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        // 模型类
        $search = \Phpcmf\Service::M('Search', $this->module['dirname'])->init($this->module['dirname']);

        // 搜索参数
        list($catid, $get) = $search->get_param($this->module);
        !$catid && $_catid && $catid = $_catid;
        $catid = intval($catid);

        // 非http请求之下
        if (!IS_API_HTTP) {
            if (!isset($this->module['setting']['search']['use']) || !$this->module['setting']['search']['use']) {
                $this->_msg(0, dr_lang('此模块已经关闭了搜索功能'));
            } elseif (IS_USE_MEMBER && \Phpcmf\Service::L('member_auth', 'member')->module_auth($this->module['dirname'], 'search', $this->member)) {
                $this->_msg(0, dr_lang('您的用户组无权限搜索'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
            } elseif ($get['keyword'] && $this->module['setting']['search']['length']
                && dr_strlen($get['keyword']) < (int)$this->module['setting']['search']['length']) {
                $this->_msg(0, dr_lang('关键字不得少于系统规定的长度'));
            } elseif ($get['keyword'] && $this->module['setting']['search']['maxlength']
                && dr_strlen($get['keyword']) > (int)$this->module['setting']['search']['maxlength']) {
                $this->_msg(0, dr_lang('关键字不得大于系统规定的长度'));
            }
        }

        // 搜索数据
        $data = $search->get_data();
        if (isset($data['code']) && $data['code'] == 0 && $data['msg']) {
            $this->_msg(0, $data['msg']);
        }
        unset($data['params']['page']);

        // 格式化数据
        $data = $this->content_model->_call_search($data);

        // 挂钩点 搜索完成之后
        $rt2 = \Phpcmf\Hooks::trigger_callback('module_search_data', $data);
        if ($rt2 && isset($rt2['code']) && $rt2['code']) {
            $data = $rt2['data'];
        }

        // 获取同级栏目及父级栏目
        list($parent, $related) = dr_related_cat(
            !$this->module['share'] ? $this->module['category'] : \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category'),
            $catid
        );

        // 获取搜索总量
        $sototal = intval($data['contentid']);

        // 存储缓存以便标签中使用
        if ($data['id'] && $sototal) {
            \Phpcmf\Service::L('cache')->set_data('module-search-'.$this->module['dirname'].'-'.$data['id'], $data, 3600);
        }

        $list = [];
        // 移动端请求时
        if (IS_API_HTTP && $data['id']) {
            $rt = \Phpcmf\Service::V()->list_tag('search module='.$this->module['dirname']
                .' id='.$data['id'].' total='.$sototal
                .' order='.$data['params']['order'].' catid='.$catid
                .(isset($_GET['more']) && $_GET['more'] ? ' more=1' : '')
                .' page=1 pagesize='.intval(\Phpcmf\Service::L('input')->request('pagesize'))
                .' urlrule=test');
            $list = $rt['return'];
        }

        // 栏目格式化
        $cat = $catid && $this->module['category'][$catid] ? $this->module['category'][$catid] : [];
        $cat && $cat['url'] = dr_url_prefix($cat['url'], MOD_DIR);
        $top = $cat;
        if ($catid && $cat['topid']) {
            $top = $this->module['category'][$cat['topid']];
            $cat['url'] = dr_url_prefix($cat['url'], MOD_DIR);
        }

        // 分页地址
        $urlrule = \Phpcmf\Service::L('Router')->search_url($data['params'], 'page', '{page}');

        // 识别自定义地址，301定向
        if (dr_is_sys_301() && !IS_API_HTTP && !isset($_GET['ajax_page'])
            && strpos(FC_NOW_URL, 'index.php') !== false && strpos($urlrule, 'index.php') === false) {
            $get['page'] > 1 && $data['params']['page'] = $get['page'];
            dr_redirect(\Phpcmf\Service::L('Router')->search_url($data['params']), 'auto', 301);exit;
        }

        \Phpcmf\Service::V()->assign($this->content_model->_format_search_seo($this->module, $catid, $data['params'], $get['page']));
        \Phpcmf\Service::V()->assign([
            'cat' => $cat,
            'top' => $top,
            'get' => $get,
            'list' => $list,
            'catid' => $catid,
            'parent' => $parent,
            'pageid' => max(1, $get['page']),
            'params' => dr_htmlspecialchars($data['params']),
            'keyword' => dr_htmlspecialchars($data['keyword']),
            'related' => $related,
            'urlrule' => $urlrule,
            'sototal' => $sototal,
            'searchid' => $data['id'],
            'search_id' => $data['id'],
            'search_sql' => $data['sql'],
            'is_search_page' => 1,
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);

        $tpl = '';
        if (isset($_GET['ajax_page']) && $_GET['ajax_page']) {
            $tpl = dr_safe_filename($_GET['ajax_page']);
            if (!is_file(\Phpcmf\Service::V()->get_dir().$tpl)) {
                log_message('debug', '搜索模板参数ajax_page值对应的模板（'.\Phpcmf\Service::V()->get_dir().$tpl.'）不存在，将加载默认的搜索模板');
                $tpl = ''; // 自定义模板不存在
            }
        } elseif (isset($this->module['setting']['search']['tpl_field'])
            && $this->module['setting']['search']['tpl_field']
            && isset($get[$this->module['setting']['search']['tpl_field']])
            && $get[$this->module['setting']['search']['tpl_field']]
        ) {
            $tpl = dr_safe_filename('search_'.$get[$this->module['setting']['search']['tpl_field']].'.html');
            if (!is_file(\Phpcmf\Service::V()->get_dir().$tpl)) {
                log_message('debug', '搜索模板字段'.$this->module['setting']['search']['tpl_field'].'参数值对应的模板（'.\Phpcmf\Service::V()->get_dir().$tpl.'）不存在，将加载默认的搜索模板');
                $tpl = ''; // 自定义模板不存在
            }
        }
        if (!$tpl) {
            $tpl = $catid && $this->module['category'][$catid]['setting']['template']['search'] ? $this->module['category'][$catid]['setting']['template']['search'] : 'search.html';
        }

        \Phpcmf\Service::V()->display($tpl);
    }

    // 模块栏目页
    public function _Category($catid = 0, $catdir = null, $page = 1, $rt = 0) {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        if ($catid) {
            $category = $this->module['category'][$catid];
            if (!$category) {
                $this->goto_404_page(dr_lang('模块【%s】栏目（%s）不存在', $this->module['dirname'], $catid));
                return;
            }
        } elseif ($catdir) {
            $catid = intval($this->module['category_dir'][$catdir]);
            $category = $this->module['category'][$catid];
            if (!$category) {
                // 无法通过目录找到栏目时，尝试多及目录
                foreach ($this->module['category'] as $t) {
                    if ($t['setting']['urlrule']) {
                        $rule = \Phpcmf\Service::L('cache')->get('urlrule', $t['setting']['urlrule']);
                        $rule['value']['catjoin'] = '/';
                        if ($rule['value']['catjoin'] && strpos($catdir, $rule['value']['catjoin'])) {
                            $catdir = trim(strchr($catdir, $rule['value']['catjoin']), $rule['value']['catjoin']);
                            if (isset($this->module['category_dir'][$catdir])) {
                                $catid = $this->module['category_dir'][$catdir];
                                $category = $this->module['category'][$catid];
                                break;
                            }
                        }
                    }
                }
                // 返回无法找到栏目
                if (!$category) {
                    $this->goto_404_page(dr_lang('模块【%s】栏目（%s）不存在', $this->module['dirname'], $catdir));
                    return;
                }
            }
        } else {
            $this->goto_404_page(dr_lang('模块【%s】栏目不存在', $this->module['dirname']));
            return;
        }

        // 格式化栏目数据
        $category = $this->content_model->_call_category($category);

        // 挂钩点 格式化栏目数据
        $rt2 = \Phpcmf\Hooks::trigger_callback('module_category_data', $category);
        if ($rt2 && isset($rt2['code']) && $rt2['code']) {
            $category = $rt2['data'];
        }

        // 判断是否外链
        if ($category['tid'] == 2) {
            dr_redirect(dr_url_prefix($category['url'], $this->module['dirname'], SITE_ID), 'refresh');exit;
        }

        // 验证是否存在子栏目，是否将下级第一个栏目作为当前页
        if ($category['tid'] != 2 && $category['child'] && $category['setting']['getchild']) {
            $temp = explode(',', $category['childids']);
            if ($temp) {
                foreach ($temp as $i) {
                    if ($i != $catid && $this->module['category'][$i]['show']
                        && $this->module['category'][$i]['tid'] != 2
                        && !$this->module['category'][$i]['setting']['getchild']) {
                        $catid = $i;
                        $category = $this->module['category'][$i];
                        // 初始化模块
                        $this->_module_init($category['mid'] ? $category['mid'] : 'share');
                        break;
                    }
                }
            }
        }

        // 跳转到搜索页面
        if (!defined('SC_HTML_FILE')
            && isset($this->module['setting']['search']['catsync'])
            && $this->module['setting']['search']['catsync']
            && $category['tid'] == 1) {
            $_GET = [
                'catid' => $catid
            ];
            return $this->_Search($catid);
        }

        // 无权限访问栏目
        if (IS_USE_MEMBER && !defined('SC_HTML_FILE')) {
            if (($this->module['share']) && $category['tid'] == 0) {
                // 识别栏目单网页
                if (!\Phpcmf\Service::L('member_auth', 'member')->category_auth($this->module, $catid, 'show', $this->member)) {
                    $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
                    return;
                }
            } else {
                if (!\Phpcmf\Service::L('member_auth', 'member')->category_auth($this->module, $catid, 'show', $this->member)) {
                    $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
                    return;
                }
            }
        }

        $category['url'] = dr_url_prefix($category['url'], $this->module['dirname']);
        $top = $category;
        if ($catid && $category['topid']) {
            $top = $this->module['category'][$category['topid']];
            $top['url'] = dr_url_prefix($top['url'], $this->module['dirname']);
        }

        // 判断内容唯一性
        !$rt && \Phpcmf\Service::L('Router')->is_redirect_url($category['url']);

        // 获取同级栏目及父级栏目
        list($parent, $related) = dr_related_cat(
            !$this->module['share'] ? $this->module['category'] : \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category'),
            $catid
        );

        // 传入模板
        \Phpcmf\Service::V()->assign($this->content_model->_format_category_seo($this->module, $catid, $page));
        \Phpcmf\Service::V()->assign(array(
            'id' => $catid,
            'cat' => $category,
            'top' => $top,
            'catid' => $catid,
            'params' => ['catid' => $catid],
            'pageid' => max(1, $page),
            'parent' => $parent,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::L('Router')->category_url($this->module, $category, '[page]'),
            'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(\Phpcmf\Service::L('Router')->category_url($this->module, $category, $page), $this->module['dirname'], SITE_ID, \Phpcmf\Service::IS_MOBILE_TPL()) : '', // 修复静态下的当前url变量
        ));

        // 识别栏目单网页模板
        if (($this->module['share'] || (isset($this->module['config']['scategory']) && $this->module['config']['scategory'])) && $category['tid'] == 0) {
            \Phpcmf\Service::V()->assign($category);
            $tpl = !$category['setting']['template']['page'] ? 'page.html' : $category['setting']['template']['page'];
        } else {
            \Phpcmf\Service::V()->module($this->module['dirname']);
            if ($category['child']) {
                $tpl = $category['setting']['template']['category'] ? $category['setting']['template']['category'] : 'category.html';
            } else {
                $tpl = $category['setting']['template']['list'] ? $category['setting']['template']['list'] : 'list.html';
            }
        }

        // 输出方式
        if (!$rt) {
            \Phpcmf\Service::V()->display($tpl);
        } else {
            $category['tpl'] = $tpl;
            return $category;
        }
    }

    // 模块内容页
    // $param 自定义字段检索
    public function _Show($id = 0, $param = [], $page = 1, $rt = 0) {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        // 通过自定义字段查找id
        $is_id = 1;
        if (!$id && isset($param['field']) && $this->module['field'][$param['field']]['ismain']) {
            $id = md5($param['field'].$param['value']);
            $is_id = 0;
        }

        $name = 'module_'.$this->module['dirname'].'_show_id_'.$id.($this->is_mobile ? '_m' : '').($page > 1 ? '_p'.$page : '');
        $data = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$data) {
            $data = $this->content_model->get_data($is_id ? $id : 0, 0, $param);
            if (!$data) {
                $this->goto_404_page(dr_lang('%s内容(#%s)不存在', $this->module['name'], $id));
                return;
            }

            // 检测转向字段
            if (!$rt) {
                foreach ($this->module['field'] as $t) {
                    if ($t['fieldtype'] == 'Redirect' && $data[$t['fieldname']]) {
                        // 存在转向字段时的情况
                        \Phpcmf\Service::M()->db->table(dr_module_table_prefix($this->module['dirname']))->where('id', $id)->set('hits', 'hits+1', FALSE)->update();
                        \Phpcmf\Service::V()->assign('goto_url', $data[$t['fieldname']]);
                        \Phpcmf\Service::V()->display('goto_url');
                        return $data;
                    }
                }
            }

            // 格式化字段
            $data = $this->_Show_Data($data, $page);

            // 缓存结果 
            if ($data['uid'] != $this->uid && SYS_CACHE) {
                if ($this->member && $this->member['is_admin']) {
                    // 管理员时不进行缓存
                    \Phpcmf\Service::L('cache')->init()->delete($name);
                } else {
                    \Phpcmf\Service::L('cache')->set_data($name, $data, SYS_CACHE_SHOW * 3600);
                    if (!$is_id) {
                        // 表示自定义查询，再缓存一次ID
                        \Phpcmf\Service::L('cache')->set_data(str_replace($id, $data['id'], $name), $data, SYS_CACHE_SHOW * 3600);
                    }
                }
            }
        }

        /*
        if ($data['status'] == 10 && !($this->uid == $data['uid'] || $this->member['is_admin'])) {
            $this->goto_404_page(dr_lang('内容被删除，暂时无法访问'));
            return $data;
        }*/

        $catid = $data['catid'];

        if ($this->is_hcategory) {
            $parent = $related = [];
            $rt2 = $this->content_model->_hcategory_member_show_auth();
            if (!$rt2['code']) {
                $this->_msg(0, $rt2['msg'], $rt2['data']);
            }
        } else {
            // 无权限访问栏目内容
            if (IS_USE_MEMBER && !defined('SC_HTML_FILE')
                && !\Phpcmf\Service::L('member_auth', 'member')->category_auth($this->module, $catid, 'show', $this->member)) {
                $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid  ? '' : dr_member_url('login/index'));
                return $data;
            }
            // 判断是否同步栏目
            if ($data['link_id'] && $data['link_id'] > 0) {
                \Phpcmf\Service::V()->assign('gotu_url', dr_url_prefix($data['url'], $this->module['dirname']));
                \Phpcmf\Service::V()->display('go.html', 'admin');
                return $data;
            }
            // 获取同级栏目及父级栏目
            list($parent, $related) = dr_related_cat(
                !$this->module['share'] ? $this->module['category'] : \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category'),
                $data['catid']
            );
        }

        // 判断分页
        if ($page && $data['content_page'] && !$data['content_page'][$page]) {
            $this->goto_404_page(dr_lang('该分页不存在'));
            return $data;
        }

        // 判断内容唯一性
        !$rt && \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix($data['url'], $this->module['dirname']));

        $cat = $this->module['category'][$catid];
        $cat['url'] = dr_url_prefix($cat['url'], $this->module['dirname']);
        $top = $cat;
        if ($catid && $cat['topid']) {
            $top = $this->module['category'][$cat['topid']];
            $top['url'] = dr_url_prefix($top['url'], $this->module['dirname']);
        }

        // 传入模板
        \Phpcmf\Service::V()->assign($data);
        \Phpcmf\Service::V()->assign($this->content_model->_format_show_seo($this->module, $data, $page));
        \Phpcmf\Service::V()->assign([
            'cat' => $cat,
            'top' => $top,
            'pageid' => max(1, $page),
            'params' => ['catid' => $catid],
            'parent' => $parent,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::L('Router')->show_url($this->module, $data, '[page]'),
            'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(\Phpcmf\Service::L('Router')->show_url($this->module, $data, $page), $this->module['dirname'], SITE_ID, \Phpcmf\Service::IS_MOBILE_TPL()) : '', // 修复静态下的当前url变量
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);
        !$rt && (\Phpcmf\Service::V()->display(isset($data['template']) && strpos($data['template'], '.html') !== FALSE && is_file(\Phpcmf\Service::V()->get_dir().$data['template']) ? $data['template'] : ($this->module['category'][$data['catid']]['setting']['template']['show'] ? $this->module['category'][$data['catid']]['setting']['template']['show'] : 'show.html')));
        return $data;
    }

    // 模块草稿、审核、定时、内容页
    protected function _MyShow($type, $id = 0, $page = 1) {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        // 标记字符
        define('MODULE_MYSHOW', $type);

        // 按类型加载内容
        switch($type) {

            case 'time':
                $row = \Phpcmf\Service::M()->table(dr_module_table_prefix($this->module['dirname']).'_time')->get($id);
                $data = dr_string2array($row['content']);
                if (!$data) {
                    $this->goto_404_page(dr_lang('定时内容#%s不存在', $id));
                } elseif (($this->uid != $data['uid'] && !$this->member['is_admin'])) {
                    $this->goto_404_page(dr_lang('定时内容只能自己访问'));
                }
                break;

            case 'recycle':
                $row = \Phpcmf\Service::M()->table(dr_module_table_prefix($this->module['dirname']).'_recycle')->get($id);
                $row = dr_string2array($row['content']);
                if (!$row) {
                    $this->goto_404_page(dr_lang('回收站内容#%s不存在', $id));
                } elseif (!$row[SITE_ID.'_'.$this->module['dirname']]) {
                    $this->goto_404_page(dr_lang('回收站内容#%s格式不规范', $id));
                } elseif (!$this->member['is_admin']) {
                    $this->goto_404_page(dr_lang('无权限访问回收站的内容'));
                }
                $data = $row[SITE_ID.'_'.$this->module['dirname']];
                if (isset($row[SITE_ID.'_'.$this->module['dirname'].'_data_'.intval($data['tableid'])])
                    && $row[SITE_ID.'_'.$this->module['dirname'].'_data_'.intval($data['tableid'])]) {
                    $data = array_merge($data, $row[SITE_ID.'_'.$this->module['dirname'].'_data_'.intval($data['tableid'])]);
                }
                break;

            case 'verify':
                $row = \Phpcmf\Service::M()->table(dr_module_table_prefix($this->module['dirname']).'_verify')->get($id);
                $data = dr_string2array($row['content']);
                if (!$data) {
                    $this->goto_404_page(dr_lang('审核内容#%s不存在', $id));
                } elseif (!$this->uid) {
                    $this->goto_404_page(dr_lang('需要登录之后才能查看'));
                } elseif (($this->uid != $data['uid'] && !$this->member['is_admin'])) {
                    $this->goto_404_page(dr_lang('无权限访问审核中的内容'));
                }
                break;

            case 'draft':
                $row = \Phpcmf\Service::M()->table(dr_module_table_prefix($this->module['dirname']).'_draft')->get($id);
                $data = dr_string2array($row['content']);
                if (!$data) {
                    $this->goto_404_page( dr_lang('草稿内容#%s不存在', $id));
                } elseif (!$this->uid) {
                    $this->goto_404_page(dr_lang('需要登录之后才能查看'));
                } elseif (($this->uid != $data['uid'] && !$this->member['is_admin'])) {
                    $this->goto_404_page(dr_lang('无权限访问别人的草稿箱内容'));
                }
                break;

            default:
                $this->goto_404_page(dr_lang('未定义的操作'));exit;
        }

        $data['id'] = 0;

        // 格式化字段
        $data = $this->_Show_Data($data, $page);

        // 判断分页
        if ($page && $data['content_page'] && !$data['content_page'][$page]) {
            $this->goto_404_page(dr_lang('该分页不存在'));
            return;
        }

        // 获取同级栏目及父级栏目
        list($parent, $related) = dr_related_cat(
            !$this->module['share'] ? $this->module['category'] : \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category'),
            $data['catid']
        );

        \Phpcmf\Service::V()->assign($data);
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->show($this->module, $data, $page));
        \Phpcmf\Service::V()->assign([
            'cat' => $this->module['category'][$data['catid']],
            'pageid' => max(1, $page),
            'params' => ['catid' => $data['catid']],
            'parent' => $parent,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::L('Router')->show_url($this->module, $data, '[page]'),
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);
        \Phpcmf\Service::V()->display(is_file(dr_tpl_path().'show_'.$type.'.html') ? 'show_'.$type.'.html' : 'show.html');
        return $data;
    }

    // 内容页面的字段格式化处理
    protected function _Show_Data($data, $page) {

        // 处理关键字标签
        $data['tag'] = $data['keywords'] = isset($data['keywords']) && $data['keywords'] ? trim((string)$data['keywords']) : '';
        $data['kws'] = [];
        $data['tags'] = [];
        if ($data['keywords']) {
            $tag = explode(',', $data['keywords']);
            $obj = false;
            if (dr_is_app('tag')) {
                // 是否安装tag
                $obj = \Phpcmf\Service::M('tag', 'tag');
                if (!method_exists($obj, 'get_tag_url')) {
                    $obj = false;
                }
            }
            foreach ($tag as $t) {
                $t = trim($t);
                if ($t) {
                    // 读缓存
                    if ($obj) {
                        $url = $obj->get_tag_url($t);
                        if ($url) {
                            $data['tags'][$t] = $url;
                        }
                    }
                    $data['kws'][$t] = \Phpcmf\Service::L('router')->search_url([], 'keyword', $t, MOD_DIR);
                }
            }
        }

        // 格式化输出自定义字段
        $fields = $this->module['category_data_field'] ? array_merge($this->module['field'], $this->module['category_data_field']) : $this->module['field'];
        $fields['inputtime'] = ['fieldtype' => 'Date'];
        $fields['updatetime'] = ['fieldtype' => 'Date'];

        // 格式化字段
        $data = \Phpcmf\Service::L('Field')->app($this->module['dirname'])->format_value($fields, $data, $page);

        // 挂钩点 内容读取之后
        $rt2 = \Phpcmf\Hooks::trigger_callback('module_show_data', $data);
        if ($rt2 && isset($rt2['code']) && $rt2['code']) {
            $data = $rt2['data'];
        }

        // 模块的回调处理
        $data = $this->content_model->_call_show($data);

        if ($this->is_prev_next_page) {
            // 关闭插件嵌入
            $is_fstatus = dr_is_app('fstatus') && isset($this->module['field']['fstatus']) && $this->module['field']['fstatus']['ismain'] ? 1 : 0;

            // 上一篇文章
            $builder = \Phpcmf\Service::M()->db->table($this->content_model->mytable);
            $builder->where('catid', (int)$data['catid']);//->where('status', 9)
            $is_fstatus && $builder->where('fstatus', 1);
            $builder->where('id<', (int)$data['id'])->orderBy('id desc');
            $data['prev_page'] = $builder->limit(1)->get()->getRowArray();
            if (isset($data['prev_page']['url']) && $data['prev_page']['url']) {
                $data['prev_page']['url'] = dr_url_prefix($data['prev_page']['url'], $this->module['dirname'], SITE_ID, $this->is_mobile);
            }

            // 下一篇文章
            $builder = \Phpcmf\Service::M()->db->table($this->content_model->mytable);
            $builder->where('catid', (int)$data['catid']);//->where('status', 9)
            $is_fstatus && $builder->where('fstatus', 1);
            $builder->where('id>', (int)$data['id'])->orderBy('id asc');
            $data['next_page'] = $builder->limit(1)->get()->getRowArray();
            if (isset($data['next_page']['url']) && $data['next_page']['url']) {
                $data['next_page']['url'] = dr_url_prefix($data['next_page']['url'], $this->module['dirname'], SITE_ID, $this->is_mobile);
            }
        }

        return $data;
    }

    // 前端模块回调处理类
    protected function _Call_Show($data) {
        return $data;
    }

    // 模块打赏
    protected function _Donation($id = 0, $rt = 0) {
        // 从框架中移除打赏插件的支持代码
        $this->goto_404_page('请升级打赏插件，此功能不再支持');
    }


    //==================生成静态部分 - 单个文件生成（继承，用于增加修改时实时生成）=========================


    // 生成栏目静态页
    protected function _Category_Html_File() {
        if (dr_is_app('chtml')) {
            \Phpcmf\Service::L('html', 'chtml')->_Category_Html_File($this, APP_DIR);
        } else {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }
    }

    // 生成内容静态单页
    protected function _Show_Html_File() {
        if (dr_is_app('chtml')) {
            \Phpcmf\Service::L('html', 'chtml')->_Show_Html_File($this, APP_DIR);
        } else {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }
    }


    //==================生成静态部分 - 后台操作Ajax生成执行=========================


    // 生成首页静态选项列表
    protected function _Index_Html() {
        if (dr_is_app('chtml')) {
            \Phpcmf\Service::L('html', 'chtml')->_Index_Html($this);
        } else {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }
    }

    // 生成内容静态选项列表
    protected function _Show_Html() {
        if (dr_is_app('chtml')) {
            \Phpcmf\Service::L('html', 'chtml')->_Show_Html($this, APP_DIR);
        } else {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }
    }

    // 生成内容静态选项列表
    protected function _Category_Html() {
        if (dr_is_app('chtml')) {
            \Phpcmf\Service::L('html', 'chtml')->_Category_Html($this, APP_DIR);
        } else {
            $this->_json(0, '没有安装官方版【静态生成】插件');
        }
    }

}
