/**
 处理整个主题和核心功能的核心脚本
 **/
var App = function() {

    // IE mode
    var isRTL = false;

    var resizeHandlers = [];

    // 初始化主设置
    var handleInit = function() {

        if ($('body').css('direction') === 'rtl') {
            isRTL = true;
        }

    };

    var handleGoTop = function () {
        var offset = 100;
        var duration = 500;

        if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {  // ios supported
            $(window).bind("touchend touchcancel touchleave", function(e){
                if ($(this).scrollTop() > offset) {
                    $('.scroll-to-top').fadeIn(duration);
                } else {
                    $('.scroll-to-top').fadeOut(duration);
                }
            });
        } else {  // general
            $(window).scroll(function() {
                if ($(this).scrollTop() > offset) {
                    $('.scroll-to-top').fadeIn(duration);
                } else {
                    $('.scroll-to-top').fadeOut(duration);
                }
            });
        }

        $('.scroll-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, duration);
            return false;
        });
    };

    // 运行由设置的回调函数 App.addResponsiveHandler().
    var _runResizeHandlers = function() {
        // 初始化其他订阅元素
        for (var i = 0; i < resizeHandlers.length; i++) {
            var each = resizeHandlers[i];
            each.call();
        }
    };

    //在调整窗口大小时处理布局重新初始化
    var handleOnResize = function() {
        var resize;
        $(window).resize(function() {
            if (resize) {
                clearTimeout(resize);
            }
            resize = setTimeout(function() {
                _runResizeHandlers();
            }, 50); // wait 50ms until window resize finishes.
        });
    };

    //处理portlet工具和操作
    var handlePortletTools = function() {
// handle portlet fullscreen
        $('body').on('click', '.portlet > .portlet-title .fullscreen', function(e) {
            e.preventDefault();
            var portlet = $(this).closest(".portlet");
            if (portlet.hasClass('portlet-fullscreen')) {
                $(this).removeClass('on');
                portlet.removeClass('portlet-fullscreen');
                $('body').removeClass('page-portlet-fullscreen');
                portlet.children('.portlet-body').css('height', 'auto');
            } else {
                var height = App.getViewPort().height -
                    portlet.children('.portlet-title').outerHeight() -
                    parseInt(portlet.children('.portlet-body').css('padding-top')) -
                    parseInt(portlet.children('.portlet-body').css('padding-bottom'));

                $(this).addClass('on');
                portlet.addClass('portlet-fullscreen');
                $('body').addClass('page-portlet-fullscreen');
                portlet.children('.portlet-body').css('height', height);
            }
        });
    };

    // 复选框选择控制
    var handleMaterialDesign = function() {

        // Material design ckeckbox and radio effects
        $('body').on('click', '.md-checkbox > label, .md-radio > label', function() {
            var the = $(this);
            // find the first span which is our circle/bubble
            var el = $(this).children('span:first-child');

            // add the bubble class (we do this so it doesnt show on page load)
            el.addClass('inc');

            // clone it
            var newone = el.clone(true);

            // add the cloned version before our original
            el.before(newone);

            // remove the original so that it is ready to run on next click
            $("." + el.attr("class") + ":last", the).remove();
        });

        if ($('body').hasClass('page-md')) {
            // Material design click effect
            // credit where credit's due; http://thecodeplayer.com/walkthrough/ripple-click-effect-google-material-design
            var element, circle, d, x, y;
            $('body').on('click', 'a.btn, button.btn, input.btn, label.btn', function(e) {
                element = $(this);

                if(element.find(".md-click-circle").length == 0) {
                    element.prepend("<span class='md-click-circle'></span>");
                }

                circle = element.find(".md-click-circle");
                circle.removeClass("md-click-animate");

                if(!circle.height() && !circle.width()) {
                    d = Math.max(element.outerWidth(), element.outerHeight());
                    circle.css({height: d, width: d});
                }

                x = e.pageX - element.offset().left - circle.width()/2;
                y = e.pageY - element.offset().top - circle.height()/2;

                circle.css({top: y+'px', left: x+'px'}).addClass("md-click-animate");

                setTimeout(function() {
                    circle.remove();
                }, 1000);
            });
        }

        // Floating labels
        var handleInput = function(el) {
            if (el.val() != "") {
                el.addClass('edited');
            } else {
                el.removeClass('edited');
            }
        }

        $('body').on('keydown', '.form-md-floating-label .form-control', function(e) {
            handleInput($(this));
        });
        $('body').on('blur', '.form-md-floating-label .form-control', function(e) {
            handleInput($(this));
        });

        $('.form-md-floating-label .form-control').each(function(){
            if ($(this).val().length > 0) {
                $(this).addClass('edited');
            }
        });
    }


    // 滑动选择组件
    var handleBootstrapSwitch = function() {
        if (!$().bootstrapSwitch) {
            return;
        }
        $('.make-switch').bootstrapSwitch();
    };

    // Handles Bootstrap Accordions.
    var handleAccordions = function() {
        $('body').on('shown.bs.collapse', '.accordion.scrollable', function(e) {
            App.scrollTo($(e.target));
        });
    };

    // Tab切换
    var handleTabs = function() {
        //activate tab if tab id provided in the URL
        if (encodeURI(location.hash)) {
            var tabid = encodeURI(location.hash.substr(1));
            $('a[href="#' + tabid + '"]').parents('.tab-pane:hidden').each(function() {
                var tabid = $(this).attr("id");
                $('a[href="#' + tabid + '"]').click();
            });
            $('a[href="#' + tabid + '"]').click();
        }
        if ($().tabdrop) {
            $('.tabbable-tabdrop .nav-pills, .tabbable-tabdrop .nav-tabs').tabdrop({
                text: '<i class="fa fa-ellipsis-v"></i>&nbsp;<i class="fa fa-angle-down"></i>'
            });
        }
    };

    // 提示信息显示
    var handleTooltips = function() {
        // global tooltips
        $('.tooltips').tooltip();

        // portlet tooltips
        $('.portlet > .portlet-title .fullscreen').tooltip({
            trigger: 'hover',
            container: 'body',
            title: 'Fullscreen'
        });
        $('.portlet > .portlet-title > .tools > .reload').tooltip({
            trigger: 'hover',
            container: 'body',
            title: 'Reload'
        });
        $('.portlet > .portlet-title > .tools > .remove').tooltip({
            trigger: 'hover',
            container: 'body',
            title: 'Remove'
        });
        $('.portlet > .portlet-title > .tools > .config').tooltip({
            trigger: 'hover',
            container: 'body',
            title: 'Settings'
        });
        $('.portlet > .portlet-title > .tools > .collapse, .portlet > .portlet-title > .tools > .expand').tooltip({
            trigger: 'hover',
            container: 'body',
            title: 'Collapse/Expand'
        });
    };

    // Handles Bootstrap Dropdowns
    var handleDropdowns = function() {
        /*
          Hold dropdown on click
        */
        $('body').on('click', '.dropdown-menu.hold-on-click', function(e) {
            e.stopPropagation();
        });
    };

    // Handle Hower Dropdowns
    var handleDropdownHover = function() {
        $('[data-hover="dropdown"]').not('.hover-initialized').each(function() {
            $(this).dropdownHover();
            $(this).addClass('hover-initialized');
        });
    };

    // Handle textarea autosize
    var handleTextareaAutosize = function() {
        if (typeof(autosize) == "function") {
            autosize(document.querySelector('textarea.autosizeme'));
        }
    }

    // Handles Bootstrap Popovers

    // last popep popover
    var lastPopedPopover;

    var handlePopovers = function() {
        $('.popovers').popover();

        // close last displayed popover

        $(document).on('click.bs.popover.data-api', function(e) {
            if (lastPopedPopover) {
                lastPopedPopover.popover('hide');
            }
        });
    };

    // 區域滾動
    var handleScrollers = function() {
        App.initSlimScroll('.scroller');
    };

    // Handles counterup plugin wrapper
    var handleCounterup = function() {
        if (!$().counterUp) {
            return;
        }

        $("[data-counter='counterup']").counterUp({
            delay: 10,
            time: 1000
        });
    };


    // handle group element heights
    var handleHeight = function() {
        $('[data-auto-height]').each(function() {
            var parent = $(this);
            var items = $('[data-height]', parent);
            var height = 0;
            var mode = parent.attr('data-mode');
            var offset = parseInt(parent.attr('data-offset') ? parent.attr('data-offset') : 0);

            items.each(function() {
                if ($(this).attr('data-height') == "height") {
                    $(this).css('height', '');
                } else {
                    $(this).css('min-height', '');
                }

                var height_ = (mode == 'base-height' ? $(this).outerHeight() : $(this).outerHeight(true));
                if (height_ > height) {
                    height = height_;
                }
            });

            height = height + offset;

            items.each(function() {
                if ($(this).attr('data-height') == "height") {
                    $(this).css('height', height);
                } else {
                    $(this).css('min-height', height);
                }
            });

            if(parent.attr('data-related')) {
                $(parent.attr('data-related')).css('height', parent.height());
            }
        });
    }

    //* END:CORE HANDLERS *//

    return {

        //main function to initiate the theme
        init: function() {
            //IMPORTANT!!!: Do not modify the core handlers call order.

            //Core handlersww
            handleInit(); // initialize core variables
            handleOnResize(); // set and handle responsive
            handleGoTop(); // set and handle responsive

            //UI Component handlerss
            handleMaterialDesign(); //复选框选择控制w
            handleBootstrapSwitch(); // handle bootstrap switch plugin
            handleScrollers(); // handles slim scrolling contents
            handlePortletTools(); // handles portlet action bar functionality(refresh, configure, toggle, remove)

            handleDropdowns(); // handle dropdowns
            handleTabs(); // handle tabs
            handleTooltips(); // handle bootstrap tooltips
            handlePopovers(); // handles bootstrap popovers
            handleAccordions(); //handles accordions
            handleTextareaAutosize(); // handle autosize textareas
            handleCounterup(); // handle counterup instances

            //Handle group element heights
            this.addResizeHandler(handleHeight); // handle auto calculating height on window resize

        },

        //main function to initiate core javascript after ajax complete
        initAjax: function() {
            //handleUniform(); // handles custom radio & checkboxes
            handleBootstrapSwitch(); // handle bootstrap switch plugin
            handleDropdownHover(); // handles dropdown hover
            handleScrollers(); // handles slim scrolling contents
            handleDropdowns(); // handle dropdowns
            handleTooltips(); // handle bootstrap tooltips
            handlePopovers(); // handles bootstrap popovers
            handleAccordions(); //handles accordions
        },

        //init main components
        initComponents: function() {
            this.initAjax();
        },

        //public function to remember last opened popover that needs to be closed on click
        setLastPopedPopover: function(el) {
            lastPopedPopover = el;
        },

        //public function to add callback a function which will be called on window resize
        addResizeHandler: function(func) {
            resizeHandlers.push(func);
        },

        //public functon to call _runresizeHandlers
        runResizeHandlers: function() {
            _runResizeHandlers();
        },

        // wrApper function to scroll(focus) to an element
        scrollTo: function(el, offeset) {
            var pos = (el && el.length > 0) ? el.offset().top : 0;

            if (el) {
                if ($('body').hasClass('page-header-fixed')) {
                    pos = pos - $('.page-header').height();
                } else if ($('body').hasClass('page-header-top-fixed')) {
                    pos = pos - $('.page-header-top').height();
                } else if ($('body').hasClass('page-header-menu-fixed')) {
                    pos = pos - $('.page-header-menu').height();
                }
                pos = pos + (offeset ? offeset : -1 * el.height());
            }

            $('html,body').animate({
                scrollTop: pos
            }, 'slow');
        },

        initSlimScroll: function(el) {
            if (!$().slimScroll) {
                return;
            }

            $(el).each(function() {
                if ($(this).attr("data-initialized")) {
                    return; // exit
                }

                var height;

                if ($(this).attr("data-height")) {
                    height = $(this).attr("data-height");
                } else {
                    height = $(this).css('height');
                }

                $(this).slimScroll({
                    allowPageScroll: true, // allow page scroll when the element scroll is ended
                    size: '7px',
                    color: ($(this).attr("data-handle-color") ? $(this).attr("data-handle-color") : '#bbb'),
                    wrapperClass: ($(this).attr("data-wrapper-class") ? $(this).attr("data-wrapper-class") : 'slimScrollDiv'),
                    railColor: ($(this).attr("data-rail-color") ? $(this).attr("data-rail-color") : '#eaeaea'),
                    position: isRTL ? 'left' : 'right',
                    height: height,
                    alwaysVisible: ($(this).attr("data-always-visible") == "1" ? true : false),
                    railVisible: ($(this).attr("data-rail-visible") == "1" ? true : false),
                    disableFadeOut: true
                });

                $(this).attr("data-initialized", "1");
            });
        },

        destroySlimScroll: function(el) {
            if (!$().slimScroll) {
                return;
            }

            $(el).each(function() {
                if ($(this).attr("data-initialized") === "1") { // destroy existing instance before updating the height
                    $(this).removeAttr("data-initialized");
                    $(this).removeAttr("style");

                    var attrList = {};

                    // store the custom attribures so later we will reassign.
                    if ($(this).attr("data-handle-color")) {
                        attrList["data-handle-color"] = $(this).attr("data-handle-color");
                    }
                    if ($(this).attr("data-wrapper-class")) {
                        attrList["data-wrapper-class"] = $(this).attr("data-wrapper-class");
                    }
                    if ($(this).attr("data-rail-color")) {
                        attrList["data-rail-color"] = $(this).attr("data-rail-color");
                    }
                    if ($(this).attr("data-always-visible")) {
                        attrList["data-always-visible"] = $(this).attr("data-always-visible");
                    }
                    if ($(this).attr("data-rail-visible")) {
                        attrList["data-rail-visible"] = $(this).attr("data-rail-visible");
                    }

                    $(this).slimScroll({
                        wrapperClass: ($(this).attr("data-wrapper-class") ? $(this).attr("data-wrapper-class") : 'slimScrollDiv'),
                        destroy: true
                    });

                    var the = $(this);

                    // reassign custom attributes
                    $.each(attrList, function(key, value) {
                        the.attr(key, value);
                    });

                }
            });
        },

        // function to scroll to the top
        scrollTop: function() {
            App.scrollTo();
        },


        //public helper function to get actual input value(used in IE9 and IE8 due to placeholder attribute not supported)
        getActualVal: function(el) {
            el = $(el);
            if (el.val() === el.attr("placeholder")) {
                return "";
            }
            return el.val();
        },

        //public function to get a paremeter by name from URL
        getURLParameter: function(paramName) {
            var searchString = window.location.search.substring(1),
                i, val, params = searchString.split("&");

            for (i = 0; i < params.length; i++) {
                val = params[i].split("=");
                if (val[0] == paramName) {
                    return unescape(val[1]);
                }
            }
            return null;
        },

        // check for device touch support
        isTouchDevice: function() {
            try {
                document.createEvent("TouchEvent");
                return true;
            } catch (e) {
                return false;
            }
        },

        // To get the correct viewport width based on  http://andylangton.co.uk/articles/javascript/get-viewport-size-javascript/
        getViewPort: function() {
            var e = window,
                a = 'inner';
            if (!('innerWidth' in window)) {
                a = 'client';
                e = document.documentElement || document.body;
            }

            return {
                width: e[a + 'Width'],
                height: e[a + 'Height']
            };
        },

        getUniqueID: function(prefix) {
            return 'prefix_' + Math.floor(Math.random() * (new Date()).getTime());
        },

        //check RTL mode
        isRTL: function() {
            return isRTL;
        },

        // check IE8 mode
        isAngularJsApp: function() {
            return (typeof angular == 'undefined') ? false : true;
        },


        getResponsiveBreakpoint: function(size) {
            // bootstrap responsive breakpoints
            var sizes = {
                'xs' : 480,     // extra small
                'sm' : 768,     // small
                'md' : 992,     // medium
                'lg' : 1200     // large
            };

            return sizes[size] ? sizes[size] : 0;
        }
    };

}();

jQuery(document).ready(function() {
    App.init(); //初始化核心组件
});

/**
 处理整个主题和核心功能的核心脚本
 **/
var Layout = function () {

    var resBreakpointMd = App.getResponsiveBreakpoint('md');

    var ajaxContentSuccessCallbacks = [];
    var ajaxContentErrorCallbacks = [];

    //* 开始：核心处理程序*//

    //此函数处理屏幕大小调整或移动设备旋转时的响应布局。
    //为边栏和内容设置适当的高度。内容和侧边栏高度必须始终同步。
    var handleSidebarAndContentHeight = function () {

    };


    // 手柄式提要栏菜单
    var handleSidebarMenu = function () {
        // offcanvas mobile menu
        $('.page-sidebar-mobile-offcanvas .responsive-toggler').click(function() {
            $('body').toggleClass('page-sidebar-mobile-offcanvas-open');
            e.preventDefault();
            e.stopPropagation();
        });

        if ($('body').hasClass('page-sidebar-mobile-offcanvas')) {
            $(document).on('click', function(e) {
                if ($('body').hasClass('page-sidebar-mobile-offcanvas-open')) {
                    if ($(e.target).closest('.page-sidebar-mobile-offcanvas .responsive-toggler').length === 0 &&
                        $(e.target).closest('.page-sidebar-wrapper').length === 0) {
                        $('body').removeClass('page-sidebar-mobile-offcanvas-open');
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            });
        }

        // handle sidebar link click
        $('.page-sidebar-menu').on('click', 'li > a.nav-toggle, li > a > span.nav-toggle', function (e) {
            var that = $(this).closest('.nav-item').children('.nav-link');

            if (App.getViewPort().width >= resBreakpointMd && !$('.page-sidebar-menu').attr("data-initialized") && $('body').hasClass('page-sidebar-closed') &&  that.parent('li').parent('.page-sidebar-menu').length === 1) {
                return;
            }

            var hasSubMenu = that.next().hasClass('sub-menu');

            if (App.getViewPort().width >= resBreakpointMd && that.parents('.page-sidebar-menu-hover-submenu').length === 1) { // exit of hover sidebar menu
                return;
            }

            if (hasSubMenu === false) {
                if (App.getViewPort().width < resBreakpointMd && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page
                    $('.page-header .responsive-toggler').click();
                }
                return;
            }

            var parent =that.parent().parent();
            var the = that;
            var menu = $('.page-sidebar-menu');
            var sub = that.next();

            var autoScroll = menu.data("auto-scroll");
            var slideSpeed = parseInt(menu.data("slide-speed"));
            var keepExpand = menu.data("keep-expanded");

            if (!keepExpand) {
                parent.children('li.open').children('a').children('.arrow').removeClass('open');
                parent.children('li.open').children('.sub-menu:not(.always-open)').slideUp(slideSpeed);
                parent.children('li.open').removeClass('open');
            }

            var slideOffeset = -200;

            if (sub.is(":visible")) {
                $('.arrow', the).removeClass("open");
                the.parent().removeClass("open");
                sub.slideUp(slideSpeed, function () {
                    if (autoScroll === true && $('body').hasClass('page-sidebar-closed') === false) {
                        if ($('body').hasClass('page-sidebar-fixed')) {
                            menu.slimScroll({
                                'scrollTo': (the.position()).top
                            });
                        } else {
                            App.scrollTo(the, slideOffeset);
                        }
                    }
                    handleSidebarAndContentHeight();
                });
            } else if (hasSubMenu) {
                $('.arrow', the).addClass("open");
                the.parent().addClass("open");
                sub.slideDown(slideSpeed, function () {
                    if (autoScroll === true && $('body').hasClass('page-sidebar-closed') === false) {
                        if ($('body').hasClass('page-sidebar-fixed')) {
                            menu.slimScroll({
                                'scrollTo': (the.position()).top
                            });
                        } else {
                            App.scrollTo(the, slideOffeset);
                        }
                    }
                    handleSidebarAndContentHeight();
                });
            }

            e.preventDefault();
        });

        // handle menu close for angularjs version
        if (App.isAngularJsApp()) {
            $(".page-sidebar-menu li > a").on("click", function(e) {
                if (App.getViewPort().width < resBreakpointMd && $(this).next().hasClass('sub-menu') === false) {
                    $('.page-header .responsive-toggler').click();
                }
            });
        }

        // handle ajax links within sidebar menu
        $('.page-sidebar').on('click', ' li > a.ajaxify', function (e) {

        });

        // handle ajax link within main content
        $('.page-content').on('click', '.ajaxify', function (e) {

        });

        // handle scrolling to top on responsive menu toggler click when header is fixed for mobile view
        $(document).on('click', '.page-header-fixed-mobile .page-header .responsive-toggler', function(){
            App.scrollTop();
        });


        // handle the search bar close
        $('.page-sidebar').on('click', '.sidebar-search .remove', function (e) {
            e.preventDefault();
            $('.sidebar-search').removeClass("open");
        });

        // handle the search query submit on enter press
        $('.page-sidebar .sidebar-search').on('keypress', 'input.form-control', function (e) {
            if (e.which == 13) {
                dr_search_help();
                return false; //<---- Add this line
            }
        });

        // handle the search submit(for sidebar search and responsive mode of the header search)
        $('.sidebar-search .submit').on('click', function (e) {
            dr_search_help();
        });

        // handle close on body click
        if ($('.sidebar-search').length !== 0) {
            $('.sidebar-search .input-group').on('click', function(e){
                e.stopPropagation();
            });

            $('body').on('click', function() {
                if ($('.sidebar-search').hasClass('open')) {
                    $('.sidebar-search').removeClass("open");
                }
            });
        }
    };

    //帮助函数，用于计算固定提要栏布局的边栏高度。
    var _calculateFixedSidebarViewportHeight = function () {
        var sidebarHeight = App.getViewPort().height - $('.page-header').outerHeight(true);
        if ($('body').hasClass("page-footer-fixed")) {
            sidebarHeight = sidebarHeight - $('.page-footer').outerHeight();
        }

        return sidebarHeight;
    };

    // Handles fixed sidebar
    var handleFixedSidebar = function () {
        var menu = $('.page-sidebar-menu');

        handleSidebarAndContentHeight();

        if ($('.page-sidebar-fixed').length === 0) {
            return;
        }

        if (App.getViewPort().width >= resBreakpointMd && !$('body').hasClass('page-sidebar-menu-not-fixed')) {
            menu.attr("data-height", _calculateFixedSidebarViewportHeight());
            App.destroySlimScroll(menu);
            App.initSlimScroll(menu);
            handleSidebarAndContentHeight();
        }
    };


    // Hanles sidebar toggler
    var handleSidebarToggler = function () {
        if ( typeof is_min != "undefined" && is_min == 1) {
            var body = $('body');
            if ($.cookie && $.cookie('sidebar_closed') === '1' && App.getViewPort().width >= resBreakpointMd) {
                $('body').addClass('page-sidebar-closed');
                $('.page-sidebar-menu').addClass('page-sidebar-menu-closed');
            }

            // handle sidebar show/hide
            $('body').on('click', '.sidebar-toggler', function (e) {
                var sidebar = $('.page-sidebar');
                var sidebarMenu = $('.page-sidebar-menu');
                $(".sidebar-search", sidebar).removeClass("open");

                if (body.hasClass("page-sidebar-closed")) {
                    body.removeClass("page-sidebar-closed");
                    sidebarMenu.removeClass("page-sidebar-menu-closed");
                    if ($.cookie) {
                        $.cookie('sidebar_closed', '0');
                    }
                } else {
                    body.addClass("page-sidebar-closed");
                    sidebarMenu.addClass("page-sidebar-menu-closed");
                    if (body.hasClass("page-sidebar-fixed")) {
                        sidebarMenu.trigger("mouseleave");
                    }
                    if ($.cookie) {
                        $.cookie('sidebar_closed', '1');
                    }
                }

                $(window).trigger('resize');
            });
        }
    };

    return {
        // Main init methods to initialize the layout
        //IMPORTANT!!!: Do not modify the core handlers call order.




        initSidebar: function() {
            //layout handlers
            handleFixedSidebar(); // handles fixed sidebar menu
            handleSidebarMenu(); // handles main menu
            handleSidebarToggler(); // handles sidebar hide/show


            App.addResizeHandler(handleFixedSidebar); // reinitialize fixed sidebar on window resize
        },

        initContent: function() {
            App.addResizeHandler(handleSidebarAndContentHeight); // recalculate sidebar & content height on window resize
        },

        init: function () {
            this.initSidebar();
            this.initContent();
        },

        //public function to fix the sidebar and content height accordingly
        fixContentHeight: function () {
            handleSidebarAndContentHeight();
        },

        initFixedSidebar: function() {
            handleFixedSidebar();
        },

    };

}();

if (App.isAngularJsApp() === false) {
    jQuery(document).ready(function() {
        Layout.init();
    });
}

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
            dr_iframe_error(layer, index, 0);
        },
        content: url+'&is_iframe=1'
    });
}

jQuery(document).ready(function() {
    $('.onloading').click(function(){
        var index = layer.load(2, { time: 5000 });
    });
    $(document).on('click', '.fc_member_show', function() {
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
                dr_iframe_error(layer, index, 0);
            },
            content: url+'&is_iframe=1'
        });
    });

    // 当存在隐藏时单击显示区域
    $(document).on('click', '.table td,.table th', function() {
        var td = $(this);
        if (dr_isEllipsis(td[0]) == true) {
            var text = td.html();
            if (text.indexOf("checkbox") != -1) {
                return;
            } else if (text.indexOf("<input") != -1) {
                return;
            } else if (text.indexOf("class=\"btn") != -1) {
                // 存在按钮
            } else if (text.indexOf("href=\"") != -1) {
                return;
            }
            layer.tips(text, td, {
                tips: [1, '#fff'],
                time: 5000
            });
        }
    });
    // 关闭框架的加载提示
    //if (typeof parent.layer.closeAll == 'function') {
    //parent.layer.closeAll('loading');
    //}

    //离开提示失效
    if (typeof is_admin != "undefined" && is_admin == 1) {
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
    }

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
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
                    if (go == 1 && json.code > 0) {
                        setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
                }
            });
        });
}

// 弹出提示
function dr_install_confirm(url) {

    if (typeof is_oem_cms_down != "undefined" && is_oem_cms_down) {
        window.opener=null;
        window.open('','_self');
        window.close();
        alert(dr_lang('请关闭当前窗口'));
    } else {
        layer.confirm(
            dr_lang('确定要刷新整个后台吗？'),
        {
            icon: 3,
            shade: 0,
            title: lang['ts'],
            btn: [dr_lang('刷新后台'), dr_lang('直接进入')]
        }, function(index){
            layer.close(index);
            parent.location.href = admin_file+'?go='+encodeURIComponent(url);
            //parent.location.reload(true);
        }, function(index){
            layer.close(index);
            if (url) {
                window.location.href = url;
            } else {
                window.location.reload(true);
            }
        });
    }
}


// 安装app提示
function dr_install_app(url) {
    var index = layer.load(2, {
        shade: [0.3,'#fff'], //0.1透明度的白色背景
        time: 50000
    });
    $.ajax({type: "GET",dataType:"json", url: url,
        success: function(json) {
            layer.close(index);
            dr_tips(json.code, json.msg);
            if (json.code == 1) {
                setTimeout("dr_install_confirm('"+json.data.url+"')", 2000);
            }
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}

// 安装模块提示
function dr_install_module_select(url) {
    layer.confirm(
        dr_lang('共享模块: 共用一个栏目，在栏目中选择模块')+'<br>'+
        dr_lang('独立模块: 独立栏目管理，在模块中选择栏目')+'<br>',
        {
            shade: 0,
            title: dr_lang('安装选择'),
            btn: [dr_lang('独立'), dr_lang('共享')]
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 50000
            });
            $.ajax({type: "GET",dataType:"json", url: url+'&type=1',
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("dr_install_confirm('"+json.data.url+"')", 2000);
                        //dr_install_confirm(admin_file+"?c=module&m=index")
                        //setTimeout("window.location.href = '"+admin_file+"?c=module&m=index'", 2000);
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
                }
            });
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 50000
            });
            $.ajax({type: "GET",dataType:"json", url: url+'&type=0',
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("dr_install_confirm('"+json.data.url+"')", 2000);
                        //setTimeout("window.location.href = '"+admin_file+"?c=module&m=index'", 2000);
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
                }
            });
        }
    );
}

// 直接安装
function dr_install_module(url) {
    layer.confirm(
        dr_lang('你确定要安装到当前站点吗？'),
        {
            shade: 0,
            title: dr_lang('ts'),
            btn: [dr_lang('ok'), dr_lang('esc')]
        }, function(index){
            var index = layer.load(2, {
                shade: [0.3,'#fff'], //0.1透明度的白色背景
                time: 50000
            });
            $.ajax({type: "GET",dataType:"json", url: url,
                success: function(json) {
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    if (json.code == 1) {
                        setTimeout("dr_install_confirm('"+json.data.url+"')", 2000);
                        //setTimeout("window.location.reload(true)", 2000)
                    }
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_alert_error(HttpRequest, this, thrownError);
                }
            });
        }, function(index){
            return;
        }
    );
}


// 推送模块数据
function dr_module_send(title, url, nogo) {
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
                time: 9999999
            });
            $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find('#myform').serialize(),
                success: function(json) {
                    layer.close(loading);
                    // token 更新
                    if (json.token) {
                        var token = json.token;
                        $(body).find("#myform input[name='"+token.name+"']").val(token.value);
                    }
                    if (json.code == 1) {
                        layer.close(index);
                        if (json.data.url) {
                            dr_iframe_show(title, json.data.url);
                        } else if (nogo) {

                        } else {
                            setTimeout("window.location.reload(true)", 2000)
                        }
                    } else {
                        $(body).find('#dr_row_'+json.data.field).addClass('has-error');
                    }
                    dr_tips(json.code, json.msg);
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
            dr_iframe_error(layer, index, 1);
        },
        content: url+'&is_iframe=1'
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
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_iframe_error(layer, index, 0);
        },
        content: url+'&'+$('#'+myform).serialize(),
        cancel: function(index, layero){
            var body = layer.getChildFrame('body', index);
            if ($(body).find('#dr_check_status').val() == "1") {
                layer.confirm(dr_lang('关闭后将中断操作，是否确认关闭呢？'),
                    {
                        icon: 3,
                        shade: 0,
                        title: lang['ts'],
                        btn: [lang['ok'], lang['esc']]
                    }, function(index){
                        layer.closeAll();
                    });
                return false;
            }
        }
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
            // token 更新
            if (json.token) {
                var token = json.token;
                $("#"+myform+" input[name='"+token.name+"']").val(token.value);
            }
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
                        dr_iframe_error(layer, index, 0);
                    },
                    content: json.data.url,
                    cancel: function(index, layero){
                        var body = layer.getChildFrame('body', index);
                        if ($(body).find('#dr_check_status').val() == "1") {
                            layer.confirm(dr_lang('关闭后将中断操作，是否确认关闭呢？'),
                                {
                                    icon: 3,
                                    shade: 0,
                                    title: dr_lang('ts'),
                                    btn: [dr_lang('ok'), dr_lang('esc')]
                                }, function (index) {
                                    layer.closeAll();
                                });
                            return false;
                        }
                    }
                });

            } else {
                dr_tips(0, json.msg, 90000);
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            dr_iframe_error(layer, index, 0);
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
            dr_iframe_error(layer, index, 0);
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
            // token 更新
            if (json.token) {
                var token = json.token;
                $("#"+myform+" input[name='"+token.name+"']").val(token.value);
            }
            if (json.code == 1) {
                dr_tips(1, json.msg);
            } else {
                dr_tips(0, json.msg, 90000);
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
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
            // token 更新
            if (json.token) {
                var token = json.token;
                $("#"+myform+" input[name='"+token.name+"']").val(token.value);
            }
            if (json.code == 1) {
                $("#sql_result").html('<pre>'+json.msg+'</pre>');
            } else {
                $("#sql_result").html('<div class="alert alert-danger">'+json.msg+'</div>');
            }
            return false;
        },
        error: function(HttpRequest, ajaxOptions, thrownError) {
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}

function dr_call_alert() {
    dr_help(463);

}
function dr_seo_rule() {
    layer.alert(dr_lang('通用标签')+'<br>'+
        '{join}	'+dr_lang('SEO连接符号，默认是_')+'<br>'+
        '[{page}]	'+dr_lang('分页页码')+'<br>'+
        '{SITE_NAME}	'+dr_lang('网站名称')+'<br>'+
        dr_lang('支持表字段，格式：{字段名}')+' <br>'+dr_lang('例如：{title}表示标题')+'<br>'+
        dr_lang('支持网站系统常量，格式：{大写的常量名称}')+'<br>'+dr_lang('例如：{SITE_NAME}表示网站名称')+'<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        btn: []
    });
}
function dr_url_module_index() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': index.php?s=news<br>'+
        'news.html ===> {modname}.html'+
        '<br><br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{modname}	'+dr_lang('表示当前模块目录')+'<br>'+
        ''+
        '', {
        shade: 0,
        area: ['50%', '50%'],
        title: '',
        btn: []
    });
}
function dr_url_module_show() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': <br>index.php?s=news&c=show&id=1<br>'+
        'show/1.html ===> {modname}/{id}.html'+
        '<br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{id}   '+dr_lang('表示id')+'<br>'+
        '{y}   '+dr_lang('表示年')+'<br>'+
        '{m}   '+dr_lang('表示月')+'<br>'+
        '{d}   '+dr_lang('表示日')+'<br>'+
        '{page}   '+dr_lang('表示分页号')+'<br>'+
        '{dirname}   '+dr_lang('表示栏目目录名称')+'<br>'+
        '{pdirname}   '+dr_lang('包含父级子级层次的目录')+'<br>'+
        '{opdirname}   '+dr_lang('表示父级目录名称')+'<br>'+
        '{otdirname}   '+dr_lang('表示顶级目录名称')+'<br>'+
        '{modname}  '+dr_lang('表示模块目录')+'<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_module_list() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': <br>index.php?s=news&c=category&id=1<br>'+
        'news/1.html ===> {dirname}/{id}.html'+
        '<br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{id}   '+dr_lang('表示id')+'<br>'+
        '{page}   '+dr_lang('表示分页号')+'<br>'+
        '{dirname}   '+dr_lang('表示栏目目录名称')+'<br>'+
        '{pdirname}   '+dr_lang('包含父级子级层次的目录')+'<br>'+
        '{opdirname}   '+dr_lang('表示父级目录名称')+'<br>'+
        '{otdirname}   '+dr_lang('表示顶级目录名称')+'<br>'+
        '{modname}  '+dr_lang('表示模块目录')+'<br>'+

        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}

function dr_url_page() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': <br>index.php?s=page&id=1<br>'+
        'page/1.html ===> page/{id}.html'+
        '<br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{id}   '+dr_lang('表示id')+'<br>'+
        '{page}   '+dr_lang('表示分页号')+'<br>'+
        '{dirname}   '+dr_lang('表示栏目目录名称')+'<br>'+
        '{pdirname}   '+dr_lang('包含父级子级层次的目录')+'<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}
function dr_url_module_search() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': index.php?s=news&c=search<br>'+
        'news/search.html ===> {modname}/search.html'+
        '<br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{modname}  '+dr_lang('表示模块目录')+'<br>'+
        ''+
        '', {
        shade: 0,
        area: ['50%', '50%'],
        title: '',
        btn: []
    });
}

function dr_url_module_search_page() {
    layer.alert('<b>'+dr_lang('举例')+'</b><hr>'+
        dr_lang('默认地址')+': <br>index.php?s=news&c=search&field=value<br>'+
        'news/search/***.html ===> {modname}/search/{param}.html'+
        '<br><br><b>'+dr_lang('通配符')+'</b><hr>'+
        '{param}   '+dr_lang('表示搜索参数')+'<br>'+
        '{modname}  '+dr_lang('表示模块目录')+'<br>'+
        ''+
        '', {
        shade: 0,
        title: '',
        area: ['50%', '50%'],
        btn: []
    });
}

function dr_sync_cache(page) {
    $.ajax({
        type: "GET",
        dataType: "json",
        url: admin_file+"?c=api&m=cache_sync&page="+page,
        success: function (json) {
            console.log(json.msg);
            if (json.code == 0) {
            } else {
                if (json.data == 0) {
                } else {
                    dr_sync_cache(json.data);
                }
            }
        },
        error: function(HttpRequest, ajax, thrownError) {
        }
    });
}

function dr_help(id) {
    if (is_oemcms) {
        dr_tips(1, dr_lang('请联系开发商')+'：'+is_oemcms);
        return;
    }
    layer.open({
        type: 2,
        title: '<i class="fa fa-question-circle"></i>',
        shadeClose: true,
        scrollbar: false,
        shade: 0,
        area: ['80%', '90%'],
        content: 'https://www.xunruicms.com/index.php?s=doc&c=show&id='+id+'&is_phpcmf=cms'
    });
}

function dr_search_help() {
    if (is_oemcms == 1) {
        dr_tips(1, dr_lang('请联系开发商'));
        return;
    }
    layer.open({
        type: 2,
        title: '<i class="fa fa-question-circle"></i>',
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
            dr_ajax_alert_error(HttpRequest, this, thrownError);
        }
    });
}

function dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError) {
    dr_ajax_alert_error(HttpRequest, this, thrownError);
}


/*!
 * jQuery Cookie
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD (Register as an anonymous module)
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function encode(s) {
        return config.raw ? s : encodeURIComponent(s);
    }

    function decode(s) {
        return config.raw ? s : decodeURIComponent(s);
    }

    function stringifyCookieValue(value) {
        return encode(config.json ? JSON.stringify(value) : String(value));
    }

    function parseCookieValue(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape...
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }

        try {
            // Replace server-side written pluses with spaces.
            // If we can't decode the cookie, ignore it, it's unusable.
            // If we can't parse the cookie, ignore it, it's unusable.
            s = decodeURIComponent(s.replace(pluses, ' '));
            return config.json ? JSON.parse(s) : s;
        } catch(e) {}
    }

    function read(s, converter) {
        var value = config.raw ? s : parseCookieValue(s);
        return $.isFunction(converter) ? converter(value) : value;
    }

    var config = $.cookie = function (key, value, options) {

        // Write

        if (arguments.length > 1 && !$.isFunction(value)) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
            }

            return (document.cookie = [
                encode(key), '=', stringifyCookieValue(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // Read

        var result = key ? undefined : {},
            // To prevent the for loop in the first place assign an empty array
            // in case there are no cookies at all. Also prevents odd result when
            // calling $.cookie().
            cookies = document.cookie ? document.cookie.split('; ') : [],
            i = 0,
            l = cookies.length;

        for (; i < l; i++) {
            var parts = cookies[i].split('='),
                name = decode(parts.shift()),
                cookie = parts.join('=');

            if (key === name) {
                // If second argument (value) is a function it's a converter...
                result = read(cookie, value);
                break;
            }

            // Prevent storing a cookie that we couldn't decode.
            if (!key && (cookie = read(cookie)) !== undefined) {
                result[name] = cookie;
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        // Must not alter options, thus extending a fresh object...
        $.cookie(key, '', $.extend({}, options, { expires: -1 }));
        return !$.cookie(key);
    };

}));

/*!
 * 后台右侧快捷栏
 */
var QuickSidebar=function(){var i=function(){$(".dropdown-quick-sidebar-toggler a, .page-quick-sidebar-toggler, .quick-sidebar-toggler").click(function(i){$("body").toggleClass("page-quick-sidebar-open")})},a=function(){var i=$(".page-quick-sidebar-wrapper"),a=i.find(".page-quick-sidebar-chat"),e=function(){var e,t=i.find(".page-quick-sidebar-chat-users");e=i.height()-i.find(".nav-tabs").outerHeight(!0),App.destroySlimScroll(t),t.attr("data-height",e),App.initSlimScroll(t);var r=a.find(".page-quick-sidebar-chat-user-messages"),s=e-a.find(".page-quick-sidebar-chat-user-form").outerHeight(!0);s-=a.find(".page-quick-sidebar-nav").outerHeight(!0),App.destroySlimScroll(r),r.attr("data-height",s),App.initSlimScroll(r)};e(),App.addResizeHandler(e),i.find(".page-quick-sidebar-chat-users .media-list > .media").click(function(){a.addClass("page-quick-sidebar-content-item-shown")}),i.find(".page-quick-sidebar-chat-user .page-quick-sidebar-back-to-list").click(function(){a.removeClass("page-quick-sidebar-content-item-shown")});var t=function(i){i.preventDefault();var e=a.find(".page-quick-sidebar-chat-user-messages"),t=a.find(".page-quick-sidebar-chat-user-form .form-control"),r=t.val();if(0!==r.length){var s=function(i,a,e,t,r){var s="";return s+='<div class="post '+i+'">',s+='<img class="avatar" alt="" src="'+Layout.getLayoutImgPath()+t+'.jpg"/>',s+='<div class="message">',s+='<span class="arrow"></span>',s+='<a href="#" class="name">Bob Nilson</a>&nbsp;',s+='<span class="datetime">'+a+"</span>",s+='<span class="body">',s+=r,s+="</span>",s+="</div>",s+="</div>"},n=new Date,c=s("out",n.getHours()+":"+n.getMinutes(),"Bob Nilson","avatar3",r);c=$(c),e.append(c),e.slimScroll({scrollTo:"1000000px"}),t.val(""),setTimeout(function(){var i=new Date,a=s("in",i.getHours()+":"+i.getMinutes(),"Ella Wong","avatar2","Lorem ipsum doloriam nibh...");a=$(a),e.append(a),e.slimScroll({scrollTo:"1000000px"})},3e3)}};a.find(".page-quick-sidebar-chat-user-form .btn").click(t),a.find(".page-quick-sidebar-chat-user-form .form-control").keypress(function(i){return 13==i.which?(t(i),!1):void 0})},e=function(){var i=$(".page-quick-sidebar-wrapper"),a=function(){var a,e=i.find(".page-quick-sidebar-alerts-list");a=i.height()-i.find(".nav-justified > .nav-tabs").outerHeight(),App.destroySlimScroll(e),e.attr("data-height",a),App.initSlimScroll(e)};a(),App.addResizeHandler(a)},t=function(){var i=$(".page-quick-sidebar-wrapper"),a=function(){var a,e=i.find(".page-quick-sidebar-settings-list");a=i.height()-80-i.find(".nav-justified > .nav-tabs").outerHeight(),App.destroySlimScroll(e),e.attr("data-height",a),App.initSlimScroll(e)};a(),App.addResizeHandler(a)};return{init:function(){i(),a(),e(),t()}}}();App.isAngularJsApp()===!1&&jQuery(document).ready(function(){QuickSidebar.init()});