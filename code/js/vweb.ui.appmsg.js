$.extend( $.vweb.ui, { appmsg: {
    init: function( self, e ) {
        e.bind( 'ajaxSend', function(){
            self.msg( 'Loading..' );
        } )
        .bind( 'ajaxSuccess', function( ev, xhr, opts ){
            // ev is and event object
        } )
        .bind( 'ajaxError', function( ev, jqxhr, ajaxsetting, thrownErr ){
            // self.err( ev );
        } );
    },
    msg : function ( text, level ) {
        if ( text ) {
            var e = this._elt;
            var data = [{text:text, level:level || 'info'}];

            $( "#tmpl_appmsg" ).tmpl( data ).appendTo( e.empty() );

            this.lastid && window.clearTimeout( this.lastid );
            this.lastid = window.setTimeout( function(){ e.empty(); }, 5000 );
        }
    },
    alert: function( text ) {
        return this.msg( text, 'alert' );
    },
    err: function ( text ) {
        return this.msg( text, 'error' );
    }
} } );
