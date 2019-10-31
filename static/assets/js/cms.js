$(function(){

    if ($(document).width() < 600) {
        $('.hidden-mobile').hide();
        $('.table').attr('style', 'table-layout: inherit!important;');
    } else {
        $('.hidden-mobile').show();
    }
    // 排序操作
    $('.table .heading th').click(function(e) {
        var _class = $(this).attr("class");
        if (_class == '' || _class == undefined) {
            return;
        }
        var _name = $(this).attr("name");
        if (_name == '' || _name == undefined) {
            return;
        }
        var _order = '';
        if (_class == "order_sorting") {
            _order = 'desc';
        } else if (_class == "order_sorting_desc") {
            _order = 'asc';
        } else {
            _order = 'desc';
        }
        var url = window.location.href;
        url = url.replace("&order=", "&");
        url+= "&order="+_name+" "+_order;
        window.location.href=url;
    });
    // tabl
    if ($('.table-checkable')) {
        var table = $('.table-checkable');
        table.find('.group-checkable').change(function () {
            var set = jQuery(this).attr("data-set");
            var checked = jQuery(this).is(":checked");
            jQuery(set).each(function () {
                if (checked) {
                    $(this).prop("checked", true);
                    $(this).parents('tr').addClass("active");
                } else {
                    $(this).prop("checked", false);
                    $(this).parents('tr').removeClass("active");
                }
            });
        });
    }
});

// 判断当前终端是否是移动设备
function dr_is_mobile() {
	var ua = navigator.userAgent,
	 isWindowsPhone = /(?:Windows Phone)/.test(ua),
	 isSymbian = /(?:SymbianOS)/.test(ua) || isWindowsPhone, 
	 isAndroid = /(?:Android)/.test(ua), 
	 isFireFox = /(?:Firefox)/.test(ua), 
	 isChrome = /(?:Chrome|CriOS)/.test(ua),
	 isTablet = /(?:iPad|PlayBook)/.test(ua) || (isAndroid && !/(?:Mobile)/.test(ua)) || (isFireFox && /(?:Tablet)/.test(ua)),
	 isPhone = /(?:iPhone)/.test(ua) && !isTablet,
	 isPc = !isPhone && !isAndroid && !isSymbian;
	 if (isPc) {
		// pc
		return false;
	 } else {
		return true;
	 }
}

// 显示图片
function dr_preview_image(file) {
    if (is_mobile_cms == 1) {
        width = height = '90%';
    } else {
        width = height = '70%';
    }
    layer.alert('<p style="text-align: center"><a href="'+file+'" target="_blank">'+file+'</a></p><p style="text-align: center"><a href="'+file+'" target="_blank"><img style="max-width:100%" src="'+file+'"></a></p>', {
        shade: 0,
        //scrollbar: false,
        shadeClose: true,
        title: '',
        area: [width, width],
        btn: []
    });
}
// 显示url
function dr_preview_url(url) {
    if (is_mobile_cms == 1) {
        width = height = '90%';
    } else {
        width = '40%';
        height = '10%';
    }
    layer.alert('<a href="'+url+'" target="_blank">'+url+'</a>', {
        shade: 0,
        title: '',
        area: [width, width],
        btn: []
    });
}

// 提示信息
function dr_layer_tips(msg, time) {
    layer.msg(msg);
}

// 弹出显示信息
function dr_show_info(msg, width) {
    if (!width) {
        width = 50;
    }
    if (is_mobile_cms == 1) {
        width = height = '90';
    } else {
        height = '50';
    }
    layer.open({
        type: 1,
        shade: 0,
        fix:true,
        //scrollbar: false,
        shadeClose: true,
        title: lang['show'],
        area: [width+'%', height+'%'],
        content: '<div style="padding:20px;overflow-y:scrol">'+(msg)+'</div>'
    });
}
function dr_tips(code, msg, time) {

    if (!time) {
        time = 3000;
    }
    var tip = '<i class="fa fa-info-circle"></i>';
    //var theme = 'teal';
    if (code >= 1) {
        tip = '<i class="fa fa-check-circle"></i>';
        //theme = 'lime';
    } else if (code == 0) {
        tip = '<i class="fa fa-times-circle"></i>';
        //theme = 'ruby';
    }

    layer.msg(tip+'&nbsp;&nbsp;'+msg);
}
function dr_cmf_tips(code, msg, time) {
    dr_tips(code, msg, time);
}

//
function dr_iframe(type, url, width, height, nogo) {

    var title = '';
    if (type == 'add') {
        title = '<i class="fa fa-plus"></i> '+lang['add'];
    } else if (type == 'edit') {
        title = '<i class="fa fa-edit"></i> '+lang['edit'];
    } else if (type == 'send') {
        title = '<i class="fa fa-send"></i> '+lang['send'];
    } else if (type == 'save') {
        title = '<i class="fa fa-save"></i> '+lang['save'];
    } else {
        title = type;
    }
    if (!width) {
        width = '500px';
    }
    if (!height) {
        height = '70%';
    }

    if (is_mobile_cms == 1) {
       width = '95%';
       height = '90%';
    }

    layer.open({
        type: 2,
        title: title,
        fix:true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        btn: [lang['ok'], lang['esc']],
        yes: function(index, layero){
            var body = layer.getChildFrame('body', index);
            $(body).find('.form-group').removeClass('has-error');
            // 延迟加载
            var loading = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 100000000
            });
            $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find('#myform').serialize(),
                success: function(json) {
                    layer.close(loading);
                    if (json.code) {
                        layer.close(index);
                        if (json.data.tourl) {
                            setTimeout("window.location.href = '"+json.data.tourl+"'", 2000);
                        } else {
                            if (nogo == 'nogo') {

                            } else {
                                setTimeout("window.location.reload(true)", 2000);
                            }

                        }
                        dr_cmf_tips(1, json.msg);
                    } else {
                        $(body).find('#dr_row_'+json.data.field).addClass('has-error');
                        dr_cmf_tips(0, json.msg);
                    }
                    return false;
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
                }
            });
            return false;
        },
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 500){
                var obj = JSON.parse(json);
                layer.close(index);
                dr_cmf_tips(0, obj.msg);
            }
        },
        content: url+'&is_ajax=1'
    });
}
// ajax 显示内容
function dr_iframe_show(type, url, width, height) {

    var title = '';
    if (type == 'show') {
        title = '<i class="fa fa-search"></i> '+lang['show'];
    } else if (type == 'edit') {
        title = '<i class="fa fa-edit"></i> '+lang['edit'];
    } else if (type == 'code') {
        title = '<i class="fa fa-code"></i> '+lang['code'];
    } else if (type == 'cart') {
        title = '<i class="fa fa-shopping-cart"></i> '+lang['paylog'];
    } else {
        title = type;
    }
    if (!width) {
        width = '60%';
    }
    if (!height) {
        height = '70%';
    }

    if (is_mobile_cms == 1) {
        width = '95%';
        height = '90%';
    }

    layer.open({
        type: 2,
        title: title,
        fix:true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        success: function(layero, index){
            // 主要用于后台权限验证
            var body = layer.getChildFrame('body', index);
            var json = $(body).html();
            if (json.indexOf('"code":0') > 0 && json.length < 500){
                var obj = JSON.parse(json);
                layer.close(index);
                dr_cmf_tips(0, obj.msg);
            }
        },
        content: url+'&is_ajax=1'
    });
}

// 插入emoji表情
function dr_insert_emoji(name) {

    if (is_mobile_cms == 1) {
        width = '95%';
        height = '90%';
    } else {
        width = height = '70%';
    }

    layer.open({
        type: 2,
        title: '<i class="fa fa-smile-o"></i> Emoji',
        fix:true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        content: '/index.php?s=api&c=emoji&name='+name
    });
}

// ajax 操作确认 并跳转
function dr_ajax_confirm_url(url, msg, tourl) {
    layer.confirm(
        msg,
        {
            icon: 3,
            shade: 0,
            title: lang['ts'],
            btn: [lang['ok'], lang['esc']]
        }, function(index){
            layer.close(index);
            var loading = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 100000000
            });
            $.ajax({
                type: "GET",
                dataType: "json",
                url: url,
                success: function(json) {
                    layer.close(loading);
                    if (json.code) {
                        if (json.data.url) {
                            setTimeout("window.location.href = '"+json.data.url+"'", 2000);
                        } else {
                            setTimeout("window.location.href = '"+tourl+"'", 2000);
                        }
                    }
                    dr_cmf_tips(json.code, json.msg);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
                }
            });
        });
}

// ajax操作
function dr_ajax_url(url) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });
    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function (json) {
            layer.close(index);
			if (json.code == 0) {
                $('.fc-code img').click();
                if (json.data.field) {
                    $('#dr_row_'+json.data.field).addClass('has-error');
                    $('#dr_'+json.data.field).focus();
                }
			}
            dr_cmf_tips(json.code, json.msg);
            if (json.data.url) {
                setTimeout("window.location.href = '"+json.data.url+"'", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}

// ajax操作 jsonp
function dr_ajaxp_url(url) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });
    $.ajax({
        type: "GET",
        url: url,
        dataType: "jsonp",
        success: function (json) {
            layer.close(index);
            dr_cmf_tips(json.code, json.msg);
            if (json.data.url) {
                setTimeout("window.location.href = '"+json.data.url+"'", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}


// ajax保存数据
function dr_ajax_save(value, url, name) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 5000
    });
    $.ajax({
        type: "GET",
        url: url+'&name='+name+'&value='+value,
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


// ajax 批量操作确认
function dr_ajax_option(url, msg, remove) {
    layer.confirm(
        msg,
        {
            icon: 3,
            shade: 0,
            title: lang['ts'],
            btn: [lang['ok'], lang['esc']]
        }, function(index){
            layer.close(index);
            var loading = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 100000000
            });
            $.ajax({
                type: "POST",
                dataType: "json",
                url: url,
                data: $("#myform").serialize(),
                success: function(json) {
                    layer.close(loading);
                    if (json.code) {
                        if (remove) {
                            // 批量移出去
                            var ids = json.data.ids;
                            if (typeof ids != "undefined" ) {
                                console.log(ids);
                                for ( var i = 0; i < ids.length; i++){
                                    $("#dr_row_"+ids[i]).remove();
                                }
                            }
                        }
                        if (json.data.htmlfile) {
                            // 执行生成htmljs
                            $.ajax({
                                type: "GET",
                                url: json.data.htmlfile,
                                dataType: "jsonp",
                                success: function(json){ },
                                error: function(){ }
                            });
                        }
                        if (json.data.url) {
                            setTimeout("window.location.href = '"+json.data.url+"'", 2000);
                        } else {
                            setTimeout("window.location.reload(true)", 3000)
                        }
                    }
                    dr_cmf_tips(json.code, json.msg);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
                }
            });
        });
}

// ajax 批量操作确认 并跳转
function dr_ajax_option_url(url, msg, tourl) {
    layer.confirm(
        msg,
        {
            icon: 3,
            shade: 0,
            title: lang['ts'],
            btn: [lang['ok'], lang['esc']]
        }, function(index){
            layer.close(index);
            var loading = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 100000000
            });
            $.ajax({
                type: "POST",
                dataType: "json",
                url: url,
                data: $("#myform").serialize(),
                success: function(json) {
                    layer.close(loading);
                    if (json.code) {
                        if (json.data.url) {
                            setTimeout("window.location.href = '"+json.data.url+"'", 2000);
                        } else {
                            setTimeout("window.location.href = '" + tourl + "'", 2000);
                        }
                    }
                    dr_cmf_tips(json.code, json.msg);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
                }
            });
        });
}

// ajax提交
function dr_ajax_submit(url, form, time, go) {

    url = url.replace(/&page=\d+&page/g, '&page');

    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });

    $("#"+form+' .form-group').removeClass('has-error');

    $.ajax({
        type: "POST",
        dataType: "json",
        url: url,
        data: $("#"+form).serialize(),
        success: function(json) {
            layer.close(loading);
            if (json.code) {
                dr_cmf_tips(1, json.msg);
                if (json.data.htmlfile) {
                    // 执行生成htmljs
                    $.ajax({
                        type: "GET",
                        url: json.data.htmlfile,
                        dataType: "jsonp",
                        success: function(json){ },
                        error: function(){ }
                    });
                }
                if (json.data.htmllist) {
                    // 执行生成htmljs
                    $.ajax({
                        type: "GET",
                        url: json.data.htmllist,
                        dataType: "jsonp",
                        success: function(json){ },
                        error: function(){ }
                    });
                }
                if (time) {
                    var gourl = url;
                    if (go != '' && go != undefined && go != 'undefined') {
                        gourl = go;
                    } else if (json.data.url) {
                        gourl = json.data.url;
                    }
                    setTimeout("window.location.href = '"+gourl+"'", time);
                }
            } else {
                dr_cmf_tips(0, json.msg);
                $('.fc-code img').click();
                if (json.data.field) {
                    $('#dr_row_'+json.data.field).addClass('has-error');
                    $('#dr_'+json.data.field).focus();
                }
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}
// 退出登录
function dr_loginout(msg) {
    $.ajax({type: "GET",dataType:"json", url: "/index.php?s=api&c=api&m=loginout",
        success: function(json) {
            var oss_url = json.data.sso;
            // 发送同步登录信息
            for ( var i = 0; i < oss_url.length; i++){
                $.ajax({
                    type: "GET",
                    url:oss_url[i],
                    dataType: "jsonp",
                    success: function(json){ },
                    error: function(){ }
                });
            }
            dr_cmf_tips(1, json.msg);
            setTimeout('window.location.href="'+json.data.url+'"', 2000);
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}
// ajax提交登录或者注册
function dr_ajax_member(url, form) {

    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });

    $("#"+form+' .form-group').removeClass('has-error');
    
    $.ajax({
        type: "POST",
        dataType: "json",
        url: url,
        data: $("#"+form).serialize(),
        success: function(json) {
            layer.close(loading);
            if (json.code) {
                var oss_url = json.data.sso;
                // 发送同步登录信息
                for ( var i = 0; i < oss_url.length; i++){
                    //alert(oss_url[i]);
                    $.ajax({
                        type: "GET",
                        url:oss_url[i],
                        dataType: "jsonp",
                        success: function(json){
                        },
                        error: function(HttpRequest, ajaxOptions, thrownError){
                        }
                    });
                }
                if (json.data.url) {
                    window.location.href = json.data.url;
                }
            } else {
                dr_cmf_tips(0, json.msg);
                $('.fc-code img').click();
                if (json.data.field) {
                    $('#dr_row_'+json.data.field).addClass('has-error');
                    $('#dr_'+json.data.field).focus();
                }
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}
// 电脑版和手机版切换
function dr_pc_or_mobile(url) {

    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });

    $.ajax({
        type: "GET",
        dataType: "json",
        url: '/index.php?s=api&c=api&m=client&at=select&url='+encodeURIComponent(url.replace(/http:\/\//, '')),
        success: function(json) {
            layer.close(loading);
            if (json.code) {
                var oss_url = json.data.sso;
                // 发送同步cookie
                for ( var i = 0; i < oss_url.length; i++){
                    $.ajax({
                        type: "GET",
                        url:oss_url[i],
                        dataType: "jsonp",
                        success: function(json){ },
                        error: function(){ }
                    });
                }
                dr_cmf_tips(1, json.msg);
                if (json.data.url) {
                    window.location.href = json.data.url;
                }
            } else {
                dr_cmf_tips(0, json.msg);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError)
        }
    });
}

function d_topinyin(name, from, letter) {
    var val = $("#dr_" + from).val();
    if ($("#dr_" + name).val()) {
        return false
    }
    $.get("/index.php?s=api&c=api&m=pinyin&name=" + val + "&rand=" + Math.random(),
        function(data) {
            $("#dr_" + name).val(data);
            if (letter) {
                $("#dr_letter").val(data.substr(0, 1))
            }
        })
}

// 多文件上传删除元素
function dr_file_remove(e) {
    $(e).parents(".files_row").remove();
}

// 删除文件
function dr_file_delete(e, id) {
    $.get("/index.php?s=api&c=file&m=file_delete&id=" + id + "&rand=" + Math.random(),
    function(data) {
        top.dr_cmf_tips(data.code, data.msg);
        if (data.code) {
            $(e).parents(".files_row").remove();
        }
    }, 'json');
}

// 多文件上传修改文件
function dr_file_edit(e) {
    var name_obj = $(e).parents(".files_row").find(".files_row_name");
    name_obj.focus();
    return;
}

// 显示ip信息
function dr_show_ip(name) {
    if (is_mobile_cms == 1) {
        width = height = '95%';
    } else {
        width = height = '70%';
    }
    var url = "https://www.baidu.com/s?wd="+$("#dr_"+name).val();
    layer.open({
        type: 2,
        title: '<i class="fa fa-home"></i> ' + lang['ip'],
        shadeClose: true,
        shade: 0,
        area: [width, height],
        content: url
    });
}

function dr_diy_func(name) {
    dr_cmf_tips(1, '这是一个自定义函数');
}

// 模块收藏
function dr_module_favorite(dir, id) {
    $.get("/index.php?is_ajax=1&s=api&app="+dir+"&c=module&m=favorite&id="+id, function(data){
        dr_cmf_tips(data.code, data.msg);
        if (data.code) {
            $('#module_favorite_'+id).html(data.data);
        }
    }, 'json');
}

// 模块支持反对
function dr_module_digg(dir, id, value) {
    $.get("/index.php?is_ajax=1&s=api&app="+dir+"&c=module&m=digg&id="+id+'&value='+value, function(data){
        dr_cmf_tips(data.code, data.msg);
        if (data.code) {
            $('#module_digg_'+id+'_'+value).html(data.data);
        }
    }, 'json');
}

// 选中支付方式
function dr_select_paytype(name) {
    $('#dr_payselect').val(name);
}

// 注册阅读网站协议
function dr_show_protocol() {
    layer.open({
        type: 2,
        title: lang['protocol'],
        shadeClose: true,
        area: ['70%', '70%'],
        content: '/index.php?s=member&c=api&m=protocol'
    });
}

function d_tips(name, status, code) {
    var obj = $("#dr_" + name + "_tips");
    var value = obj.html();
    if (!value) {
        obj.html("")
    }
    if (status) {
        if (code) {
            dr_cmf_tips(1, code)
        }
    } else {
        $("#dr_" + name).focus();
        if (code) {
            dr_cmf_tips(0, code)
        }
    }
}
function check_title(t) {
    var id = $("#dr_id").val();
    var val = $("#dr_title").val();
    var mod = $("#dr_module").val();
    $.get("/index.php?s=api&c=api&m=checktitle&title=" + val + "&module=" + mod + "&id=" + id+'&is_ajax=1',
        function(data) {
            if (data) {
                if (t == "1") {
                    dr_cmf_tips(0, data);
                } else {
                    $("#dr_title_tips").html(data);
                }
            } else {
                if (t == "1") {
                    //dr_cmf_tips(1, 'ok');
                } else {
                    $("#dr_title_tips").html("");
                }
            }
        })
}
function get_keywords(to) {
    var title = $("#dr_title").val();
    var module = $("#dr_module").val();
    if ($("#dr_"+to).val()) {
        return false
    }
    $.get("/index.php?s=api&c=api&m=getkeywords&title="+title+"&module="+module+'&is_ajax=1',
        function(data) {
            $("#dr_"+to).val(data);
            $("#dr_"+to).tagsinput('add', data);
        }
    );
}

function d_required(name) {
    if ($("#dr_" + name).val() == "") {
        d_tips(name, false);
        return true
    } else {
        d_tips(name, true);
        return false
    }
}
function d_isemail(name) {
    var val = $("#dr_" + name).val();
    var reg = /^[-_A-Za-z0-9]+@([_A-Za-z0-9]+\.)+[A-Za-z0-9]{2,3}$/;
    if (reg.test(val)) {
        d_tips(name, true);
        return false
    } else {
        d_tips(name, false);
        return true
    }
}
function d_isurl(name) {
    var val = $("#dr_" + name).val();
    var reg = /http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/;
    var Exp = new RegExp(reg);
    if (Exp.test(val) == true) {
        d_tips(name, true);
        return false
    } else {
        d_tips(name, false);
        return true
    }
}
function d_isdomain(name) {
    var val = $("#dr_" + name).val();
    if (val.indexOf("/") > 0) {
        d_tips(name, false);
        return true
    } else {
        d_tips(name, true);
        return false
    }
};

function dr_ajax_alert_error(HttpRequest, ajaxOptions, thrownError) {
    layer.closeAll('loading');
    if (typeof is_admin != "undefined" && is_admin == 1) {
        var msg = HttpRequest.responseText;
        if (!msg) {
            dr_cmf_tips(0, lang['error_admin']);
        } else {
            layer.open({
                type: 1,
                title: lang['error_admin'],
                fix:true,
                shadeClose: true,
                shade: 0,
                area: ['50%', '50%'],
                content: "<div style=\"padding:10px;\">"+msg+"</div>"
            });
        }
    } else {
        dr_cmf_tips(0, lang['error']);
    }

}
