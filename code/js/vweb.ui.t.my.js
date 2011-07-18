$.extend( $.vweb.ui.t, { my: {
    init : function () {
        var self = this;
        $( "#expand.t_btn" ).click( function (ev){
            $.evstop( ev );
            self._elt.removeClass( 'hideall' );
        } );

        var statLoader = $.vweb.backend.weibo.create_loader( 'statuses/unread', {
            args: function () {
                return {
                    'since_id': $.vweb.ui.t.my.friend.since_id,
                    'with_new_status': 1
                }
            },
            cb: [ '$.vweb.ui.t.my.setStat' ] } );

        statLoader();
        self.whatID = window.setInterval( statLoader, 60 * 1000 );


        $( "body" ).click( function( ev ){
            var tagname = ev.target.tagName;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                self._elt.addClass( 'hideall' );
            }
        } );

        $.vweb.init_sub( this );
    },

    setStat: function( d, tgr ){
        var e = this._elt
        d.new_status && $( '#friend .f_idx .stat', e ).text( "(新)" );
        d.comments && $( '#friend .f_comment .stat', e ).text( "(" + d.comments + ")" );
        d.mentions && $( '#friend .f_at .stat', e ).text( "(" + d.mentions + ")" );
        d.dm && $( '#friend .f_message .stat', e ).text( "(" + d.dm + ")" );
    },

} } );
