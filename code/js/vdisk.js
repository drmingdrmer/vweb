
$.extend( $.vweb.ui.vdacc, {
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

        self.vdform.jsonRequest( $.vweb.create_handler( {
            ok : function () {

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
                $.vweb.ui.appmsg.msg( rst.msg );
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
            success : $.vweb.create_handler( {
                "ok" : function( json ){ $.vweb.ui.tree.update( json.data ); },
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
        var html = $.trim( $.vweb.ui.fav.edit.html() );

        // TODO unicode, utf-8, url-encoding test
        var path = $.vweb.ui.menu.path();

        var url = "/vd.php?path=" + path;

        $.ajax( {
            type : "PUT", url : url,
            data : html,
            dataType : 'json',
            success : $.vweb.create_handler( {
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
            success : $.vweb.create_handler( {
                "ok" : function( json, st, xhr ){
                    $.vweb.ui.fav.edit.html( json.html );
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
$.extend( $.vweb.ui.tree, {
    init : function() {
        $( "#tree ul" )
        .delegate( "li", "hover", function( ev ){ $( this ).toggleClass( 'hover' ); } )
        .delegate( "li.file", "click", function(){
            var e = $( this );
            $.vweb.ui.vdacc.load( {
                "fid" : e.attr( "id" ),
                "name"  : $.trim( e.text() )
            } );
        } )
        .delegate( "li.folder", "click", function(){
            var e = $( this );
            $.vweb.ui.vdacc.browse( { "dirid" : e.attr( "id" ) } );
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
