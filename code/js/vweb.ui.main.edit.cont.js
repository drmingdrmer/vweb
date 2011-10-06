$.extend( $.vweb.ui.main.edit, { cont: {
    init: function( self, e ) {
        var page = $( '#page', e );
        var pageopt = $( '#pageopt', e );
        var pagesize = $( '#pagesize', e );
        $.log( 'inited' );
        $.log( pagesize );

        $( 'input', pagesize ).click( function( ev ){
            $.log( '---' );
            $.log( $( this ).val() );
            $.evstop( ev );


            page.removeClass( 'thumbMode midpicMode' )
            .addClass( $( this ).val() );
        } );

        $.vweb.init_sub( this );
    }
} } );
