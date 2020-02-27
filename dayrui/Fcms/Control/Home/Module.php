<?php namespace Phpcmf\Home;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 用于前端模块内容显示
class Module extends \Phpcmf\Common
{
    public $module; // 模块信息

    // 模块首页
    protected function _Index($html = 0) {

        // 初始化模块
        $this->_module_init();

        // 共享模块时禁止访问首页
        if ($this->module['share']) {
            exit($this->goto_404_page(dr_lang('共享模块没有首页功能')));
        }

        if ($this->module['setting']['search']['indexsync']) {
            // 集成搜索
            return $this->_Search(0);
        } else {
            // 判断URL重复问题
            !$html && \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix(MODULE_URL, $this->module['dirname']));

            // 模板变量
            \Phpcmf\Service::V()->assign([
                'indexm' => 1,
                'markid' => 'module-'.$this->module['dirname'],
            ]);
            \Phpcmf\Service::V()->assign($this->content_model->_format_home_seo($this->module));

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
                    echo $html;exit;
                }

                if (\Phpcmf\Service::IS_PC()) {
                    // 电脑端访问
                    file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], $file), $html);
                    // 生成移动端
                    if (SITE_IS_MOBILE_HTML) {
                        ob_start();
                        \Phpcmf\Service::V()->init('mobile');
                        \Phpcmf\Service::V()->assign([
                            'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(MODULE_URL, $this->module['dirname'], SITE_ID, 1) : '', // 修复静态下的当前url变量
                        ]);
                        \Phpcmf\Service::V()->display('index.html');
                        file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], 'mobile/'.$file), ob_get_clean());
                    }
                } else {
                    // 移动端访问
                    if (SITE_IS_MOBILE_HTML) {
                        file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], 'mobile/'.$file), $html);
                    }
                    // 生成电脑端
                    ob_start();
                    \Phpcmf\Service::V()->init('pc');
                    \Phpcmf\Service::V()->assign([
                        'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(MODULE_URL, $this->module['dirname'], SITE_ID, 0) : '', // 修复静态下的当前url变量
                    ]);
                    \Phpcmf\Service::V()->display('index.html');
                    file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname'], $file), ob_get_clean());
                }

                echo $html;
            } else {

                // 启用页面缓存
                if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
                    $this->cachePage(SYS_CACHE_PAGE * 3600);
                }

                \Phpcmf\Service::V()->display('index.html');
            }
        }


    }

    // 模块打赏
    protected function _Donation($id = 0, $rt = 0) {

        // 启用页面缓存
        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            $this->cachePage(SYS_CACHE_PAGE * 3600);
        }

        if (!dr_is_app_dir('shang')) {
            $this->goto_404_page('当前模块没有安装打赏应用');
        }

        !$id && $id = intval(\Phpcmf\Service::L('input')->get('id'));

        // 初始化模块
        $this->_module_init();
        define('SC_HTML_FILE', 1); // 不跳转
        $data = $this->_Show($id, [], 1, 1);

        if ($rt) {
            return $data;
        }

        // 验证
        if (!in_array('donation', \Phpcmf\Service::M('table')->get_cache_field(SITE_ID.'_'.MOD_DIR)) ) {
            $this->goto_404_page('当前模块没有安装打赏应用');exit;
        }

        \Phpcmf\Service::V()->assign('meta_title', dr_lang('打赏作者').SITE_SEOJOIN.\Phpcmf\Service::V()->get_value('meta_title'));
        \Phpcmf\Service::V()->display('donation.html');
    }

    // 模块栏目页
    protected function _Category($catid = 0, $catdir = null, $page = 1) {

        // 启用页面缓存
        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            $this->cachePage(SYS_CACHE_PAGE * 3600);
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

        // 判断是否外链
        if ($category['tid'] == 2) {
            dr_redirect(dr_url_prefix($category['url'], $this->module['dirname']), 'refresh');exit;
        }

        // 单页验证是否存在子栏目，是否将下级第一个单页作为当前页
        if ($category['child'] && $category['setting']['getchild']) {
            $temp = explode(',', $category['childids']);
            if ($temp) {
                foreach ($temp as $i) {
                    if ($i != $catid && $this->module['category'][$i]['show'] && !$this->module['category'][$i]['child']) {
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
        if (($this->module['share']) && $category['tid'] == 0) {
            // 识别栏目单网页
            if (!dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID]['share']['category'][$catid]['show'])) {
                $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
                return;
            }
        } else {
            if (!dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['category'][$catid]['show'])) {
                $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
                return;
            }
        }

        // 判断内容唯一性
        \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix($category['url'], $this->module['dirname']));

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
            'top' => $catid && $category['topid'] ? $this->module['category'][$category['topid']] : $category,
            'page' => $page,
            'catid' => $catid,
            'params' => ['catid' => $catid],
            'markid' => 'module-'.$this->module['dirname'].'-'.$catid,
            'parent' => $parent,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::L('Router')->category_url($this->module, $category, '[page]'),
            'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(\Phpcmf\Service::L('Router')->category_url($this->module, $category, $page), $this->module['dirname'], SITE_ID, \Phpcmf\Service::V()->_is_mobile == 'mobile') : '', // 修复静态下的当前url变量
        ));

        // 识别栏目单网页模板
        if (($this->module['share'] || (isset($this->module['config']['scategory']) && $this->module['config']['scategory'])) && $category['tid'] == 0) {
            \Phpcmf\Service::V()->assign($category);
            \Phpcmf\Service::V()->assign(array(
                'pageid' => $catid,
            ));
            $tpl = !$category['setting']['template']['page'] ? 'page.html' : $category['setting']['template']['page'];
        } else {
            \Phpcmf\Service::V()->module($this->module['dirname']);
            $tpl = $category['child'] ? $category['setting']['template']['category'] : $category['setting']['template']['list'];
        }
        \Phpcmf\Service::V()->display($tpl);
    }

    // 模块搜索
    protected function _Search($_catid = 0) {

        // 启用页面缓存
        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            $this->cachePage(SYS_CACHE_PAGE * 3600);
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
                exit($this->_msg(0, dr_lang('此模块已经关闭了搜索功能')));
            } elseif (!dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['home'])) {
                exit($this->_msg(0, dr_lang('您的用户组无权限搜索'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index')));
            } elseif ($get['keyword'] && strlen($get['keyword']) < (int)$this->module['setting']['search']['length']) {
                exit($this->_msg(0, dr_lang('关键字不得少于系统规定的长度')));
            } elseif (strlen($get['keyword']) > 100) {
                exit($this->_msg(0, dr_lang('关键字太长了')));
            }
        }

        // 搜索数据
        $data = $search->get($this->module, $get, $catid);
        if (isset($data['code']) && $data['code'] == 0 && $data['msg']) {
            exit($this->_msg(0, $data['msg']));
        }
        unset($data['params']['page']);

        // 获取同级栏目及父级栏目
        list($parent, $related) = dr_related_cat(
            !$this->module['share'] ? $this->module['category'] : \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share', 'category'),
            $catid
        );

        // 获取搜索总量
        if (!$this->module['setting']['search']['total']) {
            $sototal = intval($data['contentid']);
        } else {
            $sototal = $data['contentid'] ? substr_count($data['contentid'], ',') + 1 : 0;
        }

        // 存储缓存以便标签中使用
        if ($data['id'] && $sototal) {
            \Phpcmf\Service::L('cache')->set_data('search-'.$this->module['dirname'].'-'.$data['id'], $data, 3600);
        }

        $list = [];
        if (IS_API_HTTP && $data['id']) {
            // 移动端请求时
            $pagesize = intval(\Phpcmf\Service::L('input')->request('pagesize'));
            $tag = 'search module='.$this->module['dirname'].' id='.$data['id'].' total='.$sototal.' order='.$data['params']['order'].' catid='.$catid.' more=1 page=1 pagesize='.$pagesize.' urlrule=test';
            $rt = \Phpcmf\Service::V()->list_tag($tag);
            $list = $rt['return'];
        }

        // 栏目格式化
        $cat = $catid && $this->module['category'][$catid] ? $this->module['category'][$catid] : [];
        $top = $catid && $cat['topid'] ? $this->module['category'][$cat['topid']] : $cat;

        // 分页地址
        $urlrule = \Phpcmf\Service::L('Router')->search_url($data['params'], 'page', '{page}');

        // 识别自定义地址，301定向
        if (!IS_API_HTTP && strpos(FC_NOW_URL, 'index.php') !== false && strpos($urlrule, 'index.php') === false) {
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
            'params' => $data['params'],
            'keyword' => $data['keyword'],
            'related' => $related,
            'urlrule' => $urlrule,
            'sototal' => $sototal,
            'searchid' => $data['id'],
            'search_id' => $data['id'],
            'content_id' => $data['contentid'],
            'search_sql' => $data['sql'],
            'is_search_page' => 1,
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);

        // 挂钩点 搜索完成之后
        \Phpcmf\Hooks::trigger('module_search_data', $data);

        if (isset($_GET['ajax_page']) && $_GET['ajax_page']) {
            $tpl = dr_safe_filename($_GET['ajax_page']);
        } else {
            $tpl = $catid && $this->module['category'][$catid]['setting']['template']['search'] ? $this->module['category'][$catid]['setting']['template']['search'] : 'search.html';
        }

        \Phpcmf\Service::V()->display($tpl);
    }

    // 模块内容页
    // $param 自定义字段检索
    protected function _Show($id = 0, $param = [], $page = 1, $rt = 0) {

        // 启用页面缓存
        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            $this->cachePage(SYS_CACHE_PAGE * 3600);
        }

        // 通过自定义字段查找id
        $is_id = 1;
        if (!$id && isset($param['field']) && $this->module['field'][$param['field']]['ismain']) {
            $id = md5($param['field'].$param['value']);
            $is_id = 0;
        }

        $name = 'module_'.$this->module['dirname'].'_show_id_'.$id.($page > 1 ? $page : '');
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
                        \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$this->module['dirname'])->where('id', $id)->set('hits', 'hits+1', FALSE)->update();
                        \Phpcmf\Service::V()->assign('goto_url', $data[$t['fieldname']]);
                        \Phpcmf\Service::V()->display('goto_url');
                        return;
                    }
                }
            }

            // 处理关键字标签
            $data['tag'] = $data['keywords'];
            $data['tags'] = [];
            if ($data['keywords']) {
                $tag = explode(',', $data['keywords']);
                foreach ($tag as $t) {
                    $t = trim($t);
                    $t && $data['tags'][$t] = $this->content_model->get_tag_url($t);
                }
            }

            // 关闭插件嵌入
            $is_fstatus = dr_is_app('fstatus') && isset($this->module['field']['fstatus']) && $this->module['field']['fstatus']['ismain'] ? 1 : 0;

            // 上一篇文章
            $builder = \Phpcmf\Service::M()->db->table($this->content_model->mytable);
            $builder->where('catid', (int)$data['catid'])->where('status', 9);
            $is_fstatus && $builder->where('fstatus', 1);
            $builder->where('id<', $id)->orderBy('id desc');
            $data['prev_page'] = $builder->limit(1)->get()->getRowArray();

            // 下一篇文章
            $builder = \Phpcmf\Service::M()->db->table($this->content_model->mytable);
            $builder->where('catid', (int)$data['catid'])->where('status', 9);
            $is_fstatus && $builder->where('fstatus', 1);
            $builder->where('id>', $id)->orderBy('id asc');
            $data['next_page'] = $builder->limit(1)->get()->getRowArray();

            // 格式化输出自定义字段
            $fields = $this->module['category'][$data['catid']]['field'] ? array_merge($this->module['field'], $this->module['category'][$data['catid']]['field']) : $this->module['field'];
            $fields['inputtime'] = ['fieldtype' => 'Date'];
            $fields['updatetime'] = ['fieldtype' => 'Date'];

            // 格式化字段
            $data = \Phpcmf\Service::L('Field')->app($this->module['dirname'])->format_value($fields, $data, $page);

            // 模块的回调处理
            $data = $this->content_model->_call_show($data);

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

        // 挂钩点 内容读取之后
        \Phpcmf\Hooks::trigger('module_show_read_data', $data);

        // 状态判断
        if ($data['status'] == 10 && !($this->uid == $data['uid'] || $this->member['is_admin'])) {
            $this->goto_404_page(dr_lang('内容被删除，暂时无法访问'));
            return;
        }

        $catid = $data['catid'];

        if ($this->is_hcategory) {
            $parent = $related = [];
            $this->content_model->_hcategory_member_show_auth();
        } else {
            // 无权限访问栏目内容
            if (!dr_member_auth($this->member_authid, $this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['category'][$catid]['show'])) {
                $this->_msg(0, dr_lang('您的用户组无权限访问栏目'), $this->uid || !defined('SC_HTML_FILE') ? '' : dr_member_url('login/index'));
                return;
            }
            // 判断是否同步栏目
            if ($data['link_id'] && $data['link_id'] > 0) {
                \Phpcmf\Service::V()->assign('gotu_url', dr_url_prefix($data['url'], $this->module['dirname']));
                \Phpcmf\Service::V()->display('go.html', 'admin');
                return;
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
            return;
        }

        // 判断内容唯一性
        !$rt && \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix($data['url'], $this->module['dirname']));

        // 传入模板
        \Phpcmf\Service::V()->assign($data);
        \Phpcmf\Service::V()->assign($this->content_model->_format_show_seo($this->module, $data, $page));
        \Phpcmf\Service::V()->assign([
            'cat' => $this->module['category'][$catid],
            'top' => $this->module['category'][$catid]['topid'] ? $this->module['category'][$this->module['category'][$catid]['topid']] : $this->module['category'][$catid],
            'page' => $page,
            'params' => ['catid' => $catid],
            'parent' => $parent,
            'markid' => 'module-'.$this->module['dirname'].'-'.$catid,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::L('Router')->show_url($this->module, $data, '[page]'),
            'fix_html_now_url' => defined('SC_HTML_FILE') ? dr_url_prefix(\Phpcmf\Service::L('Router')->show_url($this->module, $data, $page), $this->module['dirname'], SITE_ID, \Phpcmf\Service::V()->_is_mobile == 'mobile') : '', // 修复静态下的当前url变量
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);
        !$rt && (\Phpcmf\Service::V()->display(isset($data['template']) && strpos($data['template'], '.html') !== FALSE && is_file(\Phpcmf\Service::V()->get_dir().$data['template']) ? $data['template'] : ($this->module['category'][$data['catid']]['setting']['template']['show'] ? $this->module['category'][$data['catid']]['setting']['template']['show'] : 'show.html')));
        return $data;
    }

    // 模块草稿、审核、定时、内容页
    protected function _MyShow($type, $id = 0, $page = 1) {

        switch($type) {

            case 'time':
                $row = \Phpcmf\Service::M()->table(SITE_ID.'_'.$this->module['dirname'].'_time')->get($id);
                $data = dr_string2array($row['content']);
                if (!$data) {
                    $this->goto_404_page(dr_lang('定时内容#%s不存在', $id));
                } elseif (($this->uid != $data['uid'] && !$this->member['is_admin'])) {
                    $this->goto_404_page(dr_lang('定时内容只能自己访问'));
                }
                break;

            case 'recycle':
                $row = \Phpcmf\Service::M()->table(SITE_ID.'_'.$this->module['dirname'].'_recycle')->get($id);
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
                $row = \Phpcmf\Service::M()->table(SITE_ID.'_'.$this->module['dirname'].'_verify')->get($id);
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
                $row = \Phpcmf\Service::M()->table(SITE_ID.'_'.$this->module['dirname'].'_draft')->get($id);
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

        // 处理关键字标签
        $data['tag'] = $data['keywords'];
        $data['keyword_list'] = [];
        if ($data['keywords']) {
            $data['keywords'] = explode(',', $data['keywords']);
            foreach ($data['keywords'] as $t) {
                $t = trim($t);
                $t && $data['keyword_list'][$t] = $this->content_model->get_tag_url($t);
            }
        }

        // 格式化输出自定义字段
        $fields = $this->module['category'][$data['catid']]['field'] ? array_merge($this->module['field'], $this->module['category'][$data['catid']]['field']) : $this->module['field'];
        $fields['inputtime'] = ['fieldtype' => 'Date'];
        $fields['updatetime'] = ['fieldtype' => 'Date'];

        // 格式化字段
        $data = \Phpcmf\Service::L('Field')->app($this->module['dirname'])->format_value($fields, $data, $page);

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
            'page' => $page,
            'params' => ['catid' => $data['catid']],
            'parent' => $parent,
            'markid' => 'module-'.$this->module['dirname'].'-'.$data['catid'],
            'related' => $related,
            'urlrule' =>\Phpcmf\Service::L('Router')->show_url($this->module, $data, '[page]'),
        ]);
        \Phpcmf\Service::V()->module($this->module['dirname']);
        \Phpcmf\Service::V()->display(is_file(dr_tpl_path().'show_'.$type.'.html') ? 'show_'.$type.'.html' : 'show.html');
        return $data;
    }




    //==================生成静态部分 -  创建文件=========================


    // 生成栏目静态页面
    protected function _Create_Category_Html($catid, $page = 0) {

        if (!$catid) {
            return dr_return_data(0, '栏目id不存在');
        } elseif (!defined('MODULE_NAME')) {
            return dr_return_data(0, 'MODULE_NAME未定义');
        }

        $cat = $this->module['category'][$catid];
        if (!$cat) {
            return dr_return_data(0, '模块['.$this->module['name'].']栏目#'.$catid.'不存在');
        } elseif ($this->module['setting']['search']['catsync'] && $cat['tid'] == 1) {
            return dr_return_data(0, '此模块开启了搜索集成栏目页，因此栏目无法生成静态');
        } elseif ($this->module['setting']['html']) {
            return dr_return_data(0, '栏目没有开启静态生成，因此栏目无法生成静态');
        }

        // 无权限访问栏目内容
        if ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['category'][$catid]['show']) {
            return dr_return_data(0, '请关闭栏目访问权限');
        } elseif ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['home']) {
            return dr_return_data(0, '请关闭模块访问权限');
        } elseif ($this->member_cache['auth_site'][SITE_ID]['home']) {
            return dr_return_data(0, '请关闭站点访问权限');
        }

        $url = $page > 0 ?\Phpcmf\Service::L('Router')->category_url($this->module, $cat, $page) : $cat['url'];
        $file = \Phpcmf\Service::L('Router')->remove_domain($url); // 从地址中获取要生成的文件名

        $root = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname']);

        $hfile = dr_to_html_file($file, $root);  // 格式化生成文件
        if (!$hfile) {
            return dr_return_data(0, '地址【'.$cat['url'].'】不规范');
        }

        // 标识变量
        !defined('SC_HTML_FILE') && define('SC_HTML_FILE', 1);

        // 开启ob函数
        ob_start();
        $_GET['page'] = $page;
        \Phpcmf\Service::V()->init('pc');
        $this->_Category($catid, '', $page);
        $html = ob_get_clean();

        // 格式化生成文件
        if (!@file_put_contents($hfile, $html, LOCK_EX)) {
            @unlink($hfile);
            return dr_return_data(0, '文件【'.$hfile.'】写入失败');
        }

        // 移动端生成
        if (SITE_IS_MOBILE && SITE_IS_MOBILE_HTML) {
            ob_start();
            \Phpcmf\Service::V()->init('mobile');
            $_GET['page'] = $page;
            $this->_Category($catid, '', $page);
            $html = ob_get_clean();

            $hfile = dr_to_html_file($file, $root . 'mobile/');

            $size = file_put_contents($hfile, $html, LOCK_EX);
            if (!$size) {
                @unlink($hfile);
                return dr_return_data(0, '无权限写入文件【' . $hfile . '】');
            }
        }
        return dr_return_data(1, 'ok');
    }

    // 生成内容静态页面
    protected function _Create_Show_Html($id, $page = 0) {

        if (!$id) {
            return dr_return_data(0, '内容id不存在');
        }

        // 标识变量
        !defined('SC_HTML_FILE') && define('SC_HTML_FILE', 1);

        // 开启ob函数
        ob_start();
        \Phpcmf\Service::V()->init('pc');
        \Phpcmf\Service::V()->module($this->module['share'] ? 'share' : $this->module['dirname']);
        $data = $this->_Show($id, '', $page);
        $html = ob_get_clean();

        // 无权限访问栏目内容
        if ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['category'][$data['catid']]['show']) {
            return dr_return_data(0, '请关闭栏目访问权限');
        } elseif ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['home']) {
            return dr_return_data(0, '请关闭模块访问权限');
        } elseif ($this->member_cache['auth_site'][SITE_ID]['home']) {
            return dr_return_data(0, '请关闭站点访问权限');
        } elseif ($this->module['setting']['html']) {
            return dr_return_data(0, '栏目没有开启静态生成，因此栏目无法生成静态');
        }

        // 同步数据不执行生成
        if ($data['link_id'] > 0) {
            return dr_return_data(0, '同步数据不执行生成');
        }

        $url = $page > 0 ?\Phpcmf\Service::L('Router')->show_url($this->module, $data, $page) : $data['url'];
        $file =\Phpcmf\Service::L('Router')->remove_domain($url); // 从地址中获取要生成的文件名

        $root = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname']);
        $hfile = dr_to_html_file($file, $root);  // 格式化生成文件

        if (!$hfile) {
            return dr_return_data(0, '地址【'.$data['url'].'】不规范');
        }

        if (!@file_put_contents($hfile, $html, LOCK_EX)) {
            @unlink($hfile);
            return dr_return_data(0, '文件【'.$hfile.'】写入失败');
        }

        // 移动端生成
        if (SITE_IS_MOBILE && SITE_IS_MOBILE_HTML) {
            ob_start();
            \Phpcmf\Service::V()->init('mobile');
            \Phpcmf\Service::V()->module($this->module['share'] ? 'share' : $this->module['dirname']);
            $data = $this->_Show($id, '', $page);
            $html = ob_get_clean();
            $hfile = dr_to_html_file($file, $root.'mobile/');
            $size = file_put_contents($hfile, $html, LOCK_EX);
            if (!$size) {
                @unlink($hfile);
                return dr_return_data(0, '无权限写入文件【'.$hfile.'】');
            }

            if ($page == 0 && $data['content_page']) {
                // 生成分页的页面
                foreach ($data['content_page'] as $i => $t) {
                    $this->_Create_Show_Html($id, $i);
                }
            }
        }


        return dr_return_data(1, 'ok');
    }


    //==================生成静态部分 - 单个文件生成（继承，用于增加修改时实时生成）=========================


    // 生成栏目静态页
    protected function _Category_Html_File() {

        // 判断权限
        if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        }

        // 初始化模块
        $this->_module_init();

        if ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        } elseif ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['home']) {
            $this->_json(0, '当前模块设置了访问权限，无法生成静态');
        }

        $this->_Create_Category_Html(intval(\Phpcmf\Service::L('input')->get('id')));
        exit;

    }

    // 生成内容静态单页
    protected function _Show_Html_File() {

        // 判断权限
        if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        }

        // 初始化模块
        $this->_module_init();

        if ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        } elseif ($this->member_cache['auth_module'][SITE_ID][$this->module['dirname']]['home']) {
            $this->_json(0, '当前模块设置了访问权限，无法生成静态');
        }

        $this->_Create_Show_Html(intval(\Phpcmf\Service::L('input')->get('id')));
        exit;
    }


    //==================生成静态部分 - 后台操作Ajax生成执行=========================


    // 生成首页静态选项
    protected function _Index_Html() {

        // 判断权限
        if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        } elseif ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        } elseif ($this->member_cache['auth_module'][SITE_ID][APP_DIR]['home']) {
            $this->_json(0, '当前模块设置了访问权限，无法生成静态');
        }

        // 标识变量
        !defined('SC_HTML_FILE') && define('SC_HTML_FILE', 1);
        !$this->module && $this->_module_init();

        if (!$this->module['setting']['module_index_html']) {
            $this->_json(0, '当前模块未开启首页静态功能');
        } elseif ($this->module['setting']['search']['indexsync']) {
            $this->_json(0, '当前模块设置了集成搜索页，无法生成静态');
        }

        $root = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->module['dirname']);
        if ($this->module['domain']) {
            // 绑定域名时
            $file = 'index.html';
        } else {
            $file = ltrim(\Phpcmf\Service::L('Router')->remove_domain(MODULE_URL), '/'); // 从地址中获取要生成的文件名;
            !$file && $this->_json(0, dr_lang('生成文件名不合法: %s', MODULE_URL));
        }

        // 生成静态文件
        ob_start();
        \Phpcmf\Service::V()->init('pc');
        $this->_Index(1);
        $html = ob_get_clean();
        $file = dr_format_html_file($file, $root);
        $pc = file_put_contents($file, $html, LOCK_EX);
        if (SITE_IS_MOBILE) {
            ob_start();
            \Phpcmf\Service::V()->init('mobile');
            $this->_Index(1);
            $html = ob_get_clean();
            $file = dr_format_html_file('mobile/' . $file, $root);
            $mobile = file_put_contents($file, $html, LOCK_EX);
        }

        $this->_json(1, dr_lang('电脑端 （%s），移动端 （%s）', dr_format_file_size($pc), dr_format_file_size($mobile)));
    }

    // 生成内容静态选项
    protected function _Show_Html() {

        // 判断权限
        if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        }

        $page = max(1, intval($_GET['pp']));
        $name2 = 'show-'.APP_DIR.'-html-file';
        $pcount = \Phpcmf\Service::L('cache')->get_data($name2);
        if (!$pcount) {
            $this->_json(0, '临时缓存数据不存在：'.$name2);
        } elseif ($page > $pcount) {
            // 完成
            $this->_json(-1, '');
        }

        $name = 'show-'.APP_DIR.'-html-file-'.$page;
        $cache = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$cache) {
            $this->_json(0, '临时缓存数据不存在：'.$name);
        }

        $html = '';
        foreach ($cache as $t) {

            // 初始化模块
            if (!APP_DIR) {
                if (!$t['is_module_dirname']) {
                    $this->module = null;
                } else {
                    $this->is_module_init = false;
                    $this->_module_init($t['is_module_dirname']);
                }
            } else {
                $this->_module_init(APP_DIR);
            }

            $class = '';
            if (!$this->module) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>模块".$t['mid']."未被初始化</a>";
                $class = ' p_error';
            } elseif (!$this->module['category'][$t['catid']]['setting']['html']) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>它是动态模式</a>";
                $class = ' p_error';
            } elseif ($this->member_cache['auth_site'][SITE_ID]['page'][$t['id']]['show']) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>设置的有访问权限</a>";
                $class = ' p_error';
            } else {
                $rt = $this->_Create_Show_Html($t['id']);
                if ($rt['code']) {
                    $ok = "<a class='ok' href='".$t['url']."' target='_blank'>生成成功</a>";
                } else {
                    $ok = "<a class='error' href='".$t['url']."' target='_blank'>".$rt['msg']."</a>";
                    $class = ' p_error';
                }
            }

            $html.= '<p class="'.$class.'"><label class="rleft">(#'.$t['id'].')'.$t['title'].'</label><label class="rright">'.$ok.'</label></p>';

        }

        \Phpcmf\Service::L('cache')->clear($name);

        $this->_json($page + 1, $html, ['pcount' => $pcount]);
    }

    // 生成内容静态选项
    protected function _Category_Html() {

        // 判断权限
        if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        }

        $page = max(1, intval($_GET['pp']));
        $name2 = 'category-'.APP_DIR.'-html-file';
        $pcount = \Phpcmf\Service::L('cache')->get_data($name2);
        if (!$pcount) {
            $this->_json(0, '临时缓存数据不存在：'.$name2);
        } elseif ($page > $pcount) {
            // 完成
            $this->_json(-1, '');
        }

        $name = 'category-'.APP_DIR.'-html-file-'.$page;
        $cache = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$cache) {
            $this->_json(0, '临时缓存数据不存在：'.$name);
        }

        if (APP_DIR) {
            $this->_module_init(APP_DIR);
        }

        $html = '';
        foreach ($cache as $t) {

            if (!APP_DIR) {
                // 初始化模块
                $this->_module_init($t['mid'] ? $t['mid'] : 'share');
            }

            $class = '';
            if (!$this->module) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>模块".$t['mid']."未被初始化</a>";
                $class = ' p_error';
            } elseif (!$t['html']) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>它是动态模式</a>";
                $class = ' p_error';
            } elseif ($this->member_cache['auth_site'][SITE_ID]['page'][$t['id']]['show']) {
                $ok = "<a class='error' href='".$t['url']."' target='_blank'>设置的有访问权限</a>";
                $class = ' p_error';
            } else {
                $rt = $this->_Create_Category_Html($t['id'], $t['page']);
                if ($rt['code']) {
                    $ok = "<a class='ok' href='".$t['url']."' target='_blank'>生成成功</a>";
                } else {
                    $ok = "<a class='error' href='".$t['url']."' target='_blank'>".$rt['msg']."</a>";
                    $class = ' p_error';
                }

            }
            $html.= '<p class="'.$class.'"><label class="rleft">(#'.$t['id'].')'.$t['name'].'</label><label class="rright">'.$ok.'</label></p>';

        }

        \Phpcmf\Service::L('cache')->clear($name);

        $this->_json($page + 1, $html, ['pcount' => $pcount]);

    }

    // 前端模块回调处理类
    protected function _Call_Show($data) {

        return $data;
    }

}
