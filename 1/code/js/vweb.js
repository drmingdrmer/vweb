// Works with jquery-1.4.4, jquery-ui-1.8.9

var cfg = {
    key: '2060512444',
    xdpath: 'http://vweb.sinaapp.com/xd.html'
};

function log (mes) {
    console.log( mes );
}
function json_succ( handlers ) {

    function hdlr ( json, st, xhr ) {
        ui.appmsg.show( json.msg );
        if ( handlers[ json.rst ] ) {
            return handlers[ json.rst ]( json, st, xhr );
        }
        else if ( json.rst != "ok" && handlers[ "any" ] ) {
            return handlers.any( json, st, xhr );
        }
    }

    return hdlr;
}
function evstop ( ev ) {
    ev.stopPropagation();          /* pop up                  */
    ev.preventDefault()            /* other event on this DOM */
}
function init_sub ( self ) {
    $.each( self, function( k, v ){
        self[ k ]._elt = $( "#" + k );
        log( self[ k ]._elt );
        self[ k ].init && self[ k ].init();
    } );
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
        my : {
            friend : {},
        },
        list : {},
    },
    vd : {
        vdacc : {},
        tree : {}
    },
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
                log( "cmd ok:"  );
                log( rst );
                if ( rst.rst == "ok" ) {
                    cb && cb( rst.data );
                }
                else {
                    ui.appmsg.show( rst.rst + " " + rst.msg );
                }
            },
            error : function( xhr, st, err ) {
                log( "cmd error" );
                ui.appmsg.show( st );
                // ui.appmsg.show( err );
            }
        } );

    }
};

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
        $( ".t-ctrl" ).addClass( "ui-widget ui-corner-all" );
        $( ".t-group" ).addClass( "ui-widget ui-corner-all" );
        $( "#menu" ).addClass( "cont_dark2 cont_dark_shad2" );
        $( "#func" ).addClass( "cont_dark2 cont_dark_shad2" );
        $( "#edit" ).addClass( "cont_white0 cont_white_shad0" );
        $( "#list" ).addClass( "cont_white0 cont_white_shad0" );
        $( "#paging" ).addClass( "cont_dark0 cont_dark_shad0" );


        init_sub( this );

        $( window ).resize( function() { self.relayout(); } );
        $( "body" ).click( function( ev ) { $( ".t-autoclose" ).hide(); } );


    },
    relayout : function () {
        var bodyHeight = $( "body" ).height();
        var tabsHeight = $( "#tabs" ).h();
        var edit = $( "#edit" );

        edit.height( bodyHeight - $( "#hd" ).h() - $( "#menu" ).h() );
        $( "#t>#list" ).height( bodyHeight - tabsHeight - $( "#t>#func" ).h() - $( "#t>#paging" ).h() );
        $( "#tree" ).height( bodyHeight - tabsHeight - $( "#vdaccpane" ).h() );

        $( "#edit>#cont" ).width( edit.width() - 30 ).height( edit.height() - 30 );

    },

    setup_img_switch : function ( container ) {
        $( container ).delegate( ".t_msg img.msgimg", "click", function(){
            var e = $( this );
            var toshow = e.hasClass( "thumb" ) ? "midpic" : "thumb";
            e.hide().siblings( "img." + toshow ).show();
        } );
    }
} );

$.extend( ui.appmsg, {
    show : function ( text ) {
        if ( text ) {
            var e = this._elt;
            $( "#tmpl_appmsg" ).tmpl( [{text:text}] ).appendTo( e.empty() );
            this.lastid && window.clearTimeout( this.lastid );
            this.lastid = window.setTimeout( function(){ e.empty(); }, 5000 );
        }
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
                    text : $.trim( e.text() ).replace( / +/g, ' ' )
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
    html : function( h ) {
        if ( h ) {
            this.page.html( h );
        }
        else {
            // TODO filter and cleanup
            return this.page.html();
        }
    }
} );

$.extend( ui.t.acc, {
    init : function() {
        this.t = $( "#t" );
    },
    login : function () {
        log( "login called" );
        var self = this;
        WB.connect.login(function() {
            log( 'login ok' );
            self.t.removeClass( "invisible" );
        });
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

        var args = opt.args && opt.args.apply( this, [] ) || {};

        log( "args:" );
        log( args );

        ui.appmsg.show( "载入中..." );

        wb.cmd( cmdname, args, function( data ) {
            var cb = opt.cb;

            ui.appmsg.show( "载入成功" );
            cb[0][ cb[1] ]( data );
        } );
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
                log( "pub rst=" );
                log( rst );
                if ( rst.rst == "ok" ) {
                    ui.appmsg.show( "published" );
                    // TODO message
                }
                else {
                    ui.appmsg.show( rst.msg );
                }
            }

        } );
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
                ui.appmsg.show( rst.msg );
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
        var html = $.trim( ui.edit.html() );

        // TODO unicode, utf-8, url-encoding test
        var path = ui.menu.path();

        var url = "/vd.php?path=" + path;

        log( "to save html=" + html );
        log( "to save path=" + path );

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


        log( "to load path=" + url );

        $.ajax( {
            type : "GET", url : url,
            dataType : 'json',
            success : json_succ( {
                "ok" : function( json, st, xhr ){
                    log( json.html );
                    ui.edit.html( json.html );
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

    setup_func : function () {

        var uldr = ui.t.acc.create_loader(
            'statuses/user_timeline', {
                args: function(){ return { user_id: this.attr( 'id' ) }; },
                cb: [ ui.t.list, 'show' ]
            } );

        var atldr = ui.t.acc.create_loader(
            'statuses/user_timeline', {
                args: function(){ log( this.attr( 'screen_name' ) ); return { screen_name: this.attr( 'screen_name' ) }; },
                cb: [ ui.t.list, 'show' ]
            } );

        this._elt
        .delegate( ".t_msg .avatar a.user", "click", uldr )
        .delegate( ".t_msg .cont.msg a.at", "click", atldr );
    },

    filter_existed : function ( data ) {

        return data
    },

    reform_data : function ( data ) {
        var d = []
        $.each( data, function( i, v ) {
            d.push( v );
            if ( v.retweeted_status ) {
                v.retweeted_status.retweet = "retweet";
                d.push( v.retweeted_status );
            }
        } );
        data = d;

        var ids = ui.fav.edit.ids();
        log( ids );

        if ( ids.length > 0 ) {
            data = $.grep( data, function( v, i ) {
                return ids.indexOf( v.id + "" ) < 0;
            } );
        }

        $.each( data, function( i, v ) {
            if ( v.user.profile_image_url ) {
                v.user.avatar_50 = v.user.profile_image_url;
                v.user.avatar_30 = v.user.profile_image_url.replace( /\/50\//, '/30/' );
            }
　
            v.html = v.text.replace( /http:\/\/[^ ]+/g, function(a){
                return "<a target='_blank' href='" + a + "'>" + a + "</a>";
            } ).replace( /@[_a-zA-Z\u00ff-\u2fff\u3001-\uffff]+/g, function(a){
                return "<a class='at' screen_name='" + a.substr( 1 ) + "' href=''>" + a + "</a>";
            } );

            log( v.html );

        } );

        return data;
    },

    show : function ( data ) {
        var self = this;

        data = this.reform_data( data );

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
                // log( "stop" );
                // log( $( ev.target ).parent().attr( 'id' ) );
                // log( ui );
            },
        } );

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
        self.myButton = $( "#expand.t_btn" );

        // TODO stand alone #my in ui hierarchy
        self.myDialog  = $( "#my" );

        self.myButton.click( function (ev){
            log( 'click' );
            ev.preventDefault()            /* other event on this DOM */
            ev.stopPropagation();          /* pop up                  */
            // ev.stopImmediatePropagation(); [> pop up and other        <]
            self.switchPanel( true );

        } );

        init_sub( this );
    },
    switchPanel : function ( vis ) {
        if ( vis ) {
            this.myDialog.show();
        }
        else {
            this.myDialog.hide();
        }
    },
} );

$.extend( ui.t.my.friend, {
    init : function(){
        var self = this;
        self.formSimp = self._elt.find( "form.g_simp" );
        self.formSearch = self._elt.find( "form.g_search" );

        log( self._elt );

        var simpLoader = ui.t.acc.create_loader(
            'statuses/friends_timeline',
            {
                args: function() { return self.formSimp.serialize(); },
                cb: [ ui.t.list, 'show' ]
            }
        );

        self._elt.find( ".f_idx" ).click( simpLoader );
        self.formSimp.find( "input" ).button().click( simpLoader );
    },


} );

var filter = { };

( function( $ ) {
    $.fn.h = function() { return this.outerHeight( true ); }

    $.fn.btn_opt = function (  ) {
        var e = $( this );
        var opt = {
            icons : {},
            text : e.attr( "_text" ) != "no"
        };

        e.attr( "_icon" ) && ( opt.icons.primary = "ui-icon-" + e.attr( "_icon" ) );
        e.attr( "_icon2" ) && ( opt.icons.secondary = "ui-icon-" + e.attr( "_icon2" ) );

        log( opt );

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
    $.fn.myDialog = function( opt ) {

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
