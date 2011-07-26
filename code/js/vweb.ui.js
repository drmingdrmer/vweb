$.extend( $.vweb.ui, {

    init : function ( self, e ) {

        $.log( 'ui.init start' );

        self.relayout();

        $( ".t_opt" ).each( function() {
            var container = $( this );
            var tp = container.attr( "_type" );
            var id = container.attr( "id" );
            var name = container.attr( "name" );

            var data = [];
            container.children( "a" ).each( function( i, e ){
                e = $( e );

                data.push( {
                    type : tp,
                    id : id + i,
                    name : name,
                    value : e.attr( "href" ),
                    label : e.text() } );

                e.remove();
            } );
            $( "#tmpl_opts" ).tmpl( data ).appendTo( container );

        } );

        $( ".t_btn" ).each( function() {
            $( this ).button( $( this ).btn_opt() );
        } );

        $( ".t_btn_set" ).buttonset();

        $( ".t-dialog" ).addClass( "ui-dialog ui-widget ui-widget-content ui-corner-all" );
        $( ".t-panel" ).addClass( "ui-widget ui-corner-all" );
        $( ".t_ctrl" ).addClass( "ui-widget ui-corner-all" );
        $( ".t-group" ).addClass( "ui-widget ui-corner-all" );
        $( "#paging" ).addClass( "cont_dark0 cont_dark_shad0" );


        $.vweb.init_sub( this );

        $( window ).resize( function() { self.relayout(); } );

        $( "body" ).click( function( ev ) {
            var tagname = ev.target.tagName;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                $( ".t-autoclose" ).hide();
            }
        } )
        .droppable( {
            drop: function( ev, theui ) {
                $.log( ev );
                $.log( theui );
                var msg = theui.draggable;
                if ( $( msg ).parent( '#page' ).length ) {
                    msg.remove();
                    $.vweb.ui.t.list.msg_visible( msg.id(), true );
                }
            }
        } )
        ;

    },

    relayout : function () {
        var bodyHeight = $( "body" ).height();
        var footerHeight = $( "#footer" ).h();
        var edit = $( "#edit" );
        var subtabHeight = bodyHeight - footerHeight;

        edit.height( bodyHeight - footerHeight - $( "#maintool" ).h() );
        $( '#appmsg' ).css( { top: $( '#maintool' ).h() } );

        $( "#t>#list" ).height( subtabHeight
            - $( "#t>#update,#t>#func,#t>#paging" ).filter( ':visible' ).h() );

        $( "#tree" ).height( subtabHeight - $( "#vdaccpane" ).h() );

        // // NOTE temporarily disabled
        // $( "#edit>#cont" )
        // .width( edit.width() - 30 )
        // .height( edit.height() - 30 );

    }

} );
