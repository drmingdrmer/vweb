$.extend( $.vweb.ui.t.my, { friend : {
    init : function( self, e ){
        self.formSimp = e.find( "form.g_simp" );
        self.formSearch = e.find( "form.g_search" );

        var simpLoader = $.vweb.backend.weibo.create_loader( 'statuses/friends_timeline', {
            args: function() { return self.formSimp.serialize(); },
            cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.addLast', '$.vweb.ui.t.my.friend.clearStat']
        } );

        // option arg of all these 3 loader: since_id, max_id, count, page
        var mineLoader = $.vweb.backend.weibo.create_loader( 'statuses/user_timeline', {
            args: function(){ return { user_id: $.vweb.account.id }; },
            cb: [ '$.vweb.ui.t.list.show' ]
        } );
        var atLoader = $.vweb.backend.weibo.create_loader( 'statuses/mentions',
            { cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.clearStat' ] } );

        var cmtLoader = $.vweb.backend.weibo.create_loader( 'statuses/comments_to_me',
            { cb: [ '$.vweb.ui.t.list.show', '$.vweb.ui.t.my.friend.clearStat' ] });

        var msgLoader = $.vweb.backend.weibo.create_loader( 'direct_messages',
            { cb: [ '$.vweb.ui.t.my.friend.clearStat' ] });


        $( '.f_mine', e ).click( mineLoader );

        $( ".f_idx", e ).click( simpLoader );
        self.formSimp.find( "input" ).button().click( simpLoader );


        $( ".f_at", e ).click( atLoader );
        $( ".f_comment", e ).click( cmtLoader );

        // don't stop event propagation. it links to another page.
        $( ".f_message", e ).click(
            function( ev ){ $.vweb.ui.t.my.friend.clearStat( null, $( this ) ); }
        );

        $.vweb.init_sub( self );

        // TODO default action after page loaded
        window.setTimeout(function() { self._elt.find( ".f_idx" ).trigger( 'click' ); }, 0);
    },

    // used only for record last id
    addLast: function ( d ) {
        d = d[ 0 ];
        if ( !this.since_id || d && (d.id+0) > (this.since_id+0) ) {
            this.since_id = d.id;
        }
    },

    clearStat: function ( data, triggerElt ) {
        // triggerElt may be not a valid DOM if "load" is called directly
        if ( ! triggerElt.attr ) { return; }

        $( '.stat', triggerElt ).empty();
        if ( triggerElt.attr( "_reset_type" ) ) {
            $.vweb.backend.weibo.create_loader(
                'statuses/reset_count',
                { args: function() { return { type: triggerElt.attr( "_reset_type" ) }; } }
                )();
        }
    }

} } );
