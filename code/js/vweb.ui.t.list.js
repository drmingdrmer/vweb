$.extend( $.vweb.ui.t, { list: {
    init : function () {
        this.last = {};
        $.vweb.setup_img_switch( this._elt.empty() );
        this.setup_func();
    },

    repost_cb: function ( rst ) {
        var e = this._elt;
        $.vweb.handle_json( { 'ok': function(){
            $( "#" + rst.info.id, e ).removeClass( 'in_repost' );
            $( "#" + rst.info.id + " .g_repost", e ).remove();
        } }, rst );
    },

    comment_cb: function ( rst ) {
        var e = this._elt;
        $.vweb.handle_json( { 'ok': function(){
            $( "#" + rst.info.id + " .g_comment", e ).remove();
        } }, rst );
    },

    setup_func : function () {

        var uldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { user_id: this.id() }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );

        var atldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );

        var atldr = $.vweb.backend.weibo.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ '$.vweb.ui.t.list.show' ]
            } );



        this._elt
        .delegate( ".t_msg .avatar a.user, .t_msg .username a.user", "click", uldr )
        .delegate( ".t_msg .cont.msg a.at", "click", atldr )
        .delegate( ".t_msg .f_destroy", "click", function( ev ){
            $.evstop( ev );
            $.vweb.backend.weibo.t_cmd( 'POST', "destroy", '',
                { id: $( this ).p( ".t_msg" ).id() }, { } );
        } )
        .delegate( ".t_msg .f_retweet", "click", function( ev ){
            $.evstop( ev );
            var e = $( this ).p( ".t_msg" );
            e.addClass( 'in_repost' );
            $( ".g_repost", e ).remove();
            var rp = $( "#tmpl_repost" ).tmpl( [ {
                id: e.id(),
                text: e.hasClass( "retweeter" )
                    ? '//@' + $( ".username .user", e ).simpText() + ':' + $( ".cont.msg .msg", e ).simpText()
                    : ''
            } ] ).prependTo( e );
            $( '.f_text', rp ).focus();
        } )
        .delegate( ".t_msg .g_repost .f_cancel", "click", function( ev ){
            $.evstop( ev );
            $( this ).p( ".t_msg" ).removeClass( 'in_repost' );
            $( this ).p( ".g_repost" ).remove();
        } )
        .delegate( ".t_msg .f_comment", "click", function ( ev ) {
            $.evstop( ev );
            var e = $( this ).p( ".t_msg" );
            $( ".g_comment", e ).remove();
            $( "#tmpl_comment" ).tmpl( [ {
                id: e.id(),
                text: ''
            } ] ).appendTo( e );
        } )
        .delegate( ".t_msg .g_comment .f_cancel", "click", function( ev ){
            $.evstop( ev );
            $( this ).p( ".g_comment" ).remove();
        } )
        .delegate( ".t_msg .f_fav", "click", function( ev ){
            $.evstop( ev );
            $.log( this );
            $.vweb.backend.weibo.t_cmd( 'POST', "fav", '',
                { id: $( this ).p( ".t_msg" ).id() }, { } );
        } )
        ;
    },

    filter_existed : function ( data ) {
        return data;
    },

    msg_visible: function( id, visible ) {
        var e = $( '#' + id, this._elt );
        var f = visible ? "show" : "hide";

        e[ f ]().prev( '.retweeter' )[ f ]();
    },

    show : function ( data ) {

        data = $.vweb.tweets( data ).splitRetweet().exclude( $.vweb.ui.main.edit.ids() )
        .stdAvatar().defaultUser( 'sender' ).setMe( $.vweb.account.id )
        .htmlLinks().get();

        this._elt.empty();
        $( "#tmpl_msg[_mode=\"" + MODE + "\"]" ).tmpl( data ).appendTo( this._elt );

        this.setup_draggable();
    },
    setup_draggable : function () {

        this._elt.children().has( '.imgwrap' ).draggable( {
            connectToSortable: "#page",
            handle : ".imgwrap",
            helper : "clone",
            revert : "invalid",
            zIndex : 2000,
            cursorAt:{ left:50, top:50 },
            stop : function ( ev, ui ) {
            }
        } );


    }
} } );
