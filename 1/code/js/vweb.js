// Works with jquery-1.4.4, jquery-ui-1.8.9

var cfg = {
    key: '2060512444',
    xdpath: 'http://vweb.sinaapp.com/xd.html'
};

function log (mes) {
    console.log( mes );
}

var ui = { msg : {}, menu : {}, edit : {}, list : {}, my : {}, acc : {}, vdacc : {}, tool : {} };

var wb = {
    cmd : function ( cmd, args, cb ) {

        log( cmd, args );

        $.ajax( {
            type : "GET",
            url : "t.php?act=" + cmd + "&" + $.param( args ),
            dataType : "json",
            success : function( rst, st, xhr ) {
                log( "cmd ok:"  );
                log( rst );
                if ( rst.rst == "ok" ) {
                    cb && cb( rst.data );
                }
                else {
                    ui.msg.show( rst.rst + " " + rst.msg );
                }
            },
            error : function( xhr, st, err ) {
                log( "cmd error" );
                ui.msg.show( st );
                // ui.msg.show( err );
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


        $( ".t_opt" ).each( function() {
            var container = $( this );
            var tp = container.attr( "_type" );
            var cid = container.attr( "id" );

            container.children( "a" ).each( function( i, e ){
                e = $( e );
                container.append(
                    $( "<input/>" )
                    .attr( {
                        type : tp,
                        id : cid + i,
                        name : cid,
                        value : e.attr( "href" )
                    } ) )
                .append(
                    $( "<label></label>" )
                    .text( e.text() )
                    .attr( "for", cid + i )
                );
                e.remove();
            } );

        } );

        self.msg.init();
        self.menu.init();
        self.acc.init();
        self.vdacc.init();
        self.edit.init();
        self.list.init();
        self.my.init();

        $( window ).resize( function() { self.relayout(); } );

        $( "body" ).click( function( ev ) { self.close_all(); } );


    },
    close_all : function () {
        $( ".t-autoclose" ).hide();
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
} );

$.extend( ui.msg, {
    init : function() {
        var self = this;
        self.container = $( "#appmsg" );

    },
    show : function ( text ) {
        var self = this;

        var node = $( "<span></span>" );

        node
        .text( text )
        .addClass( "ui-state-highlight ui-corner-all" )
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
            return "/vweb/" + this.eltPath.val();
        }
    }

} );

$.extend( ui.acc, {
    init : function() {
        this.tool = $( "#tool" );
        this.alogin = $( "#acc #login" );
        this.alogout = $( "#acc #logout" );
    },
    login : function () {
        log( "login called" );
        var self = this;
        WB.connect.login(function() {
            log( 'login ok' );
            self.tool.removeClass( "invisible" );
            self.alogin.hide();
            self.alogout.show();
        });
    },
    logout : function () {
        var self = this;
        WB.connect.logout(function() {
            log( 'logout ok' );

            self.alogin.show();
            self.alogout.hide();

            self.tool.addClass( "invisible" );
        });
    },
} );

$.extend( ui.vdacc, {
    init : function() {
        var self = this;
        self.vdlogin = $( "#vdlogin" );
        self.loginbtn = $( "#vdacc #login" );
        self.logoutbtn = $( "#vdacc #logout" );

        self.vdlogin.hide();


        self.vdlogin.find( "input[name=submit]" ).click( function( ev ) {

             self.vdlogin.jsonRequest( function( json ) {
                 log( "vdisk login rst=" );
                 log( json );
                 if ( json.rst == "ok" ) {
                     ui.msg.show( "OK login" );
                     self.st_logged_in();
                 }
                 else {
                     ui.msg.show( "Failed login:" + json.msg );
                 }

            } );

            ev.preventDefault();
            ev.stopPropagation();
        } );

    },
    st_logged_in : function(){
        var self = this;
        log( "st_logged_in called" );
        self.vdlogin.hide();
        self.loginbtn.hide();
        self.logoutbtn.show();
        log( "st_logged_in finished" );
    },
    keeptoken : function( cb ) {
        var self = this;
        var url = "/vd.php?act=keeptoken";

        $.ajax( {
            url : url,
            dataType : "json",
            success : function( rst, st, xhr ) {
                log( 'vd keeptoken success' );
                if ( rst.rst == "ok" ) {
                    self.st_logged_in()
                    cb && cb();
                }
            }
        } );
    },
    show_form : function() {
        this.vdlogin.show();
    },
    save : function( cb ) {
        var html = ui.edit.html();

        // TODO unicode, utf-8, url-encoding test
        var path = ui.menu.path();

        var url = "/vd.php?path=" + path;

        log( "to save html=" + html );
        log( "to save path=" + path );

        $.ajax( {
            type : "PUT", url : url,
            data : html,
            dataType : 'json',
            success : function ( json, st, xhr ) {
                if ( json.rst == "ok" ) {
                    ui.msg.show( "Saved" );
                    cb && cb();
                }
                else {
                    ui.msg.show( "Failed saving: " + json.msg );
                }
            }

        } );
    },
    logout : function() {

    }
} );

$.extend( ui.edit, {
    init : function () {
        this.edit = $( "#edit" );
        this.cont = this.edit.children( "#cont" );

        this.cont.empty();
        this.setup_func();
    },
    setup_func : function () {

        this.cont.sortable({
            handle : ".handle",
            receive : function ( ev, ui ) {
                var msg = ui.item;
                msg.hide();
                // TODO add to global filter list

                log( "receive" );
                log( $( ev.target ).parent().attr( 'id' ) );
                log( ev );
                log( ui );
            }
        });

    },
    ids : function () {
        var ids = [];
        this.cont.find( ".t-msg" ).each( function() {
            ids.push( $( this ).attr( "id" ) );
        } );
        log( "ids=", ids );
        return ids;
    },
    pagedata : function () {

    }, 
    html : function( h ) {
        if ( h ) {
            this.cont.html( h );
        }
        else {
            // get
            // TODO filter and cleanup
            return this.cont.html();
        }
    }
} );

$.extend( ui.list, {
    init : function () {
        this.eltList = $( "#list" );
        this.eltList.empty();
    },
    msgNode : function ( d ) {
        var node = $( "#tmpl_msg" ).tmpl( d );
        return node;
    },
    show : function ( data, name ) {
        var self = this;
        var ids = ui.edit.ids();

        log( ids );

        if ( ids.length > 0 ) {
            data = $.grep( data, function( v, i ) {
                return ids.indexOf( v.id + "" ) < 0;
            } );
        }

        log( data );

        this.eltList.empty();

        $.each( data, function( i, v ) {
            var node = self.msgNode( v );
            self.eltList.append( node );
        } );

        this.setup_draggable();
    },
    setup_draggable : function () {

        this.eltList.children().draggable( {
            connectToSortable: "#edit>#cont",
            // handle : ".handle",
            helper : "clone",
            revert : "invalid",
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
        self.myButton = $( "#my.t-btn" );
        self.myDialog  = $( "#my.t-dialog" );
        self.friend = self.myDialog.find( "#friend" );
        log( self.friend );

        self.friend.find( ".f_idx" ).click( function(){
            wb.cmd( 'friends_timeline', {}, function( rst ) {
                ui.list.show( rst );
            } );
        } );

        self.myDialog.find( ".t_opt" ).buttonset().click( function( ev ){
            ev.stopPropagation();
        } );

        self.myButton.click( function (ev){
            log( 'click' );
            ev.preventDefault()            /* other event on this DOM */
            ev.stopPropagation();          /* pop up                  */
            // ev.stopImmediatePropagation(); [> pop up and other        <]
            self.switchPanel( true );

        } );


    },
    close_all : function() {
        this.switchPanel( false );
    },
    set_dialog_pos : function () {
        var tool = $( "#tool" );
        var ppos = tool.offset();
        log( ppos );
        this.myDialog.css( ppos );

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
    mine : function (  ) {
        this.close_all();

        wb.cmd( cmds[ "sts.friends_timeline" ], {}, {

            ok : function ( rst ) {
                log( "ok called:", rst );
                ui.list.show( rst );

            },

            error : function () {

            }
        } );
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
