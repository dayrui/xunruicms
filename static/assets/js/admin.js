

var Demo = function() {

    // Handle Theme Settings
    var handleTheme = function() {
        var panel = $('.theme-panel');
        $('.toggler', panel).click(function() {
            $('.toggler').hide();
            $('.toggler-close').show();
            $('.theme-panel > .theme-options').show();
        });
        $('.toggler-close', panel).click(function() {
            $('.toggler').show();
            $('.toggler-close').hide();
            $('.theme-panel > .theme-options').hide();
        });
    };



    return {
        init: function() {
            if (App.isAngularJsApp() === false) {
                handleTheme();
            }

        }
    };

}();




// 显示用户资料信息
function dr_show_member(name) {
    if (is_mobile_cms == 1) {
        width = height = '90%';
    } else {
        width = '50%';
        height = '70%';
    }
    var url = admin_file+"?c=api&m=member&name="+$("#dr_"+name).val();
    layer.open({
        type: 2,
        title: '<i class="fa fa-user"></i> ' + lang['member'],
        shadeClose: true,
        shade: 0,
        scrollbar: false,
        area: [width, width],
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.close(index);
                dr_tips(0, obj.msg);
            }
        },
        content: url+'&is_ajax=1'
    });
}

jQuery(document).ready(function() {
    Demo.init(); // init metronic core componets
    $('.onloading').click(function(){
        var index = layer.load(2, { time: 5000 });
    });
    $('.fc_member_show').click(function(){
        var uid = $(this).attr("uid");
        var name = $(this).attr("member");
        var url = admin_file+"?c=api&m=member&name="+name+"&uid="+uid;
        if (is_mobile_cms == 1) {
            width = height = '90%';
        } else {
            width = '50%';
            height = '70%';
        }
        layer.open({
            type: 2,
            title: '<i class="fa fa-user"></i> ' + lang['member'],
            shadeClose: true,
            shade: 0,
            area: [width, width],
            success: function(layero, index){
                // 主要用于后台权限验证
                var body = layer.getChildFrame('body', index);
                var json = $(body).html();
                if (json.indexOf('"code":0') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(0, obj.msg);
                }
            },
            content: url+'&is_ajax=1'
        });
    });

    // 关闭框架的加载提示
    //if (typeof parent.layer.closeAll == 'function') {
        //parent.layer.closeAll('loading');
    //}

    //离开提示失效
    var _t;
    var blnCheckUnload = false;
    window.onunloadcancel = function(){
        clearTimeout(_t);
    }
    window.onbeforeunload = function() {
        if (blnCheckUnload) {
            setTimeout(function(){_t = setTimeout(onunloadcancel, 0)}, 0);
            return lang['unloadtips'];
        }
    }
    $("[type='submit'], [type='button']").click(function(){
        blnCheckUnload = false;
    });
    $("select").change(function(){
        blnCheckUnload = true;
    });
    $(document).keydown(function (event) {
        if (event.keyCode >=40 || event.keyCode == 0) {
            blnCheckUnload = true;
        };
        if (event.keyCode == 16 || event.keyCode == 82 || event.keyCode==91) {
            blnCheckUnload = false;
        }
    });
    // 宽度小时
    if ($(document).width() < 900) {
        $(".fc-all-menu-top").remove();
        $(".fc-mini-menu-top").show();
        // 缩小table
        /*
        $('.page-breadcrumb a').each(function () {
            var name = $(this).html();
            re =new RegExp(/<i class=\"(.+)\"(.+)/i);
            if (re.test(name)) {
                var result=  name.match(re);
                $(this).html('<i class="'+result[1]+'"></i>');
                $(this).attr('title', result[2].replace('></i> ', ''));
            }
        });*/
        // 缩小table下方按钮
        $('.fc-list-select button').each(function () {
            var name = $(this).html();
            re =new RegExp(/<i class=\"(.+)\"(.+)/i);
            if (re.test(name)) {
                var result=  name.match(re);
                $(this).html('<i class="'+result[1]+'"></i>');
                $(this).attr('title', result[2].replace('></i> ', ''));
            }
        });
        // 缩小后台导航面包屑
        $('a[data-toggle="tab"]').each(function () {
            var name = $(this).html();
            re =new RegExp(/<i class=\"(.+)\"(.+)/i);
            if (re.test(name)) {
                var result=  name.match(re);
                $(this).html('<i class="'+result[1]+'"></i>');
                $(this).attr('title', result[2].replace('></i> ', ''));
            }
        });
        // 大挪移logo
        $('.my-top-left').html('<div class="fc-mini-logo">'+$('.page-header-inner .page-logo').html()+'</div>');


    } else {
        $(".fc-all-menu-top").show();
        $(".fc-mini-menu-top").remove();
    }
    // table
});

// 动态执行菜单链接
function dr_admin_menu_ajax(url, not_sx) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 10000
    });
    $.ajax({type: "GET",dataType:"json", url: url,
        success: function(json) {
            layer.close(index);
            dr_tips(json.code, json.msg);
            if (json.code == 1) {
                if (not_sx) {
                    return;
                } else {
                    setTimeout("window.location.reload(true)", 2000);
                }
            }
            //if (is_sx) {
                //setTimeout("window.location.reload(true)", 2000);
           // }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}

// 动态更新缓存
function dr_update_cache(model, namespace) {
    dr_update_cache_all();
}

// 动态执行链接
function dr_load_ajax(msg, url, go) {
    layer.confirm(
        msg,
        {
            icon: 3,
            shade: 0,
            title: lang['ts'],
            btn: [lang['ok'], lang['esc']]
        }, function(index){
            layer.close(index);
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });

            $.ajax({type: "GET",dataType:"json", url: url,
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (go == 1) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
        });
}

// 安装模块提示
function dr_install_module_select(url) {
    layer.confirm(
        '共享模块: 共用一个栏目，在栏目中选择模块<br>'+
        '独立模块: 独立栏目管理，在模块中选择栏目<br>',
        {
            shade: 0,
            title: '安装选择',
            btn: ['独立', '共享', '了解区别'],
            btn3: function(index, layero){
                dr_help(626);
            }
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });
            $.ajax({type: "GET",dataType:"json", url: url+'&type=1',
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });
            $.ajax({type: "GET",dataType:"json", url: url+'&type=0',
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
        }
    );
}

function dr_install_module(url) {
    layer.confirm(
        '你确定要安装到当前站点吗？',
        {
            shade: 0,
            title: '安装',
            btn: ['安装', '取消']
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });
            $.ajax({type: "GET",dataType:"json", url: url,
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
        }, function(index){
            return;
        }
    );
}

// 安装app提示
function dr_install_app(url) {
    layer.confirm(
        '您在使用第三方应用程序时，官方不保证它的合法性、安全性、完整性、真实性或品质等，请用户自行判断是否安装并承担所有风险。',
        {
            shade: 0,
            title: '免责声明',
            btn: ['安装', '取消']
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });
            $.ajax({type: "GET",dataType:"json", url: url,
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
        }, function(index){
            return;
        }
    );
}


// 推送模块数据
function dr_module_send(title, url) {
    var width = '50%';
    var height = '60%';
    if (is_mobile_cms == 1) {
        width = height = '90%';
    }
    url+= '&'+$("#myform").serialize();
    layer.open({
        type: 2,
        title: title,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        btn: [lang['ok']],
        yes: function(index, layero){
            var body = layer.getChildFrame('body', index);
            $(body).find('.form-group').removeClass('has-error');
            // 延迟加载
            var loading = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 5000
            });
            $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find('#myform').serialize(),
                success: function(json) {
                    layer.close(loading);
                    if (json.code == 1) {
                        layer.close(index);
                        setTimeout("window.location.reload(true)", 2000)
                    } else {
                        $(body).find('#dr_row_'+json.data.field).addClass('has-error');
                    }
                    dr_tips(json.code, json.msg);
                    return false;
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
            return false;
        },
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.close(index);
                dr_tips(0, obj.msg);
            }
            if (json.indexOf('"code":1') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.close(index);
                dr_tips(1, obj.msg);
            }
        },
        content: url+'&is_ajax=1'
    });
}
// 批量模块数据 ajax
function dr_module_send_ajax(url) {
    url+= '&'+$("#myform").serialize();
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 10000
    });
    $.ajax({type: "GET",dataType:"json", url: url,
        success: function(json) {
            layer.close(index);
            dr_tips(json.code, json.msg);
            if (json.code == 1) {
                setTimeout("window.location.reload(true)", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}


// 添加快捷菜单
function dr_add_menu() {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 5000
    });
    $.ajax({
        type: "GET",
        url: admin_file+"?c=api&m=menu&v="+encodeURIComponent($("#right_page").attr("url")),
        dataType: "json",
        success: function (json) {
            layer.close(index);
            dr_tips(json.code, json.msg);
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}

// ajax关闭或启用
function dr_ajax_open_close(e, url, fan) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 5000
    });
    var obj = $(e);
    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function (json) {
            layer.close(index);
            if (json.code == 1) {
                if (json.data.value == fan) {
                    obj.attr('class', 'badge badge-no');
                    obj.html('<i class="fa fa-times"></i>');
                } else {
                    obj.attr('class', 'badge badge-yes');
                    obj.html('<i class="fa fa-check"></i>');
                }
            }
            dr_tips(json.code, json.msg);
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}

// 百分百进度控制
function dr_bfb(title, myform, url) {
    layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    layer.open({
        type: 2,
        title: title,
        scrollbar: false,
        resize: true,
        maxmin: true, //开启最大化最小化按钮
        shade: 0,
        area: ['80%', '80%'],
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.closeAll(index);
                dr_tips(0, obj.msg);
            }
        },
        content: url+'&'+$('#'+myform).serialize()
    });
}
// 百分百提交再进度控制
function dr_bfb_submit(title, myform, url) {
    layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    $.ajax({type: "POST",dataType:"json", url: url, data: $('#'+myform).serialize(),
        success: function(json) {
            layer.closeAll('loading');
            if (json.code == 1) {


                layer.open({
                    type: 2,
                    title: title,
                    scrollbar: false,
                    resize: true,
                    maxmin: true, //开启最大化最小化按钮
                    shade: 0,
                    area: ['80%', '80%'],
                    success: function(layero, index){
                        // 主要用于后台权限验证
                        var body = layer.getChildFrame('body', index);
                        var json = $(body).html();
                        if (json.indexOf('"code":0') > 0 && json.length < 150){
                            var obj = JSON.parse(json);
                            layer.closeAll('loading');
                            dr_tips(0, obj.msg);
                        }
                    },
                    content: json.data.url
                });

            } else {
                dr_tips(0, json.msg, 90000);
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}

// 打开预览文件
function dr_show_file_code(title, url) {
    layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    layer.open({
        type: 2,
        title: title,
        scrollbar: false,
        resize: true,
        maxmin: true, //开启最大化最小化按钮
        shade: 0,
        area: ['80%', '80%'],
        success: function(layero, index){
            layer.closeAll('loading');
        },
        content: url
    });
}

// 导出页面控制
function dr_export(table, sql) {
    dr_tips(0, '此功能不可用');
}

// 提交生成静态页面
function dr_submit_htmlfile(myform, url) {
    layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    layer.open({
        type: 2,
        title: lang['htmlfile'],
        shadeClose: true,
        shade: 0,
        area: ['480px', '30%'],
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.closeAll(index);
                dr_tips(0, obj.msg);
            }
        },
        content: url+'&'+$('#'+myform).serialize()
    });
}

// 提交到执行页面
function dr_submit_todo(myform, url) {
    layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    layer.open({
        type: 2,
        title: lang['todoing'],
        shadeClose: true,
        shade: 0,
        area: ['480px', '30%'],
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 150){
                var obj = JSON.parse(json);
                layer.closeAll(index);
                dr_tips(0, obj.msg);
            }
        },
        content: url+'&'+$('#'+myform).serialize()
    });
}

// 提交到执行页面 post
function dr_submit_post_todo(myform, url) {
    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    $.ajax({type: "POST",dataType:"json", url: url, data: $('#'+myform).serialize(),
        success: function(json) {
            layer.close(loading);
            if (json.code == 1) {
                dr_tips(1, json.msg);
            } else {
                dr_tips(0, json.msg, 90000);
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}

// 提交到执行sql页面 post
function dr_submit_sql_todo(myform, url) {
    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 1000
    });
    $("#sql_result").html(' ... ');
    $.ajax({type: "POST",dataType:"json", url: url, data: $('#'+myform).serialize(),
        success: function(json) {
            layer.close(loading);
            if (json.code == 1) {
                $("#sql_result").html('<pre>'+json.msg+'</pre>');
            } else {
                $("#sql_result").html('<div class="alert alert-danger">'+json.msg+'</div>');
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
        }
    });
}



function dr_call_alert() {
    layer.alert('回调是用于在列表显示时对其值进行格式化<br>'+
        '此处填写函数名称即可<br>函数需要开发者自己定义，函数定义格式为: <br>function($value, $param); <br>$value是传入值，$param是列表搜索参数<br><br>'+
        '标题: title<br>'+
        '评论: comment<br>'+
        '多文件: files<br>'+
        'uid会员: uid<br>'+
        '地区联动: linkage_address<br>'+
        '栏目: catid<br>'+
        '时间: datetime<br>会员信息: author', {
        title: '',
        shade: 0,
        btn: []
    });

}
function dr_seo_rule() {
    layer.alert('通用标签<br>'+
        '{join}	SEO连接符号，默认“_”<br>'+
        '{modulename}	当前模型名称<br>'+
        '{keyword}	搜索时的关键字<br>'+
        '{param}	搜索时的参数<br>'+
        '[{page}]	分页页码<br>'+
        '{SITE_NAME}	网站名称<br>'+
        '支持“对应表”任何字段，格式：{字段名}，<br>如：{title}表示标题<br>'+
        '支持网站系统常量，格式：{大写的常量名称}，<br>如：{SITE_NAME}表示网站名称<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        btn: []
    });
}
function dr_url_module_index() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: index.php?s=news<br>'+
        '形如news.html 这种地址格式为: {modname}.html 或者直接写成 news.html'+
        '<br><br><br><b>通配符</b><hr>'+
        '{modname}	表示当前模块目录<br>'+
        '如果此模块绑定了域名,那么此规则就无效了<br>'+
        ''+
        '', {
        shade: 0,
        area: ['50%', '50%'],
        title: '',
        btn: []
    });
}
function dr_url_module_list() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=category&id=1<br>'+
        '形如news/1.html <br>这种地址格式为: {dirname}/{id}.html'+
        '<br><br><b>通配符</b><hr>'+
        '{id}   表示栏目id<br>'+
        '{page}   表示分页号<br>'+
        '{dirname}   表示栏目目录名称<br>'+
        '{pdirname}   包含父级层次的目录<br>'+
        '{modname}  表示模块目录（只能独立模块使用，共享模块不能使用）<br>'+
        '支持主表任何字段，格式：{字段名}，如：{name}表示栏目名称<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}

function dr_url_mform_list() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=xxxxx&cid=1<br>'+
        '形如news/xxxxx/1-list.html <br>这种地址格式为: {modname}/{form}/{cid}-list.html'+
        '<br><br><b>通配符</b><hr>'+
        '{page}   表示分页号<br>'+
        '{cid}   表示对应模块内容的id<br>'+
        '{form}   表示表单的别名<br>'+
        '{modname}  表示模块目录<br>'+
        '支持主表任何字段，格式：{字段名}，如：{name}表示栏目名称<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_mform_show() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=xxxxx&m=show&cid=1<br>'+
        '形如news/xxxxx/1-show.html <br>这种地址格式为: {modname}/{form}/{cid}-show.html'+
        '<br><br><b>通配符</b><hr>'+
        '{id}   表示表单内容id<br>'+
        '{page}   表示分页号<br>'+
        '{cid}   表示对应模块内容的id<br>'+
        '{form}   表示表单的别名<br>'+
        '{modname}  表示模块目录<br>'+
        '支持主表任何字段，格式：{字段名}，如：{name}表示栏目名称<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_mform_post() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=xxxxx&m=post&cid=1<br>'+
        '形如news/xxxxx/1-post.html <br>这种地址格式为: {modname}/{form}/{cid}-post.html'+
        '<br><br><b>通配符</b><hr>'+
        '{page}   表示分页号<br>'+
        '{cid}   表示对应模块内容的id<br>'+
        '{form}   表示表单的别名<br>'+
        '{modname}  表示模块目录<br>'+
        '支持主表任何字段，格式：{字段名}，如：{name}表示栏目名称<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_module_show() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=show&id=1<br>'+
        '形如show/1.html <br>这种地址格式为: {modname}/{id}.html'+
        '<br><br><b>通配符</b><hr>'+
        '{id}   表示id<br>'+
        '{y}   表示年<br>'+
        '{m}   表示月<br>'+
        '{d}   表示日<br>'+
        '{page}   表示分页号<br>'+
        '{dirname}   表示栏目目录名称<br>'+
        '{pdirname}   包含父级层次的目录<br>'+
        '{modname}  表示模块目录<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_page() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=page&id=1<br>'+
        '形如page/1.html <br>这种地址格式为: page/{id}.html'+
        '<br><br><b>通配符</b><hr>'+
        '{id}   表示id<br>'+
        '{page}   表示分页号<br>'+
        '{dirname}   表示栏目目录名称<br>'+
        '{pdirname}   包含父级层次的目录<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_module_tag() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=tag&name=guanjianci<br>'+
        '形如tag/guanjianci.html <br>这种地址格式为: tag/{tag}.html'+
        '<br><br><b>通配符</b><hr>'+
        '{tag}   表示tag英文名称<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_module_search() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: index.php?s=news&c=search<br>'+
        '形如news/search.html 这种地址格式为: {modname}/search.html'+
        '<br><br><br><b>通配符</b><hr>'+
        '{modname}	表示当前模块目录<br>'+
        ''+
        '', {
        shade: 0,
        area: ['50%', '50%'],
        title: '',
        btn: []
    });
}
function dr_url_fanzhan() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: index.php?fid=分站别名<br>'+
        '形如/beijin.html 这种地址格式为: index.php?fid=beijin'+
        '<br><br><br><b>通配符</b><hr>'+
        '{fid}	表示当前分站别名<br>'+
        ''+
        '', {
        shade: 0,
        area: ['50%', '50%'],
        title: '',
        btn: []
    });
}

function dr_url_module_search_page() {
    layer.alert('<b>举例</b><hr>'+
        '默认模块地址: <br>index.php?s=news&c=search&字段=值<br>'+
        '形如news/search/搜索参数.html <br>这种地址格式为: {modname}/search/{param}.html'+
        '<br><br><b>通配符</b><hr>'+
        '{param}   表示搜索参数<br>'+
        '{modname}  表示模块目录<br>'+
        '<br><br><b>使用自定义函数方法(需要有php开发经验)</b><hr>'+
        '{自定义函数方法名($data)}	   表示用自定义函数方法来定义url<br>'+
        '<br><br><b>自定义函数举例(需要有php开发经验)</b><hr>'+
        '自定义函数文件: /config/custom.php <br>增加以下函数体:<br>'+
        'function my_url($data) { return "你的URL"; } // 这个函数内容你自己定义<br>'+
        '那么你就填写: {my_url($data)}<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}

function dr_help(id) {
    layer.open({
        type: 2,
        title: '<i class="fa fa-question-circle"></i> 在线帮助',
        shadeClose: true,
        scrollbar: false,
        shade: 0,
        area: ['80%', '90%'],
        content: 'https://www.xunruicms.com/index.php?s=doc&c=show&id='+id+'&is_phpcmf=cms'
    });
}

function dr_search_help() {
    layer.open({
        type: 2,
        title: '<i class="fa fa-question-circle"></i> 在线帮助',
        shadeClose: true,
        scrollbar: false,
        shade: 0,
        area: ['80%', '90%'],
        content: admin_file+'?c=api&m=search_help&kw='+$('#mysearchform_kw').val()
    });
}

function dr_test_html_dir(id) {
    $.ajax({type: "GET",dataType:"json", url: admin_file+"?c=api&m=test_dir&v="+encodeURIComponent($("#"+id).val()),
        success: function(json) {
            dr_tips(json.code, json.msg);
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}

function dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError) {
    layer.closeAll('loading');
    var msg = HttpRequest.responseText;
    if (!msg) {
        dr_tips(0, lang['error']);
    } else {
        layer.open({
            type: 1,
            title: lang['error'],
            fix:true,
            shadeClose: true,
            shade: 0,
            area: ['50%', '50%'],
            content: "<div style=\"padding:10px;\">"+msg+"</div>"
        });
    }
}