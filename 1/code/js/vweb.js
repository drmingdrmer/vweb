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

var ui = { appmsg : {}, menu : {}, edit : {}, list : {}, my : {}, acc : {}, vdacc : {}, tool : {}, tree : {} };

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


        $( ".t-btn" ).each( function() {
            $( this ).button( $( this ).btn_opt() );
        } );

        $( ".t_btn_set" ).buttonset();


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

        $.each( [ "appmsg", "menu", "acc", "vdacc", "edit", "list", "my", "tree" ],
            function( i, v ){ self[ v ].init && self[ v ].init(); } );

        $( window ).resize( function() { self.relayout(); } );
        $( "body" ).click( function( ev ) { $( ".t-autoclose" ).hide(); } );


    },
    relayout : function () {
        var app = $( "#app" );
        var head = $( "#hd" );
        var edit = $( "#edit" );
        // var cont = $( "#edit>#cont" );
        var tool = $( "#tool" );
        var func = $( "#tool>#func" );
        var list = $( "#tool>#list" );


        var bodyHeight = $( window ).height();
        var appHeightDiff = app.outerHeight() - app.height();
        var appHeight = bodyHeight - appHeightDiff;


        var appWidth = app.width();
        var toolWidth = tool.outerWidth( true );
        var editHeight = appHeight - head.outerHeight( true );
        var editWidth = appWidth - toolWidth - 4;

        var toolHeight = editHeight;
        var funcHeight = func.outerHeight( true );
        var listHieght = toolHeight - funcHeight;

        editHeight -= edit.innerHeight() - edit.height();
        editWidth = editWidth - ( edit.innerWidth() - edit.width() );

        app.height( appHeight );

        edit.height( editHeight );
        edit.width( editWidth );
        // cont.width( contWidth );

        tool.height( toolHeight );
        list.height( listHieght );

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
    init : function() {
        var self = this;
        self.container = $( "#appmsg" );

    },
    show : function ( text ) {
        if ( !text ) {
            return;
        }
        var self = this;

        self.container.empty();

        var node = $( "<span></span>" );

        node
        .text( text )
        .addClass( "msg ui-state-highlight ui-corner-all" )
        .prependTo( self.container );

        window.setTimeout( function(){
            node.remove();
        }, 5000 );

    }
} );

$.extend( ui.menu, {
    init : function () {
        this.eltMenu = $( "#menu" );
        this.eltPath = this.eltMenu.find( "#path" );
    },
    path : function ( p ) {
        if ( p ) {
            this.eltPath.val( p );
        }
        else {
            return "/vweb/" + this.eltPath.val() + ".html";
        }
    }

} );

$.extend( ui.acc, {
    init : function() {
        this.tool = $( "#tool" );
        // this.alogin = $( "#acc #login" );
        // this.alogout = $( "#acc #logout" );
    },
    login : function () {
        log( "login called" );
        var self = this;
        WB.connect.login(function() {
            log( 'login ok' );
            self.tool.removeClass( "invisible" );
            // self.alogin.hide();
            // self.alogout.show();
        });
    },
    logout : function () {
        var self = this;
        WB.connect.logout(function() {
            log( 'logout ok' );

            // self.alogin.show();
            // self.alogout.hide();

            self.tool.addClass( "invisible" );
        });
    },
    pub : function () {

        var data = ui.edit.layoutdata();

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
        self.vddialog = self.vdform.dialog({ autoOpen: false });


        // self.vdform.find( "input[name=submit]" ).click( function( ev ) {
        self.vdform.find( "input[name=submit]" ).submit( function( ev ) {
            self.do_login();
        } );

    },
    do_login : function() {
        var self = this;

        self.vdform.jsonRequest( json_succ( {
            "ok" : function () {

                self.vddialog.dialog( "close" );

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
                    self.vddialog.dialog( "open" );
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
                    self.vddialog.dialog( "open" );
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

        $( "#tmpl_tree_item" ).tmpl( data )
        .appendTo( $( "#tree ul" ).empty() );
    }

} );

$.extend( ui.edit, {
    init : function () {
        var self = this;
        this.edit = $( "#edit" );
        this.cont = this.edit.children( "#cont" );
        this.page = this.cont.children( "#page" );

        this.page.empty();

        this.setup_func();


        this.edit.find( "#edit_mode input" ).button().click( function() {
            $( this ).parent().find( "input" ).each( function() {
                self.cont.removeClass( $( this ).val() );
            } );
            self.cont.addClass( $( this ).val() );
        } );

        this.edit.find( "#screen_mode input" ).button();


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

$.extend( ui.list, {
    init : function () {
        this.eltList = $( "#list" );
        this.eltList.empty();
        ui.setup_img_switch( this.eltList );
    },
    filter_existed : function ( data ) {
        var ids = ui.edit.ids();

        log( ids );

        if ( ids.length > 0 ) {
            data = $.grep( data, function( v, i ) {
                return ids.indexOf( v.id + "" ) < 0;
            } );
        }

        return data
    },
    show : function ( data, name ) {
        var self = this;

        var d = []
        $.each( data, function( i, v ) {
            d.push( v );
            if ( v.retweeted_status ) {
                v.retweeted_status.retweet = "retweet";
                d.push( v.retweeted_status );
            }
        } );


        data = this.filter_existed( d );

        $.each( data, function( i, v ) {
            if ( v.user.profile_image_url ) {
                v.user.avatar_50 = v.user.profile_image_url;
                v.user.avatar_30 = v.user.profile_image_url.replace( /\/50\//, '/30/' );
            }
        } );

        log( data );

        this.eltList.empty();
        $( "#tmpl_msg" ).tmpl( data ).appendTo( this.eltList );


        this.setup_draggable();
    },
    setup_draggable : function () {

        this.eltList.children().draggable( {
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

$.extend( ui.my, {
    init : function () {
        var self = this;
        self.myButton = $( "#expand.t-btn" );
        self.myDialog  = $( "#my" );

        self.friend.init( self.myDialog );

        self.set_dialog_pos();


        self.myButton.click( function (ev){
            log( 'click' );
            ev.preventDefault()            /* other event on this DOM */
            ev.stopPropagation();          /* pop up                  */
            // ev.stopImmediatePropagation(); [> pop up and other        <]
            self.switchPanel( true );

        } );


    },
    set_dialog_pos : function () {
        var tool = $( "#tool" );

        // // when using layout, tool is absolute positioned
        // var ppos = tool.offset();
        // log( ppos );
        this.myDialog.css( { left:0, top:0 } );

    },
    switchPanel : function ( vis ) {
        if ( vis ) {
            this.myDialog.show();
            this.set_dialog_pos();
        }
        else {
            this.myDialog.hide();
        }
    },
} );

$.extend( ui.my, {
    friend : {}
} );

$.extend( ui.my.friend, {
    init : function( dialog ){
        var self = this;
        self.dialog = dialog;
        self.elt = self.dialog.find( "#friend" );
        self.formSimp = self.elt.find( "form.g_simp" );
        self.formSearch = self.elt.find( "form.g_search" );

        log( self.elt );

        function friend_simp_load( ev ) {
            ev.stopPropagation();

            var args = self.formSimp.serialize();
            log( "args:" );
            log( args );


            ui.appmsg.show( "loadding..." );

            wb.cmd( 'friends_timeline', args, function( rst ) {
                ui.appmsg.show( "updated" );
                ui.list.show( rst );
                $( ".t-autoclose" ).hide();
            } );
        }


        // TODO realod search if group "search" is active
        self.elt.find( ".f_idx" ).click( friend_simp_load );

        self.formSimp.find( "input" ).button().click( friend_simp_load );

    }
} );




var filter = {

};

( function( $ ) {
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
