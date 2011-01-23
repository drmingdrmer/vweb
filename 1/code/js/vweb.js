
WB.core.load(['connect', 'client'], function() {
    var cfg = {
        key: '2060512444',
        xdpath: 'http://vweb.sinaapp.com/xd.html'
    };
    WB.connect.init(cfg);
    WB.client.init(cfg);
});

var cmds = {
    "sts.public_timeline"      : "/statuses/public_timeline.json"       , // 获取最新更新的公共微博消息
    "sts.friends_timeline"     : "/statuses/friends_timeline.json"      , // 获取当前用户所关注用户的最新微博信息
    "sts.user_timeline"        : "/statuses/user_timeline.json"         , // 获取用户发布的微博信息列表
    "sts.mentions"             : "/statuses/mentions.json"              , // 获取@当前用户的微博列表
    "sts.comments_timeline"    : "/statuses/comments_timeline.json"     , // 获取当前用户发送及收到的评论列表
    "sts.comments_by_me"       : "/statuses/comments_by_me.json"        , // 获取当前用户发出的评论
    "sts.comments"             : "/statuses/comments.json"              , // 获取指定微博的评论列表
    "sts.counts"               : "/statuses/counts.json"                , // 批量获取一组微博的评论数及转发数
    "sts.unread"               : "/statuses/unread.json"                , // 获取当前用户未读消息数
    "sts.show"                 : "/statuses/show/#{id}.json"            , // 根据ID获取单条微博信息内容
    "usr.sts.id"               : "/#{userid}/statuses/#{id}"            , // 根据微博ID和用户ID跳转到单条微博页面(验证不成功)
    "sts.update"               : "/statuses/update.json"                , // 发布一条微博信息
    "sts.upload"               : "/statuses/upload.json"                , // 上传图片并发布一条微博信息(验证不成功)
    "sts.destroy"              : "/statuses/destroy/#{uid}.json"        , // 删除一条微博信息
    "sts.repost"               : "/statuses/retweet/#{id}.json"         , // 转发一条微博信息（可加评论）
    "sts.comment"              : "/statuses/comment.json"               , // 对一条微博信息进行评论
    "sts.comment_destroy"      : "/statuses/comment_destroy/#{id}.json" , // 删除当前用户的微博评论信息
    "sts.reply"                : "/statuses/reply.json"                 , // 回复微博评论信息
    "usr.show"                 : "/users/show.json"                     , // 根据用户ID获取用户资料（授权用户）
    "sts.friends"              : "/statuses/friends.json"               , // 获取当前用户关注对象列表及最新一条微博信息
    "sts.followers"            : "/statuses/followers.json"             , // 获取当前用户粉丝列表及最新一条微博信息
    "msg"                      : "/direct_messages.json"                , // 获取当前用户最新私信列表
    "msg.sent"                 : "/direct_messages/sent.json"           , // 获取当前用户发送的最新私信列表
    "msg.new"                  : "/direct_messages/new.json"            , // 发送一条私信
    "msg.destroy"              : "/direct_messages/destroy/#{id}.json"  , // 删除一条私信
    "frs.create"               : "/friendships/create.json"             , // 关注某用户
    "frs.destroy"              : "/friendships/destroy.json"            , // 取消关注
    "frs.exists"               : "/friendships/exists.json"             , // 是否关注某用户(推荐使用friendships/show)
    "frs.show"                 : "/friendships/show.json"               , // 获取两个用户关系的详细情况
    "frd.ids"                  : "/friends/ids.json"                    , // 获取用户关注对象uid列表
    "flr.ids"                  : "/followers/ids.json"                  , // 获取用户粉丝对象uid列表
    "acc.verify_credentials"   : "/account/verify_credentials.json"     , // 验证当前用户身份是否合法
    "acc.rate_limit_status"    : "/account/rate_limit_status.json"      , // 获取当前用户API访问频率限制
    "acc.end_session"          : "/account/end_session.json"            , // 当前用户退出登录
    "acc.update_profile_image" : "/account/update_profile_image.json"   , // 更改头像
    "acc.update_profile"       : "/account/update_profile.json"         , // 更改资料
    "acc.register"             : "/account/register.json"               , // 注册新浪微博帐号
    "Account/activate"         : "/Account/activate.json"               , // 二次注册微博的接口
    "emotions"                 : "/emotions.json"                       , // 表情接口，获取表情列表
    "fav"                      : "/favorites.json"                      , // 获取当前用户的收藏列表
    "fav.create"               : "/favorites/create.json"               , // 添加收藏
    "fav.destroy"              : "/favorites/destroy.json"              , // 删除当前用户收藏的微博信息
    "usr.search"               : "/users/search.json"                   , // 搜索微博用户(仅对新浪合作开发者开放)
    "search"                   : "/search.json"                         , // 搜索微博文章(仅对新浪合作开发者开放)
    "sts.search"               : "/statuses/search.json"                , // 搜索微博(多条件组合)(仅对合作开发者开放)
    "sts.magic_followers"      : "/statuses/magic_followers.json"       , // 获取用户优质粉丝列表
    NULL : undefined
}

var wb = {
    login : function () {
        WB.connect.login(function() {
            $( "#tool" ).removeClass( "invisible" );
        });
    },
    logout : function () {
        WB.connect.logout(function() {
            $( "#tool" ).removeClass( "invisible" );
        });
    },
    run_api_cmd : function ( cmd, args ) {

        var type = "GET";

        WB.client.parseCMD(cmd, function(sResult, bStatus) {
            log((bStatus == true) + '\n' + JSON.stringify(sResult, null, '\t'));
        }, args, {
            'method': type
        });
    }
};

var ui = {
    relayout : function () {
        var app = $( "#app" );
        var head = $( "#hd" );
        var edit = $( "#edit" );
        var tool = $( "#tool" );


        var bodyHeight = $( window ).height();
        var appHeightDiff = app.outerHeight() - app.height();
        var appHeight = bodyHeight - appHeightDiff;

        var appWidth = app.width();
        var toolWidth = tool.outerWidth( true );
        var editHeight = appHeight - head.outerHeight( true );
        var editWidth = appWidth - toolWidth - 4;


        app.height( appHeight );
        edit.height( editHeight );
        edit.width( editWidth );
        tool.height( editHeight );
    }
}

var my = {
    switch : function ( vis ) {
        var panel = $( "#my.t-dialog" );
        if ( vis ) {
            panel.show();
        }
        else {
            panel.hide();
        }
    },

    load : function (  ) {

    }
}
