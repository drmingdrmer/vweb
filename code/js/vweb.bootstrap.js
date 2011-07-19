$(document).ready( function() {
    if ( MODE == 'album' ) {
        $( '.nomode_album' ).remove();
    }


    $.vweb.backend.weibo.t_cmd( 'GET', 'account/verify_credentials', {}, undefined, {
        success:function( json ) {
            $.vweb.account = json.data;
            $.vweb.ui.init( $.vweb.ui );
        },
        error: function( jqxhr, errstr, exception ) {

        }
    } );

} );
