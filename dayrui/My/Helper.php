<?php
function my_url($data) {
    // 传入的$data数组是当前栏目的详细数据
    return '自定义函数输出栏目name字段：'.$data['name'];
}