<?php namespace Phpcmf\Model\Bbs;

// 模块内容模型类

class Content extends \Phpcmf\Model\Content {

    // 评论成功操作之后
    public function _comment_after($data) {

        // 格式化
        $title = dr_clearhtml($data['content']);
        // 更新统计
        if ($data['catid']) {
            $sql = 'update `'.$this->dbprefix($this->mytable).'_cat_count'.'` set `last_uid`='.$data['uid'].',`last_time`='.SYS_TIME.',`last_url`="'.str_replace('"', '', $data['index']['url']).'",`last_title`="'.str_replace('"', '', $data['title']).'",`last_cid`='
                .$data['cid'].',`today_replys`=`today_replys`+1,`replys`=`replys`+1 where catid='.intval($data['catid']);
            $this->db->query($sql);
        }
        if ($data['cid']) {
            $this->table($this->mytable)->update(intval($data['cid']), array(
                'updatetime' =>SYS_TIME,
                'reply_info' => dr_array2string(array(
                    'uid' => $data['uid'],
                    'url' => $data['index']['url'],
                    'title' => $title,
                    'inputtiem' => SYS_TIME,
                )),
            ));
        }
    }
}