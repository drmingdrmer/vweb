// Works with jquery-1.4.4, jquery-ui-1.8.9

$.vweb = {
    conf: {
        loginPage: "http://" + window.location.host,
        appLink: 'http://t.cn/a0yUgu',  // vweb
        appLinkDev: 'http://t.cn/aOXV5H',  // 2.vweb
        maxChar: 110
    },
    account: undefined,
    ui : {
        main : {
            maintool : {},
            menu : {},
            edit : {}
        },
        t : {
            update : {},
            my : {
                friend : {},
                globalsearch : {}
            },
            paging : {},
            list : {}
        }
    },
    backend : {}
};

$.extend( $.vweb, {
    // TODO request not through t_cmd should also be handled like this
    tweets: function( data ) {
        function _stdAvatar( e ) {
            if ( e.profile_image_url ) {
                e.avatar_50 = e.profile_image_url;
                e.avatar_30 = e.profile_image_url.replace( /\/50\//, '/30/' );
            }
        }
        var self = {
            _d : data,
            get: function () { return this._d; },
            splitRetweet: function () {
                var d = [];
                $.each( this._d, function( i, v ){
                    d.push( v );
                    if ( v.retweeted_status ) {
                        v.status = 'retweeter';
                        v.retweeted_status.status = "retweet";
                        d.push( v.retweeted_status );
                    }
                } );
                this._d = d;
                return this;
            },
            exclude: function (ids) {
                this._d = ids.length == 0
                    ? this._d
                    : $.grep( this._d, function( v, i ) {
                        return ids.indexOf( v.id + "" ) < 0
                            && ( !v.retweeted_status || ids.indexOf( v.retweeted_status.id + "" ) < 0  );
                    } );
                return this;
            },
            stdAvatar: function ( userkey ) {
                $.each( this._d, function( i, v ) {
                    $.each( [ 'user', 'sender', 'recipient' ], function( ii, k ){
                        v[ k ] && _stdAvatar( v[ k ] );
                    } );
                } );
                return this;
            },
            htmlLinks: function () {
                $.each( this._d, function( i, v ) {
                    v.html = v.text.replace(
                        /(http:\/\/(?:sinaurl|t)\.cn\/[a-zA-Z0-9_]+)/g,
                        "<a target='_blank' class='raw' href='$1'>$1</a>" )
                    .replace(
                        /@([_a-zA-Z0-9\u4e00-\u9fa5\-]+)/g,
                        "<a class='at' screen_name='$1' href=''>@$1</a>" )
                    .replace(
                        /#([^#]+)#/g,
                        "<a class='topic' href=''>@$1</a>" )
                    ;
                } );
                return this;
            },
            defaultUser: function ( which ) {
                $.each( this._d, function( i, v ) {
                    v[ which ] && !v.user && ( v.user = v[ which ] );
                } );
                return this;
            },
            setMe: function( id ){
                $.each( this._d, function( i, v ){
                    v.user.isme = ( id == v.user.id );
                } );
                return this;
            },
            historyText: function () {
                $.each( this._d, function( i, v ){
                    v.text_forhis = v.text.replace( /([\u0100-\uffff])/g, '\u00ff$1' )
                    .substr( 0, 10 ).replace( /\u00ff/g, '' );

                    if ( v.text_forhis != v.text ) {
                        v.text_forhis +=  '...';
                    }
                } );
                return this;
            },
            historyTime: function () {
                var now = new Date();
                $.each( this._d, function( i, v ){
                    var d = new Date( v.created_at );
                    var dt = d.getYear() + "年" + d.getMonth() + "月" + d.getDate();
                    dt = ( dt == now.getYear() + "年" + now.getMonth() + "月" + now.getDate() ) ?  "今天" : dt;
                    v.time_forhis = dt + " " + d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
                } );
                return this;
            }
        };
        return self;
    },
    handle_json: function( handlers, json, st, xhr ) {
        if ( handlers[ json.rst ] ) {
            return handlers[ json.rst ]( json, st, xhr );
        }
        else if ( json.rst != "ok" && handlers[ "any" ] ) {
            return handlers.any( json, st, xhr );
        }
    },
    create_handler: function( handlers ) {
        return function ( json, st, xhr ) {
            return $.vweb.handle_json( handlers, json, st, xhr );
        };
    },
    init_sub: function( self ) {
        $.each( self, function( k, v ){
            var u = self[ k ];
            if ( u.init ) {
                u._elt = $( "#" + k );
                u.init( u, u._elt );
            }
        } );
    },

    setup_img_switch : function ( container ) {
        var swi = { thumb: "midpic", midpic: "thumb" };

        $( container ).delegate( ".t_msg .imgwrap", "click", function(){
            var e = $( this ).p( ".t_msg" );
            $.log( e + 'clicked' );
            e.toggleClass( 'thumb' );
            e.toggleClass( 'midpic' );
        } );
    }

} );

$.extend( $.vweb.ui, {

    init : function () {

        var self = this;

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
        $( "#menu" ).addClass( "cont_dark2 cont_dark_shad2" );
        $( "#func" ).addClass( "cont_dark2 cont_dark_shad2" );
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

        edit.height( bodyHeight - footerHeight - $( "#maintool,#menu" ).h() );
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


$.extend( $.vweb.ui.main, {
    _path : [],
    _fn   : "",

    init : function () {
        // TODO need these?
        this.eltMenu = $( "#menu" );
        this.eltPath = this.eltMenu.find( "#path" );
        $.vweb.init_sub( this );
    },
    path : function ( p ) {
        if ( p ) {
            this._path = p;
            // this.eltPath.val( p );
        }
        else {
            return this._path;
            // return "/vweb/" + this.eltPath.val() + ".html";
        }
    },
    filename : function (fn) {
        if ( fn ) {
            this._fn = fn;
        }
        return this._fn;
    }

} );

$.extend( $.vweb.ui.main.maintool, {
    init : function () {

        var self = this;
        var fn = this.fn = $( "#fn", this._elt );
        var pubmsg = this.pubmsg = $( "#pubmsg", this._elt );
        var charleft = this.charleft = $( "#charleft" ).hide();
        var pub = this.pub = $( "#pub", this._elt );
        this._defaultAlbumName = '未命名相册';


        fn.DefaultValue( this._defaultAlbumName );

        function pubmsg_open () {
            pubmsg.addClass( 'focused' );
            charleft.show();
            self.update_charleft();
            $.vweb.ui.relayout();
        }

        function pubmsg_close () {
            pubmsg.removeClass( 'focused' );
            charleft.hide();
            $.vweb.ui.relayout();
        }

        pubmsg.focus( function( ev ){

            if ( $( this ).simpVal() == '' ) {
                return;
            }

            self.focusTWID && window.clearTimeout( self.focusTWID );
            self.focusTWID = window.setTimeout( pubmsg_open, 50 );

        } )
        .keydown( function( ev ){

            if ( ev.keyCode == 13 && ( ev.metaKey || ev.ctrlKey ) ) {
                // ctrl-cr. command-cr on Mac.

                pub.click();
            }
            else if ( ev.keyCode == 27 ) {
                // esc

                pubmsg.blur();
                pubmsg_close();
            }
            else {

                // NOTE: val() retreives text without the key just pressed.
                self.keydownTW && window.clearTimeout( self.keydownTW );
                self.keydownTW = window.setTimeout(function() {
                    var txt = pubmsg.simpVal();
                    if ( txt.length > 20 || pubmsg.val().match( /\n/ ) ) {
                        pubmsg.hasClass( 'focused' ) || pubmsg_open();
                    }
                    self.update_charleft();
                 }, 10);
                return;
            }

            $.evstop( ev );
        } )
        ;

        $( document ).click( function( ev ){
            var tagname = ev.target.tagName;
            if ( tagname && tagname.match( /INPUT|BUTTON|TEXTAREA/ ) ) {
                return;
            }
            pubmsg_close();
        } )

        $( document ).keydown( function( ev ){

            if ( ev.keyCode == 13 && ( ev.metaKey || ev.ctrlKey ) ) {
                // ctrl-cr. command-cr on Mac.

                pubmsg.focus();
            }
            else {
                return;
            }

            $.evstop( ev );
        } );

        pub.click( function( ev ){
            $.evstop( ev );

            var msg = pubmsg.simpVal();

            if ( msg.length == 0 ) {
                $.vweb.ui.appmsg.alert( "还没有填写内容" );
                pubmsg.focus();
            }
            else if ( msg.length > $.vweb.conf.maxChar ) {
                $.vweb.ui.appmsg.alert( "字数太多" );
                pubmsg.focus();
            }
            else {
                var data = {
                    page: $.vweb.ui.main.edit.pagedata(),
                    layout: $.vweb.ui.main.edit.layoutdata() };

                $.vweb.backend.weibo.t_cmd( 'POST', 'pub', { albumname: fn.val(), msg: msg },
                    JSON.stringify( data ), {
                        success: function( json ) {
                            if ( json.rst == 'ok' ) {
                                pubmsg.val( '' ).blur();
                            }
                        }
                    } );
            }

        } );
    },

    update_charleft: function(){
        this.charleft.text( "剩余 " + (
            $.vweb.conf.maxChar - this.pubmsg.val().length ) + " 字" );
    }

} );

$.extend( $.vweb.ui.main.menu, {
    init : function () {}
} );

$.extend( $.vweb.ui.main.edit, {
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
                $.evstop( ev );
                $.log( ev );
                $( '#pagehint' ).remove();
                var msg = theui.item;
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
} );


$.extend( $.vweb.ui.t.update, {
    init: function () {

    },
    upload_cb: function (rst) {
        $.vweb.handle_json( {
            ok: function ( json, st, xhr ) {
                var e = $.vweb.ui.t.update._elt;
                $( '.f_status', e ).val('');
                $( '.f_pic', e ).val( '' );
            }
        }, rst );
    }

} );

$.extend( $.vweb.ui.t.list, {
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
        return data
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

        this._elt.children().draggable( {
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
} );

$.extend( $.vweb.ui.t.paging, {
    init: function() {
        var self = this;

        this._elt.find( "#btnhis" ).click( function(){
            $( "#history" ).toggle();
        } );

        $( '.f_prev', this._elt ).click( function(  ){
            if ( ! self.last ) { return; }
            var l = self.last;
            // var since_id = l.json.data[ 0 ].id;
            // var args = $.extend( {}, l.cmd.args, { since_id: since_id } );
            // delete args.max_id;

            var args = $.extend( {}, l.cmd.args );
            args.page = args.page ? args.page - 1 : 1;
            args.page = args.page <= 0 ? 1 : args.page;

            $.vweb.backend.weibo.load( l.cmd.name, { args: args, cb: l.cmd.cb } );

        } );
        $( '.f_next', this._elt ).click( function(  ){
            if ( ! self.last ) { return; }
            var l = self.last;
            // var max_id = l.json.data[ l.json.data.length - 1 ].id;
            // var args = $.extend( {}, l.cmd.args, { max_id: max_id } );
            // delete args.since_id;

            var args = $.extend( {}, l.cmd.args );
            args.page = args.page ? args.page + 1 : 2;

            $.vweb.backend.weibo.load( l.cmd.name, { args: args, cb: l.cmd.cb } );

        } );

        $( "#history" ).delegate( ".t_msg .f_del", "click", function( ev ){
            var e = $( this ).parent();
            $.vweb.ui.main.edit.removehis( e.attr( "id" ) );
            e.remove();
        } );

    },
    loadhis: function () {
        $.vweb.ui.main.edit.page.find( ".t_his" ).clone().appendTo(
            $( "#history" ).empty() );
    },
    addhis: function ( json, cmd ) {
        var rec = $.vweb.backend.weibo.gen_cmd_hisrecord( json, cmd );

        this.last = { cmd:cmd, json:json };

        $.log( 'paging.addhis:' );
        $.log( rec );

        $( this._elt ).find( ".t_his#" + rec.hisid ).remove();
        $( "#tmpl_hisrec" ).tmpl( [ rec ] ).prependTo( $( "#history" ) );
    }
} );

$.extend( $.vweb.ui.t, {
    init: function (){
        $.log( 'init $.vweb.ui.t' );
        $.vweb.init_sub( this );
    }
} );

$.extend( $.vweb.ui.t.my, {
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

} );

$.extend( $.vweb.ui.t.my.friend, {
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

} );
$.extend( $.vweb.ui.t.my.globalsearch, {
    init : function(){
        var self = this;
        self.buttonSubmit = self._elt.find( ".f_submit" );
        self.formParam = self._elt.find( "form.g_search" );

        var searchLoader = $.vweb.backend.weibo.create_loader(
            'statuses/search',
            {
                args: function() { return self.formParam.serialize(); },
                cb: [ '$.vweb.ui.t.list.show' ]
            }
        );

        self.buttonSubmit.click( searchLoader );
    }
} );

$( function() {
    if ( MODE == 'album' ) {
        $( '.nomode_album' ).remove();
    }

    $.vweb.backend.weibo.t_cmd( 'GET', 'account/verify_credentials', {}, undefined, {
        success:function( json ) {
            $.vweb.account = json.data;
            $.vweb.ui.init( $.vweb.ui );
        },
        error: function( jqxhr, errstr, exception ) {

        }
    } );

} );
