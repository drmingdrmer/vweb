// Works with jquery-1.4.4, jquery-ui-1.8.9

var cfg = {
    key: '2060512444',
    xdpath: 'http://vweb.sinaapp.com/xd.html'
};

var ui = {
    appmsg : {},
    fav : {
        hd : {},
        menu : {},
        edit : {}
    },
    tabs : {},
    t : {
        acc : {},
        update : {},
        my : {
            friend : {},
            globalsearch : {}
        },
        paging : {},
        list : {}
    },
    vd : {
        vdacc : {},
        tree : {}
    }
};

var wb = {

    cmd : function ( cmd, args, cb ) {

        if ( $.isPlainObject( args ) ) {
            args = $.param( args );
        }

        log( cmd, args );

        $.ajax( {
            type : "GET",
            url : "t.php?act=" + cmd + "&" + args,
            dataType : "json",
            success : function( rst, st, xhr ) {
                if ( rst.rst == "ok" ) {
                    cb && cb( rst.data );
                }
                else {
                    ui.appmsg.msg( rst.rst + " " + rst.msg );
                }
            },
            error : function( xhr, st, err ) {
                ui.appmsg.msg( st );
            }
        } );

    }
};

function log (mes) {
    console.log( mes );
}


function json_handler ( handlers, json, st, xhr ) {
    ui.appmsg[ json.rst == 'ok' ? 'msg' : 'err' ]( json.msg );

    if ( handlers[ json.rst ] ) {
        return handlers[ json.rst ]( json, st, xhr );
    }
    else if ( json.rst != "ok" && handlers[ "any" ] ) {
        return handlers.any( json, st, xhr );
    }
}

function json_succ( handlers ) {

    function hdlr ( json, st, xhr ) {
        return json_handler( handlers, json, st, xhr );
    }

    return hdlr;
}
function evstop ( ev ) {
    ev.stopPropagation();          /* pop up                  */
    ev.preventDefault()            /* other event on this DOM */
}
function init_sub ( self ) {
    $.each( self, function( k, v ){
        var u = self[ k ];
        u._elt = $( "#" + k );
        u.init && u.init( u._elt );
    } );
}
function $td ( data ) {
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
        filter: function (ids) {
            this._d = ids.length == 0
                ? this._d
                : $.grep( this._d, function( v, i ) {
                    return ids.indexOf( v.id + "" ) < 0;
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
                v.html = v.text.replace( /http:\/\/sinaurl.cn\/[a-zA-Z0-9_]+/g, function(a){
                    return "<a target='_blank' href='" + a + "'>" + a + "</a>";
                } ).replace( /@[_a-zA-Z0-9\u4e00-\u9fa5]+/g, function(a){
                    return "<a class='at' screen_name='" + a.substr( 1 ) + "' href=''>" + a + "</a>";
                } );
            } );
            return this;
        },
        defaultUser: function ( which ) {
            $.each( this._d, function( i, v ) {
                v[ which ] && !v.user && ( v.user = v[ which ] );
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
}
Function.prototype.dele = function( self, args ) {
    var fun = this;
    return function () {
        return fun.apply( self || this, args || arguments );
    }
}
Function.prototype.dele$ = function() {
    var args = arguments;
    var fun = this;
    return function () {
        return fun.apply( $( this ), args );
    }
}
Function.prototype.delethis = function( self, args ) {
    var thiz = this;
    return function () {
        return thiz.apply( self || this, args );
    }
}



$.extend( ui, {
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
        $( "#edit" ).addClass( "cont_white0 cont_white_shad0" );
        $( "#list" ).addClass( "cont_white0 cont_white_shad0" );
        $( "#paging" ).addClass( "cont_dark0 cont_dark_shad0" );


        init_sub( this );

        $( window ).resize( function() { self.relayout(); } );
        $( "body" ).click( function( ev ) {
            var tagname = ev.target.tagname;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                $( ".t-autoclose" ).hide();
            }
        } );

    },
    relayout : function () {
        var bodyHeight = $( "body" ).height();
        var tabsHeight = $( "#tabs" ).h();
        var edit = $( "#edit" );
        var subtabHeight = bodyHeight - tabsHeight;

        edit.height( bodyHeight - $( "#hd,#menu" ).h() );

        $( "#t>#list" ).height( subtabHeight - $( "#t>#update,#t>#func,#t>#paging" ).h() );
        $( "#tree" ).height( subtabHeight - $( "#vdaccpane" ).h() );

        $( "#edit>#cont" ).width( edit.width() - 30 ).height( edit.height() - 30 );

    },

    setup_img_switch : function ( container ) {
        var swi = { thumb: "midpic", midpic: "thumb" };

        $( container ).delegate( ".t_msg img.msgimg", "click", function(){
            var e = $( this ).p( ".t_msg" );
            e.toggleClass( 'thumb' );
            e.toggleClass( 'midpic' );
        } );
    }
} );

$.extend( ui.appmsg, {
    msg : function ( text ) {
        if ( text ) {
            var e = this._elt;
            $( "#tmpl_appmsg" ).tmpl( [{text:text}] ).appendTo( e.empty() );
            this.lastid && window.clearTimeout( this.lastid );
            this.lastid = window.setTimeout( function(){ e.empty(); }, 5000 );
        }
    },
    err: function ( text ) {
        return this.msg( text );
    }
} );

$.extend( ui.fav, {
    _path : [],
    _fn   : "",

    init : function () {
        // TODO need these?
        this.eltMenu = $( "#menu" );
        this.eltPath = this.eltMenu.find( "#path" );
        init_sub( this );
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

$.extend( ui.fav.hd, {
    init : function () {
        $( "#pub" ).click( function( ev ){
            evstop( ev );
            ui.t.acc.pub();
        } );
    },

} );

$.extend( ui.fav.menu, {
    init : function () {

    }
} );

$.extend( ui.fav.edit, {
    init : function () {
        var self = this;
        this.cont = this._elt.children( "#cont" );
        this.page = this.cont.children( "#page" );

        this.page.empty();

        this.setup_func();


        this._elt.find( "#edit_mode input" ).button().click( function() {
            $( this ).parent().find( "input" ).each( function() {
                self.cont.removeClass( $( this ).val() );
            } );
            self.cont.addClass( $( this ).val() );
        } );

        this._elt.find( "#screen_mode input" ).button();


    },
    setup_func : function () {
        ui.setup_img_switch( this.page );

        this.page.sortable({
            // handle : ".handle",
            receive : function ( ev, ui ) {
                var msg = ui.item;
                msg.hide();
                // TODO add to global filter list

                log( "receive" );
                log( $( ev.target ).parent().attr( 'id' ) );
                log( ev );
                log( ui );
                log( ui.item.parent().attr( "id" ) );
                log( ui.helper.parent().attr( "id" ) );
            },
            // NOTE: helper setting to "clone" prevents click event to trigger
            helper : "clone"
        });
    },
    ids : function () {
        var ids = [];
        this.page.find( ".t_msg" ).each( function() {
            ids.push( $( this ).attr( "id" ) );
        } );
        log( "ids=", ids );
        return ids;
    },
    layoutdata : function () {
        var rst = [];
        var root = this.cont.offset();
        root.top -= this.cont.scrollTop();
        root.left -= this.cont.scrollLeft();

        // var rootw = this.cont.innerWidth();
        // var rooth = this.cont.innerHeight();

        var rootw = this.cont[ 0 ].scrollWidth;
        var rooth = this.cont[ 0 ].scrollHeight;

        this.cont.find( ".t_msg" ).each( function() {
            var e = $( this );
            var thumb = e.find( "img.thumb:visible" );
            var thumbData = null;

            if ( thumb.length > 0 ) {
                var tp = thumb.offset();
                thumbData = {
                    t : tp.top - root.top,
                    l : tp.left - root.left,
                    w : thumb.outerWidth(),
                    h : thumb.outerHeight(),
                    img : thumb.attr( "src" )
                };
                rst.push( thumbData );
            }

            if ( e.find( ".cont .msg:visible" ).length > 0 ) {
                var p = e.offset();

                var d = {
                    t : p.top - root.top,
                    l : p.left - root.left,
                    w : e.outerWidth() - ( thumbData ? thumbData.w + 4 : 0 ),
                    h : e.outerHeight(),
                    color : "#000",
                    text : e.simpText()
                };

                rst.push( d );
            }


            var midpic = e.find( "img.midpic:visible" );

            if ( midpic.length > 0 ) {
                var tp = midpic.offset();
                midpicData = {
                    t : tp.top - root.top,
                    l : tp.left - root.left,
                    w : midpic.outerWidth(),
                    h : midpic.outerHeight(),
                    img : midpic.attr( "src" )
                };
                rst.push( midpicData );
            }

        } );

        return {
            w : rootw,
            h : rooth,
            bgcolor : "#fff",
            d : rst
        };
    },
    addhis: function ( rec ) {
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

$.extend( ui.t.acc, {
    init : function() { },

    cmdtostr: function ( cmdname, opt, idfirst ) {
        var args = [];
        var o = {};

        $.isPlainObject( opt ) && ( $.extend( o, opt ) ) || ( o = $.unparam( opt ) );
        o.max_id || ( o.max_id = idfirst );
        $.each( o, function( k, v ){ args.push( k + '__' + v ); } );
        args.sort();

        var s = cmdname.replace( /\//, '__' ) + '____' + args.join( '____' ) ;
        log( 'cmd str=' + s );
        return s;
    },

    strtocmd: function ( s ) {
        var args = s.split( /____/ );
        var cmdname = args.shift().replace( /__/, '/' );
        var opt = {};
        $.each( args, function( i, v ){
            var q = v.split( '__' );
            opt[ q[ 0 ] ] = q[ 1 ];
        } );

        return [ cmdname, opt ];
    },

    create_loader : function ( cmdname, opt ) {
        var realload = this.load;
        return function ( ev ) {
            ev && evstop( ev );
            realload.apply( $(this), [ cmdname, opt ] );
        }
    },

    load : function( cmdname, opt ) {
        // 'this' is set by create_loader and which is the DOM fired the event

        var trigger = this;
        var args = opt.args && opt.args.apply( this, [] ) || {};

        log( "args:" );
        log( args );

        ui.appmsg.msg( "载入中..." );

        wb.cmd( cmdname, args, function( data ) {
            var cb = opt.cb;
            var t = trigger;
            var cmd = { name : cmdname, args : args };

            ui.appmsg.msg( "载入成功" );

            // TODO do not addhis after paging down/up
            ui.t.acc.addhis( data[ 0 ], cmd );

            cb && $.each( cb, function( i, v ){
                eval( v + "(data,t,cmd)" );
                // var f = eval( v );
                // f.apply( [ data ] );
            } );
        } );
    },

    addhis: function ( d, cmd ) {
        if ( ! d ) { return; }

        d.hisid = ui.t.acc.cmdtostr( cmd.name, cmd.args, d.id );
        log( d );

        hisdata = $td( [ d ] ).stdAvatar().defaultUser('sender').historyText().historyTime().get()[ 0 ];
        log( hisdata );

        ui.t.paging.addhis( hisdata );
        ui.fav.edit.addhis( hisdata );
    }, 

    pub : function () {

        var data = ui.fav.edit.layoutdata();

        // TODO specific title
        var msg = ( new Date() );
        if ( data.d.length > 0 ) {
            msg += data.d[ 0 ].text;
        }

        log( "layout data:" );
        log( data );

        $.ajax( {
            type : "POST", url : "t.php?act=pub&msg=" + msg,
            data : JSON.stringify( data ),
            dataType : "json",
            success : function( rst, st, xhr ) {
                if ( rst.rst == "ok" ) {
                    ui.appmsg.msg( "published" );
                    // TODO message
                }
                else {
                    ui.appmsg.msg( rst.msg );
                }
            }

        } );
    }
} );

$.extend( ui.t.update, {
    init: function () {

    },
    upload_cb: function (rst) {
        json_handler( {}, rst );
    }

} );

$.extend( ui.vdacc, {
    afterLogin : [],
    curPath : "",
    curFile : "Untitled",
    init : function() {
        var self = this;
        self.vdform = $( "form#vdform" );
        // self.vddialog = self.vdform.dialog({ autoOpen: false });


        // self.vdform.find( "input[name=submit]" ).click( function( ev ) {
        self.vdform.find( "input[name=submit]" ).submit( function( ev ) {
            self.do_login();
        } );

    },
    do_login : function() {
        var self = this;

        self.vdform.jsonRequest( json_succ( {
            "ok" : function () {

                // self.vddialog.dialog( "close" );

                var jobs = self.afterLogin;
                self.afterLogin = [];
                $.each( jobs, function( i, v ){
                    v();
                } );
            }

        } ) );

        ev.preventDefault();
        ev.stopPropagation();
    },
    keeptoken : function( cb ) {
        var self = this;
        var url = "/vd.php?act=keeptoken";

        $.ajax( {
            url : url,
            dataType : "json",
            success : function( rst, st, xhr ) {
                ui.appmsg.msg( rst.msg );
                if ( rst.rst == "ok" ) {
                    cb && cb( rst, st, xhr );
                }
            }
        } );
    },
    browse : function( opt ) {
        var self = this;
        var url = "/vd.php?act=list";

        if ( opt ) {
            url += opt.dirid ? "&dirid=" + opt.dirid : "&path=" + opt.path;
        }

        $.ajax( {
            type : "GET", url : url,
            dataType : 'json',
            success : json_succ( {
                "ok" : function( json ){ ui.tree.update( json.data ); },
                "invalid_token" : function () {
                    self.afterLogin.push( function(){
                        self.browse();
                    } );
                    // self.vddialog.dialog( "open" );
                }
            } )
        } );
    },
    save : function( cb ) {

        var self = this;
        var html = $.trim( ui.fav.edit.html() );

        // TODO unicode, utf-8, url-encoding test
        var path = ui.menu.path();

        var url = "/vd.php?path=" + path;

        $.ajax( {
            type : "PUT", url : url,
            data : html,
            dataType : 'json',
            success : json_succ( {
                "ok" : cb,
                "invalid_token" : function () {
                    self.afterLogin.push( function(){
                        self.save( cb );
                    } );
                    // self.vddialog.dialog( "open" );
                }
            } )
        } );
    },
    load : function( what ) {
        var self = this;
        var url = "/vd.php?act=load&";
        url += what.fid ? "&fid=" + what.fid : "&path=" + what.path;



        $.ajax( {
            type : "GET", url : url,
            dataType : 'json',
            success : json_succ( {
                "ok" : function( json, st, xhr ){
                    ui.fav.edit.html( json.html );
                    // what.path ?

                },
                "invalid_token" : function () {
                    self.afterLogin.push( function(){
                        self.load( path );
                    } );
                    self.vddialog.dialog( "open" );
                }
            } )
        } );
    },
    logout : function() {

    }
} );
$.extend( ui.tree, {
    init : function() {
        $( "#tree ul" )
        .delegate( "li", "hover", $.fn.toggleClass.dele$( 'hover' ) )
        .delegate( "li.file", "click", function(){
            var e = $( this );
            ui.vdacc.load( {
                "fid" : e.attr( "id" ),
                "name"  : $.trim( e.text() )
            } );
        } )
        .delegate( "li.folder", "click", function(){
            var e = $( this );
            ui.vdacc.browse( { "dirid" : e.attr( "id" ) } );
        } );
    },
    update : function ( data ) {
        $.each( data, function( i, v ){
            if ( v.sha1 ) {
                v.class = "file";
            }
            else {
                v.class = "folder";
            }
        } );

        data.sort( function( a, b ){
            var va = a.class == "folder" ? 0 : 1;
            var vb = b.class == "folder" ? 0 : 1;

            return va == vb ? ( a.name > b.name ? 1 : ( a.name < b.name ? -1 : 0 ) ) : ( va - vb );

        } );

        $( "#tmpl_tree_item" ).tmpl( data )
        .appendTo( $( "#tree ul" ).empty() );
    }

} );

$.extend( ui.t.list, {
    init : function () {
        ui.setup_img_switch( this._elt.empty() );
        this.setup_func();
    },

    repost_cb: function ( rst ) {
        var e = this._elt;
        json_handler( { 'ok': function(){
            $( "#" + rst.info.id + " .g_repost", e ).remove();
        } }, rst );
    },

    comment_cb: function ( rst ) {
        var e = this._elt;
        json_handler( { 'ok': function(){
            $( "#" + rst.info.id + " .g_comment", e ).remove();
        } }, rst );
    },

    setup_func : function () {

        var uldr = ui.t.acc.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { user_id: this.id() }; },
                cb: [ 'ui.t.list.show' ]
            } );

        var atldr = ui.t.acc.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ 'ui.t.list.show' ]
            } );

        var atldr = ui.t.acc.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ 'ui.t.list.show' ]
            } );

        this._elt
        .delegate( ".t_msg .avatar a.user", "click", uldr )
        .delegate( ".t_msg .cont.msg a.at", "click", atldr )

        .delegate( ".t_msg .f_retweet", "click", function( ev ){
            evstop( ev );
            var e = $( this ).p( ".t_msg" );
            $( ".g_repost", e ).remove();
            $( "#tmpl_repost" ).tmpl( [ {
                id: e.id(),
                text: e.hasClass( "retweeter" ) ? $( ".cont.msg .msg", e ).simpText() : ''
            } ] ).prependTo( e );
        } )
        .delegate( ".t_msg .g_repost .f_cancel", "click", function( ev ){
            evstop( ev );
            $( this ).p( ".g_repost" ).remove();
        } )

        .delegate( ".t_msg .f_comment", "click", function ( ev ) {
            evstop( ev );
            var e = $( this ).p( ".t_msg" );
            $( ".g_comment", e ).remove();
            $( "#tmpl_comment" ).tmpl( [ {
                id: e.id(),
                text: ''
            } ] ).appendTo( e );
        } )
        .delegate( ".t_msg .g_comment .f_cancel", "click", function( ev ){
            evstop( ev );
            $( this ).p( ".g_comment" ).remove();
        } )

        .delegate( ".t_msg .f_fav", "click", function( ev ){
            evstop( ev );
            $.ajax( {
                type : "POST", url : "/t.php?act=fav&resptype=json",
                data : { id: $( this ).p( ".t_msg" ).id() },
                dataType : 'json',
                success : json_succ( {
                    "ok" : function( json ){}
                } )
            } );
        } )
        ;
    },

    filter_existed : function ( data ) {

        return data
    },

    show : function ( data ) {
        var self = this;

        data = $td( data ).splitRetweet().filter( ui.fav.edit.ids() )
        .stdAvatar().defaultUser( 'sender' ).htmlLinks()
        .get();

        log( data );

        this._elt.empty();
        $( "#tmpl_msg" ).tmpl( data ).appendTo( this._elt );

        this.setup_draggable();
    },
    setup_draggable : function () {

        this._elt.children().draggable( {
            connectToSortable: "#page",
            // handle : ".handle",
            helper : "clone",
            revert : "invalid",
            zIndex : 2000,
            stop : function ( ev, ui ) {
            },
        } );

    }
} );

$.extend( ui.t.paging, {
    init: function() {
        this._elt.find( "#btnhis" ).click( function(){
            $( "#history" ).toggle();
        } );
        $( "#history" ).delegate( ".t_msg .f_del", "click", function( ev ){
            var e = $( this ).parent();
            ui.fav.edit.removehis( e.attr( "id" ) );
            e.remove();
        } );
    },
    loadhis: function () {
        ui.fav.edit.page.find( ".t_his" ).clone().appendTo( $( "#history" ).empty() );
    },
    addhis: function ( rec ) {
        log( 'paging.addhis:' );
        log( rec );
        $( this._elt ).find( ".t_his#" + rec.hisid ).remove();
        $( "#tmpl_hisrec" ).tmpl( [ rec ] ).prependTo( $( "#history" ) );
    }
} );

$.extend( ui.t, {
    init: function (){
        init_sub( this );
    }
} );

$.extend( ui.t.my, {
    init : function () {
        var self = this;
        $( "#expand.t_btn" ).click( function (ev){
            evstop( ev );
            self._elt.removeClass( 'hideall' );
        } );

        var statLoader = ui.t.acc.create_loader( 'statuses/unread', {
            args: function () {
                return {
                    'since_id': ui.t.my.friend.since_id,
                    'with_new_status': 1
                }
            },
            cb: [ 'ui.t.my.setStat' ] } );

        statLoader();
        self.whatID = window.setInterval( statLoader, 60 * 1000 );


        $( "body" ).click( function( ev ){
            var tagname = ev.target.tagName;
            if ( tagname != 'INPUT' && tagname != 'BUTTON' ) {
                self._elt.addClass( 'hideall' );
            }
        } );

        init_sub( this );
    },

    setStat: function( d, tgr ){
        var e = this._elt
        d.new_status && $( '#friend .f_idx .stat', e ).text( "(新)" );
        d.comments && $( '#friend .f_comment .stat', e ).text( "(" + d.comments + ")" );
        d.mentions && $( '#friend .f_at .stat', e ).text( "(" + d.mentions + ")" );
        d.dm && $( '#friend .f_message .stat', e ).text( "(" + d.dm + ")" );
    },

} );

$.extend( ui.t.my.friend, {
    init : function( e ){
        var self = this;
        self.formSimp = e.find( "form.g_simp" );
        self.formSearch = e.find( "form.g_search" );

        var simpLoader = ui.t.acc.create_loader(
            'statuses/friends_timeline',
            { args: function() { return self.formSimp.serialize(); },
              cb: [ 'ui.t.list.show', 'ui.t.my.friend.addLast', 'ui.t.my.friend.unsetStat'] }
        );

        // option arg of all these 3 loader: since_id, max_id, count, page
        var atLoader = ui.t.acc.create_loader( 'statuses/mentions', { cb: [ 'ui.t.list.show', 'ui.t.my.friend.unsetStat' ] });
        var cmtLoader = ui.t.acc.create_loader( 'statuses/comments_to_me', { cb: [ 'ui.t.list.show', 'ui.t.my.friend.unsetStat' ] });
        var msgLoader = ui.t.acc.create_loader( 'direct_messages', { cb: [ 'ui.t.my.friend.unsetStat' ] });

        $( ".f_idx", e ).click( simpLoader );
        self.formSimp.find( "input" ).button().click( simpLoader );


        $( ".f_at", e ).click( atLoader );
        $( ".f_comment", e ).click( cmtLoader );

        // don't stop event propagation. it links to another page.
        $( ".f_message", e ).click(
            function( ev ){ ui.t.my.friend.unsetStat( null, $( this ) ); }
        );

        init_sub( self );

        // TODO default action after page loaded
        window.setTimeout(function() { self._elt.find( ".f_idx" ).trigger( 'click' ); }, 0);
    },

    addLast: function ( d ) {
        d = d[ 0 ];
        if ( !this.since_id || d && (d.id+0) > (this.since_id+0) ) {
            this.since_id = d.id;
        }
    },

    unsetStat: function ( data, triggerElt ) {
        $( '.stat', triggerElt ).empty();
        if ( triggerElt.attr( "_reset_type" ) ) {
            ui.t.acc.create_loader(
                'statuses/reset_count',
                { args: function() { return { type: triggerElt.attr( "_reset_type" ) }; } }
                )();
        }
    }

} );
$.extend( ui.t.my.globalsearch, {
    init : function(){
        var self = this;
        self.buttonSubmit = self._elt.find( ".f_submit" );
        self.formParam = self._elt.find( "form.g_search" );

        var searchLoader = ui.t.acc.create_loader(
            'statuses/search',
            {
                args: function() { return self.formParam.serialize(); },
                cb: [ 'ui.t.list.show' ]
            }
        );

        self.buttonSubmit.click( searchLoader );
    }
} );

var filter = { };

( function( $ ) {

    $.unparam = function( s ){
        var o = {};
        var args = s.split( "&" );
        $.each( args, function( i, e ){
            var kv = e.split( "=" );
            var k = decodeURIComponent( k );
            var v = decodeURIComponent( v );
            o[ k ] = v;
        } );

        return o;
    }


    $.unescape = function(html) {
        var htmlNode = document.createElement('div');
        htmlNode.innerHTML = html;
        if (htmlNode.innerText) {
            return htmlNode.innerText; // IE
        }
        return htmlNode.textContent; // FF
    }

    $.fn.h = function() {
        var h = 0;
        this.each( function( i, v ){
            h += $(v).outerHeight( true );
        } );
        return h;
    }
    $.fn.p = $.fn.parents;

    $.fn.id = function() { return this.attr( 'id' ); }
    $.fn.simpText = function(t) {
        return $.trim( this.text( t ) ).replace( / +/g, ' ' );
    }

    $.fn.btn_opt = function (  ) {
        var e = $( this );
        var opt = {
            icons : {},
            text : e.attr( "_text" ) != "no"
        };

        e.attr( "_icon" ) && ( opt.icons.primary = "ui-icon-" + e.attr( "_icon" ) );
        e.attr( "_icon2" ) && ( opt.icons.secondary = "ui-icon-" + e.attr( "_icon2" ) );

        return opt;
    }
    $.fn.jsonRequest = function( succ ){

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
    $.fn.fullPanel = function( opt ) {

        opt = $.extend( {
            autoOpen : false,
            modal : true,
            draggable : false,
            resizable : false
        }, opt );

        $( this ).dialog( opt );

        if ( $( this ).dialog( 'option', 'title' ) == '' ) {

            $( this ).dialog( 'option', 'title',
                $( this ).find( '#title' ).hide().text() );

        }
    }
} )( jQuery );

/* ( function( $ ){
 *
 * $.widget( 'ui.hint', {
 *
 *         title : '',
 *         iconName : 'info',
 *         baseClass : 'ui-state-highlight',
 *
 *         _create : function() {
 *
 *             this.element
 *                     .addClass( this.baseClass + ' ui-corner-all' );
 *
 *             var text = this.element.text();
 *
 *             var p = $( '<p></p>' )
 *                     .css( { 'padding' : '5px' } )
 *                     .appendTo( this.element.empty() );
 *
 *             var hintIcon = $( '<span></span>' )
 *                     .addClass( 'ui-icon ui-icon-' + this.iconName )
 *                     .css( { 'float' : 'left',
 *                             'margin' : '4px' } )
 *                     .appendTo( p );
 *
 *             var title = $( '<strong></strong>' )
 *                     .text( this.title )
 *                     .appendTo( p );
 *
 *             p.append( text );
 *
 *         }
 * } );
 *
 * // $.widget( 'ui.error', $.ui.hint, {
 * //         iconName : 'alert',
 * //         baseClass : 'ui-state-error'
 * // } );
 *
 * } )( jQuery );
 */
