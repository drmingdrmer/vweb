
$.extend( $.vweb.backend, { weibo: {
    // TODO request not through t_cmd should also be handled like this
    t_cmd: function( verb, cmd, args, data, cbs ) {

        args = $.isPlainObject( args ) ? $.param( args ) : args;
        cbs = cbs || {};

        $.log( cmd, args );

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

    }
} } );
