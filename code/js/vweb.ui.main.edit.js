$.extend( $.vweb.ui.main, { edit: {
    init : function ( self, e ) {
        this._cont = e.children( "#cont" );
        this.page = this._cont.children( "#page" );

        this.setup_func( self, e );


        e.find( "#edit_mode input" ).button().click( function() {
            $( this ).parent().find( "input" ).each( function() {
                self._cont.removeClass( $( this ).val() );
            } );
            self._cont.addClass( $( this ).val() );
        } );

        e.find( "#screen_mode input" ).button();

        $.vweb.init_sub( this );
    },
    setup_func : function ( self, e ) {
        $.vweb.setup_img_switch( this.page );
        var emp = $( '<div class="t_msg"></div>' ).hide();

        var action = {};

        // NOTE: jquery ui bug: if there is no item matches "items" option, no
        // sortable function would be set up. Thus first msg would be lost
        this.page.append( emp );
        this.page.sortable({
            items:".t_msg",
            tolerance:'pointer',
            appendTo:"body",
            zIndex:2000,
            opacity:0.8,
            start: function( ev, theui ){
                $( theui.placeholder )
                .width( theui.helper.width() )
                .height( theui.helper.height() )
                .css( { visibility:'visible' } )
                ;
            },
            receive : function ( ev, theui ) {
                var msg = theui.item, l = $.vweb.ui.t.list._elt;

                // do not stop event. Or the item would be placed at last.
                // $.evstop( ev );
                $.log( 'receive' );
                $( '#pagehint' ).remove();

                msg.offset().top - l.scrollTop() < 40 && l.scrollTo( msg.add( msg.prev( '.retweeter' ) )[ 0 ], { duration: $.vweb.conf.fadeDuration, offset: -40 } );

                $.vweb.ui.t.list.msg_visible( msg.id(), false );

                action.receive = true;
                action.last = 'receive';
            },
            update: function( ev, theui ) {
                $.log( 'update' );
            },
            out: function( ev, theui ) {
                $.log( 'out' );
                action.out = true;
                action.last = 'out';
            },
            over: function( ev, theui ) {
                $.log( 'over' );
                action.over = true;
                action.last = 'over';
            },
            remove: function( ev, theui ) {
                $.log( 'remove' );
            },
            // not stop. before stop an 'out' would be fired.
            // stop: function( ev, theui ){
            beforeStop: function( ev, theui ){
                $.log( 'stop' );

                $.log( theui );

                if ( action.last =='out' && ! action.receive ) {
                    var msg = $( theui.item );
                    if ( msg.parent( '#page' ).length ) {
                        msg.remove();
                        $.vweb.ui.t.list.msg_visible( msg.id(), true );
                    }
                }
                action = {};
            },
            // NOTE: helper setting to "clone" prevents click event to trigger
            helper : "clone"
        });

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

        this._cont.find( ".t_msg" ).each( function() {
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
