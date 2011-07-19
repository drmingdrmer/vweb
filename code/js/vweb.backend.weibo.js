
$.extend( $.vweb.backend, { weibo: {
    // TODO request not through t_cmd should also be handled like this
    t_cmd: function( verb, cmd, args, data, cbs ) {

        args = $.isPlainObject( args ) ? $.param( args ) : args;
        cbs = cbs || {};

        $.log( 'cmd:' );
        $.log( cmd );
        $.log( 'args:' );
        $.log( args );

        $.ajax( {
            type : verb,
            url : "t.php?act=" + cmd + "&resptype=json&" + args,
            data: data,
            dataType : "json",
            success : function( json, st, xhr ) {

                if ( json.rst == 'weiboerror' ) {

                    $.log( 'weiboerror error' );

                    var msg = json.msg || '0:';
                    var cm = msg.split( ':' );
                    var msgCode = cm[ 0 ];
                    msg = cm[ 1 ];

                    if ( msgCode == '40028' ) {
                        // too many repeated update
                        // content length error
                        // TODO do not use ui.appmsg
                        // $.vweb.ui.appmsg.err( msg );
                    }
                    else {
                        // TODO do not use ui.appmsg
                        // $.vweb.ui.appmsg.err( msg );
                    }

                    return;
                }
                else if ( json.rst == 'auth' ) {
                    // no Oauth key
                    window.location.href = $.vweb.conf.loginPage;
                }
                else{
                }

                $.log( 'it is ok' );
                $.log( json );

                // TODO do not use ui.appmsg
                // $.vweb.ui.appmsg.msg( json.rst + " " + json.msg );
                cbs.success && cbs.success( json );

            },
            error : function( jqxhr, errstr, exception ) {
                // TODO do not use ui.appmsg
                // $.vweb.ui.appmsg.msg( errstr );
                cbs.error && cbs.error( jqxhr, errstr, exception );
            }
        } );

    },

    cmd_serialize: function ( cmdname, opt, idfirst ) {
        var args = [];
        var o = {};

        $.isPlainObject( opt ) && ( $.extend( o, opt ) ) || ( o = $.unparam( opt ) );
        o.max_id || ( o.max_id = idfirst );
        $.each( o, function( k, v ){ args.push( k + '__' + v ); } );
        args.sort();

        var s = cmdname.replace( /\//g, '__' ) + '____' + args.join( '____' ) ;
        $.log( 'cmd str=' + s );
        return s;
    },

    cmd_unserialize: function ( s ) {
        var args = s.split( /____/ );
        var cmdname = args.shift().replace( /__/g, '/' );
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
            $.evstop( ev );
            realload.apply( $(this), [ cmdname, opt ] );
        }
    },

    load : function( cmdname, opt ) {
        // 'this' is set by create_loader and which is the DOM fired the event

        $.log( opt );
        var trigger = this;
        var args = {};
        if ( opt.args ) {
            args = opt.args.apply ? opt.args.apply( this, [] ) : opt.args;
            args = $.isPlainObject( args ) ? args : $.unparam( args );
        }

        $.log( "args:" );
        $.log( args );

        $.vweb.backend.weibo.t_cmd( 'GET', cmdname, args, undefined, {
            success: function( json ) {
                if ( json.rst == 'ok' ) {
                    var t = trigger;
                    var cmd = { name: cmdname, args: args, cb:opt.cb };

                    $.vweb.ui.appmsg.msg( "载入成功" );

                    // TODO do not addhis after paging down/up
                    $.vweb.backend.weibo.addhis( json, cmd );

                    opt.cb && $.each( opt.cb, function( i, v ){
                        eval( v + "(json.data,t,cmd)" );
                    } );
                }
                else { /* need something to be done? */  }
            }
        }
        );
    },

    addhis: function ( json, cmd ) {
        if ( json.rst != 'ok' || ! json.data[ 0 ] ) {
            return;
        }

        $.vweb.ui.t.paging.addhis( json, cmd );
        $.vweb.ui.main.edit.addhis( json, cmd );
    },

    gen_cmd_hisrecord: function( json, cmd ){
        var d = json.data[ 0 ];

        d.hisid = $.vweb.backend.weibo.cmd_serialize( cmd.name, cmd.args, d.id );
        $.log( d );

        var hisdata = $.vweb.tweets( [ d ] ).stdAvatar().defaultUser('sender')
        .historyText().historyTime().get()[ 0 ];

        $.log( hisdata );

        return hisdata;
    }

} } );
