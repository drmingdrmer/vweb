$( function(){

    var thumbHeight = 120, nThumbPerLine = 3;

    function evstop( ev ) {
        ev && ev.stopPropagation && ev.stopPropagation();          /* pop up                  */
        ev && ev.preventDefault && ev.preventDefault();            /* other event on this DOM */
        return true;
    }

    $.fn.xTimes = function ( func, n ) {
        return n > 0 ? this[func]().xTimes( func, n-1 ) : this;
    }
    $.fn.xUp = function() { return this.xTimes( 'prev', nThumbPerLine ); }
    $.fn.xDown = function() { return this.xTimes( 'next', nThumbPerLine ); }


    $( '.t_msg' ).each( function( i, e ) {
        $( '.imgwrap.midpic', e )
        .append(
            $( '.cont .msg', e )
            .prepend( $( '.avatar', e ) )
        )
        // .append( $( '<div class="side"><p></p><a href="#">转发</a></div>' ) )
        ;
    } );

    $( '#page' ).delegate( ".t_msg .imgwrap.thumb", "click", function(ev){
        evstop( ev );

        var e = $( this ).parents( ".t_msg" );
        // get cls first or ".showmid" would be removed
        var cls = e.prevAll( '.showmid' )[0] ? 'delayImg' : 'delayWrap';

        $( '.t_msg' ).removeClass( 'showmid delayWrap delayImg' ).addClass( cls );
        e.addClass( 'showmid' );

        var p = e.offset().top - $( window ).scrollTop(), wh = $( window ).height();
        p > 40 && p < wh - thumbHeight || $( document ).scrollTo( e, { duration: 200, offset: -wh/2 + thumbHeight } );

    } );

    $( document ).keydown( function( ev ){

        var func = ( {
            37: 'prev',  // left
            38: 'xUp',   // down
            39: 'next',  // right
            40: 'xDown', // down
            32: 'next'   // space
        } )[ ev.keyCode ];

        func && evstop( ev ) && $( '.imgwrap.thumb',
            $( '.t_msg.showmid' )[func]()[0] || $( '.t_msg:first' ) ).click();
    } );

    $( window ).resize( function( ev ) {
        window._resize_handle && window.clearTimeout( window._resize_handle );
        window._resize_handle = window.setTimeout( function(){
                $("<style type='text/css'>.t_msg .cont .imgwrap.midpic .msgimg { max-height: "
                    + ( $(window).height() - 240 )  + "px;}</style>").appendTo("head");
        }, 100);
    } ).resize();


} );
