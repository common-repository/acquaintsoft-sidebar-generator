/*! Acquaint sidebar code library - v3.0.1
 * http://acquaintsoft.com/
 **/

(function (acquaintUi) {

    var docobj = null;
    var htmlobj = null;
    var bodyobj = null;
    var _modal_overlay = null;


    // Popup for add/edit
    acquaintUi.popup = function popup(template, css) {
        initmain();
        return new acquaintUi.acquaintUiWindow(template, css);
    };

    // ajax method for ajaxcall
    acquaintUi.ajax = function ajax(ajaxurl, default_action) {
        initmain();
        return new acquaintUi.acquaintUiAjaxData(ajaxurl, default_action);
    };


    acquaintUi.upgrade_multiselect = function upgrade_multiselect(base) {
        initmain(); // load inti method for default things
        base = jQuery(base || bodyobj);
        var items = base.find('select[multiple]'),
        ajax_items = base.find('input[data-select-ajax]');


        var acquaint_clean_ghosts = function acquaint_clean_ghosts(el) {
            var id = el.attr('id'),
                    s2id = '#s2id_' + id,
                    ghosts = el.parent().find(s2id);

            ghosts.remove();
        };

        // Normal select or multiselect list.
        var acquaint_upgrade_item = function acquaint_upgrade_item() {
            var el = jQuery(this),
                    options = {
                        'closeOnSelect': false,
                        'width': '100%'
                    };

            // Prevent double initialization 
            if (typeof el.data('select2') === 'object') {
                return;
            }
            if (typeof el.data('chosen') === 'object') {
                return;
            }
            if (el.filter('[class*=acf-]').length) {
                return;
            }

            // Prevent double initialization 
            if (el.data('acquaintui-select') === '1') {
                return;
            }

            // Prevent auto-initialization when manually disabled by user.
            if (el.closest('.no-auto-init', base[0]).length) {
                return;
            }

            el.data('acquaintui-select', '1');
            acquaint_clean_ghosts(el);

            
            window.setTimeout(function () {
                el.acquaintuiSelect(options);
            }, 1);
        };

        // Select list with ajax
        var acquaint_upgrade_ajax = function acquaint_upgrade_ajax() {
            var acquaint_format_item = function acquaint_format_item(item) {
                return item.val;
            };

            var acquaint_get_id = function acquaint_get_id(item) {
                return item.key;
            };

            var acquaint_init_selection = function acquaint_init_selection(me, callback) {
                var vals = me.val(),
                        data = [],
                        plain = [];

                jQuery(vals.split(',')).each(function () {
                    var item = this.split('::');
                    plain.push(item[0]);
                    data.push({key: item[0], val: item[1]});
                });

                me.val(plain.join(','));
                callback(data);
            };

            var el = jQuery(this),
                    options = {
                        'closeOnSelect': false,
                        'width': '100%',
                        'multiple': true,
                        'minimumInputLength': 1,
                        'ajax': {
                            url: el.attr('data-select-ajax'),
                            dataType: 'json',
                            quietMillis: 100,
                            cache: true,
                            data: function (term, page) {
                                return {
                                    q: term,
                                };
                            },
                            results: function (data, page) {
                                return {
                                    results: data.items
                                };
                            }
                        },
                        'id': acquaint_get_id,
                        'formatResult': acquaint_format_item,
                        'formatSelection': acquaint_format_item,
                        'initSelection': acquaint_init_selection
                    };

            // Prevent double initialization 
            if (typeof el.data('select2') === 'object') {
                return;
            }
            if (typeof el.data('chosen') === 'object') {
                return;
            }
            if (el.filter('[class*=acf-]').length) {
                return;
            }

            // Prevent double initialization 
            if (el.data('acquaintui-select') === '1') {
                return;
            }

            // Prevent auto-initialization when manually disabled by user
            if (el.closest('.no-auto-init', base[0]).length) {
                return;
            }

            el.data('acquaintui-select', '1');
            acquaint_clean_ghosts(el);

           
            window.setTimeout(function () {
                el.acquaintuiSelect(options);
            }, 1);
        };

        if ('function' === typeof jQuery.fn.each2) {
            items.each2(acquaint_upgrade_item);
            ajax_items.each2(acquaint_upgrade_ajax);
        } else {
            items.each(acquaint_upgrade_item);
            ajax_items.each(acquaint_upgrade_ajax);
        }

    };

   // Confirm box for delete
    acquaintUi.acquaint_confirm = function acquaint_confirm(args) {
        var parent, modal, container, el_msg, el_btn, ind, item, primary_button;

        if (!args instanceof Object) {
            return false;
        }
        if (undefined === args['message']) {
            return false;
        }

        args['modal'] = undefined === args['modal'] ? true : args['modal'];
        args['layout'] = undefined === args['layout'] ? 'fixed' : args['layout'];
        args['parent'] = undefined === args['parent'] ? bodyobj : args['parent'];
        args['buttons'] = undefined === args['buttons'] ? ['OK'] : args['buttons'];
        args['callback'] = undefined === args['callback'] ? false : args['callback'];

        parent = jQuery(args['parent']);

        function acquaint_handle_close() {
            var me = jQuery(this),
                    key = parseInt(me.data('key'));

            if (args['modal']) {
                if (args['layout'] === 'fixed') {
                    acquaintUi._close_modal();
                } else {
                    modal.remove();
                }
            }
            container.remove();

            if ('function' === typeof args['callback']) {
                args['callback'](key);
            }
        }

        if (args['modal']) {
            if (args['layout'] === 'fixed') {
                acquaintUi._make_modal('acquaintui-acquaint_confirm-modal');
            } else {
                modal = jQuery('<div class="acquaintui-acquaint_confirm-modal"></div>')
                        .css({'position': args['layout']})
                        .appendTo(parent);
            }
        }

        container = jQuery('<div class="acquaintui-acquaint_confirm-box"></div>')
                .css({'position': args['layout']})
                .appendTo(parent);

        el_msg = jQuery('<div class="acquaintui-acquaint_confirm-msg"></div>')
                .html(args['message']);

        el_btn = jQuery('<div class="acquaintui-acquaint_confirm-btn"></div>');
        primary_button = true;
        for (ind = 0; ind < args['buttons'].length; ind += 1) {
            item = jQuery('<button></button>')
                    .html(args['buttons'][ind])
                    .addClass(primary_button ? 'button-primary' : 'button-secondary')
                    .data('key', ind)
                    .click(acquaint_handle_close)
                    .prependTo(el_btn);
            primary_button = false;
        }

        el_msg.appendTo(container);
        el_btn.appendTo(container)
                .find('.button-primary')
                .focus();

        return true;
    };

   // Event messages(Success, error)
    acquaintUi.message = function message(args) {
        var parent, msg_box, btn_close, need_insert, debug;
        initmain();

        // Hides the message again
        var acquaint_hide_message = function acquaint_hide_message(ev) {
            ev.preventDefault();
            msg_box.remove();
            return false;
        };

        // Toggle the error-details when click
        var acquaint_toggle_debug = function acquaint_toggle_debug(ev) {
            var me = jQuery(this).closest('.acquaintui-msg');
            me.find('.debug').toggle();
        };

        if ('undefined' === typeof args) {
            return false;
        }

        if ('string' === typeof args || args instanceof Array) {
            args = {'message': args};
        }

        if (args['message'] instanceof Array) {
            args['message'] = args['message'].join('<br />');
        }

        if (!args['message']) {
            return false;
        }

        args['type'] = undefined === args['type'] ? 'ok' : args['type'].toString().toLowerCase();
        args['close'] = undefined === args['close'] ? true : args['close'];
        args['parent'] = undefined === args['parent'] ? '.wrap' : args['parent'];
        args['insert_after'] = undefined === args['insert_after'] ? 'h2' : args['insert_after'];
        args['id'] = undefined === args['id'] ? '' : args['id'].toString().toLowerCase();
        args['class'] = undefined === args['class'] ? '' : args['class'].toString().toLowerCase();
        args['details'] = undefined === args['details'] ? false : args['details'];

        if (args['type'] === 'error' || args['type'] === 'red') {
            args['type'] = 'err';
        }
        if (args['type'] === 'success' || args['type'] === 'green') {
            args['type'] = 'ok';
        }

        parent = jQuery(args['parent']).first();
        if (!parent.length) {
            return false;
        }

        if (args['id'] && jQuery('.acquaintui-msg[data-id="' + args['id'] + '"]').length) {
            msg_box = jQuery('.acquaintui-msg[data-id="' + args['id'] + '"]').first();
            need_insert = false;
        } else {
            msg_box = jQuery('<div><p></p></div>');
            if (args['id']) {
                msg_box.attr('data-id', args['id']);
            }
            need_insert = true;
        }
        msg_box.find('p').html(args['message']);

        if (args['type'] === 'err' && args['details'] && window.JSON) {
            jQuery('<div class="debug" style="display:none"></div>')
                    .appendTo(msg_box)
                    .text(JSON.stringify(args['details']));
            jQuery('<i class="dashicons dashicons-editor-help light"></i>')
                    .prependTo(msg_box.find('p:first'))
                    .click(acquaint_toggle_debug)
                    .after(' ');
        }

        msg_box.removeClass().addClass('updated acquaintui-msg ' + args['class']);
        if ('err' === args['type']) {
            msg_box.addClass('error');
        }

        if (need_insert) {
            if (args['close']) {
                btn_close = jQuery('<a href="#" class="notice-dismiss"></a>');
                btn_close.prependTo(msg_box);

                btn_close.click(acquaint_hide_message);
            }

            if (args['insert_after'] && parent.find(args['insert_after']).length) {
                parent = parent.find(args['insert_after']).first();
                parent.after(msg_box);
            } else {
                parent.prepend(msg_box);
            }
        }

        return true;
    };

   // Tool tip
    acquaintUi.acquaint_tooltip = function acquaint_tooltip(el, args) {
        var tip, parent;
        initmain();

        // Positions the acquaint_tooltip according to the function args.
        var acquaint_position_tip = function acquaint_position_tip(tip) {
            var tip_width = tip.outerWidth(),
                    tip_height = tip.outerHeight(),
                    tip_padding = 5,
                    el_width = el.outerWidth(),
                    el_height = el.outerHeight(),
                    pos = {};

            pos['left'] = (el_width - tip_width) / 2;
            pos['top'] = (el_height - tip_height) / 2;
            pos[ args['pos'] ] = 'auto';

            switch (args['pos']) {
                case 'top':
                    pos['bottom'] = el_height + tip_padding;
                    break;
                case 'bottom':
                    pos['top'] = el_height + tip_padding;
                    break;
                case 'left':
                    pos['right'] = el_width + tip_padding;
                    break;
                case 'right':
                    pos['left'] = el_width + tip_padding;
                    break;
            }
            tip.css(pos);
        };

        // Visible tooltip
        var acquaint_show_tip = function acquaint_show_tip(ev) {
            var tip = jQuery(this)
                    .closest('.acquaintui-tip-box')
                    .find('.acquaintui-tip');

            tip.addClass('acquaintui-visible');
            tip.show();
            acquaint_position_tip(tip);
            window.setTimeout(function () {
                acquaint_position_tip(tip);
            }, 35);
        };

        // Hide tooltip
        var acquaint_hide_tip = function acquaint_hide_tip(ev) {
            var tip = jQuery(this)
                    .closest('.acquaintui-tip-box')
                    .find('.acquaintui-tip');

            tip.removeClass('acquaintui-visible');
            tip.hide();
        };

        // Tooltip state toggle
        var acquaint_toggle_tip = function acquaint_toggle_tip(ev) {
            if (tip.hasClass('acquaintui-visible')) {
                acquaint_hide_tip.call(this, ev);
            } else {
                acquaint_show_tip.call(this, ev);
            }
        };

        if ('string' === typeof args) {
            args = {'content': args};
        }
        if (undefined === args['content']) {
            return false;
        }
        el = jQuery(el);
        if (!el.length) {
            return false;
        }

        args['trigger'] = undefined === args['trigger'] ? 'hover' : args['trigger'].toString().toLowerCase();
        args['pos'] = undefined === args['pos'] ? 'top' : args['pos'].toString().toLowerCase();
        args['class'] = undefined === args['class'] ? '' : args['class'].toString().toLowerCase();

        parent = el.parent();
        if (!parent.hasClass('acquaintui-tip-box')) {
            parent = el
                    .wrap('<span class="acquaintui-tip-box"></span>')
                    .parent()
                    .addClass(args['class'] + '-box');
        }

        tip = parent.find('> .acquaintui-tip');
        el.off();

        if (!tip.length) {
            tip = jQuery('<div class="acquaintui-tip"></div>');
            tip
                    .addClass(args['class'])
                    .addClass(args['pos'])
                    .appendTo(el.parent())
                    .hide();

            if (!isNaN(args['width'])) {
                tip.width(args['width']);
            }
        }

        if ('hover' === args['trigger']) {
            el.on('mouseenter', acquaint_show_tip).on('mouseleave', acquaint_hide_tip);
        } else if ('click' === args['trigger']) {
            el.on('click', acquaint_toggle_tip);
        }

        tip.html(args['content']);

        return true;
    };

    // Upgrade tooltip obejcts
    acquaintUi.upgrade_acquaint_tooltips = function upgrade_acquaint_tooltips() {
        var el = jQuery('[data-acquaintui-acquaint_tooltip]');

        el.each(function () {
            var me = jQuery(this),
                    args = {
                        'content': me.attr('data-acquaintui-acquaint_tooltip'),
                        'pos': me.attr('data-pos'),
                        'trigger': me.attr('data-trigger'),
                        'class': me.attr('data-class'),
                        'width': me.attr('data-width')
                    };

            acquaintUi.acquaint_tooltip(me, args);
        });
    };

   // convert array to object
    acquaintUi.obj = function (value) {
        var obj = {};

        if (value instanceof Object) {
            obj = value;
        } else if (value instanceof Array) {
            if (typeof value.reduce === 'function') {
                obj = value.reduce(function (o, v, i) {
                    o[i] = v;
                    return o;
                }, {});
            } else {
                for (var i = value.length - 1; i > 0; i -= 1) {
                    if (value[i] !== undefined) {
                        obj[i] = value[i];
                    }
                }
            }
        } else if (typeof value === 'string') {
            obj.scalar = value;
        } else if (typeof value === 'number') {
            obj.scalar = value;
        } else if (typeof value === 'boolean') {
            obj.scalar = value;
        }

        return obj;
    };



  // Initilize main function
    function initmain() {
        if (null !== htmlobj) {
            return;
        }

        docobj = jQuery(document);
        htmlobj = jQuery('html');
        bodyobj = jQuery('body');

        boxes_init();
        tabs_init();

        if (!bodyobj.hasClass('no-auto-init')) {
           
            window.setTimeout(function () {
                acquaintUi.upgrade_multiselect();
                acquaintUi.upgrade_acquaint_tooltips();
            }, 20);
        }

        acquaintUi.binary = new acquaintUi.acquaintUiBinary();
    }

   // Initilize wordpress accordian box
    function boxes_init() {
        // Toggle the box state (open/closed)
        var toggle_box = function toggle_box(ev) {
            var box = jQuery(this).closest('.acquaintui-box');
            ev.preventDefault();

            // Don't toggle the box if it is static.
            if (box.hasClass('static')) {
                return false;
            }

            box.toggleClass('closed');
            return false;
        };

        bodyobj.on('click', '.acquaintui-box > h3', toggle_box);
        bodyobj.on('click', '.acquaintui-box > h3 > .toggle', toggle_box);
    }

   // wordpress tab Initilize
    function tabs_init() {
        
        // Open/close tab
        var acquaint_activate_tab = function acquaint_activate_tab(ev) {
            var tab = jQuery(this),
                    all_tabs = tab.closest('.acquaintui-tabs'),
                    content = all_tabs.next('.acquaintui-tab-contents'),
                    active = all_tabs.find('.active.tab'),
                    sel_tab = tab.attr('href'),
                    sel_active = active.attr('href'),
                    content_tab = content.find(sel_tab),
                    content_active = content.find(sel_active);

            // Close prev tab.
            if (!tab.hasClass('active')) {
                active.removeClass('active');
                content_active.removeClass('active');
            }

            // Open selected tab.
            tab.addClass('active');
            content_tab.addClass('active');

            ev.preventDefault();
            return false;
        };

       // popup background layer
        acquaintUi._make_modal = function (the_class, html_classes) {
            var overlay = acquaintUi._modal_overlay();

            overlay.removeClass().addClass('acquaintui-overlay');
            if (the_class) {
                overlay.addClass(the_class);
            }

            bodyobj.addClass('acquaintui-has-overlay');
            htmlobj.addClass('acquaintui-no-scroll');
            if (html_classes) {
                htmlobj.addClass(html_classes);
            }

            return overlay;
        };

       // popup/modal overlay
        acquaintUi._modal_overlay = function () {
            if (null === _modal_overlay) {
                _modal_overlay = jQuery('<div></div>')
                        .addClass('acquaintui-overlay')
                        .appendTo(bodyobj);
            }
            return _modal_overlay;
        };

        // Close popup
        acquaintUi._close_modal = function (html_classes) {
            bodyobj.removeClass('acquaintui-has-overlay');
            htmlobj.removeClass('acquaintui-no-scroll');
            if (html_classes) {
                htmlobj.removeClass(html_classes);
            }
        };

        bodyobj.on('click', '.acquaintui-tabs .tab', acquaint_activate_tab);
    }

    // Initialize the object.
    jQuery(function () {
        initmain();
    });

}(window.acquaintUi = window.acquaintUi || {}));




(function (acquaintUi) {


  
    var nextid = 1;
    var all_popup_list = {};

   // return currently open all popup
    acquaintUi.popups = function () {
        return all_popup_list;
    };

  // Popup window
    acquaintUi.acquaintUiWindow = function (_template, _css) {

      
        var curobj = this;
        var visible_obj = false;
        var modal_obj = false;
        var titleclose = true;
        var backclose = true;
        var width_o = 740;
        var height_o = 400;
        var title_default = 'Modal';
        var content_default = '';
        var classes_list = '';
        var animation_in_effect = '';
        var animation_out_effect = '';
        var content_changed_flag = false;
        var need_check_size_flag = false;
        var snap_obj = {top: false, left: false, right: false, bottom: false};
        var slidein_val = 'none';
        var onshow_obj = null;
        var onhide_obj = null;
        var onclose_obj = null;
        var onresize_obj = null;
        var wnd_obj = null;
        var popup_obj = null;
        var status_type = 'hidden';
        var slidein_status_type = 'none';
        var icon_collapse_obj = '';
        var icon_expand_obj = '';
        var slidein_speed_data = 400;
        
        
        this.id = 0;


        // Retunr mdal poperty
        this.is_modal = function is_modal() {
            return modal_obj;
        };

       //visible status
        this.is_visible = function is_visible() {
            return visible_obj;
        };

        // return slide in property
        this.is_slidein = function is_slidein() {
            return slidein_val;
        };

       // return snap obj property
        this.get_snap = function get_snap() {
            return snap_obj;
        };
        
        // modal property
        this.modal = function modal(state, background_close) {
            if (undefined === background_close) {
                background_close = true;
            }

            modal_obj = (state ? true : false);
            backclose = (background_close ? true : false);

            _update_window();
            return curobj;
        };

       // window size
        this.size = function size(width, height) {
            var new_width = parseFloat(width),
                    new_height = parseFloat(height);

            if (isNaN(new_width)) {
                new_width = 0;
            }
            if (isNaN(new_height)) {
                new_height = 0;
            }
            if (new_width >= 0) {
                width_o = new_width;
            }
            if (new_height >= 0) {
                height_o = new_height;
            }

            need_check_size_flag = true;
            _update_window();
            return curobj;
        };

        // popup snap constraits
        this.snap = function snap() {
            var is_middle = false;
            snap_obj = {top: false, left: false, right: false, bottom: false};

            for (var i = 0; i < arguments.length && !is_middle; i += 1) {
                var snap_to = arguments[i].toLowerCase();

                switch (snap_to) {
                    case 'top':
                    case 'left':
                    case 'right':
                    case 'bottom':
                        snap_obj[snap_to] = true;
                        break;

                    case 'none':
                    case 'center':
                        is_middle = true;
                        break;
                }
            }

            if (is_middle) {
                snap_obj = {top: false, left: false, right: false, bottom: false};
            }

            need_check_size_flag = true;
            _update_window();
            return curobj;
        };

        // slidein popup
        this.slidein = function slidein(option, duration) {
            option = option.toLowerCase();
            slidein_val = 'none';

            switch (option) {
                case 'down':
                    slidein_val = 'down';
                    icon_collapse_obj = 'dashicons-arrow-down-alt2';
                    icon_expand_obj = 'dashicons-arrow-up-alt2';
                    break;

                case 'up':
                    slidein_val = 'up';
                    icon_collapse_obj = 'dashicons-arrow-up-alt2';
                    icon_expand_obj = 'dashicons-arrow-down-alt2';
                    break;
            }

            if (!isNaN(duration) && duration >= 0) {
                slidein_speed_data = duration;
            }

            need_check_size_flag = true;
            _update_window();
            return curobj;
        };

      // open/close animation popup
        this.animate = function animate(anim_in, anim_out) {
            var can_animate = false,
                    domPrefixes = 'Webkit Moz O ms Khtml'.split(' ');

            if (popup_obj[0].style.animationName !== undefined) {
                can_animate = true;
            }

            if (can_animate === false) {
                for (var i = 0; i < domPrefixes.length; i++) {
                    if (popup_obj[0].style[ domPrefixes[i] + 'AnimationName' ] !== undefined) {
                        can_animate = true;
                        break;
                    }
                }
            }

            if (!can_animate) {
                // Sorry guys, CSS animations are not supported...
                anim_in = '';
                anim_out = '';
            }

            animation_in_effect = anim_in;
            animation_out_effect = anim_out;

            return curobj;
        };

     
        this.set_class = function set_class(class_names) {
            classes_list = class_names;
            content_changed_flag = true;

            _update_window();
            return curobj;
        };

        // loading overlay
        this.loading = function loading(state) {
            if (state) {
                popup_obj.addClass('acquaintui-loading');
            } else {
                popup_obj.removeClass('acquaintui-loading');
            }
            return curobj;
        };

      // on resize
        this.onresize = function onresize(callback) {
            onresize_obj = callback;
            return curobj;
        };

       
        this.onshow = function onshow(callback) {
            onshow_obj = callback;
            return curobj;
        };

        this.onhide = function onhide(callback) {
            onhide_obj = callback;
            return curobj;
        };

       
        this.onclose = function onclose(callback) {
            onclose_obj = callback;
            return curobj;
        };

       // confirm
        this.acquaint_confirm = function acquaint_confirm(args) {
            if (status_type !== 'visible') {
                return curobj;
            }
            if (!args instanceof Object) {
                return curobj;
            }

            args['layout'] = 'absolute';
            args['parent'] = popup_obj;

            acquaintUi.acquaint_confirm(args);

            return curobj;
        };

        // window title
        this.title = function title(new_title, can_close) {
            if (undefined === can_close) {
                can_close = true;
            }

            title_default = new_title;
            titleclose = (can_close ? true : false);

            _update_window();
            return curobj;
        };

        // window content
        this.content = function content(data, move) {
            if (data instanceof jQuery) {
                if (move) {
                    // Move the object into the popup.
                    content_default = data;
                } else {
                    // Create a copy of the object inside the popup.
                    content_default = data.html();
                }
            } else {
                // Content is text, will always be a copy.
                content_default = data;
            }

            need_check_size_flag = true;
            content_changed_flag = true;

            _update_window();
            return curobj;
        };
        // popup window
        this.show = function show() {
            // Add the DOM elements to the document body and add event handlers.
            wnd_obj.appendTo(jQuery('body'));
            popup_obj.hide();
            _hook();

            visible_obj = true;
            need_check_size_flag = true;
            status_type = 'visible';

            _update_window();

          
            popup_obj.hide();
            window.setTimeout(function () {
         
                popup_obj.show();
            }, 2);

            if ('none' === slidein_val && animation_in_effect) {
                popup_obj.addClass(animation_in_effect + ' animated');
                popup_obj.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                    popup_obj.removeClass('animated');
                    popup_obj.removeClass(animation_in_effect);
                });
            }

            if (typeof onshow_obj === 'function') {
                onshow_obj.apply(curobj, [curobj.$()]);
            }
            return curobj;
        };

      // hide popup
        this.hide = function hide() {
            function hide_popup() {
                if ('none' === slidein_val) {
                    // Remove the popup from the DOM (but keep it in memory)
                    wnd_obj.detach();
                    _unhook();
                }

                visible_obj = false;
                status_type = 'hidden';
                _update_window();

                if (typeof onhide_obj === 'function') {
                    onhide_obj.apply(curobj, [curobj.$()]);
                }
            }

            if ('none' === slidein_val && animation_out_effect) {
                popup_obj.addClass(animation_out_effect + ' animated');
                popup_obj.one(
                        'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend',
                        function () {
                            popup_obj.removeClass('animated');
                            popup_obj.removeClass(animation_out_effect);
                            hide_popup();
                        }
                );
            } else {
                hide_popup();
            }

            return curobj;
        };

        // destroy popup
        this.destroy = function destroy() {
            var orig_onhide = onhide_obj;

            // Prevent infinite loop when calling .destroy inside onclose handler.
            if (status_type === 'closing') {
                return;
            }

            onhide_obj = function () {
                if (typeof orig_onhide === 'function') {
                    orig_onhide.apply(curobj, [curobj.$()]);
                }

                status_type = 'closing';

                if (typeof onclose_obj === 'function') {
                    onclose_obj.apply(curobj, [curobj.$()]);
                }

                // Completely remove the popup from the memory.
                wnd_obj.remove();
                wnd_obj = null;
                popup_obj = null;

                delete all_popup_list[curobj.id];

                curobj = null;
            };

            curobj.hide();
        };

       
        this.on = function on(event, selector, callback) {
            wnd_obj.on(event, selector, callback);

            if (wnd_obj.filter(selector).length) {
                wnd_obj.on(event, callback);
            }

            return curobj;
        };

       
        this.off = function off(event, selector, callback) {
            wnd_obj.off(event, selector, callback);

            if (wnd_obj.filter(selector).length) {
                wnd_obj.off(event, callback);
            }

            return curobj;
        };

       
        this.$ = function $(selector) {
            if (selector) {
                return wnd_obj.find(selector);
            } else {
                return wnd_obj;
            }
        };


        // ==============================
        // == Private functions =========

        // Init main function
        function initmain() {
            curobj.id = nextid;
            nextid += 1;
            all_popup_list[curobj.id] = curobj;

            if (!_template) {
                // Defines the default popup template.
                _template = '<div class="acquaintui-popup">' +
                        '<div class="popup-title">' +
                        '<span class="the-title"></span>' +
                        '<span class="popup-close"><i class="dashicons dashicons-no-alt"></i></span>' +
                        '</div>' +
                        '<div class="popup-content"></div>' +
                        '</div>';
            }

            // Create the DOM elements.
            wnd_obj = jQuery(_template);

            // Add custom CSS.
            if (_css) {
                jQuery('<style>' + _css + '</style>').prependTo(wnd_obj);
            }

            // Add default selector class to the base element if the class is missing.
            if (!wnd_obj.filter('.popup').length && !wnd_obj.find('.popup').length) {
                wnd_obj.addClass('popup');
            }

            // See comments in top section for difference between wnd_obj and popup_obj.
            if (wnd_obj.hasClass('popup')) {
                popup_obj = wnd_obj;
            } else {
                popup_obj = wnd_obj.find('.popup').first();
            }

            // Add supported content modification methods.
            if (!popup_obj.find('.popup-title').length) {
                curobj.title = function () {
                    return curobj;
                };
            }

            if (!popup_obj.find('.popup-content').length) {
                curobj.content = function () {
                    return curobj;
                };
            }

            if (!popup_obj.find('.slidein-toggle').length) {
                if (popup_obj.find('.popup-title .popup-close').length) {
                    popup_obj.find('.popup-title .popup-close').addClass('slidein-toggle');
                } else if (popup_obj.find('.popup-title').length) {
                    popup_obj.find('.popup-title').addClass('slidein-toggle');
                } else {
                    popup_obj.prepend('<span class="slidein-toggle only-slidein"><i class="dashicons"></i></span>');
                }
            }

            visible_obj = false;
        }

       // Event listner
        function _hook() {
            if (popup_obj && !popup_obj.data('hooked')) {
                popup_obj.data('hooked', true);
                popup_obj.on('click', '.popup-close', _click_close);
                popup_obj.on('click', '.popup-title', _click_title);
                popup_obj.on('click', '.close', curobj.hide);
                popup_obj.on('click', '.destroy', curobj.destroy);
                popup_obj.on('click', 'thead .check-column :checkbox', _toggle_checkboxes);
                popup_obj.on('click', 'tfoot .check-column :checkbox', _toggle_checkboxes);
                popup_obj.on('click', 'tbody .check-column :checkbox', _check_checkboxes);
                jQuery(window).on('resize', _resize_and_move);

                if (jQuery().draggable !== undefined) {
                    popup_obj.draggable({
                        containment: jQuery('body'),
                        scroll: false,
                        handle: '.popup-title'
                    });
                }
            }
        }

       // remove event listners
        function _unhook() {
            if (popup_obj && popup_obj.data('hooked')) {
                popup_obj.data('hooked', false);
                popup_obj.off('click', '.popup-close', _click_close);
                popup_obj.off('click', '.popup-title', _click_title);
                popup_obj.off('click', '.close', curobj.hide);
                popup_obj.off('click', '.check-column :checkbox', _toggle_checkboxes);
                jQuery(window).off('resize', _resize_and_move);
            }
        }

       // update window
        function _update_window() {
            if (!wnd_obj) {
                return false;
            }
            if (!popup_obj) {
                return false;
            }

            var _overlay = acquaintUi._modal_overlay(),
                    _el_title = popup_obj.find('.popup-title'),
                    _el_content = popup_obj.find('.popup-content'),
                    _title_span = _el_title.find('.the-title');

            // Window title.
            if (_template && !_title_span.length) {
                _title_span = _el_title;
            }
            _title_span.html(title_default);

            if (titleclose) {
                popup_obj.removeClass('no-close');
            } else {
                popup_obj.addClass('no-close');
            }

            // Display a copy of the specified content.
            if (content_changed_flag) {
                // Remove the current button bar.
                wnd_obj.find('.buttons').remove();
                popup_obj.addClass('no-buttons');

                // Update the content.
                if (content_default instanceof jQuery) {
                    // content_default is a jQuery element.
                    _el_content.empty().append(content_default);
                } else {
                    // content_default is a HTML string.
                    _el_content.html(content_default);
                }

                // Move the buttons out of the content area.
                var buttons = _el_content.find('.buttons');
                if (buttons.length) {
                    buttons.appendTo(popup_obj);
                    popup_obj.removeClass('no-buttons');
                }

                // Add custom class to the popup.
                popup_obj.addClass(classes_list);

                content_changed_flag = false;
            }

            if (_overlay instanceof jQuery) {
                _overlay.off('click', _modal_close);
            }

            // Show or hide the window and modal background.
            if (visible_obj) {
                _show_the_popup();

                if (modal_obj) {
                    acquaintUi._make_modal('', 'has-popup');
                }

                if (backclose) {
                    _overlay.on('click', _modal_close);
                }

                if (need_check_size_flag) {
                    need_check_size_flag = false;
                    _resize_and_move();
                }

                // Allow the browser to display + render the title first.
                window.setTimeout(function () {
                    if ('down' === slidein_val) {
                        _el_content.css({bottom: _el_title.height() + 1});
                    } else {
                        _el_content.css({top: _el_title.height() + 1});
                    }
                    if (!height_o) {
                        window.setTimeout(_resize_and_move, 5);
                    }
                }, 5);
            } else {
                _hide_the_popup();

                var wnd, remove_modal = true;
                for (wnd in all_popup_list) {
                    if (all_popup_list[wnd] === curobj) {
                        continue;
                    }
                    if (!all_popup_list[wnd].is_visible()) {
                        continue;
                    }
                    if (all_popup_list[wnd].is_modal()) {
                        remove_modal = false;
                        break;
                    }
                }

                if (remove_modal) {
                    acquaintUi._close_modal('has-popup no-scroll can-scroll');
                }
            }

            // Adjust the close-icon according to slide-in state.
            var icon = popup_obj.find('.popup-close .dashicons');
            if (icon.length) {
                if ('none' === slidein_val) {
                    icon.removeClass().addClass('dashicons dashicons-no-alt');
                } else {
                    if ('collapsed' === slidein_status_type) {
                        icon.removeClass().addClass('dashicons').addClass(icon_collapse_obj);
                    } else if ('expanded' === slidein_status_type) {
                        icon.removeClass().addClass('dashicons').addClass(icon_expand_obj);
                    }
                }
            }

            // Remove all "slidein-..." classes from the popup.
            popup_obj[0].className = popup_obj[0].className.replace(/\sslidein-.+?\b/g, '');

            if ('none' === slidein_val) {
                popup_obj.removeClass('slidein');
                popup_obj.removeClass('wdev-slidein');
                popup_obj.addClass('wdev-window');
            } else {
                popup_obj.addClass('slidein');
                popup_obj.addClass('slidein-' + slidein_val);
                popup_obj.addClass('slidein-' + slidein_status_type);
                popup_obj.addClass('wdev-slidein');
                popup_obj.removeClass('wdev-window');
            }
            if (snap_obj.top) {
                popup_obj.addClass('snap-top');
            }
            if (snap_obj.left) {
                popup_obj.addClass('snap-left');
            }
            if (snap_obj.right) {
                popup_obj.addClass('snap-right');
            }
            if (snap_obj.bottom) {
                popup_obj.addClass('snap-bottom');
            }
        }

        // popup
        function _show_the_popup() {
            popup_obj.show();

            // We have a collapsed slide-in. Animate it.
            var have_slidein = 'none' !== slidein_val,
                    can_expand = ('collapsed' === slidein_status_type);

            if (have_slidein) {
                // First time the slide in is opened? Animate it.
                if (!can_expand && 'none' === slidein_status_type) {
                    var styles = {};
                    slidein_status_type = 'collapsed';
                    styles = _get_popup_size(styles);
                    styles = _get_popup_pos(styles);
                    popup_obj.css(styles);

                    can_expand = true;
                }

                if (can_expand) {
                    slidein_status_type = 'expanding';
                    _resize_and_move(slidein_speed_data);
                    need_check_size_flag = false;

                    window.setTimeout(function () {
                        slidein_status_type = 'expanded';
                        _update_window();
                        window.setTimeout(_resize_and_move, 10);
                    }, slidein_speed_data);
                }
            }
        }

       // hide popup
        function _hide_the_popup() {
            switch (slidein_val) {
                case 'up':
                case 'down':
                    var can_collapse = ('expanded' === slidein_status_type);

                    if (can_collapse) {
                        var wnd = jQuery(window),
                                window_height = wnd.innerHeight(),
                                popup_pos = popup_obj.position(),
                                styles = {};

                        // First position the popup using the `top` property only.
                        styles['margin-top'] = 0;
                        styles['margin-bottom'] = 0;
                        styles['bottom'] = 'auto';
                        styles['top'] = popup_pos.top;
                        popup_obj.css(styles);

                        // Calculate the destination position of the popup and animate.
                        slidein_status_type = 'collapsing';
                        styles = _get_popup_pos();
                        popup_obj.animate(styles, slidein_speed_data, function () {
                            slidein_status_type = 'collapsed';
                            _update_window();
                            window.setTimeout(_resize_and_move, 10);
                        });
                    }
                    break;

                default:
                    popup_obj.hide();
                    break;
            }
        }

       // click on title
        function _click_title(ev) {
            if ('none' !== slidein_val) {
                if (visible_obj) {
                    curobj.hide();
                } else {
                    curobj.show();
                }
                ev.stopPropagation();
            }
        }

       // close click 
        function _click_close(ev) {
            if ('none' === slidein_val) {
                curobj.hide();
            } else {
                if (visible_obj) {
                    curobj.hide();
                } else {
                    curobj.show();
                }
            }
            ev.stopPropagation();
        }

       // modal close
        function _modal_close() {
            var _overlay = acquaintUi._modal_overlay();
            if (!wnd_obj) {
                return false;
            }
            if (!_overlay instanceof jQuery) {
                return false;
            }

            _overlay.off('click', _modal_close);
            curobj.hide();
        }

        // resize and move 
        function _resize_and_move(duration) {
            if (!popup_obj) {
                return false;
            }

            if (typeof onresize_obj === 'function') {
                onresize_obj.apply(curobj, [curobj.$()]);
            } else {
                var styles = {};

                styles = _get_popup_size(styles);
                styles = _get_popup_pos(styles);

                // Size and position.
                if (!isNaN(duration) && duration > 0) {
                    popup_obj.animate(styles, duration);
                } else {
                    popup_obj.css(styles);
                }
            }
        }

        // get popup size
        function _get_popup_size(size) {
            var wnd = jQuery(window),
                    window_width = wnd.innerWidth(),
                    window_height = wnd.innerHeight(),
                    border_x = parseInt(popup_obj.css('border-left-width')) +
                    parseInt(popup_obj.css('border-right-width')),
                    border_y = parseInt(popup_obj.css('border-top-width')) +
                    parseInt(popup_obj.css('border-bottom-width')),
                    real_width = width_o + border_x,
                    real_height = height_o + border_y;

            if ('object' !== typeof size) {
                size = {};
            }

            // Calculate the width and height ------------------------------

            if (!height_o || !width_o) {
                var get_width = !width_o,
                        get_height = !height_o,
                        new_width = 0, new_height = 0;

                popup_obj.find('*').each(function () {
                    var el = jQuery(this),
                            pos = el.position(),
                            el_width = el.outerWidth() + pos.left,
                            el_height = el.outerHeight() + pos.top;

                    if (get_width && new_width < el_width) {
                        new_width = el_width;
                    }
                    if (get_height && new_height < el_height) {
                        new_height = el_height;
                    }
                });

                if (get_width) {
                    real_width = new_width + border_x;
                }
                if (get_height) {
                    real_height = new_height + border_y;
                }
            }

            if (snap_obj.left && snap_obj.right) {
                // Snap to 2 sides: full width.
                size['width'] = window_width - border_x;
            } else {
                if (window_width < real_width) {
                    real_width = window_width;
                }
                size['width'] = real_width - border_x;
            }

            if (snap_obj.top && snap_obj.bottom) {
                // Snap to 2 sides: full height.
                size['height'] = window_height - border_y;
            } else {
                if (window_height < real_height) {
                    real_height = window_height;
                }
                size['height'] = real_height - border_y;
            }

            return size;
        }

       // popup position
        function _get_popup_pos(styles) {
            var wnd = jQuery(window),
                    el_toggle = popup_obj.find('.slidein-toggle'),
                    window_width = wnd.innerWidth(),
                    window_height = wnd.innerHeight(),
                    border_x = parseInt(popup_obj.css('border-left-width')) +
                    parseInt(popup_obj.css('border-right-width')),
                    border_y = parseInt(popup_obj.css('border-top-width')) +
                    parseInt(popup_obj.css('border-bottom-width'));

            if ('object' !== typeof styles) {
                styles = {};
            }
            if (undefined === styles['width'] || undefined === styles['height']) {
                styles = _get_popup_size(styles);
            }

            // Position X: (empty) / left / right / left + right
            if (!snap_obj.left && !snap_obj.right) {
                // Center X.
                styles['left'] = (window_width - styles['width']) / 2;
            } else if (snap_obj.left && snap_obj.right) {
                // Snap to 2 sides.
                styles['left'] = 0;
            } else {
                // Snap to one side.
                if (snap_obj.left) {
                    styles['left'] = 0;
                }
                if (snap_obj.right) {
                    styles['left'] = window_width - styles['width'] - border_x;
                }
            }

            if ('none' !== slidein_val && ('collapsed' === slidein_status_type || 'collapsing' === slidein_status_type)) {
                // We have a collapsed slide-in. Y-position is fixed.
                if ('down' === slidein_val) {
                    styles['top'] = el_toggle.outerHeight() - styles['height'];
                } else {
                    styles['top'] = window_height - el_toggle.outerHeight();
                }
            } else {
                // Position Y: (empty) / top / bottom / top + bottom
                if (!snap_obj.top && !snap_obj.bottom) {
                    // Center Y.
                    styles['top'] = (window_height - styles['height']) / 2;
                } else if (snap_obj.top && snap_obj.bottom) {
                    // Snap to 2 sides.
                    styles['top'] = 0;
                } else {
                    // Snap to one side.
                    if (snap_obj.top) {
                        styles['top'] = 0;
                    }
                    if (snap_obj.bottom) {
                        styles['top'] = window_height - styles['height'] - border_y;
                    }
                }
            }

            styles['margin-top'] = 0;
            styles['margin-bottom'] = 0;
            styles['bottom'] = 'auto';
            styles['right'] = 'auto';

            if (undefined === styles['top']) {
                styles['top'] = 'auto';
            }
            if (undefined === styles['left']) {
                styles['left'] = 'auto';
            }

            return styles;
        }
        // checkbox toggles
        function _toggle_checkboxes(ev) {
            var chk = jQuery(this),
                    c = chk.prop('checked'),
                    toggle = (ev.shiftKey);

            // Toggle checkboxes inside the table body
            chk
                    .closest('table')
                    .children('tbody, thead, tfoot')
                    .filter(':visible')
                    .children()
                    .children('.check-column')
                    .find(':checkbox')
                    .prop('checked', c);
        }

       // check checkboxes
        function _check_checkboxes(ev) {
            var chk = jQuery(this),
                    unchecked = chk
                    .closest('tbody')
                    .find(':checkbox')
                    .filter(':visible')
                    .not(':checked');

            chk
                    .closest('table')
                    .children('thead, tfoot')
                    .find(':checkbox')
                    .prop('checked', (0 === unchecked.length));

            return true;
        }

        // Initialize the popup window.
        curobj = this;
        initmain();

    }; /* ** End: acquaintUiWindow ** */

}(window.acquaintUi = window.acquaintUi || {}));


(function (acquaintUi) {


    /// progress bar
    acquaintUi.acquaintUiProgress = function () {

       
        var curobj = this;
        var current_obj = 0;
        var max_val = 100;
        var label_val = '';       
        var _el = null;
        var _el_bar = null;
        var _el_full = null;
        var _el_label = null;
        var _el_percent = null;
        
        // progress bar value
        this.value = function value(val) {
            if (!isNaN(val)) {
                current_obj = parseInt(val);
                update_method();
            }
            return curobj;
        };

       // max value for progressbar
        this.max = function max(val) {
            if (!isNaN(val)) {
                max_val = parseInt(val);
                update_method();
            }
            return curobj;
        };

        
        this.label = function label(val) {
            label_val = val;
            update_method();
            return curobj;
        };

       
        this.on = function on(event, selector, callback) {
            _el.on(event, selector, callback);
            return curobj;
        };

        
        this.off = function off(event, selector, callback) {
            _el.off(event, selector, callback);
            return curobj;
        };

       
        this.$ = function $() {
            return _el;
        };

        // initmain
        function initmain() {
            max_val = 100;
            current_obj = 0;

            _el = jQuery('<div class="acquaintui-progress-wrap"></div>');
            _el_full = jQuery('<div class="acquaintui-progress-full"></div>');
            _el_bar = jQuery('<div class="acquaintui-progress"></div>');
            _el_label = jQuery('<div class="acquaintui-progress-label"></div>');
            _el_percent = jQuery('<div class="acquaintui-progress-percent"></div>');

            // Attach the window to the current page.
            _el_bar.appendTo(_el_full);
            _el_percent.appendTo(_el_full);
            _el_full.appendTo(_el);
            _el_label.appendTo(_el);

            update_method();
        }

        // update progress bar
        function update_method() {
            var percent = current_obj / max_val * 100;
            if (percent < 0) {
                percent = 0;
            }
            if (percent > 100) {
                percent = 100;
            }

            _el_bar.width(percent + '%');
            _el_percent.text(parseInt(percent) + ' %');

            if (label_val && label_val.length) {
                _el_label.html(label_val);
                _el_label.show();
            } else {
                _el_label.hide();
            }
        }

        // Initialize the progress bar.
        curobj = this;
        initmain();

    }; /* ** End: acquaintUiProgress ** */

}(window.acquaintUi = window.acquaintUi || {}));

(function (acquaintUi) {

    if (acquaintUi.add_action) {
        return;
    }

    
    acquaintUi.filters = acquaintUi.filters || {};
    // new action call back
    acquaintUi.add_action = function (tag, callback, priority) {
        acquaintUi.add_filter(tag, callback, priority);
    };

   // remove action callback
    acquaintUi.remove_action = function (tag, callback) {
        acquaintUi.remove_filter(tag, callback);
    };

   // add filter
    acquaintUi.add_filter = function (tag, callback, priority) {
        if (undefined === callback) {
            return;
        }

        if (undefined === priority) {
            priority = 10;
        }

        // If the tag doesn't exist, create it.
        acquaintUi.filters[ tag ] = acquaintUi.filters[ tag ] || [];
        acquaintUi.filters[ tag ].push({priority: priority, callback: callback});
    };

    // remove filter callback
    acquaintUi.remove_filter = function (tag, callback) {
        acquaintUi.filters[ tag ] = acquaintUi.filters[ tag ] || [];

        acquaintUi.filters[ tag ].forEach(function (filter, i) {
            if (filter.callback === callback) {
                acquaintUi.filters[ tag ].splice(i, 1);
            }
        });
    };

    // remove all callback options
    acquaintUi.remove_all_actions = function (tag, priority) {
        acquaintUi.remove_all_filters(tag, priority);
    };

    // remove all filters callback
    acquaintUi.remove_all_filters = function (tag, priority) {
        acquaintUi.filters[ tag ] = acquaintUi.filters[ tag ] || [];

        if (undefined === priority) {
            acquaintUi.filters[ tag ] = [];
        } else {
            acquaintUi.filters[ tag ].forEach(function (filter, i) {
                if (filter.priority === priority) {
                    acquaintUi.filters[ tag ].splice(i, 1);
                }
            });
        }
    };

   // do action
    acquaintUi.do_action = function (tag, options) {
        var actions = [];

        if (undefined !== acquaintUi.filters[ tag ] && acquaintUi.filters[ tag ].length > 0) {

            acquaintUi.filters[ tag ].forEach(function (hook) {

                actions[ hook.priority ] = actions[ hook.priority ] || [];
                actions[ hook.priority ].push(hook.callback);

            });

            actions.forEach(function (hooks) {

                hooks.forEach(function (callback) {
                    callback(options);
                });

            });
        }
    };

    // apply filter
    acquaintUi.apply_filters = function (tag, value, options) {
        var filters = [];

        if (undefined !== acquaintUi.filters[ tag ] && acquaintUi.filters[ tag ].length > 0) {

            acquaintUi.filters[ tag ].forEach(function (hook) {

                filters[ hook.priority ] = filters[ hook.priority ] || [];
                filters[ hook.priority ].push(hook.callback);
            });

            filters.forEach(function (hooks) {

                hooks.forEach(function (callback) {
                    value = callback(value, options);
                });

            });
        }

        return value;
    };

    

}(window.acquaintUi = window.acquaintUi || {}));


(function (acquaintUi) {


    //ajaxdata
    acquaintUi.acquaintUiAjaxData = function (_ajaxurl, _default_action) {

        var curobj = this;
        var _void_frame = null;
        var _data = {};
        var _onprogress = null;
        var _ondone = null;
        var _support_progress = false;
        var _support_file_api = false;
        var _support_form_data = false;


        // set data which is used in ajax
        this.data = function data(obj) {
            _data = obj;
            return curobj;
        };

        /**
         * Returns an ajax-compatible version of the data object passed in.
         * 
         */
        this.extract_data = function extract_data(obj) {
            _data = obj;
            return acquaint_get_data(undefined, false);
        };

        
        //  Define the upload/download progress callback.
        
        this.onprogress = function onprogress(callback) {
            _onprogress = callback;
            return curobj;
        };

        // callback when ajax done
        this.ondone = function ondone(callback) {
            _ondone = callback;
            return curobj;
        };

      // reste all config
        this.reset = function reset() {
            _data = {};
            _onprogress = null;
            _ondone = null;
            return curobj;
        };

       // ajax data in text
        this.load_text = function load_text(action) {
            action = action || _default_action;
            acquaint_load(action, 'text');

            return curobj;
        };

        // ajax data in json
        this.load_json = function load_json(action) {
            action = action || _default_action;
            acquaint_load(action, 'json');

            return curobj;
        };

        
        this.load_http = function load_http(target, action) {
            target = target || 'acquaintui_void';
            action = action || _default_action;
            acquaint_form_submit(action, target);

            return curobj;
        };



        /**
         * Initialize the formdata object
         */
        function initmain() {
            // Initialize missing Ajax-URL: Use WordPress ajaxurl if possible.
            if (!_ajaxurl && typeof window.ajaxurl === 'string') {
                _ajaxurl = window.ajaxurl;
            }

            // Initialize an invisible iframe for file downloads.
            _void_frame = jQuery('body').find('#acquaintui_void');

            if (!_void_frame.length) {
              
                _void_frame = jQuery('<iframe></iframe>')
                        .attr('name', 'acquaintui_void')
                        .attr('id', 'acquaintui_void')
                        .css({
                            'width': 1,
                            'height': 1,
                            'display': 'none',
                            'visibility': 'hidden',
                            'position': 'absolute',
                            'left': -1000,
                            'top': -1000
                        })
                        .hide()
                        .appendTo(jQuery('body'));
            }

            // Find out what HTML5 feature we can use.
            acquaint_what_is_supported();

            // Reset all configurations.
            curobj.reset();
        }

       // feature detection
        function acquaint_what_is_supported() {
            var inp = document.createElement('INPUT');
            var xhr = new XMLHttpRequest();

            // HTML 5 files API
            inp.type = 'file';
            _support_file_api = 'files' in inp;

            // HTML5 ajax upload "progress" events
            _support_progress = !!(xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));

            // HTML5 FormData object
            _support_form_data = !!window.FormData;
        }

        //Creates the XMLHttpReqest object used for the jQuery ajax calls.
        function acquaint_what_is_supported() {
            var xhr = new window.XMLHttpRequest();

            if (_support_progress) {
                // Upload progress
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        acquaint_what_call_progress(percentComplete);
                    } else {
                        acquaint_what_call_progress(-1);
                    }
                }, false);

                // Download progress
                xhr.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        acquaint_what_call_progress(percentComplete);
                    } else {
                        acquaint_what_call_progress(-1);
                    }
                }, false);
            }

            return xhr;
        }

       //onprogress callback
        function acquaint_what_call_progress(value) {
            if (_support_progress && typeof _onprogress === 'function') {
                _onprogress(value);
            }
        }

        //Calls the "onprogress" callback doen
        function acquaint_call_done(response, okay, xhr) {
            acquaint_what_call_progress(100);
            if (typeof _ondone === 'function') {
                _ondone(response, okay, xhr);
            }
        }

        // gather data for submit
        function acquaint_get_data(action, use_formdata) {
            var data = {};
            use_formdata = use_formdata && _support_form_data;

            if (_data instanceof jQuery) {

                // ===== CONVERT <form> to data object.

                // WP-Editor needs some special attention first:
                _data.find('.wp-editor-area').each(function () {
                    var id = jQuery(this).attr('id'),
                            sel = '#wp-' + id + '-wrap',
                            container = jQuery(sel),
                            editor = window.tinyMCE.get(id);

                    if (editor && container.hasClass('tmce-active')) {
                        editor.save(); // Update the textarea content.
                    }
                });

                if (use_formdata) {
                    data = new window.FormData(_data[0]);
                } else {
                    data = {};

                   
                    var push_counters = {},
                            patterns = {
                                "validate": /^[a-zA-Z_][a-zA-Z0-9_-]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                                "key": /[a-zA-Z0-9_-]+|(?=\[\])/g,
                                "push": /^$/,
                                "fixed": /^\d+$/,
                                "named": /^[a-zA-Z0-9_-]+$/
                            };

                    var _build = function (base, key, value) {
                        base[key] = value;
                        return base;
                    };

                    var _push_counter = function (key) {
                        if (push_counters[key] === undefined) {
                            push_counters[key] = 0;
                        }
                        return push_counters[key]++;
                    };

                    jQuery.each(_data.serializeArray(), function () {
                        // skip invalid keys
                        if (!patterns.validate.test(this.name)) {
                            return;
                        }

                        var k,
                                keys = this.name.match(patterns.key),
                                merge = this.value,
                                reverse_key = this.name;

                        while ((k = keys.pop()) !== undefined) {

                            // adjust reverse_key
                            reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                            // push
                            if (k.match(patterns.push)) {
                                merge = _build([], _push_counter(reverse_key), merge);
                            }

                            // fixed
                            else if (k.match(patterns.fixed)) {
                                merge = _build([], k, merge);
                            }

                            // named
                            else if (k.match(patterns.named)) {
                                merge = _build({}, k, merge);
                            }
                        }

                        data = jQuery.extend(true, data, merge);
                    });

                    // ----- End: Convert FORM to OBJECT

                    // Add file fields
                    _data.find('input[type=file]').each(function () {
                        var me = jQuery(this),
                                name = me.attr('name'),
                                inp = me.clone(true)[0];
                        data[':files'] = data[':files'] || {};
                        data[':files'][name] = inp;
                    });
                }
            } else if (typeof _data === 'string') {

                // ===== PARSE STRING to data object.

                var temp = _data.split('&').map(function (kv) {
                    return kv.split('=', 2);
                });

                data = (use_formdata ? new window.FormData() : {});
                for (var ind in temp) {
                    var name = decodeURI(temp[ind][0]),
                            val = decodeURI(temp[ind][1]);

                    if (use_formdata) {
                        data.append(name, val);
                    } else {
                        if (undefined !== data[name]) {
                            if ('object' !== typeof data[name]) {
                                data[name] = [data[name]];
                            }
                            data[name].push(val);
                        } else {
                            data[name] = val;
                        }
                    }
                }
            } else if (typeof _data === 'object') {

                // ===== USE OBJECT to populate data object.

                if (use_formdata) {
                    data = new window.FormData();
                    for (var data_key in _data) {
                        if (_data.hasOwnProperty(data_key)) {
                            data.append(data_key, _data[data_key]);
                        }
                    }
                } else {
                    data = jQuery.extend({}, _data);
                }
            }

            if (undefined !== action) {
                if (data instanceof window.FormData) {
                    data.append('action', action);
                } else {
                    data.action = action;
                }
            }

            return data;
        }

       // submit data
        function acquaint_load(action, type) {
            var data = acquaint_get_data(action, true),
                    ajax_args = {},
                    response = null,
                    okay = false;

            if (type !== 'json') {
                type = 'text';
            }

            acquaint_what_call_progress(-1);

            ajax_args = {
                url: _ajaxurl,
                type: 'POST',
                dataType: 'html',
                data: data,
                xhr: acquaint_what_is_supported,
                success: function (resp, status, xhr) {
                    okay = true;
                    response = resp;
                    if ('json' === type) {
                        try {
                            response = jQuery.parseJSON(resp);
                        } catch (ignore) {
                            response = {'status': 'ERR', 'data': resp};
                        }
                    }
                },
                error: function (xhr, status, error) {
                    okay = false;
                    response = error;
                },
                complete: function (xhr, status) {
                    if (response instanceof Object && 'ERR' === response.status) {
                        okay = false;
                    }
                    acquaint_call_done(response, okay, xhr);
                }
            };

            if (data instanceof window.FormData) {
                ajax_args.processData = false;  // tell jQuery not to process the data
                ajax_args.contentType = false;  // tell jQuery not to set contentType
            }

            jQuery.ajax(ajax_args);
        }

        // send data form normal form to inframe
        function acquaint_form_submit(action, target) {
            var data = acquaint_get_data(action, false),
                    form = jQuery('<form></form>'),
                    ajax_action = '';

            // Append all data fields to the form.
            for (var name in data) {
                if (data.hasOwnProperty(name)) {
                    if (name === ':files') {
                        for (var file in data[name]) {
                            var inp = data[name][file];
                            form.append(inp);
                        }
                    } else if (name === 'action') {
                        ajax_action = name + '=' + data[name].toString();
                    } else {
                        jQuery('<input type="hidden" />')
                                .attr('name', name)
                                .attr('value', data[name])
                                .appendTo(form);
                    }
                }
            }

            if (_ajaxurl.indexOf('?') === -1) {
                ajax_action = '?' + ajax_action;
            } else {
                ajax_action = '&' + ajax_action;
            }

            // Set correct form properties.
            form.attr('action', _ajaxurl + ajax_action)
                    .attr('method', 'POST')
                    .attr('enctype', 'multipart/form-data')
                    .attr('target', target)
                    .hide()
                    .appendTo(jQuery('body'));

            // Submit the form.
            form.submit();
        }


        // Initialize the formdata object
        curobj = this;
        initmain();

    }; /* ** End: acquaintUiAjaxData ** */

}(window.acquaintUi = window.acquaintUi || {}));


(function (acquaintUi) {




    // Handles conversions of binary <-> text.
 
    acquaintUi.acquaintUiBinary = function () {
        var map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

        acquaintUi.acquaintUiBinary.utf8_encode = function utf8_encode(string) {
            if (typeof string !== 'string') {
                return string;
            } else {
                string = string.replace(/\r\n/g, "\n");
            }
            var output = '', i = 0, charCode;

            for (i; i < string.length; i++) {
                charCode = string.charCodeAt(i);

                if (charCode < 128) {
                    output += String.fromCharCode(charCode);
                } else if ((charCode > 127) && (charCode < 2048)) {
                    output += String.fromCharCode((charCode >> 6) | 192);
                    output += String.fromCharCode((charCode & 63) | 128);
                } else {
                    output += String.fromCharCode((charCode >> 12) | 224);
                    output += String.fromCharCode(((charCode >> 6) & 63) | 128);
                    output += String.fromCharCode((charCode & 63) | 128);
                }
            }

            return output;
        };

        acquaintUi.acquaintUiBinary.utf8_decode = function utf8_decode(string) {
            if (typeof string !== 'string') {
                return string;
            }

            var output = '', i = 0, charCode = 0;

            while (i < string.length) {
                charCode = string.charCodeAt(i);

                if (charCode < 128) {
                    output += String.fromCharCode(charCode);
                    i += 1;
                } else if ((charCode > 191) && (charCode < 224)) {
                    output += String.fromCharCode(((charCode & 31) << 6) | (string.charCodeAt(i + 1) & 63));
                    i += 2;
                } else {
                    output += String.fromCharCode(((charCode & 15) << 12) | ((string.charCodeAt(i + 1) & 63) << 6) | (string.charCodeAt(i + 2) & 63));
                    i += 3;
                }
            }

            return output;
        };

       //Converts a utf-8 string into an base64 encoded string
        acquaintUi.acquaintUiBinary.base64_encode = function base64_encode(input) {
            if (typeof input !== 'string') {
                return input;
            } else {
                input = acquaintUi.acquaintUiBinary.utf8_encode(input);
            }
            var output = '', a, b, c, d, e, f, g, i = 0;

            while (i < input.length) {
                a = input.charCodeAt(i++);
                b = input.charCodeAt(i++);
                c = input.charCodeAt(i++);
                d = a >> 2;
                e = ((a & 3) << 4) | (b >> 4);
                f = ((b & 15) << 2) | (c >> 6);
                g = c & 63;

                if (isNaN(b)) {
                    f = g = 64;
                } else if (isNaN(c)) {
                    g = 64;
                }

                output += map.charAt(d) + map.charAt(e) + map.charAt(f) + map.charAt(g);
            }

            return output;
        };

       //Converts a base64 string into the original (binary) data
        acquaintUi.acquaintUiBinary.base64_decode = function base64_decode(input) {
            if (typeof input !== 'string') {
                return input;
            } else {
                input.replace(/[^A-Za-z0-9\+\/\=]/g, '');
            }
            var output = '', a, b, c, d, e, f, g, i = 0;

            while (i < input.length) {
                d = map.indexOf(input.charAt(i++));
                e = map.indexOf(input.charAt(i++));
                f = map.indexOf(input.charAt(i++));
                g = map.indexOf(input.charAt(i++));

                a = (d << 2) | (e >> 4);
                b = ((e & 15) << 4) | (f >> 2);
                c = ((f & 3) << 6) | g;

                output += String.fromCharCode(a);
                if (f !== 64) {
                    output += String.fromCharCode(b);
                }
                if (g !== 64) {
                    output += String.fromCharCode(c);
                }
            }

            return acquaintUi.acquaintUiBinary.utf8_decode(output);
        };

    }; 

}(window.acquaintUi = window.acquaintUi || {}));