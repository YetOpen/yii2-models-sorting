/**
 * Created by Yarmaliuk Mikhail on 08.05.2018.
 *
 * @module ARSort
 */

/**
 * ARSort
 *
 * @author Mikhail Yarmaliuk
 *
 * @param {ARSort} app
 * @param {jQuery} $
 */
var ARSort = (function (app, $) {

    /**
     * Options module
     *
     * @type {{}}
     */
    var options = {};

    /**
     * Sortable plugin options
     *
     * @type {{}}
     */
    var sortableOptions = {
        items: 'tbody > tr',
        cursor: 'move',
        axis: 'y',
        handle: '> td:first-child',
        placeholder: 'ar-sort-placeholder',
        helper: function (e, ui) {
            ui.children().each(function () {
                $(this).width($(this).width());
            });

            return ui;
        },
        start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function (e, ui) {
            var gridOptions = $(this).data('arSorting');
            var currentItem = ui.item.find('[data-attribute="' + gridOptions.attribute + '"]');
            var beforeItem = ui.item.prev().find('[data-attribute="' + gridOptions.attribute + '"]');
            var afterItem = ui.item.next().find('[data-attribute="' + gridOptions.attribute + '"]');

            $.post(gridOptions.actionUrl, {
                currentID: currentItem.data('id'),
                beforeID: beforeItem.data('id'),
                afterID: afterItem.data('id'),
                mpDataARSort: gridOptions.mpDataARSort
            }, function (response) {
                if (response.resut === true) {
                    $.each([currentItem, beforeItem, afterItem], function (i, el) {
                        var itemID = el.data('id');

                        if (response.positions[itemID] !== undefined) {
                            el.html(response.positions[itemID]);
                        }
                    });
                }
            });
        }
    };

    /**
     * Attach module to grid
     *
     * @param {string} selector
     * @param {Object} moduleOpt
     * @param {Object} pluginOpt
     *
     * @return {bool}
     */
    app.attachGrid = function (selector, moduleOpt, pluginOpt) {
        var grid = $(selector);

        if (!grid.length) {
            return false;
        }

        if (grid.data('ui-sortable')) {
            return true;
        }

        var pluginOptions = $.extend({}, sortableOptions, pluginOpt || {});
        var moduleOptions = $.extend({}, options, moduleOpt || {});

        return grid.data('arSorting', moduleOptions).sortable(pluginOptions).disableSelection().length ? true : false;
    };

    /**
     * Detach module off the grid
     *
     * @param {string} selector
     *
     * @return {bool}
     */
    app.detachGrid = function (selector) {
        var grid = $(selector);

        if (!grid.length || !grid.data('ui-sortable')) {
            return false;
        }

        $(selector).sortable('destroy');

        return true;
    };

    /**
     * Init ARSort
     *
     * @param {Object} opt
     *
     * @return {undefined}
     */
    app.init = function (opt) {
        options = $.extend({}, options, opt);
    };

    return app;
}(ARSort || {}, jQuery));