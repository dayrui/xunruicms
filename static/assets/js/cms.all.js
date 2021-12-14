if(typeof jQuery == 'undefined'){
    window.alert("没有引用jquery库");
}

var cms_post_addfunc = new Array();
// js语言函数
function dr_lang(str) {

    if (typeof lang != "undefined" && typeof lang[str] != "undefined" && lang[str]) {
        return lang[str];
    }

    return str;
}


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
        var url = decodeURI(window.location.href);
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

// 时间戳转换
function dr_strtotime(datetime) {
    if (datetime.indexOf(" ") == -1) {
        datetime+= ' 00:00:00';
    }
    var tmp_datetime = datetime.replace(/:/g,'-');
    tmp_datetime = tmp_datetime.replace(/ /g,'-');
    var arr = tmp_datetime.split("-");
    var now = new Date(Date.UTC(arr[0],arr[1]-1,arr[2],arr[3]-8,arr[4],arr[5]));
    return parseInt(now.getTime()/1000);
}
// 主目录相对路径
function dr_get_web_dir() {
    if (typeof web_dir != "undefined" && web_dir) {
        return web_dir;
    }
    return '/';
}

// 是否有隐藏区域
function dr_isEllipsis(dom) {
	var checkDom = dom.cloneNode(), parent, flag;
	checkDom.style.width = dom.offsetWidth + 'px';
	checkDom.style.height = dom.offsetHeight + 'px';
	checkDom.style.overflow = 'auto';
	checkDom.style.position = 'absolute';
	checkDom.style.zIndex = -1;
	checkDom.style.opacity = 0;
	checkDom.style.whiteSpace = "nowrap";
	checkDom.innerHTML = dom.innerHTML;

	parent = dom.parentNode;
	parent.appendChild(checkDom);
	flag = checkDom.scrollWidth > checkDom.offsetWidth;
	parent.removeChild(checkDom);
	return flag;
};

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

function dr_ftable_mydelete(e){
    var ob = $(e);
    ob.parent().find('.form-control2').val('0');
    ob.parent().find('.ftable-show').hide();
    ob.parent().find('.ftable-delete').hide();
}

function dr_ftable_myshow(e){
    var ob = $(e);
    var url = ob.parent().find('.form-control3').val();
    dr_preview_image(url);
}

function dr_ftable_myfileinput (e, url){
    var ob = $(e);
    var c = 1;
    layer.open({
        type: 2,
        title: '<i class=\"fa fa-folder-open\"></i>',
        fix:true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: ['50%', '50%'],
        btn: [ dr_lang('确定') ],
        yes: function(index, layero){
            var body = layer.getChildFrame('body', index);
            // 延迟加载
            var loading = layer.load(2, {
                time: 10000000
            });
            $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find('#myform').serialize(),
            success: function(json2) {
                layer.close(loading);
                if (json2.code == 1) {
                    layer.close(index);
                    var v = json2.data.result[0];
                    ob.parent().find('.form-control2').val(v.id);
                    ob.parent().find('.form-control3').val(v.url);
                    ob.parent().find('.ftable-show').show();
                    ob.parent().find('.ftable-delete').show();
                    dr_tips(1, json2.msg);
                } else {
                    dr_tips(0, json2.msg);

                }
                return false;
            }
        });

            return false;
        },
        success: function(layero, index){
            // 主要用于权限验证
            var body = layer.getChildFrame('body', index);
            var json2 = $(body).html();
            if (json2.indexOf('\"code\":0') > 0 && jso2n.length < 150){
                var obj = JSON.parse(json2);
                layer.close(index);
                dr_tips(0, obj.msg);
            }
            if (json2.indexOf('\"code\":1') > 0 && json2.length < 150){
                var obj = JSON.parse(json2);
                layer.close(index);
                dr_tips(1, obj.msg);
            }
        },
        content: url+'&is_ajax=1'
    });
}

// 显示视频
function dr_preview_video(file) {

    var width = '450px';
    var height = '330px';
    var att = 'width="350" height="280"';

    if (is_mobile_cms == 1) {
        width = height = '90%';
        var att = 'width="90%" height="200"';
    }
    layer.alert('<p style="text-align: center"><a href="'+file+'" target="_blank">'+file+'</a></p><p style="text-align: center"> <video class="video-js vjs-default-skin" controls="" preload="auto" '+att+'><source src="'+file+'" type="video/mp4"/></video>\n</p>', {
        shade: 0,
        //scrollbar: false,
        shadeClose: true,
        title: '',
        area: [width, width],
        btn: []
    });
}

// 显示图片
function dr_preview_image(file) {

    var width = '400px';
    var height = '300px';

    if (is_mobile_cms == 1) {
        width = height = '80%';
    }
    top.layer.alert('<p style="text-align: center"><a href="'+file+'" target="_blank">'+file+'</a></p><p style="text-align: center"><a href="'+file+'" target="_blank"><img style="max-width:100%" src="'+file+'?'+Date.parse(new Date())+'"></a></p>', {
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

    var width = '400px';
    var height = '200px';
    if (is_mobile_cms == 1) {
        width = height = '90%';
    }
    layer.alert('<div style="text-align: center;"><a href="'+url+'" target="_blank">'+url+'</a></div>', {
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
        title: dr_lang('查看'),
        area: [width+'%', height+'%'],
        content: '<div style="padding:20px;overflow-y:scroll">'+(msg)+'</div>'
    });
}
function dr_tips(code, msg, time) {

    if (!time || time == "undefined") {
        time = 3000;
    } else {
        time = time * 1000;
    }

    var is_tip = 0;
    if (time < 0) {
        is_tip = 1;
    } else if (code == 0 && msg.length > 15) {
        is_tip = 1;
    }

    if (is_tip) {
        if (code == 0) {
            layer.alert(msg, {
                shade: 0,
                title: "",
                icon: 2
            })
        } else {
            layer.alert(msg, {
                shade: 0,
                title: "",
                icon: 1
            })
        }
    } else {
        var tip = '<i class="fa fa-info-circle"></i>';
        //var theme = 'teal';
        if (code >= 1) {
            tip = '<i class="fa fa-check-circle"></i>';
            //theme = 'lime';
        } else if (code == 0) {
            tip = '<i class="fa fa-times-circle"></i>';
            //theme = 'ruby';
        }
        layer.msg(tip+'&nbsp;&nbsp;'+msg, {time: time});
    }

}
function dr_cmf_tips(code, msg, time) {
    dr_tips(code, msg, time);
}

// 窗口提交
function dr_iframe(type, url, width, height, rt) {

    var title = '';
    if (type == 'add') {
        title = '<i class="fa fa-plus"></i> '+dr_lang('添加');
    } else if (type == 'edit') {
        title = '<i class="fa fa-edit"></i> '+dr_lang('修改');
    } else if (type == 'send') {
        title = '<i class="fa fa-send"></i> '+dr_lang('推送');
    } else if (type == 'save') {
        title = '<i class="fa fa-save"></i> '+dr_lang('保存');
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
        maxmin: true,
        resize: true,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        btn: [dr_lang('确定'), dr_lang('取消')],
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
                        if (json.data.jscode) {
                            eval(json.data.jscode);
                            return;
                        } else if (json.data.tourl) {
                            setTimeout("window.location.href = '"+json.data.tourl+"'", 2000);
                        } else {
                            if (rt == 'nogo') {

                            } else {
                                setTimeout("window.location.reload(true)", 2000);
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
                        dr_cmf_tips(1, json.msg);
                    } else {
                        $(body).find('#dr_row_'+json.data.field).addClass('has-error');
                        dr_cmf_tips(0, json.msg, json.data.time);
                    }
                    return false;
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
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
        title = '<i class="fa fa-search"></i> '+dr_lang('查看');
    } else if (type == 'edit') {
        title = '<i class="fa fa-edit"></i> '+dr_lang('修改');
    } else if (type == 'code') {
        title = '<i class="fa fa-code"></i> '+dr_lang('代码');
    } else if (type == 'cart') {
        title = '<i class="fa fa-shopping-cart"></i> '+dr_lang('交易记录');
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
        content: dr_get_web_dir()+'index.php?s=api&c=emoji&name='+name
    });
}

// ajax 操作确认 并跳转
function dr_ajax_confirm_url(url, msg, tourl) {
    layer.confirm(
        msg,
        {
            icon: 3,
            shade: 0,
            title: dr_lang('提示'),
            btn: [dr_lang('确定'), dr_lang('取消')]
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
                        } else if (tourl) {
                            setTimeout("window.location.href = '"+tourl+"'", 2000);
                        }
                    }
                    dr_cmf_tips(json.code, json.msg);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_cmf_tips(json.code, json.msg, json.data.time);
            if (json.data.url) {
                setTimeout("window.location.href = '"+json.data.url+"'", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_cmf_tips(json.code, json.msg, json.data.time);
            if (json.data.url) {
                setTimeout("window.location.href = '"+json.data.url+"'", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_tips(json.code, json.msg, json.data.time);
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
            title: dr_lang('提示'),
            btn: [dr_lang('确定'), dr_lang('取消')]
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
                    dr_cmf_tips(json.code, json.msg, json.data.time);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            title: dr_lang('提示'),
            btn: [dr_lang('确定'), dr_lang('取消')]
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
                    dr_cmf_tips(json.code, json.msg, json.data.time);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
                }
            });
        });
}

// ajax提交
function dr_ajax_submit(url, form, time, go) {

    var flen = $('[id='+form+']').length;
    // 验证id是否存在
    if (flen == 0) {
        dr_cmf_tips(0, dr_lang('表单id属性不存在') + ' ('+form+')');
        return;
    }
    // 验证重复
    if (flen > 1) {
        dr_cmf_tips(0, dr_lang('表单id属性已重复定义') + ' ('+form+')');
        return;
    }

    // 验证必填项管理员
    var tips_obj = $('#'+form).find('[name=is_tips]');
    if (tips_obj.val() == 'required') {
        tips_obj.val('');
    }
    if ($('#'+form).find('[name=is_admin]').val() == 1) {
        $('#'+form).find('.dr_required').each(function () {
            if (!$(this).val()) {
                tips_obj.val('required');
            }
        });
    }

    var tips = tips_obj.val();
    if (tips) {
        if (tips == 'required') {
            tips = '有必填字段未填写，确认提交吗？';
        }
        layer.confirm(
        tips,
        {
            icon: 3,
            shade: 0,
            title: dr_lang('提示'),
            btn: [dr_lang('确定'), dr_lang('取消')]
        }, function(index){
            dr_post_submit(url, form, time, go);
        });
    } else {
        dr_post_submit(url, form, time, go);
    }
}

// 提交时追加执行函数
function dr_post_addfunc(func) {
    cms_post_addfunc.push(func);
}

// 处理post提交
function dr_post_submit(url, form, time, go) {

    var p = url.split('/');
    if ((p[0] == 'http:' || p[0] == 'https:') && document.location.protocol != p[0]) {
        alert('当前提交的URL是'+p[0]+'模式，请使用'+document.location.protocol+'模式访问再提交');
        return;
    }

    url = url.replace(/&page=\d+&page/g, '&page');

    $("#"+form+' .form-group').removeClass('has-error');
    var cms_post_dofunc = "";
    for(var i = 0; i < cms_post_addfunc.length; i++) {
        var cms_post_dofunc = cms_post_addfunc[i];
        var rst = cms_post_dofunc();
        if (rst) {
            dr_cmf_tips(0, rst);
            return;
        }
    }


    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });
    $.ajax({
        type: "POST",
        dataType: "json",
        url: url,
        data: $("#"+form).serialize(),
        success: function(json) {
            layer.close(loading);
            if (json.code) {
                dr_cmf_tips(1, json.msg, json.data.time);
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
                if (json.data.button) {
                    layer.alert(json.msg, {
                        shade: 0,
                        shade: 0,
                        title: "",
                        btn: [json.data.button.name],
                        icon: 2
                    }, function(){
                        window.open(json.data.button.url, '_blank').location;
                    })
                } else {
                    dr_cmf_tips(0, json.msg, json.data.time);
                }
                $('.fc-code img').click();
                if (json.data.field) {
                    $('#dr_row_'+json.data.field).addClass('has-error');
                    $('#dr_'+json.data.field).focus();
                }
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}
// 退出登录
function dr_loginout(msg) {
    $.ajax({type: "GET",dataType:"json", url: dr_get_web_dir()+'index.php?s=api&c=api&m=loginout',
        success: function(json) {
            var oss_url = json.data.sso;
            // 发送同步登录信息
            for ( var i = 0; i < oss_url.length; i++){
                var result = fetchJsonp(oss_url[i], {
				  jsonpCallback: 'callback',
				  timeout: 3000
				})
				result.then(function(response) {
				  return response.json()
				}).then(function(json) {
					console.log(JSON.stringify(json));
				})['catch'](function(ex) {
				  console.log('failed:' + ex);
				});
            }
            dr_cmf_tips(1, json.msg, json.data.time);
            setTimeout('window.location.href="'+json.data.url+'"', 2000);
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}
// ajax提交登录或者注册
function dr_ajax_member(url, form) {

    var flen = $('[id='+form+']').length;
    // 验证id是否存在
    if (flen == 0) {
        dr_cmf_tips(0, dr_lang('表单id属性不存在') + ' ('+form+')');
        return;
    }
    // 验证重复
    if (flen > 1) {
        dr_cmf_tips(0, dr_lang('表单id属性已重复定义') + ' ('+form+')');
        return;
    }

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
				   var result = fetchJsonp(oss_url[i], {
                      jsonpCallback: 'callback',
                      timeout: 3000
                    })
                    result.then(function(response) {
                      return response.json()
                    }).then(function(json) {
                        console.log(JSON.stringify(json));
                    })['catch'](function(ex) {
                      console.log('failed:' + ex);
                    });

                }
                if (json.data.url) {
                    if (oss_url.length > 2) {
                        dr_cmf_tips(1, json.msg, json.data.time);
                        setTimeout('window.location.href="'+json.data.url+'"', 3000);
                    } else {
                        window.location.href = json.data.url;
                    }
                }
            } else {
                dr_cmf_tips(0, json.msg, json.data.time);
                $('.fc-code img').click();
                if (json.data.field) {
                    $('#dr_row_'+json.data.field).addClass('has-error');
                    $('#dr_'+json.data.field).focus();
                }
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
    $.get(dr_get_web_dir()+'index.php?is_ajax=1&s=api&c=api&m=ip_address&value='+$('#dr_'+name).val(), function(html){
        layer.alert(html, {
            shade: 0,
            title: "",
            icon: 1
        })
    }, 'text');
}

function dr_diy_func(name) {
    dr_cmf_tips(1, '这是一个自定义函数');
}

// 模块收藏
function dr_module_favorite(dir, id) {
    $.get(dr_get_web_dir()+"index.php?is_ajax=1&s=api&app="+dir+"&c=module&m=favorite&id="+id, function(data){
        dr_cmf_tips(data.code, data.msg);
        if (data.code) {
            $('#module_favorite_'+id).html(data.data);
        }
    }, 'json');
}

// 模块支持反对
function dr_module_digg(dir, id, value) {
    $.get(dr_get_web_dir()+"index.php?is_ajax=1&s=api&app="+dir+"&c=module&m=digg&id="+id+'&value='+value, function(data){
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
        title: dr_lang('用户注册协议'),
        shadeClose: true,
        area: ['70%', '70%'],
        content: dr_get_web_dir()+'index.php?s=member&c=api&m=protocol'
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
    $.get(dr_get_web_dir()+"index.php?s=api&c=api&m=checktitle&title=" + val + "&module=" + mod + "&id=" + id+'&is_ajax=1',
    function(data) {
        if (data) {
            dr_cmf_tips(0, data);
        }
    });
}
function get_keywords(to) {
    var title = $("#dr_title").val();
    var module = $("#dr_module").val();
    if ($("#dr_"+to).val()) {
        return false
    }
    $.get(dr_get_web_dir()+"index.php?s=api&c=api&m=getkeywords&title="+title+"&module="+module+'&is_ajax=1',
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

function dr_ajax_alert_error(HttpRequest, ajax, thrownError) {
    layer.closeAll('loading');
    if (typeof is_admin != "undefined" && is_admin) {
        var msg = HttpRequest.responseText;
        var html = '请求状态：'+HttpRequest.status+'<br>';
        html+= '请求方式：'+ajax.type+'<br>';
        html+= '请求地址：'+ajax.url+'<br>';
        if (!msg) {
            msg = thrownError;
        }
        if (is_admin == '1') {
            layer.open({
                type: 1,
                title: dr_lang('系统崩溃，请检查错误日志'),
                fix:true,
                shadeClose: true,
                shade: 0,
                area: ['50%', '50%'],
                btn: [dr_lang('查看日志')],
                yes: function(index, layero) {
                    layer.close(index);
                    dr_iframe_show(dr_lang('错误日志'), admin_file+"?c=error&m=log_show");
                },
                content: "<div style=\"padding:10px;border-bottom: 1px solid #eee;\">"+html+"</div><div style=\"padding:10px;\">"+msg+"</div>"
            });
        } else {
            layer.open({
                type: 1,
                title: dr_lang('系统崩溃，请检查错误日志'),
                fix:true,
                shadeClose: true,
                shade: 0,
                area: ['50%', '50%'],
                content: "<div style=\"padding:10px;border-bottom: 1px solid #eee;\">"+html+"</div><div style=\"padding:10px;\">"+msg+"</div>"
            });
        }
    } else {
        dr_cmf_tips(0, dr_lang('系统错误'));
    }
}

// 初始化滚动区域
function dr_slimScroll_init(div, ht) {

    if (!$().slimScroll) {
        return;
    }

    var obj = div+' .scroller';
    var obj2 = div+' .scroller_body';

    if ($(obj).attr("data-inited") === "1") { // destroy existing instance before updating the height
        $(obj).removeAttr("data-inited");
        $(obj).removeAttr("style");

        var attrList = {};

        // store the custom attribures so later we will reassign.
        if ($(obj).attr("data-handle-color")) {
            attrList["data-handle-color"] = $(obj).attr("data-handle-color");
        }
        if ($(obj).attr("data-wrapper-class")) {
            attrList["data-wrapper-class"] = $(obj).attr("data-wrapper-class");
        }
        if ($(obj).attr("data-rail-color")) {
            attrList["data-rail-color"] = $(obj).attr("data-rail-color");
        }
        if ($(obj).attr("data-always-visible")) {
            attrList["data-always-visible"] = $(obj).attr("data-always-visible");
        }
        if ($(obj).attr("data-rail-visible")) {
            attrList["data-rail-visible"] = $(obj).attr("data-rail-visible");
        }

        $(obj).slimScroll({
            wrapperClass: ($(obj).attr("data-wrapper-class") ? $(obj).attr("data-wrapper-class") : 'slimScrollDiv'),
            destroy: true
        });

        var the = $(obj);

        // reassign custom attributes
        $.each(attrList, function(key, value) {
            the.attr(key, value);
        });

    }

    var nht = $(obj2).height();
    var height;

    if (nht > ht) {
        height = ht;
    } else {
        height = 'auto';
    }

    $(obj).slimScroll({
        allowPageScroll: false, // allow page scroll when the element scroll is ended
        size: '7px',
        color: ($(obj).attr("data-handle-color") ? $(obj).attr("data-handle-color") : '#bbb'),
        wrapperClass: ($(obj).attr("data-wrapper-class") ? $(obj).attr("data-wrapper-class") : 'slimScrollDiv'),
        railColor: ($(obj).attr("data-rail-color") ? $(obj).attr("data-rail-color") : '#eaeaea'),
        position: 'right',
        height: height,
        alwaysVisible: ($(obj).attr("data-always-visible") == "1" ? true : false),
        railVisible: ($(obj).attr("data-rail-visible") == "1" ? true : false),
        disableFadeOut: true
    });

    $(obj).attr("data-inited", "1");

}

// fetchJsonp
(function (global, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['exports', 'module'], factory);
  } else if (typeof exports !== 'undefined' && typeof module !== 'undefined') {
    factory(exports, module);
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, mod);
    global.fetchJsonp = mod.exports;
  }
})(this, function (exports, module) {
  'use strict';

  var defaultOptions = {
    timeout: 5000,
    jsonpCallback: 'callback',
    jsonpCallbackFunction: null
  };

  function generateCallbackFunction() {
    return 'jsonp_' + Date.now() + '_' + Math.ceil(Math.random() * 100000);
  }

  function clearFunction(functionName) {
    try {
      delete window[functionName];
    } catch (e) {
      window[functionName] = undefined;
    }
  }

  function removeScript(scriptId) {
    var script = document.getElementById(scriptId);
    if (script) {
      document.getElementsByTagName('head')[0].removeChild(script);
    }
  }

  function fetchJsonp(_url) {
    var options = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];

    var url = _url;
    var timeout = options.timeout || defaultOptions.timeout;
    var jsonpCallback = options.jsonpCallback || defaultOptions.jsonpCallback;

    var timeoutId = undefined;

    return new Promise(function (resolve, reject) {
      var callbackFunction = options.jsonpCallbackFunction || generateCallbackFunction();
      var scriptId = jsonpCallback + '_' + callbackFunction;

      window[callbackFunction] = function (response) {
        resolve({
          ok: true,
          json: function json() {
            return Promise.resolve(response);
          }
        });

        if (timeoutId) clearTimeout(timeoutId);

        removeScript(scriptId);

        clearFunction(callbackFunction);
      };

      url += url.indexOf('?') === -1 ? '?' : '&';

      var jsonpScript = document.createElement('script');
      jsonpScript.setAttribute('src', '' + url + jsonpCallback + '=' + callbackFunction);
      if (options.charset) {
        jsonpScript.setAttribute('charset', options.charset);
      }
      jsonpScript.id = scriptId;
      document.getElementsByTagName('head')[0].appendChild(jsonpScript);

      timeoutId = setTimeout(function () {
        reject(new Error('JSONP request to ' + _url + ' timed out'));

        clearFunction(callbackFunction);
        removeScript(scriptId);
        window[callbackFunction] = function () {
          clearFunction(callbackFunction);
        };
      }, timeout);

      jsonpScript.onerror = function () {
        reject(new Error('JSONP request to ' + _url + ' failed'));

        clearFunction(callbackFunction);
        removeScript(scriptId);
        if (timeoutId) clearTimeout(timeoutId);
      };
    });
  }

  module.exports = fetchJsonp;
});
