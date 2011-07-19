$.extend( $, {
    arrayIndexOf: function( arr, obj ){
        for(var i=0; i<arr.length; i++){
            if(arr[i]==obj){
                return i;
            }
        }
        return -1;
    },
    log: function(mes) {
        $( '#debug' ).append( '<div>' + mes + '</div>' );
        // console && console.log( mes );
    },
    evstop: function( ev ) {
        if ( ev ) {
            ev.stopPropagation && ev.stopPropagation(); /* pop up                  */
            ev.preventDefault && ev.preventDefault();   /* other event on this DOM */
        }
    },
    simpText: function( str ) {
        return $.trim( str ).replace( / +/g, ' ' );
    },
    unparam: function( s ) {
        var o = {};
        var args = s.split( "&" );
        $.each( args, function( i, e ){
            if ( e != "" ) {
                var kv = e.split( "=" );
                var k = decodeURIComponent( kv[ 0 ] );
                var v = decodeURIComponent( kv[ 1 ] );
                o[ k ] = v;
            }
        } );

        return o;
    },
    unescape: function( html ) {
        var htmlNode = document.createElement('div');
        htmlNode.innerHTML = html;
        if (htmlNode.innerText) {
            return htmlNode.innerText; // IE
        }
        return htmlNode.textContent; // FF
    }
} );


$.extend( $.fn, {
    to_json: function() {
        // TODO filter non-comment node
        var rst = [];
        $( this ).each( function( i, v ) {
            var e = $( this );
            var j, text;
            if ( this.nodeType == 3 ) {
                text = e.simpText();
                if ( text != '' ) {
                    rst.push( { text: this.nodeValue } );
                }
            }
            else {

                j = { node: { tag: this.tagName, id:e.id(), 'class': e.attr( 'class' ) } };

                switch ( j.node.tag ) {
                case 'IMG' :
                    j.node.src = e.attr( 'src' );
                    break;
                case 'A' :
                    j.node.href = e.attr( 'href' );
                    break;
                }
                var children = e.contents();
                if ( children.length > 0 ) {
                    j.children = children.to_json();
                }
                rst.push( j );
            }
        } );

        return rst;
    },
    offset_tl: function(){
        var tl = $(this).offset();
        return { t:tl.top, l:tl.left };
    },
    _outerSize: function( funcname, withMargin ){
        withMargin = withMargin == undefined ? true : withMargin;
        var s = 0;
        this.each( function( i, v ){
            s += $(v)[ funcname ]( withMargin );
        } );
        return s;
    },
    h: function( withMargin ) {
        return this._outerSize( 'outerHeight', withMargin );
    },
    w: function( withMargin ) {
        return this._outerSize( 'outerWidth', withMargin );
    },
    size_wh: function( withMargin ) {
        return { w: this.w( withMargin ), h: this.h( withMargin ) };
    },
    id: function() {
        return this.attr( 'id' );
    },
    simpText: function() {
        return $.simpText( this.text() );
    },
    simpVal: function() {
        return $.simpText( this.val() );
    },
    btn_opt: function (  ) {
        var e = $( this );
        var opt = {
            icons : {},
            text : e.attr( "_text" ) != "no"
        };

        e.attr( "_icon" ) && ( opt.icons.primary = "ui-icon-" + e.attr( "_icon" ) );
        e.attr( "_icon2" ) && ( opt.icons.secondary = "ui-icon-" + e.attr( "_icon2" ) );

        return opt;
    },
    jsonRequest: function( succ ){

        $( this ).each( function(){

            var form = $( this );

            $.ajax( {
                url : form.attr( 'action' ),
                type : form.attr( 'method' ),
                data : form.serialize(),
                dataType : 'json',
                success : succ
            } );

        } );
    }
} );

$.extend( $.fn, {
    tl: $.fn.offset_tl,
    p: $.fn.parents
} );
