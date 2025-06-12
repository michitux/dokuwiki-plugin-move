/**
 * Rename dialog for end users
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */
(function () {
    if (!JSINFO || !JSINFO.move_renameokay) return;


    // basic dialog template
    const $dialog = jQuery(
        '<div>' +
        '<form>' +
        '<label>' + LANG.plugins.move.newname + '<br>' +
        '<input type="text" name="id" style="width:100%">' +
        '</label>' +
        '<label>' +
        '<input type="checkbox" name="media" value="1"> ' + LANG.plugins.move.rename_media +
        '</label>' +
        '</form>' +
        '</div>'
    );

    /**
     * Executes the renaming based on the form contents
     * @return {boolean}
     */
    const renameFN = function () {
        const newid = $dialog.find('input[name=id]').val();
        if (!newid) return false;
        if (newid === JSINFO.id) return false;

        const doMedia = $dialog.find('input[name=media]').is(':checked');

        // remove buttons and show throbber
        $dialog.html(
            '<img src="' + DOKU_BASE + 'lib/images/throbber.gif" /> ' +
            LANG.plugins.move.inprogress
        );
        $dialog.dialog('option', 'buttons', []);

        // post the data
        jQuery.post(
            DOKU_BASE + 'lib/exe/ajax.php',
            {
                call: 'plugin_move_rename',
                id: JSINFO.id,
                newid: newid,
                media: doMedia ? 1 : 0,
            },
            // redirect or display error
            function (result) {
                if (result.error) {
                    $dialog.html(result.error);
                } else {
                    window.location.href = result.redirect_url;
                }
            }
        );

        return false;
    };

    /**
     * Create the actual dialog modal and show it
     */
    const showDialog = function () {
        $dialog.dialog({
            title: LANG.plugins.move.rename + ' ' + JSINFO.id,
            width: 800,
            height: 200,
            dialogClass: 'plugin_move_dialog',
            modal: true,
            buttons: [
                {
                    text: LANG.plugins.move.cancel,
                    click: function () {
                        $dialog.dialog("close");
                    }
                },
                {
                    text: LANG.plugins.move.rename,
                    click: renameFN
                }
            ],
            // remove HTML from DOM again
            close: function () {
                jQuery(this).remove();
            }
        });
        $dialog.find('input[name=id]').val(JSINFO.id);
        $dialog.find('form').submit(renameFN);
    };

    /**
     * Bind an event handler as the first handler
     *
     * @param {jQuery} $owner
     * @param {string} event
     * @param {function} handler
     * @link https://stackoverflow.com/a/4700103
     */
    const bindFirst = function ($owner, event, handler) {
        $owner.unbind(event, handler);
        $owner.bind(event, handler);

        const events = jQuery._data($owner[0])['events'][event];
        events.unshift(events.pop());

        jQuery._data($owner[0])['events'][event] = events;
    };


    // attach handler to menu item
    jQuery('.plugin_move_page')
        .show()
        .click(function (e) {
            e.preventDefault();
            showDialog();
        });

    // attach handler to mobile menu entry
    const $mobileMenuOption = jQuery('form select[name=do] option[value=plugin_move]');
    if ($mobileMenuOption.length === 1) {
        bindFirst($mobileMenuOption.closest('select[name=do]'), 'change', function (e) {
            const $select = jQuery(this);
            if ($select.val() !== 'plugin_move') return;
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            $select.val('');
            showDialog();
        });
    }

})();
