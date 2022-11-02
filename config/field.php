<?php

/**
 * 字段样式设置
 */

return [

    // 后台
    'admin' => '<div class="form-group" rs="is_admin" id="dr_row_{name}">
    <label class="control-label col-md-2">{text}</label>
    <div class="col-md-10">{value}</div>
</div>',

    // 用户中心
    'member' => '<div class="form-group" rs="is_member" id="dr_row_{name}">
    <label class="control-label col-md-2">{text}</label>
    <div class="col-md-10">{value}</div>
</div>',

    // 前台
    'home' => '<div class="form-group" rs="is_home" id="dr_row_{name}">
    <label class="control-label col-md-2">{text}</label>
    <div class="col-md-10">{value}</div>
</div>',
    
    
];
