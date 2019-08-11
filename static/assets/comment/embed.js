(function($) {
    $.fn.extend({
        "insert": function(html, value) {
            value = $.extend({
                    "text": html
                },
                value);
            var dthis = $(this)[0];
            if (document.selection) {
                $(dthis).focus();
                var fus = document.selection.createRange();
                fus.text = value.text;
                $(dthis).focus()
            } else {
                if (dthis.selectionStart || dthis.selectionStart == "0") {
                    var start = dthis.selectionStart;
                    var end = dthis.selectionEnd;
                    dthis.value = dthis.value.substring(0, start) + value.text + dthis.value.substring(end, dthis.value.length)
                } else {
                    this.value += value.text;
                    this.focus()
                }
            }
            return $(this)
        }
    })
})(jQuery);
function getCursortPosition(ctrl) {
    var CaretPos = 0;
    if (document.selection) {
        ctrl.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart("character", -ctrl.value.length);
        CaretPos = Sel.text.length
    } else {
        if (ctrl.selectionStart || ctrl.selectionStart == "0") {
            CaretPos = ctrl.selectionStart
        }
    }
    return (CaretPos)
}
$(function() {
    $("#ds-smilies-tooltip").hide();
    dr_show_bq()
});
function dr_show_bq() {
    $("#ds-smilies-tooltip").hide();
    $("a.ds-add-emote").bind("click",
        function(event) {
            $(".ds-smilies-container img").unbind("click");
            $("#ds-smilies-tooltip").show();
            $(document).one("click",
                function() {
                    $(".ds-smilies-container img").unbind("click");
                    $("#ds-smilies-tooltip").hide()
                });
            $("#ds-smilies-tooltip").click(function(ev) {
                ev.stopPropagation()
            });
            var emote = jQuery(this);
            var winheight = jQuery("#ds-smilies-tooltip").height();
            var top = emote.offset().top - 236;
            var left = emote.offset().left - 10;
            var form = emote.parents(".ds_form_post");
            $("#ds-reset #ds-smilies-tooltip").offset({
                top: top,
                left: left
            });
            $(".ds-smilies-container img").bind("click",
                function(es) {
                    var title = jQuery(this).attr("title");
                    var html = title;
                    var objj = form.find('textarea[name="content"]');
                    var value = objj.val();
                    value = $.extend({
                            "text": html
                        },
                        value);
                    var dthis = objj[0];
                    if (document.selection) {
                        $(dthis).focus();
                        var fus = document.selection.createRange();
                        fus.text = value.text;
                        $(dthis).focus()
                    } else {
                        if (dthis.selectionStart || dthis.selectionStart == "0") {
                            var start = dthis.selectionStart;
                            var end = dthis.selectionEnd;
                            dthis.value = dthis.value.substring(0, start) + value.text + dthis.value.substring(end, dthis.value.length)
                        } else {
                            this.value += value.text;
                            this.focus()
                        }
                    }
                    $("#ds-smilies-tooltip").hide();
                    $(".ds-smilies-container img").unbind("click")
                });
            event.stopPropagation()
        })
}
function dr_post_comment() {
    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });
    $.ajax({
        type: "POST",
        dataType: "json",
        url: comment_url + "&m=post",
        data: $("#myform").serialize(),
        success: function(data) {
            layer.close(loading);
            dr_tips(data.code, data.msg)
            if (data.code == 1) {
                dr_todo_ajax()
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            layer.closeAll('loading');
            alert(HttpRequest.responseText);
        }
    })
}
function dr_reply_comment(id) {
    var loading = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 100000000
    });
    $.ajax({
        type: "POST",
        dataType: "json",
        url: comment_url + "&m=post&rid=" + id,
        data: $("#myform_" + id).serialize(),
        success: function(data) {
            layer.close(loading);
            dr_tips(data.code, data.msg)
            if (data.code == 1) {
                dr_todo_ajax()
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            layer.closeAll('loading');
            alert(HttpRequest.responseText);
        }
    })
}
function dr_reply_show(id, username, tid) {
    var html = $("#dr_post_form").html();
    html = html.replace("myform", "myform_" + tid);
    html = html.replace("dr_review_post", "dr_review_post_" + id);
    html = html.replace("dr_post_comment()", "dr_reply_comment(" + tid + ")");
    $(".ds-replybox2").hide();
    $("#dr_reply_" + id).html(html);
    var obj = $("#myform_" + tid + " textarea[name='content']");
    obj.val("@" + username + "  ");
    obj.focus();
    $("#dr_reply_" + id).show();
    dr_show_bq();
    $("#dr_review_post_" + id).remove();
    $("#dr_reply_" + id+' .ds-myfield').remove();
}
function dr_zc_comment(id) {
    $.ajax({
        type: "GET",
        dataType: "json",
        url: comment_url + "&m=op&t=zc&rid=" + id,
        data: {},
        success: function(data) {
            if (data.code == 1) {
                $("#dr_comment_zc_" + id).html(data.msg)
            } else {
                dr_tips(data.code, data.msg)
            }
        }
    })
}
function dr_fd_comment(id) {
    $.ajax({
        type: "GET",
        dataType: "json",
        url: comment_url + "&m=op&t=fd&rid=" + id,
        data: {},
        success: function(data) {
            if (data.code == 1) {
                $("#dr_comment_fd_" + id).html(data.msg)
            } else {
                dr_tips(0, data.msg)
            }
        }
    })
}
function dr_delete_comment(id) {
    $.ajax({
        type: "GET",
        dataType: "json",
        url: comment_url + "&m=op&t=delete&rid=" + id,
        data: {},
        success: function(data) {
            if (data.code == 1) {
                dr_todo_ajax();
                dr_tips(1, data.msg)
            } else {
                dr_tips(0, data.msg)
            }
        }
    })
}
function dr_review_value(iid, vid) {
    $(".dr_review_value_" + iid).removeClass("active");
    $("#dr_review_value_" + iid + "_" + vid).addClass("active");
    $("#dr_review_option_" + iid).val(vid)
};