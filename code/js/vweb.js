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
                        return $.arrayIndexOf( ids, v.id + "" ) < 0
                            && ( !v.retweeted_status || $.arrayIndexOf( ids, v.retweeted_status.id + "" ) < 0  );
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
                $.log( 'to  init_sub: ' + k );
                u.init( u, u._elt );
                $.log( 'end init_sub: ' + k );
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

