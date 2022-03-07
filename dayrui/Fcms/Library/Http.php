<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * Http格式化数据类
 */

class Http {

    // 用于用户中心财务记录时间格式化回调，开发者可以根据实际情况回调返回参数
    public function member_paylog_list($data) {

        $rt = [];
        if ($data['list']) {
            foreach ($data['list'] as $t) {
                $t['inputtime'] = dr_date($t['inputtime']);
                $rt[] = $t;
            }
        }

        return $rt;
    }

    // 用于内容评论列表的格式化回调，开发者可以根据实际情况回调返回参数
    public function member_content_comment($data) {

        $rt = [];
        if ($data['list']) {
            foreach ($data['list'] as $t) {
                $t['thumb'] = dr_thumb($t['thumb']);
                $t['inputtime'] = dr_date($t['inputtime']);
                $rt[] = $t;
            }
        }

        return $rt;
    }

    // 用于模块评论列表时间格式化回调，开发者可以根据实际情况回调返回参数
    public function module_comment_list($data) {

        if ($data['list']) {
            foreach ($data['list'] as $i => $t) {
                $t['inputtime'] = dr_date($t['inputtime']);
                $t['avatar'] = dr_avatar($t['uid']);
                $data['list'][$i] = $t;
            }
        }

        return $data;
    }

    // 用于模块搜索列表的格式化回调，开发者可以根据实际情况回调返回参数
    public function module_search_news_list($data) {

        $rt = [];
        if ($data['list']) {
            foreach ($data['list'] as $t) {
                $t['thumb'] = dr_thumb($t['thumb']);
                $rt[] = $t;
            }
        }

        return $rt;
    }


    // 用于模块内容详情的格式化回调，开发者可以根据实际情况回调返回参数
    public function module_show_news_list($data) {

        $rt = [];
        if ($data) {
            return [
                'id' => $data['id'],
                'title' => $data['title'],
                'thumb' => dr_thumb($data['thumb']),
                'content' => $data['content'],
            ];
        }

        return $rt;
    }

}