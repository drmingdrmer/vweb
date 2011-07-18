$.extend( $.vweb.ui.t, { update: {
    init: function () {

    },
    upload_cb: function (rst) {
        $.vweb.handle_json( {
            ok: function ( json, st, xhr ) {
                var e = $.vweb.ui.t.update._elt;
                $( '.f_status', e ).val('');
                $( '.f_pic', e ).val( '' );
            }
        }, rst );
    }

} } );
