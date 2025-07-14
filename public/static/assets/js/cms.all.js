if(typeof jQuery == 'undefined'){
    console.log("没有引用jquery库");
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
    ob.parent().find('.form-control-file').val('0');
    ob.parent().find('.form-control-link').val('0');
    ob.parent().find('.form-control-preview').val('');
    ob.parent().find('.ftable-show').hide();
    ob.parent().find('.ftable-delete').hide();
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
                    // token 更新
                    if (json2.token) {
                        var token = json2.token;
                        $(body).find("#myform input[name='"+token.name+"']").val(token.value);
                    }
                    if (json2.code == 1) {
                        layer.close(index);
                        var v = json2.data.result[0];
                        ob.parent().find('.form-control2').val(v.id);
                        ob.parent().find('.form-control-link').val(v.url);
                        ob.parent().find('.form-control-preview').val(v.url);
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
            dr_iframe_error(layer, index, 1);
        },
        content: url+'&is_iframe=1'
    });
}

function dr_ftable_myshow(e){
    var ob = $(e);
    var url = ob.parent().find('.form-control-link').val();
    var preview = ob.parent().find('.form-control-preview').val();

    var width = '400px';
    var height = '300px';

    if (is_mobile_cms == 1) {
        width = height = '80%';
    }
    var dev = '';
    if (typeof is_cms_dev != "undefined" && is_cms_dev) {
        dev = '<p style="text-align: center"><a href="'+preview+'" target="_blank">'+preview+'</a></p>';
    }
    top.layer.alert(dev+'<p style="text-align: center"><a href="'+url+'" target="_blank"><img style="max-width:100%" src="'+preview+'?'+Date.parse(new Date())+'"></a></p>', {
        shade: 0,
        //scrollbar: false,
        shadeClose: true,
        title: '',
        area: [width, width],
        btn: []
    });
}

// 多行文本内容
function dr_ftable_textareainput(id){

    var val = $('#'+id).val();

    var width = '50%';
    var height = '50%';

    if (is_mobile_cms == 1) {
        width = height = '90%';
    }

    layer.open({
        type: 1,
        title: '<i class=\"fa fa-edit\"></i>',
        fix:true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        btn: [ dr_lang('确定') ],
        yes: function(index, layero){
            $('#'+id).val($('#form-'+id).val());
            layer.close(index);
        },
        content: '<div style="padding:10px;height:100%;"><textarea style="height:100%;" id="form-'+id+'" class="form-control">'+val+'</textarea></div>'
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
    var dev = '';
    if (typeof is_cms_dev != "undefined" && is_cms_dev) {
        dev = '<p style="text-align: center"><a href="'+file+'" target="_blank">'+file+'</a></p>';
    }
    layer.alert(dev+'<p style="text-align: center"> <video class="video-js vjs-default-skin" controls="" preload="auto" '+att+'><source src="'+file+'" type="video/mp4"/></video>\n</p>', {
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
    var dev = '';
    if (typeof is_cms_dev != "undefined" && is_cms_dev) {
        dev = '<p style="text-align: center"><a href="'+file+'" target="_blank">'+file+'</a></p>';
    }
    top.layer.alert(dev+'<p style="text-align: center"><a href="'+file+'" target="_blank"><img style="max-width:100%" src="'+file+'?'+Date.parse(new Date())+'"></a></p>', {
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
    } else if (code == 0) {
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
        layer.msg(msg, {time: time});
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
                    // token 更新
                    if (json.token) {
                        var token = json.token;
                        $(body).find("#myform input[name='"+token.name+"']").val(token.value);
                    }
                    if (json.code) {
                        if (rt == 'noclose') {
                            // 不关闭动作
                        } else {
                            layer.close(index);
                        }
                        if (json.data.jscode) {
                            eval(json.data.jscode);
                            return;
                        } else if (json.data.tourl) {
                            setTimeout("window.location.href = '"+json.data.tourl+"'", 2000);
                        } else {
                            if (rt == 'nogo' || rt == 'noclose') {
                                // 不刷新动作
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
            dr_iframe_error(layer, index, 0);
        },
        content: url+'&is_iframe=1'
    });
}
// ajax 显示内容
function dr_iframe_show(type, url, width, height, is_close ) {

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
        maxmin: true,
        scrollbar: false,
        shadeClose: true,
        shade: 0,
        area: [width, height],
        success: function(layero, index){
            // 主要用于后台权限验证
            dr_iframe_error(layer, index, 0);
        },end: function(){
            if (is_close == "load") {
                window.location.reload(true)
            }
        },
        content: url+'&is_iframe=1'
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
                        if (json.data.jscode) {
                            eval(json.data.jscode);
                            return;
                        } else if (json.data.url) {
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
                    // token 更新
                    if (json.token) {
                        var token = json.token;
                        $("#myform input[name='"+token.name+"']").val(token.value);
                    }
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
                        if (json.data.jscode) {
                            eval(json.data.jscode);
                            return;
                        } else if (json.data.url) {
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
                    // token 更新
                    if (json.token) {
                        var token = json.token;
                        $("#myform input[name='"+token.name+"']").val(token.value);
                    }
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
            tips = dr_lang('有必填字段未填写，确认提交吗？');
        }
        layer.confirm(
            tips,
            {
                icon: 3,
                shade: 0,
                title: dr_lang('提示'),
                btn: [dr_lang('确定'), dr_lang('取消')]
            }, function(indedr_ajax_submitx){
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

    // https不统一时，取根域名
    var p = url.split('/');
    if ((p[0] == 'http:' || p[0] == 'https:') && document.location.protocol != p[0]) {
        const parsedUrl = new URL(url);
        url = parsedUrl.pathname + parsedUrl.search + parsedUrl.hash;
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
            // token 更新
            if (json.token) {
                var token = json.token;
                $("#"+form+" input[name='"+token.name+"']").val(token.value);
            }
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
                // 判断是否来自后台
                if (typeof admin_file != "undefined" && admin_file) {
                    dr_sync_cache(0); // 自动更新缓存
                }
            } else {
                if (json.data.button) {
                    layer.alert(json.msg, {
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
                $('#'+form+' .fc-code img').click();
                if (json.data.field) {
                    $('#'+form+' #dr_row_'+json.data.field).addClass('has-error');
                    $('#'+form+' #dr_'+json.data.field).focus();
                }
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}
// 退出登录
function dr_loginout(url) {
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
                })['catch'](function(ex) {
                });
            }
            dr_cmf_tips(1, json.msg, json.data.time);
            if (url && url != dr_lang('退出成功')) {
                setTimeout("window.location.href = '"+url+"'", 2000);
            } else {
                setTimeout('window.location.href="'+json.data.url+'"', 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}
// ajax提交登录或者注册
function dr_ajax_member(url, form, go) {

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
            // token 更新
            if (json.token) {
                var token = json.token;
                $("#"+form+" input[name='"+token.name+"']").val(token.value);
            }
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
                    })['catch'](function(ex) {
                    });
                }
                if (typeof go != "undefined" && go && go.length > 2) {
                    dr_cmf_tips(1, json.msg, json.data.time);
                    setTimeout('window.location.href="'+go+'"', 3000);
                } else if (json.data.url) {
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

function dr_iframe_error(layer, index, is_show = 0) {
    var body = layer.getChildFrame('body', index);
    var json = $(body).html();
    json = json.replace(/<.*?>/g,"");//去掉标签
    if (json.indexOf('"code":0') > 0 && json.length < 150){
        var obj = JSON.parse(json);
        layer.close(index);
        dr_cmf_tips(0, obj.msg);
    }
    if (is_show == 1 && json.indexOf('"code":1') > 0 && json.length < 150){
        var obj = JSON.parse(json);
        layer.close(index);
        dr_tips(1, obj.msg);
    }
}

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


function dr_progress_start(msg) {
    NProgress.start();
    layer.msg(msg, {
        icon: 16
        ,shade: 0.3
        ,time: false
    });
}
function dr_progress_end() {
    NProgress.done();
    layer.closeAll()
}
function dr_progress(msg, percent) {
    NProgress.set(percent / 100);
}

/* NProgress, (c) 2013, 2014 Rico Sta. Cruz - http://ricostacruz.com/nprogress
 * @license MIT */

;(function(root, factory) {

    if (typeof define === 'function' && define.amd) {
        define(factory);
    } else if (typeof exports === 'object') {
        module.exports = factory();
    } else {
        root.NProgress = factory();
    }

})(this, function() {
    var NProgress = {};

    NProgress.version = '0.2.0';

    var Settings = NProgress.settings = {
        minimum: 0.08,
        easing: 'ease',
        positionUsing: '',
        speed: 200,
        trickle: true,
        trickleRate: 0.02,
        trickleSpeed: 800,
        showSpinner: true,
        barSelector: '[role="bar"]',
        spinnerSelector: '[role="spinner"]',
        parent: 'body',
        template: '<div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div>'
    };

    /**
     * Updates configuration.
     *
     *     NProgress.configure({
     *       minimum: 0.1
     *     });
     */
    NProgress.configure = function(options) {
        var key, value;
        for (key in options) {
            value = options[key];
            if (value !== undefined && options.hasOwnProperty(key)) Settings[key] = value;
        }

        return this;
    };

    /**
     * Last number.
     */

    NProgress.status = null;

    /**
     * Sets the progress bar status, where `n` is a number from `0.0` to `1.0`.
     *
     *     NProgress.set(0.4);
     *     NProgress.set(1.0);
     */

    NProgress.set = function(n) {
        var started = NProgress.isStarted();

        n = clamp(n, Settings.minimum, 1);
        NProgress.status = (n === 1 ? null : n);

        var progress = NProgress.render(!started),
            bar      = progress.querySelector(Settings.barSelector),
            speed    = Settings.speed,
            ease     = Settings.easing;

        progress.offsetWidth; /* Repaint */

        queue(function(next) {
            // Set positionUsing if it hasn't already been set
            if (Settings.positionUsing === '') Settings.positionUsing = NProgress.getPositioningCSS();

            // Add transition
            css(bar, barPositionCSS(n, speed, ease));

            if (n === 1) {
                // Fade out
                css(progress, {
                    transition: 'none',
                    opacity: 1
                });
                progress.offsetWidth; /* Repaint */

                setTimeout(function() {
                    css(progress, {
                        transition: 'all ' + speed + 'ms linear',
                        opacity: 0
                    });
                    setTimeout(function() {
                        NProgress.remove();
                        next();
                    }, speed);
                }, speed);
            } else {
                setTimeout(next, speed);
            }
        });

        return this;
    };

    NProgress.isStarted = function() {
        return typeof NProgress.status === 'number';
    };

    /**
     * Shows the progress bar.
     * This is the same as setting the status to 0%, except that it doesn't go backwards.
     *
     *     NProgress.start();
     *
     */
    NProgress.start = function() {
        if (!NProgress.status) NProgress.set(0);

        var work = function() {
            setTimeout(function() {
                if (!NProgress.status) return;
                NProgress.trickle();
                work();
            }, Settings.trickleSpeed);
        };

        if (Settings.trickle) work();

        return this;
    };

    /**
     * Hides the progress bar.
     * This is the *sort of* the same as setting the status to 100%, with the
     * difference being `done()` makes some placebo effect of some realistic motion.
     *
     *     NProgress.done();
     *
     * If `true` is passed, it will show the progress bar even if its hidden.
     *
     *     NProgress.done(true);
     */

    NProgress.done = function(force) {
        if (!force && !NProgress.status) return this;

        return NProgress.inc(0.3 + 0.5 * Math.random()).set(1);
    };

    /**
     * Increments by a random amount.
     */

    NProgress.inc = function(amount) {
        var n = NProgress.status;

        if (!n) {
            return NProgress.start();
        } else {
            if (typeof amount !== 'number') {
                amount = (1 - n) * clamp(Math.random() * n, 0.1, 0.95);
            }

            n = clamp(n + amount, 0, 0.994);
            return NProgress.set(n);
        }
    };

    NProgress.trickle = function() {
        return NProgress.inc(Math.random() * Settings.trickleRate);
    };

    /**
     * Waits for all supplied jQuery promises and
     * increases the progress as the promises resolve.
     *
     * @param $promise jQUery Promise
     */
    (function() {
        var initial = 0, current = 0;

        NProgress.promise = function($promise) {
            if (!$promise || $promise.state() === "resolved") {
                return this;
            }

            if (current === 0) {
                NProgress.start();
            }

            initial++;
            current++;

            $promise.always(function() {
                current--;
                if (current === 0) {
                    initial = 0;
                    NProgress.done();
                } else {
                    NProgress.set((initial - current) / initial);
                }
            });

            return this;
        };

    })();

    /**
     * (Internal) renders the progress bar markup based on the `template`
     * setting.
     */

    NProgress.render = function(fromStart) {
        if (NProgress.isRendered()) return document.getElementById('nprogress');

        addClass(document.documentElement, 'nprogress-busy');

        var progress = document.createElement('div');
        progress.id = 'nprogress';
        progress.innerHTML = Settings.template;

        var bar      = progress.querySelector(Settings.barSelector),
            perc     = fromStart ? '-100' : toBarPerc(NProgress.status || 0),
            parent   = document.querySelector(Settings.parent),
            spinner;

        css(bar, {
            transition: 'all 0 linear',
            transform: 'translate3d(' + perc + '%,0,0)'
        });

        if (!Settings.showSpinner) {
            spinner = progress.querySelector(Settings.spinnerSelector);
            spinner && removeElement(spinner);
        }

        if (parent != document.body) {
            addClass(parent, 'nprogress-custom-parent');
        }

        parent.appendChild(progress);
        return progress;
    };

    /**
     * Removes the element. Opposite of render().
     */

    NProgress.remove = function() {
        removeClass(document.documentElement, 'nprogress-busy');
        removeClass(document.querySelector(Settings.parent), 'nprogress-custom-parent');
        var progress = document.getElementById('nprogress');
        progress && removeElement(progress);
    };

    /**
     * Checks if the progress bar is rendered.
     */

    NProgress.isRendered = function() {
        return !!document.getElementById('nprogress');
    };

    /**
     * Determine which positioning CSS rule to use.
     */

    NProgress.getPositioningCSS = function() {
        // Sniff on document.body.style
        var bodyStyle = document.body.style;

        // Sniff prefixes
        var vendorPrefix = ('WebkitTransform' in bodyStyle) ? 'Webkit' :
            ('MozTransform' in bodyStyle) ? 'Moz' :
                ('msTransform' in bodyStyle) ? 'ms' :
                    ('OTransform' in bodyStyle) ? 'O' : '';

        if (vendorPrefix + 'Perspective' in bodyStyle) {
            // Modern browsers with 3D support, e.g. Webkit, IE10
            return 'translate3d';
        } else if (vendorPrefix + 'Transform' in bodyStyle) {
            // Browsers without 3D support, e.g. IE9
            return 'translate';
        } else {
            // Browsers without translate() support, e.g. IE7-8
            return 'margin';
        }
    };

    /**
     * Helpers
     */

    function clamp(n, min, max) {
        if (n < min) return min;
        if (n > max) return max;
        return n;
    }

    /**
     * (Internal) converts a percentage (`0..1`) to a bar translateX
     * percentage (`-100%..0%`).
     */

    function toBarPerc(n) {
        return (-1 + n) * 100;
    }


    /**
     * (Internal) returns the correct CSS for changing the bar's
     * position given an n percentage, and speed and ease from Settings
     */

    function barPositionCSS(n, speed, ease) {
        var barCSS;

        if (Settings.positionUsing === 'translate3d') {
            barCSS = { transform: 'translate3d('+toBarPerc(n)+'%,0,0)' };
        } else if (Settings.positionUsing === 'translate') {
            barCSS = { transform: 'translate('+toBarPerc(n)+'%,0)' };
        } else {
            barCSS = { 'margin-left': toBarPerc(n)+'%' };
        }

        barCSS.transition = 'all '+speed+'ms '+ease;

        return barCSS;
    }

    /**
     * (Internal) Queues a function to be executed.
     */

    var queue = (function() {
        var pending = [];

        function next() {
            var fn = pending.shift();
            if (fn) {
                fn(next);
            }
        }

        return function(fn) {
            pending.push(fn);
            if (pending.length == 1) next();
        };
    })();

    /**
     * (Internal) Applies css properties to an element, similar to the jQuery
     * css method.
     *
     * While this helper does assist with vendor prefixed property names, it
     * does not perform any manipulation of values prior to setting styles.
     */

    var css = (function() {
        var cssPrefixes = [ 'Webkit', 'O', 'Moz', 'ms' ],
            cssProps    = {};

        function camelCase(string) {
            return string.replace(/^-ms-/, 'ms-').replace(/-([\da-z])/gi, function(match, letter) {
                return letter.toUpperCase();
            });
        }

        function getVendorProp(name) {
            var style = document.body.style;
            if (name in style) return name;

            var i = cssPrefixes.length,
                capName = name.charAt(0).toUpperCase() + name.slice(1),
                vendorName;
            while (i--) {
                vendorName = cssPrefixes[i] + capName;
                if (vendorName in style) return vendorName;
            }

            return name;
        }

        function getStyleProp(name) {
            name = camelCase(name);
            return cssProps[name] || (cssProps[name] = getVendorProp(name));
        }

        function applyCss(element, prop, value) {
            prop = getStyleProp(prop);
            element.style[prop] = value;
        }

        return function(element, properties) {
            var args = arguments,
                prop,
                value;

            if (args.length == 2) {
                for (prop in properties) {
                    value = properties[prop];
                    if (value !== undefined && properties.hasOwnProperty(prop)) applyCss(element, prop, value);
                }
            } else {
                applyCss(element, args[1], args[2]);
            }
        }
    })();

    /**
     * (Internal) Determines if an element or space separated list of class names contains a class name.
     */

    function hasClass(element, name) {
        var list = typeof element == 'string' ? element : classList(element);
        return list.indexOf(' ' + name + ' ') >= 0;
    }

    /**
     * (Internal) Adds a class to an element.
     */

    function addClass(element, name) {
        var oldList = classList(element),
            newList = oldList + name;

        if (hasClass(oldList, name)) return;

        // Trim the opening space.
        element.className = newList.substring(1);
    }

    /**
     * (Internal) Removes a class from an element.
     */

    function removeClass(element, name) {
        var oldList = classList(element),
            newList;

        if (!hasClass(element, name)) return;

        // Replace the class name.
        newList = oldList.replace(' ' + name + ' ', ' ');

        // Trim the opening and closing spaces.
        element.className = newList.substring(1, newList.length - 1);
    }

    /**
     * (Internal) Gets a space separated list of the class names on the element.
     * The list is wrapped with a single space on each end to facilitate finding
     * matches within the list.
     */

    function classList(element) {
        return (' ' + (element.className || '') + ' ').replace(/\s+/gi, ' ');
    }

    /**
     * (Internal) Removes an element from the DOM.
     */

    function removeElement(element) {
        element && element.parentNode && element.parentNode.removeChild(element);
    }

    return NProgress;
});

