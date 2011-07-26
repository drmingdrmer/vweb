$.extend( $.vweb.ui.main, { edit: {
    init : function ( self, e ) {
        this.cont = e.children( "#cont" );
        this.page = this.cont.children( "#page" );

        this.setup_func();


        e.find( "#edit_mode input" ).button().click( function() {
            $( this ).parent().find( "input" ).each( function() {
                self.cont.removeClass( $( this ).val() );
            } );
            self.cont.addClass( $( this ).val() );
        } );

        e.find( "#screen_mode input" ).button();
    },
    setup_func : function () {
        $.vweb.setup_img_switch( this.page );
        var e = $( '<div class="t_msg"></div>' ).hide();

        // NOTE: jquery ui bug: if there is no item matches "items" option, no
        // sortable function would be set up. Thus first msg would be lost
        this.page.append( e );
        this.page.sortable({
            items:".t_msg",
            tolerance:'pointer',
            appendTo:"body",
            zIndex:2000,
            receive : function ( ev, theui ) {
                var msg = theui.item;
                $.evstop( ev );
                $.log( ev );
                $( '#pagehint' ).remove();
                $.vweb.ui.t.list.msg_visible( msg.id(), false );
            },
            // NOTE: helper setting to "clone" prevents click event to trigger
            helper : "clone"
        })
        .droppable( {
            // doing nothing but prevent 'body' from receiving 'drop' event
            drop: function( ev, theui ) {
                $.evstop( ev );
            }
        } );
        ;

        // e.remove();

    },
    ids : function () {
        return $.map( $( '.t_msg', this.page ), function( v, i ){
            return $( v ).id();
        } );
    },
    pagedata: function(){
        return this.page.children( ".t_msg:not(.t_his)" ).to_json();
    },
    layoutdata : function () {
        var rst = [];
        var root = this.page.offset();
        root.top -= this.page.scrollTop();
        root.left -= this.page.scrollLeft();

        function lo( elt, attrs ) {
            return $.extend( attrs || {}, elt.offset_tl(), elt.size_wh( false ) );
        }

        this.cont.find( ".t_msg" ).each( function() {
            var e = $( this );
            var thumb = $( "img.thumb:visible", e );
            var midpic = $( "img.midpic:visible", e);

            if ( thumb.length > 0 ) {
                rst.push( lo( thumb.p( '.imgwrap' ), { bgcolor:'#000' } ) );
                rst.push( lo( thumb, { img : thumb.attr( "src" ) } ) );
            }

            if ( e.find( ".cont .msg:visible" ).length > 0 ) {
                rst.push( lo( e, { color : "#000", text : e.simpText() } ) );
                rst[ rst.length-1 ].w -= thumb ? lo( thumb ).w + 4 : 0;
            }

            midpic.length > 0 && rst.push( lo( midpic, { img : midpic.attr( "src" ) } ) );

        } );

        var actualsize = { w:0, h:0 };

        $.each( rst, function( i, v ){
            v.t -= root.top;
            v.l -= root.left;

            actualsize.w = Math.max( v.l+v.w, actualsize.w );
            actualsize.h = Math.max( v.t+v.h, actualsize.h );
        } );

        return $.extend( { bgcolor : "#fff", d : rst }, actualsize );
    },
    addhis: function ( json, cmd ) {

        var rec = $.vweb.backend.weibo.gen_cmd_hisrecord( json, cmd );

        this.page.find( "#" + rec.hisid ).remove();
        $( "#tmpl_hisrec" ).tmpl( [ rec ] ).prependTo( this.page );
    },
    removehis: function ( id ) {
        this.page.find( ".t_his#" + id ).remove();
    },
    html : function( h ) {
        return this.page.html( h );
    }
} } );
