/**
 Core script to handle the entire theme and core functions
 **/
var App = function() {

    // IE mode
    var isRTL = false;
    var isIE8 = false;
    var isIE9 = false;
    var isIE10 = false;

    var resizeHandlers = [];

    var assetsPath = '../assets/';

    var globalImgPath = 'global/img/';

    var globalPluginsPath = 'global/plugins/';

    var globalCssPath = 'global/css/';

    // theme layout color set

    var brandColors = {
        'blue': '#89C4F4',
        'red': '#F3565D',
        'green': '#1bbc9b',
        'purple': '#9b59b6',
        'grey': '#95a5a6',
        'yellow': '#F8CB00'
    };

    // initializes main settings
    var handleInit = function() {

        if ($('body').css('direction') === 'rtl') {
            isRTL = true;
        }

        isIE8 = !!navigator.userAgent.match(/MSIE 8.0/);
        isIE9 = !!navigator.userAgent.match(/MSIE 9.0/);
        isIE10 = !!navigator.userAgent.match(/MSIE 10.0/);

        if (isIE10) {
            $('html').addClass('ie10'); // detect IE10 version
        }

        if (isIE10 || isIE9 || isIE8) {
            $('html').addClass('ie'); // detect IE10 version
        }
    };

    // runs callback functions set by App.addResponsiveHandler().
    var _runResizeHandlers = function() {
        // reinitialize other subscribed elements
        for (var i = 0; i < resizeHandlers.length; i++) {
            var each = resizeHandlers[i];
            each.call();
        }
    };

    // handle the layout reinitialization on window resize
    var handleOnResize = function() {
        var resize;
        if (isIE8) {
            var currheight;
            $(window).resize(function() {
                if (currheight == document.documentElement.clientHeight) {
                    return; //quite event since only body resized not window.
                }
                if (resize) {
                    clearTimeout(resize);
                }
                resize = setTimeout(function() {
                    _runResizeHandlers();
                }, 50); // wait 50ms until window resize finishes.
                currheight = document.documentElement.clientHeight; // store last body client height
            });
        } else {
            $(window).resize(function() {
                if (resize) {
                    clearTimeout(resize);
                }
                resize = setTimeout(function() {
                    _runResizeHandlers();
                }, 50); // wait 50ms until window resize finishes.
            });
        }
    };

    // Handles portlet tools & actions
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

    // Handles custom checkboxes & radios using jQuery iCheck plugin
    var handleiCheck = function() {

    };

    // 滑动选择组件
    var handleBootstrapSwitch = function() {
        if (!$().bootstrapSwitch) {
            return;
        }
        $('.make-switch').bootstrapSwitch();
    };

    // Handles Bootstrap confirmations
    var handleBootstrapConfirmation = function() {
    }

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

    // Handles Bootstrap Modals.
    var handleModals = function() {

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

    var handleAlerts = function() {

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

    // Handles scrollable contents using jQuery SlimScroll plugin.
    var handleScrollers = function() {
        App.initSlimScroll('.scroller');
    };

    // 图片预览
    var handleFancybox = function() {

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

    // Fix input placeholder issue for IE8 and IE9
    var handleFixInputPlaceholderForIE = function() {
        //fix html5 placeholder attribute for ie7 & ie8
        if (isIE8 || isIE9) { // ie8 & ie9
            // this is html5 placeholder fix for inputs, inputs with placeholder-no-fix class will be skipped(e.g: we need this for password fields)
            $('input[placeholder]:not(.placeholder-no-fix), textarea[placeholder]:not(.placeholder-no-fix)').each(function() {
                var input = $(this);

                if (input.val() === '' && input.attr("placeholder") !== '') {
                    input.addClass("placeholder").val(input.attr('placeholder'));
                }

                input.focus(function() {
                    if (input.val() == input.attr('placeholder')) {
                        input.val('');
                    }
                });

                input.blur(function() {
                    if (input.val() === '' || input.val() == input.attr('placeholder')) {
                        input.val(input.attr('placeholder'));
                    }
                });
            });
        }
    };

    // Handle Select2 Dropdowns
    var handleSelect2 = function() {
        if ($().select2) {
            $.fn.select2.defaults.set("theme", "bootstrap");
            $('.select2me').select2({
                placeholder: "Select",
                width: 'auto',
                allowClear: true
            });
        }
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

            //Core handlers
            handleInit(); // initialize core variables
            handleOnResize(); // set and handle responsive

            //UI Component handlers
            handleMaterialDesign(); // handle material design
            handleiCheck(); // handles custom icheck radio and checkboxes
            handleBootstrapSwitch(); // handle bootstrap switch plugin
            handleScrollers(); // handles slim scrolling contents
            handleFancybox(); // handle fancy box
            handleSelect2(); // handle custom Select2 dropdowns
            handlePortletTools(); // handles portlet action bar functionality(refresh, configure, toggle, remove)
            handleAlerts(); //handle closabled alerts
            handleDropdowns(); // handle dropdowns
            handleTabs(); // handle tabs
            handleTooltips(); // handle bootstrap tooltips
            handlePopovers(); // handles bootstrap popovers
            handleAccordions(); //handles accordions
            handleModals(); // handle modals
            handleBootstrapConfirmation(); // handle bootstrap confirmations
            handleTextareaAutosize(); // handle autosize textareas
            handleCounterup(); // handle counterup instances

            //Handle group element heights
            this.addResizeHandler(handleHeight); // handle auto calculating height on window resize

            // Hacks
            handleFixInputPlaceholderForIE(); //IE8 & IE9 input placeholder issue fix
        },

        //main function to initiate core javascript after ajax complete
        initAjax: function() {
            //handleUniform(); // handles custom radio & checkboxes
            handleiCheck(); // handles custom icheck radio and checkboxes
            handleBootstrapSwitch(); // handle bootstrap switch plugin
            handleDropdownHover(); // handles dropdown hover
            handleScrollers(); // handles slim scrolling contents
            handleSelect2(); // handle custom Select2 dropdowns
            handleFancybox(); // handle fancy box
            handleDropdowns(); // handle dropdowns
            handleTooltips(); // handle bootstrap tooltips
            handlePopovers(); // handles bootstrap popovers
            handleAccordions(); //handles accordions
            handleBootstrapConfirmation(); // handle bootstrap confirmations
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
            var pos = (el && el.size() > 0) ? el.offset().top : 0;

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

        // wrApper function to  block element(indicate loading)
        blockUI: function(options) {

        },

        // wrApper function to  un-block element(finish loading)
        unblockUI: function(target) {

        },

        startPageLoading: function(options) {

        },

        stopPageLoading: function() {

        },

        alert: function(options) {


        },

        //public function to initialize the fancybox plugin
        initFancybox: function() {
            handleFancybox();
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

        // check IE8 mode
        isIE8: function() {
            return isIE8;
        },

        // check IE9 mode
        isIE9: function() {
            return isIE9;
        },

        //check RTL mode
        isRTL: function() {
            return isRTL;
        },

        // check IE8 mode
        isAngularJsApp: function() {
            return (typeof angular == 'undefined') ? false : true;
        },

        getAssetsPath: function() {
            return assetsPath;
        },

        setAssetsPath: function(path) {
            assetsPath = path;
        },

        setGlobalImgPath: function(path) {
            globalImgPath = path;
        },

        getGlobalImgPath: function() {
            return assetsPath + globalImgPath;
        },

        setGlobalPluginsPath: function(path) {
            globalPluginsPath = path;
        },

        getGlobalPluginsPath: function() {
            return assetsPath + globalPluginsPath;
        },

        getGlobalCssPath: function() {
            return assetsPath + globalCssPath;
        },

        // get layout color code by color name
        getBrandColor: function(name) {
            if (brandColors[name]) {
                return brandColors[name];
            } else {
                return '';
            }
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

<!-- END THEME LAYOUT SCRIPTS -->

jQuery(document).ready(function() {
    App.init(); // init metronic core componets
});

/**
 Core script to handle the entire theme and core functions
 **/
var Layout = function () {

    var resBreakpointMd = App.getResponsiveBreakpoint('md');

    var ajaxContentSuccessCallbacks = [];
    var ajaxContentErrorCallbacks = [];

    //* BEGIN:CORE HANDLERS *//
    // this function handles responsive layout on screen size resize or mobile device rotate.

    // Set proper height for sidebar and content. The content and sidebar height must be synced always.
    var handleSidebarAndContentHeight = function () {
        var content = $('.page-content');
        var sidebar = $('.page-sidebar');
        var body = $('body');
        var height;

        if (body.hasClass("page-footer-fixed") === true && body.hasClass("page-sidebar-fixed") === false) {
            var available_height = App.getViewPort().height - $('.page-footer').outerHeight() - $('.page-header').outerHeight();
            var sidebar_height = sidebar.outerHeight();
            if (sidebar_height > available_height) {
                available_height = sidebar_height + $('.page-footer').outerHeight();
            }
            if (content.height() < available_height) {
                content.attr('style', 'min-height:' + available_height + 'px');
            }
        } else {
            if (body.hasClass('page-sidebar-fixed')) {
                height = _calculateFixedSidebarViewportHeight();
                if (body.hasClass('page-footer-fixed') === false) {
                    height = height - $('.page-footer').outerHeight();
                }
            } else {
                var headerHeight = $('.page-header').outerHeight();
                var footerHeight = $('.page-footer').outerHeight();

                if (App.getViewPort().width < resBreakpointMd) {
                    height = App.getViewPort().height - headerHeight - footerHeight;
                } else {
                    height = sidebar.height() + 20;
                }

                if ((height + headerHeight + footerHeight) <= App.getViewPort().height) {
                    height = App.getViewPort().height - headerHeight - footerHeight;
                }
            }
            content.attr('style', 'min-height:' + height + 'px');
        }
    };

    // Handle sidebar menu links
    var handleSidebarMenuActiveLink = function(mode, el) {
        var url = location.hash.toLowerCase();

        var menu = $('.page-sidebar-menu');

        if (mode === 'click' || mode === 'set') {
            el = $(el);
        } else if (mode === 'match') {
            menu.find("li > a").each(function() {
                var path = $(this).attr("href").toLowerCase();
                // url match condition
                if (path.length > 1 && url.substr(1, path.length - 1) == path.substr(1)) {
                    el = $(this);
                    return;
                }
            });
        }

        if (!el || el.size() == 0) {
            return;
        }

        if (el.attr('href').toLowerCase() === 'javascript:;' || el.attr('href').toLowerCase() === '#') {
            return;
        }

        var slideSpeed = parseInt(menu.data("slide-speed"));
        var keepExpand = menu.data("keep-expanded");

        // begin: handle active state
        if (menu.hasClass('page-sidebar-menu-hover-submenu') === false) {
            menu.find('li.nav-item.open').each(function() {
                var match = false;
                $(this).find('li').each(function(){
                    if ($(this).find(' > a').attr('href') === el.attr('href')) {
                        match = true;
                        return;
                    }
                });

                if (match === true) {
                    return;
                }

                $(this).removeClass('open');
                $(this).find('> a > .arrow.open').removeClass('open');
                $(this).find('> .sub-menu').slideUp();
            });
        } else {
            menu.find('li.open').removeClass('open');
        }

        menu.find('li.active').removeClass('active');
        menu.find('li > a > .selected').remove();
        // end: handle active state

        el.parents('li').each(function () {
            $(this).addClass('active');
            $(this).find('> a > span.arrow').addClass('open');

            if ($(this).parent('ul.page-sidebar-menu').size() === 1) {
                $(this).find('> a').append('<span class="selected"></span>');
            }

            if ($(this).children('ul.sub-menu').size() === 1) {
                $(this).addClass('open');
            }
        });

        if (mode === 'click') {
            if (App.getViewPort().width < resBreakpointMd && $('.page-sidebar').hasClass("in")) { // close the menu on mobile view while laoding a page
                $('.page-header .responsive-toggler').click();
            }
        }
    };

    // Handle sidebar menu
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

            if (App.getViewPort().width >= resBreakpointMd && !$('.page-sidebar-menu').attr("data-initialized") && $('body').hasClass('page-sidebar-closed') &&  that.parent('li').parent('.page-sidebar-menu').size() === 1) {
                return;
            }

            var hasSubMenu = that.next().hasClass('sub-menu');

            if (App.getViewPort().width >= resBreakpointMd && that.parents('.page-sidebar-menu-hover-submenu').size() === 1) { // exit of hover sidebar menu
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
        if ($('.sidebar-search').size() !== 0) {
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

    // Helper function to calculate sidebar height for fixed sidebar layout.
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

        if ($('.page-sidebar-fixed').size() === 0) {
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

    };

    // Handles the horizontal menu
    var handleHorizontalMenu = function () {
        //handle tab click
        $('.page-header').on('click', '.hor-menu a[data-toggle="tab"]', function (e) {
            e.preventDefault();
            var nav = $(".hor-menu .nav");
            var active_link = nav.find('li.current');
            $('li.active', active_link).removeClass("active");
            $('.selected', active_link).remove();
            var new_link = $(this).parents('li').last();
            new_link.addClass("current");
            new_link.find("a:first").append('<span class="selected"></span>');
        });

        // handle search box expand/collapse
        $('.page-header').on('click', '.search-form', function (e) {
            $(this).addClass("open");
            $(this).find('.form-control').focus();

            $('.page-header .search-form .form-control').on('blur', function (e) {
                $(this).closest('.search-form').removeClass("open");
                $(this).unbind("blur");
            });
        });

        // handle hor menu search form on enter press
        $('.page-header').on('keypress', '.hor-menu .search-form .form-control', function (e) {
            if (e.which == 13) {
                $(this).closest('.search-form').submit();
                return false;
            }
        });

        // handle header search button click
        $('.page-header').on('mousedown', '.search-form.open .submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.search-form').submit();
        });


        $(document).on('click', '.mega-menu-dropdown .dropdown-menu', function (e) {
            e.stopPropagation();
        });
    };

    // Handles Bootstrap Tabs.
    var handleTabs = function () {
        // fix content height on tab click
        $('body').on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
            handleSidebarAndContentHeight();
        });
    };

    // Handles the go to top button at the footer
    var handleGoTop = function () {
        var offset = 300;
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

    // Hanlde 100% height elements(block, portlet, etc)
    var handle100HeightContent = function () {

        $('.full-height-content').each(function(){
            var target = $(this);
            var height;

            height = App.getViewPort().height -
                $('.page-header').outerHeight(true) -
                $('.page-footer').outerHeight(true) -
                $('.page-title').outerHeight(true) -
                $('.page-bar').outerHeight(true);

            if (target.hasClass('portlet')) {
                var portletBody = target.find('.portlet-body');

                App.destroySlimScroll(portletBody.find('.full-height-content-body')); // destroy slimscroll

                height = height -
                    target.find('.portlet-title').outerHeight(true) -
                    parseInt(target.find('.portlet-body').css('padding-top')) -
                    parseInt(target.find('.portlet-body').css('padding-bottom')) - 5;

                if (App.getViewPort().width >= resBreakpointMd && target.hasClass("full-height-content-scrollable")) {
                    height = height - 35;
                    portletBody.find('.full-height-content-body').css('height', height);
                    App.initSlimScroll(portletBody.find('.full-height-content-body'));
                } else {
                    portletBody.css('min-height', height);
                }
            } else {
                App.destroySlimScroll(target.find('.full-height-content-body')); // destroy slimscroll

                if (App.getViewPort().width >= resBreakpointMd && target.hasClass("full-height-content-scrollable")) {
                    height = height - 35;
                    target.find('.full-height-content-body').css('height', height);
                    App.initSlimScroll(target.find('.full-height-content-body'));
                } else {
                    target.css('min-height', height);
                }
            }
        });
    };
    //* END:CORE HANDLERS *//

    return {
        // Main init methods to initialize the layout
        //IMPORTANT!!!: Do not modify the core handlers call order.

        initHeader: function() {
            handleHorizontalMenu(); // handles horizontal menu
        },

        setSidebarMenuActiveLink: function(mode, el) {
            handleSidebarMenuActiveLink(mode, el);
        },

        initSidebar: function() {
            //layout handlers
            handleFixedSidebar(); // handles fixed sidebar menu
            handleSidebarMenu(); // handles main menu
            handleSidebarToggler(); // handles sidebar hide/show

            if (App.isAngularJsApp()) {
                handleSidebarMenuActiveLink('match'); // init sidebar active links
            }

            App.addResizeHandler(handleFixedSidebar); // reinitialize fixed sidebar on window resize
        },

        initContent: function() {
            handle100HeightContent(); // handles 100% height elements(block, portlet, etc)
            handleTabs(); // handle bootstrah tabs

            App.addResizeHandler(handleSidebarAndContentHeight); // recalculate sidebar & content height on window resize
            App.addResizeHandler(handle100HeightContent); // reinitialize content height on window resize
        },

        initFooter: function() {
            handleGoTop(); //handles scroll to top functionality in the footer
        },

        init: function () {
            this.initHeader();
            this.initSidebar();
            this.initContent();
            this.initFooter();
        },

        loadAjaxContent: function(url, sidebarMenuLink) {

        },

        addAjaxContentSuccessCallback: function(callback) {
            ajaxContentSuccessCallbacks.push(callback);
        },

        addAjaxContentErrorCallback: function(callback) {
            ajaxContentErrorCallbacks.push(callback);
        },

        //public function to fix the sidebar and content height accordingly
        fixContentHeight: function () {
            handleSidebarAndContentHeight();
        },

        initFixedSidebarHoverEffect: function() {

        },

        initFixedSidebar: function() {
            handleFixedSidebar();
        },

    };

}();

if (App.isAngularJsApp() === false) {
    jQuery(document).ready(function() {
        Layout.init(); // init metronic core componets
    });
}

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

    // 当存在隐藏时单击显示区域
    $(".table td,.table th").click(function(){
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
        '函数需要开发者自己定义<br><br>'+
        '标题: title<br>'+
        '评论: comment<br>'+
        '多文件: files （只显示有或无）<br>'+
        '单文件: file<br>'+
        'uid会员: uid<br>'+
        '地区联动: linkage_address<br>'+
        '地区联动名称: linkage_name<br>'+
        '单选字段名称: radio_name<br>'+
        '下拉字段名称: select_name<br>'+
        '复选框字段名称: checkbox_name<br>'+
        '栏目: catid<br>'+
        'URL地址: url<br>'+
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