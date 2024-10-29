/**
 * =============================================================================
 *Acquaint sidebar related function
 */
window.Sidebars = null;
(function ($) {
    window.Sidebars = {
        /* Default variables*/
        sidebars: [],
        prefix_for_sidebar: 'acq-',
        edit_form: null,
        delete_form: null,
        export_form: null,
        location_form: null,
        right: null,
        extras: null,
        action_handlers: {},
        // Init method
        init: function () {

            Sidebars.loadControls();
            Sidebars.loadTTools();
            Sidebars.loadSidebars();
            Sidebars.loadToolbar();
            Sidebars.loadColumns();
        },

        // load control 
        loadControls: function () {
            return Sidebars.right = jQuery("#widgets-right"), Sidebars.extras = jQuery("#widgets-extra"), null === Sidebars.edit_form && (Sidebars.edit_form = Sidebars.extras.find(".editor").clone(), Sidebars.extras.find(".editor").remove()), null === Sidebars.delete_form && (Sidebars.delete_form = Sidebars.extras.find(".delete").clone(), Sidebars.extras.find(".delete").remove()), null === Sidebars.export_form && (Sidebars.export_form = Sidebars.extras.find(".acq-export").clone(), Sidebars.extras.find(".acq-export").remove()), null === Sidebars.location_form && (Sidebars.location_form = Sidebars.extras.find(".place").clone(), Sidebars.extras.find(".place").remove()), jQuery("#title-options").detach().prependTo(Sidebars.right), Sidebars

        },

        // Load define columns
        loadColumns: function () {
            
            var title__f = jQuery('<div class="acq-title"><h2></h2></div>');
            var sidebars = Sidebars.right.find('.widgets-holder-wrap');
            
            var col_1 = Sidebars.right.find('.sidebars-column-1');
            var col_2 = Sidebars.right.find('.sidebars-column-2');
            

            // Sorting method
            function sort_toggle() {

                var obj = jQuery(this),
                col = obj.closest('.sidebars-column-1, .sidebars-column-2'),
                dir = col.data('sort-dir');

                dir = ('asc' === dir ? 'desc' : 'asc');
                Sidebars.sort_sidebars(col, dir);
            }
            title__f.find('h2').append('<span class="acq-title-val"></span><i class="acq-icon dashicons dashicons-sort"></i>').css({'cursor': 'pointer'});
            title__f.clone().prependTo(col_1).click(sort_toggle).find('.acq-title-val').text(SidebarsData.custom_sidebars);
            title__f.clone().prependTo(col_2).click(sort_toggle).find('.acq-title-val').text(SidebarsData.theme_sidebars);
            
            col_1 = jQuery('<div class="inner"></div>').appendTo(col_1);
            col_2 = jQuery('<div class="inner"></div>').appendTo(col_2);

            sidebars.each(function check_sidebar() {
                var obj = jQuery(this),
                        sbar = obj.find('.widgets-sortables');

                if (Sidebars.chkSidebar(sbar)) {
                    obj.appendTo(col_1);
                } else {
                    obj.appendTo(col_2);
                }
            });

        },
        /**       
         *   default load all sidebar
         */
        loadSidebars: function () {
            Sidebars.right.find('.widgets-sortables').each(function () {
                var key, sb,
                        state = false,
                        obj = jQuery(this),
                        id = obj.attr('id');

                if (obj.data('acq-init') === true) {
                    return;
                }
                obj.data('acq-init', true);

                if (Sidebars.chkSidebar(this)) {
                    sb = Sidebars.add(id, 'custom');
                } else {
                    sb = Sidebars.add(id, 'theme');

                    // Set correct "replaceable" flag for the toolbar.
                    for (key in SidebarsData.replaceable) {
                        if (!SidebarsData.replaceable.hasOwnProperty(key)) {
                            continue;
                        }
                        if (SidebarsData.replaceable[key] === id) {
                            state = true;
                            break;
                        }
                    }

                    Sidebars.setReplaceable(sb, state, false);
                }
            });
            return Sidebars;
        },
        /**
         *  Load default tools         
         */
        loadTTools: function () {
            var sidebar_create_btn = jQuery('.btn-create-sidebar'),                
                    topbar = jQuery('.acq-options'),                   
                    data = {};

            // Button: Add new sidebar.
            sidebar_create_btn.click(function () {
                data.id = '';
                data.title = SidebarsData.title_new;
                data.button = SidebarsData.btn_new;
                data.description = '';
                data.name = '';

                Sidebars.Editor_show(data);
            });

        
          
        },
        /**
         *  Load toolbar
         */

        loadToolbar: function () {
            function tool_action(e) {
                var i = jQuery(e.target).closest(".tool"),
                        action = i.data("action"),
                        a = Sidebars.EditbarID(i),
                        r = Sidebars.find(a);
                return !Sidebars.handleAction(action, r);
            }
            return Sidebars.registerAction("edit", Sidebars.Editor_show), Sidebars.registerAction("location", Sidebars.LocationShow), Sidebars.registerAction("delete", Sidebars.RemoveShow), Sidebars.registerAction("replaceable", Sidebars.setReplaceable), Sidebars.right.on("click", ".tool", tool_action), Sidebars
        },
        /**
         * Triggers the callback method
         */
        handleAction: function (action, sb) {
            if ('function' === typeof Sidebars.action_handlers[ action ]) {
                return !!Sidebars.action_handlers[ action ](sb);
            }
            return false;
        },

        /**
         * Register new action when toolbar click         
         */
        registerAction: function (task, cb_func) {
            Sidebars.action_handlers[ task ] = cb_func;
        },
        
        /* Display ajax error 
         */
        showAjaxError: function (details) {
            var msg = {};

            msg.message = SidebarsData.ajax_error;
            msg.details = details;
            msg.parent = '#widgets-right';
            msg.insert_after = '#acq-title-options';
            msg.id = 'editor';
            msg.type = 'err';

            acquaintUi.message(msg);
        },

        /**
         * Sort sidebars
         */
        sort_sidebars: function (col, dir) {
            var sidebars = col.find('.widgets-holder-wrap'),
                    icon = col.find('.acq-title .acq-icon');

            sidebars.sortElements(function (a, b) {
                var val_a = jQuery(a).find('.sidebar-name h2').text(),
                        val_b = jQuery(b).find('.sidebar-name h2').text();

                if (dir === 'asc') {
                    return val_a > val_b ? 1 : -1;
                } else {
                    return val_a < val_b ? 1 : -1;
                }
            });

            // Change the indicator.
            col.data('sort-dir', dir);
            if ('asc' === dir) {
                icon
                        .removeClass('dashicons-arrow-down dashicons-sort')
                        .addClass('dashicons-arrow-up');
            } else {
                icon
                        .removeClass('dashicons-arrow-up dashicons-sort')
                        .addClass('dashicons-arrow-down');
            }
        },
        
        /**        
         *  Display add/edit sectrion in popup
         */
        Editor_show: function (data) {
            var popup = null,
                    ajax = null;

            if (data instanceof ACq_Sidebar) {
                data = {
                    id: data.getID(),
                    title: SidebarsData.title_edit.replace('[Sidebar]', data.name),
                    button: SidebarsData.btn_edit
                };
            }
           

            // Show the "extra" fields
            function extraShow() {
                popup.$().addClass('acqb-has-more');
                popup.size(782, 545);
            }
            
             // Hide the "extra" fields
            function extraHide() {
                popup.$().removeClass('acqb-has-more');
                popup.size(782, 215);
            }

            // Toggle the "extra" fields based on the checkbox state.
            function extraToggle() {
                if (jQuery(this).prop('checked')) {
                    extraShow();
                } else {
                    extraHide();
                }
            }

            // Populates the input fields in the editor with given data.
            function values_set(data, okay, xhr) {
                popup.loading(false);

                // Ignore error responses from Ajax.
                if (!data) {
                    return false;
                }

                if (!okay) {
                    popup.destroy();
                    Sidebars.showAjaxError(data);
                    return false;
                }

                if (data.sidebar) {
                    data = data.sidebar;
                }

                // Populate known fields.
                if (data.id) {
                    popup.$().find('#acqb-id').val(data.id);
                }
                if (data.name) {
                    popup.$().find('#acqb-name').val(data.name);
                }
                if (data.description) {
                    popup.$().find('#acqb-description').val(data.description);
                }
                if (data.before_title) {
                    popup.$().find('#acqb-before-title').val(data.before_title);
                }
                if (data.after_title) {
                    popup.$().find('#acqb-after-title').val(data.after_title);
                }
                if (data.before_widget) {
                    popup.$().find('#acqb-before-widget').val(data.before_widget);
                }
                if (data.after_widget) {
                    popup.$().find('#acqb-after-widget').val(data.after_widget);
                }
                if (data.button) {
                    popup.$().find('.btn-save').text(data.button);
                }
            }

            // Close popup after ajax request
            function save_data_ajax(resp, okay, xhr) {
                var msg = {}, sb;

                popup.loading(false);
                popup.destroy();

                msg.message = resp.message;
                // msg.details = resp;
                msg.parent = '#widgets-right';
                msg.insert_after = '#acq-title-options';
                msg.id = 'editor';

                if (okay) {
                    if ('update' === resp.action) {
                        // Update the name/description of the sidebar.
                        sb = Sidebars.find(resp.data.id);
                        Sidebars.SidebarUpdate(sb, resp.data);
                    } else if ('insert' === resp.action) {
                        // Insert a brand new sidebar container.
                        Sidebars.SidebarInsert(resp.data);
                    }
                } else {
                    msg.type = 'err';
                }
                acquaintUi.message(msg);
            }

            // Submit the data via ajax.
            function save_data() {
                var form = popup.$().find('form');

                // Start loading-animation.
                popup.loading(true);

                ajax.reset()
                        .data(form)
                        .ondone(save_data_ajax)
                        .load_json();

                return false;
            }

            // Show the EDITOR popup.
            popup = acquaintUi.popup()
                    .modal(true)
                    .title(data.title)
                    .onshow(extraHide)
                    .content(Sidebars.edit_form);

            extraHide();
            values_set(data, true, null);

            // Create new ajax object to get sidebar details.
            ajax = acquaintUi.ajax(null, 'acq-ajax');
            if (data.id) {
                popup.loading(true);
                ajax.reset()
                        .data({
                            'do': 'get',
                            'sb': data.id
                        })
                        .ondone(values_set)
                        .load_json();
            }

            popup.show();
            popup.$().find('#acqb-name').focus();

            // Add event hooks to the editor.
            popup.$().on('click', '#acqb-more', extraToggle);
            popup.$().on('click', '.btn-save', save_data);
            popup.$().on('click', '.btn-cancel', popup.destroy);

            return true;
        },
        /**
         * Update the name/description of an existing sidebar container.
         *
         * @since  1.0.0
         */
        SidebarUpdate: function (sb, data) {
            // Update the title.
            sb.sb
                    .find('.sidebar-name h2')
                    .text(data.name);

            // Update description.
            sb.sb
                    .find('.sidebar-description')
                    .html('<p class="description"></p>')
                    .find('.description')
                    .text(data.description);

            return Sidebars;
        },
        /**
         * Insert a brand new sidebar container.
         *
         * @since  1.0.0
         */
        SidebarInsert: function (data) {
            var box = jQuery('<div class="widgets-holder-wrap"></div>'),
                    inner = jQuery('<div class="widgets-sortables ui-sortable"></div>'),
                    name = jQuery('<div class="sidebar-name"><div class="sidebar-name-arrow"><br></div><h2></h2></div>'),
                    desc = jQuery('<div class="sidebar-description"></div>'),
                    col = Sidebars.right.find('.sidebars-column-1 > .inner:first');

            // Set sidebar specific values.
            inner.attr('id', data.id);

            name
                    .find('h2')
                    .text(data.name);

            desc
                    .html('<p class="description"></p>')
                    .find('.description')
                    .text(data.description);

            // Assemble the new sidebar box in correct order.
            name.appendTo(inner);
            desc.appendTo(inner);
            inner.appendTo(box);

            // Display the new sidebar on screen.
            box.prependTo(col);

            // Remove hooks added by wpWidgets.init()
            jQuery('#widgets-right .sidebar-name').unbind('click');
            jQuery('#widgets-left .sidebar-name').unbind('click');
            jQuery(document.body).unbind('click.widgets-toggle');
            jQuery('.widgets-chooser')
                    .off('click.widgets-chooser')
                    .off('keyup.widgets-chooser');
            jQuery('#available-widgets .widget .widget-title').off('click.widgets-chooser');
            jQuery('.widgets-chooser-sidebars').empty();

            // Re-Init the page using wpWidgets.init()
            window.wpWidgets.init();

            // Add the plugin toolbar to the new sidebar.
            Sidebars.loadSidebars();

            return Sidebars;
        },


        /**
         * =====================================================================
         * Ask for confirmation before deleting a sidebar
         */
        RemoveShow: function (sb) {
            var popup = null,
                    ajax = null,
                    id = sb.getID(),
                    name = sb.name;

            // Insert the sidebar name into the delete message.
            function insert_name(el) {
                el.find('.name').text(name);
            }

            // Closes the delete confirmation.
            function close_popup() {
                popup.loading(false);
                popup.destroy();
            }

            // Handle response of the delete ajax-call.
            function handle_done(resp, okay, xhr) {
                var msg = {};

                popup.loading(false);
                popup.destroy();

                msg.message = resp.message;
                // msg.details = resp;
                msg.parent = '#widgets-right';
                msg.insert_after = '#acq-title-options';
                msg.id = 'editor';

                if (okay) {
                    // Remove the Sidebar from the page.
                    Sidebars.right
                            .find('#' + id)
                            .closest('.widgets-holder-wrap')
                            .remove();

                    // Remove object from internal collection.
                    Sidebars.remove(id);
                } else {
                    msg.type = 'err';
                }

                acquaintUi.message(msg);
            }

            // Deletes the sidebar and closes the confirmation popup.
            function delete_sidebar() {
                popup.loading(true);

                ajax.reset()
                        .data({
                            'do': 'delete',
                            'sb': id
                        })
                        .ondone(handle_done)
                        .load_json();
            }

            // Show the REMOVE popup.
            popup = acquaintUi.popup()
                    .modal(true)
                    .size(560, 160)
                    .title(SidebarsData.title_delete)
                    .content(Sidebars.delete_form)
                    .onshow(insert_name)
                    .show();

            // Create new ajax object.
            ajax = acquaintUi.ajax(null, 'acq-ajax');

            popup.$().on('click', '.btn-cancel', close_popup);
            popup.$().on('click', '.btn-delete', delete_sidebar);

            return true;
        },

    
        /**
         * =====================================================================
         * Show popup to assign sidebar to default categories.
         *
         * @since  2.0
         */
        LocationShow: function (sb) {
            var popup = null,
                    ajax = null,
                    form = null,
                    id = sb.getID();

            // Display the location data after it was loaded by ajax.
            function handle_done_load(resp, okay, xhr) {
                var theme_sb, opt, name, msg = {}; // Only used in error case.

                popup.loading(false);

                if (!okay) {
                    popup.destroy();
                    Sidebars.showAjaxError(resp);
                    return;
                }

                // Display the sidebar name.
                popup.$().find('.sb-name').text(resp.sidebar.name);
                var sb_id = resp.sidebar.id;

                // Only show settings for replaceable sidebars
                var sidebars = popup.$().find('.acq-replaceable');
                sidebars.hide();
                resp.replaceable = acquaintUi.obj(resp.replaceable);
                for (var key0 in resp.replaceable) {
                    if (!resp.replaceable.hasOwnProperty(key0)) {
                        continue;
                    }
                    sidebars.filter('.' + resp.replaceable[key0]).show();
                }

                // Add a new option to the replacement list.
                function _add_option(item, lists, key) {
                    var opt = jQuery('<option></option>');
                    opt.attr('value', key).text(item.name);
                    lists.append(opt);
                }

                // Check if the current sidebar is a replacement in the list.
                function _select_option(replacement, sidebar, key, lists) {
                    var row = lists
                            .closest('.acq-replaceable')
                            .filter('.' + sidebar),
                            option = row
                            .find('option[value="' + key + '"]'),
                            group = row.find('optgroup.used'),
                            check = row.find('.detail-toggle');

                    if (replacement === sb_id) {
                        option.prop('selected', true);
                        if (true !== check.prop('checked')) {
                            check.prop('checked', true);
                            row.addClass('open');

                            // Upgrade the select list with chosen.
                            acquaintUi.upgrade_multiselect(row);
                        }
                    } else {
                        if (!group.length) {
                            group = jQuery('<optgroup class="used">')
                                    .attr('label', row.data('lbl-used'))
                                    .appendTo(row.find('.details select'));
                        }
                        option.detach().appendTo(group);
                    }
                }

                // ----- Category ----------------------------------------------
                // Refresh list for single categories and category archives.
                var lst_cat = popup.$().find('.acq-datalist.acq-cat');
                var lst_act = popup.$().find('.acq-datalist.acq-arc-cat');
                var data_cat = resp.categories;
                lst_act.empty();
                lst_cat.empty();
                // Add the options
                for (var key1 in data_cat) {
                    _add_option(data_cat[ key1 ], lst_act, key1);
                    _add_option(data_cat[ key1 ], lst_cat, key1);
                }

                // Select options
                for (var key2 in data_cat) {
                    if (data_cat[ key2 ].single) {
                        for (theme_sb in data_cat[ key2 ].single) {
                            _select_option(
                                    data_cat[ key2 ].single[ theme_sb ],
                                    theme_sb,
                                    key2,
                                    lst_cat
                                    );
                        }
                    }
                    if (data_cat[ key2 ].archive) {
                        for (theme_sb in data_cat[ key2 ].archive) {
                            _select_option(
                                    data_cat[ key2 ].archive[ theme_sb ],
                                    theme_sb,
                                    key2,
                                    lst_act
                                    );
                        }
                    }
                }

                // ----- Post Type ---------------------------------------------
                // Refresh list for single posttypes.
                var lst_pst = popup.$().find('.acq-datalist.acq-pt');
                var data_pst = resp.posttypes;
                lst_pst.empty();
                // Add the options
                for (var key3 in data_pst) {
                    opt = jQuery('<option></option>');
                    name = data_pst[ key3 ].name;

                    opt.attr('value', key3).text(name);
                    lst_pst.append(opt);
                }

                // Select options
                for (var key4 in data_pst) {
                    if (data_pst[ key4 ].single) {
                        for (theme_sb in data_pst[ key4 ].single) {
                            _select_option(
                                    data_pst[ key4 ].single[ theme_sb ],
                                    theme_sb,
                                    key4,
                                    lst_pst
                                    );
                        }
                    }
                }

                // ----- Archives ----------------------------------------------
                // Refresh list for archive types.
                var lst_arc = popup.$().find('.acq-datalist.acq-arc');
                var data_arc = resp.archives;
                lst_arc.empty();
                // Add the options
                for (var key5 in data_arc) {
                    opt = jQuery('<option></option>');
                    name = data_arc[ key5 ].name;

                    opt.attr('value', key5).text(name);
                    lst_arc.append(opt);
                }

                // Select options
                for (var key6 in data_arc) {
                    if (data_arc[ key6 ].archive) {
                        for (theme_sb in data_arc[ key6 ].archive) {
                            _select_option(
                                    data_arc[ key6 ].archive[ theme_sb ],
                                    theme_sb,
                                    key6,
                                    lst_arc
                                    );
                        }
                    }
                }

                // ----- Authors ----------------------------------------------
                // Refresh list for authors.
                var lst_aut = popup.$().find('.acq-datalist.acq-arc-aut');
                var data_aut = resp.authors;
                lst_aut.empty();
                // Add the options
                for (var key7 in data_aut) {
                    opt = jQuery('<option></option>');
                    name = data_aut[ key7 ].name;

                    opt.attr('value', key7).text(name);
                    lst_aut.append(opt);
                }

                // Select options
                for (var key8 in data_aut) {
                    if (data_aut[ key8 ].archive) {
                        for (theme_sb in data_aut[ key8 ].archive) {
                            _select_option(
                                    data_aut[ key8 ].archive[ theme_sb ],
                                    theme_sb,
                                    key8,
                                    lst_aut
                                    );
                        }
                    }
                }

            } // end: handle_done_load()

            // User clicks on "replace <sidebar> for <category>" checkbox.
            function toggle_details(ev) {
                var inp = jQuery(this),
                        row = inp.closest('.acq-replaceable'),
                        sel = row.find('select');

                if (inp.prop('checked')) {
                    row.addClass('open');

                    // Upgrade the select list with chosen.
                    acquaintUi.upgrade_multiselect(row);

                    // Tell the select list to render the contents again.
                    sel.trigger('change.select2');
                } else {
                    row.removeClass('open');

                    // Remove all selected options.
                    sel.val([]);
                }
            }

            // After saving data via ajax is done.
            function save_data_ajax(resp, okay, xhr) {
                var msg = {};

                popup.loading(false);
                popup.destroy();

                msg.message = resp.message;
                // msg.details = resp;
                msg.parent = '#widgets-right';
                msg.insert_after = '#acq-title-options';
                msg.id = 'editor';

                if (!okay) {
                    msg.type = 'err';
                }

                acquaintUi.message(msg);
            }

            // Submit the data and close the popup.
            function save_data() {
                popup.loading(true);

                ajax.reset()
                        .data(form)
                        .ondone(save_data_ajax)
                        .load_json();
            }

            // Show the LOCATION popup.
            popup = acquaintUi.popup()
                    .modal(true)
                    .size(782, 560)
                    .title(SidebarsData.title_location)
                    .content(Sidebars.location_form)
                    .show();

            popup.loading(true);
            form = popup.$().find('.frm-location');
            form.find('.sb-id').val(id);

            // Initialize ajax object.
            ajax = acquaintUi.ajax(null, 'acq-ajax');
            ajax.reset()
                    .data({
                        'do': 'get-location',
                        'sb': id
                    })
                    .ondone(handle_done_load)
                    .load_json();

            // Attach events.
            popup.$().on('click', '.detail-toggle', toggle_details);
            popup.$().on('click', '.btn-save', save_data);
            popup.$().on('click', '.btn-cancel', popup.destroy);

            return true;
        },

     

        /**
         *   Change the replaceable flag        
         */
        setReplaceable: function (sb, state, do_ajax) {
            var ajax,
                    theme_sb = Sidebars.right.find('.sidebars-column-2 .widgets-holder-wrap'),
                    the_bar = jQuery(sb.sb).closest('.widgets-holder-wrap'),
                    chk = the_bar.find('.acq-toolbar .chk-replaceable'),
                    marker = the_bar.find('.replace-marker'),
                    btn_replaceable = the_bar.find('.acq-toolbar .btn-replaceable');

            // After changing a sidebars "replaceable" flag.
            function handle_done_replaceable(resp, okay, xhr) {
                // Adjust the "replaceable" flag to match the data returned by the ajax request.
                if (resp instanceof Object && typeof resp.replaceable === 'object') {
                    SidebarsData.replaceable = acquaintUi.obj(resp.replaceable);

                    theme_sb.find('.widgets-sortables').each(function () {
                        var _state = false,
                                _me = jQuery(this),
                                _id = _me.attr('id'),
                                _sb = Sidebars.find(_id);

                        for (var key in SidebarsData.replaceable) {
                            if (!SidebarsData.replaceable.hasOwnProperty(key)) {
                                continue;
                            }
                            if (SidebarsData.replaceable[key] === _id) {
                                _state = true;
                                break;
                            }
                        }
                        Sidebars.setReplaceable(_sb, _state, false);
                    });
                }

                // Enable the checkboxes again after the ajax request is handled.
                theme_sb.find('.acq-toolbar .chk-replaceable').prop('disabled', false);
                theme_sb.find('.acq-toolbar .btn-replaceable').removeClass('wpmui-loading');
            }

            if (undefined === state) {
                state = chk.prop('checked');
            }
            if (undefined === do_ajax) {
                do_ajax = true;
            }

            if (chk.data('active') === state) {
                return false;
            }
            chk.data('active', state);
            chk.prop('checked', state);

            if (state) {
                if (!marker.length) {
                    jQuery('<div></div>')
                            .appendTo(the_bar)
                            .attr('data-label', SidebarsData.lbl_replaceable)
                            .addClass('replace-marker');
                }
                the_bar.addClass('replaceable');
            } else {
                marker.remove();
                the_bar.removeClass('replaceable');
            }

            if (do_ajax) {
                // Disable the checkbox until ajax request is done.
                theme_sb.find('.acq-toolbar .chk-replaceable').prop('disabled', true);
                theme_sb.find('.acq-toolbar .btn-replaceable').addClass('wpmui-loading');

                ajax = acquaintUi.ajax(null, 'acq-ajax');
                ajax.reset()
                        .data({
                            'do': 'replaceable',
                            'state': state,
                            'sb': sb.getID()
                        })
                        .ondone(handle_done_replaceable)
                        .load_json();
            }

            /**
             * This function is called by Sidebars.handleAction. Return value
             * False means that the default click event should be executed after
             * this function was called.
             */
            return false;
        },


        /**        
         * Find the specified object for sidebar
         */
        find: function (id) {
            return Sidebars.sidebars[id];
        },

        /** Create new object for sidebar
         */
        add: function (id, type) {
            Sidebars.sidebars[id] = new ACq_Sidebar(id, type);
            return Sidebars.sidebars[id];
        },

        /** Re,ove sidebar object*/
         
        remove: function (id) {
            delete Sidebars.sidebars[id];
        },

        /**
         Check if specified id sidebar exist or not
         */
        chkSidebar: function (el) {
            var id = jQuery(el).attr('id'),
                    prefix = id.substr(0, Sidebars.prefix_for_sidebar.length);

            return prefix === Sidebars.prefix_for_sidebar;
        },

        /**
         * Append sidebar
         */
        addIdToLabel: function ($obj, id) {
            if (true !== $obj.data('label-done')) {
                var prefix = $obj.attr('for');
                $obj.attr('for', prefix + id);
                $obj.find('.has-label').attr('id', prefix + id);
                $obj.data('label-done', true);
            }
        },

        /**
         *  return sidebar ID
         */
        EditbarID: function ($obj) {
            var wrapper = $obj.closest('.widgets-holder-wrap'),
                    sb = wrapper.find('.widgets-sortables:first'),
                    id = sb.attr('id');
            return id;
        }

    };

    jQuery(function ($) {
        $('#acqfooter').hide();
        if ($('#widgets-right').length > 0) {
            Sidebars.init();
        }
        $('.defaultsContainer').hide();

        $('#widgets-right .widgets-sortables').on("sort", function (event, ui) {
            var topx = $('#widgets-right').top;
            ui.position.top = -$('#widgets-right').css('top');
        });
    });

})(jQuery);


// Sort elements 
jQuery.fn.sortElements = (function () {

    var sort = [].sort;

    return function (comparator, getSortable) {

        getSortable = getSortable || function () {
            return this;
        };

        var placements = this.map(function () {

            var sortElement = getSortable.call(this),
                    parentNode = sortElement.parentNode,
                 
                    nextSibling = parentNode.insertBefore(
                            document.createTextNode(''),
                            sortElement.nextSibling
                            );

            return function () {

                if (parentNode === this) {
                    throw new Error(
                            "You can't sort elements if any one is a descendant of another."
                            );
                }

                // Insert before flag:
                parentNode.insertBefore(this, nextSibling);
                // Remove flag:
                parentNode.removeChild(nextSibling);

            };

        });

        return sort.call(this, comparator).each(function (i) {
            placements[i].call(getSortable.call(this));
        });

    };

})();

// Remove extra space method
function trim(str) {
    str = str.replace(/^\s\s*/, '');
    for (var i = str.length - 1; i >= 0; i--) {
        if (/\S/.test(str.charAt(i))) {
            str = str.substring(0, i + 1);
            break;
        }
    }
    return str;
}
function ACq_Sidebar(e, i) {
    var t;
    this.id = e.split("%").join("\\%"), this.type = i, this.sb = jQuery("#" + this.id), this.widgets = "", this.name = trim(this.sb.find(".sidebar-name h2").text()), this.description = trim(this.sb.find(".sidebar-description").text()), t = "custom" === i ? window.Sidebars.extras.find(".display-custom-sidebar").clone() : window.Sidebars.extras.find(".display-theme-sidebar").clone(), this.sb.parent().append(t), t.find("label").each(function () {
        var i = jQuery(this);
        window.Sidebars.addIdToLabel(i, e)
    })
}
ACq_Sidebar.prototype.getID = function () {
    return this.id.split('\\').join('');
};
