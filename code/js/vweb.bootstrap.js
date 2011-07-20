$(document).ready( function() {
    if ( MODE == 'album' ) {
        $( '.nomode_album' ).remove();
    }

    if ( $.browser.msie ){
        $( "#list" ).css( { 'position' : 'relative' } );
        // $('<link rel="stylesheet" type="text/css" href="css/msie.css" />').appendTo("head");
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
