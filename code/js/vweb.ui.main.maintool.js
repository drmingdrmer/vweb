$.extend( $.vweb.ui.main, { maintool: {
    init : function ( self, e ) {

        var fn = this.fn = $( "#fn", e );
        var pubmsg = this.pubmsg = $( "#pubmsg", e );
        var charleft = this.charleft = $( "#charleft" ).hide();
        var pub = this.pub = $( "#pub", e );
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
                                $.vweb.ui.appmsg.msg( "发布成功" );
                                pubmsg.val( '' ).blur();
                            }
                            else {
                                $.vweb.ui.appmsg.alert( json.msg );
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

} } );
