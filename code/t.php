<?

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'img.php' );

header('Content-Type:text/html; charset=utf-8');


$cmds = array(
    "statuses/public_timeline"        =>  "/statuses/public_timeline.json"       , // 获取最新更新的公共微博消息
    "statuses/friends_timeline"       =>  "/statuses/friends_timeline.json"      , // 获取当前用户所关注用户的最新微博信息
    "statuses/user_timeline"          =>  "/statuses/user_timeline.json"         , // 获取用户发布的微博信息列表
    "statuses/mentions"               =>  "/statuses/mentions.json"              , // 获取@当前用户的微博列表
    "statuses/comments_timeline"      =>  "/statuses/comments_timeline.json"     , // 获取当前用户发送及收到的评论列表
    "statuses/comments_by_me"         =>  "/statuses/comments_by_me.json"        , // 获取当前用户发出的评论
    "statuses/comments_to_me"         =>  "/statuses/comments_to_me.json"        , // 获取当前用户收到的评论
    "statuses/comments"               =>  "/statuses/comments.json"              , // 获取指定微博的评论列表
    "statuses/counts"                 =>  "/statuses/counts.json"                , // 批量获取一组微博的评论数及转发数
    "statuses/reset_count"            =>  "statuses/reset_count"                 , 
    "statuses/unread"                 =>  "/statuses/unread.json"                , // 获取当前用户未读消息数
    "statuses/show/#{id}"             =>  "/statuses/show/#{id}.json"            , // 根据ID获取单条微博信息内容
    "#{userid}/statuses/#{id}"             =>  "/#{userid}/statuses/#{id}"            , // 根据微博ID和用户ID跳转到单条微博页面(验证不成功)
    "statuses/update"                 =>  "/statuses/update.json"                , // 发布一条微博信息
    "statuses/upload"                 =>  "/statuses/upload.json"                , // 上传图片并发布一条微博信息(验证不成功)
    "statuses/destroy/#{uid}"         =>  "/statuses/destroy/#{uid}.json"        , // 删除一条微博信息
    "statuses/retweet/#{id}"          =>  "/statuses/retweet/#{id}.json"         , // 转发一条微博信息（可加评论）
    "statuses/comment"                =>  "/statuses/comment.json"               , // 对一条微博信息进行评论
    "statuses/comment_destroy/#{id}"  =>  "/statuses/comment_destroy/#{id}.json" , // 删除当前用户的微博评论信息
    "statuses/reply"                  =>  "/statuses/reply.json"                 , // 回复微博评论信息
    "users/show"                      =>  "/users/show.json"                     , // 根据用户ID获取用户资料（授权用户）
    "statuses/friends"                =>  "/statuses/friends.json"               , // 获取当前用户关注对象列表及最新一条微博信息
    "statuses/followers"              =>  "/statuses/followers.json"             , // 获取当前用户粉丝列表及最新一条微博信息
    "direct_messages"                 =>  "/direct_messages.json"                , // 获取当前用户最新私信列表
    "direct_messages/sent"            =>  "/direct_messages/sent.json"           , // 获取当前用户发送的最新私信列表
    "direct_messages/new"             =>  "/direct_messages/new.json"            , // 发送一条私信
    "direct_messages/destroy/#{id}"   =>  "/direct_messages/destroy/#{id}.json"  , // 删除一条私信
    "friendships/create"              =>  "/friendships/create.json"             , // 关注某用户
    "friendships/destroy"             =>  "/friendships/destroy.json"            , // 取消关注
    "friendships/exists"              =>  "/friendships/exists.json"             , // 是否关注某用户(推荐使用friendships/show)
    "friendships/show"                =>  "/friendships/show.json"               , // 获取两个用户关系的详细情况
    "friends/ids"                     =>  "/friends/ids.json"                    , // 获取用户关注对象uid列表
    "followers/ids"                   =>  "/followers/ids.json"                  , // 获取用户粉丝对象uid列表
    "account/verify_credentials"      =>  "/account/verify_credentials.json"     , // 验证当前用户身份是否合法
    "account/rate_limit_status"       =>  "/account/rate_limit_status.json"      , // 获取当前用户API访问频率限制
    "account/end_session"             =>  "/account/end_session.json"            , // 当前用户退出登录
    "account/update_profile_image"    =>  "/account/update_profile_image.json"   , // 更改头像
    "account/update_profile"          =>  "/account/update_profile.json"         , // 更改资料
    "account/register"                =>  "/account/register.json"               , // 注册新浪微博帐号
    "Account/activate"                =>  "/Account/activate.json"               , // 二次注册微博的接口
    "emotions"                        =>  "/emotions.json"                       , // 表情接口，获取表情列表
    "favorites"                       =>  "/favorites.json"                      , // 获取当前用户的收藏列表
    "favorites/create"                =>  "/favorites/create.json"               , // 添加收藏
    "favorites/destroy"               =>  "/favorites/destroy.json"              , // 删除当前用户收藏的微博信息
    "users/search"                    =>  "/users/search.json"                   , // 搜索微博用户(仅对新浪合作开发者开放)
    "search"                          =>  "/search.json"                         , // 搜索微博文章(仅对新浪合作开发者开放)
    "statuses/search"                 =>  "/statuses/search.json"                , // 搜索微博(多条件组合)(仅对合作开发者开放)
    "statuses/magic_followers"        =>  "/statuses/magic_followers.json"         // 获取用户优质粉丝列表
);

function output_exit( $arr ) {
    $cb = $_REQUEST[ 'cb' ];

    switch ( $_REQUEST[ 'resptype' ] ) {
        case 'json' :
            return res_json( $arr );
        case 'jscb':
        default:
            return res_cb( $arr, $cb );
    }
}

function json_to_html( &$d ) {

    $html = '';

    foreach ($d as $e) {
        if ( $e[ 'text' ] ) {
            return $e[ 'text' ];
        }
        else if ( $e[ 'node' ] ) {
            $n = $e[ 'node' ];

            $html .= "<{$n['tag']} id=\"{$n['id']}\" class=\"{$n['class']}\"";

            if ( $n[ 'tag' ] == 'A' && $n[ 'href' ] ) {
                $html .= " href=\"{$n['href']}\"";
            }
            if ( $n[ 'tag' ] == 'IMG' && $n[ 'src' ] ) {
                $html .= " src=\"{$n['src']}\"";
            }

            $html .= ">";

            if ( $e[ 'children' ] ) {
                $html .= json_to_html( $e[ 'children' ] );
            }

            $html .="</{$n['tag']}>";

        }
    }
    return $html;
}


/*
 * (
 *     [id] => 1937013762
 *     [screen_name] => xpxpxxp2
 *     [name] => xpxpxxp2
 *     [province] => 11
 *     [city] => 8
 *     [location] => 北京 海淀区
 *     [description] => 
 *     [url] => 
 *     [profile_image_url] => http://tp3.sinaimg.cn/1937013762/50/0/1
 *     [domain] => 
 *     [gender] => m
 *     [followers_count] => 7
 *     [friends_count] => 54
 *     [statuses_count] => 12
 *     [favourites_count] => 0
 *     [created_at] => Tue May 17 00:00:00 +0800 2011
 *     [following] => 
 *     [allow_all_act_msg] => 
 *     [geo_enabled] => 1
 *     [verified] => 
 *     [status] => Array
 *         (
 *             [created_at] => Sun Jul 10 13:38:09 +0800 2011
 *             [id] => 14076670939
 *             [text] => Sun Jul 10 2011 13:38:06 GMT+0800 (CST)undefined
 *             [source] => <a href="" rel="nofollow">未通过审核应用</a>
 *             [favorited] => 
 *             [truncated] => 
 *             [in_reply_to_status_id] => 
 *             [in_reply_to_user_id] => 
 *             [in_reply_to_screen_name] => 
 *             [thumbnail_pic] => http://ww3.sinaimg.cn/thumbnail/73747c02jw1dj0k3gtf95j.jpg
 *             [bmiddle_pic] => http://ww3.sinaimg.cn/bmiddle/73747c02jw1dj0k3gtf95j.jpg
 *             [original_pic] => http://ww3.sinaimg.cn/large/73747c02jw1dj0k3gtf95j.jpg
 *             [geo] => 
 *             [mid] => 2111107103071633
 *         )
 * 
 * )
 */

isset( $_SESSION['last_key'] ) || resmsg( 'auth', 'auth' );


$c = new MySaeTClient( WB_AKEY, WB_SKEY,
    $_SESSION['last_key']['oauth_token'],
    $_SESSION['last_key']['oauth_token_secret']  );


$verb = $_SERVER[ 'REQUEST_METHOD' ];
$act = $_REQUEST[ 'act' ];


!$act && resmsg( 'noact', 'noact' );


if ( $verb == "GET" ) {

    $p = $_GET;

    if ( $cmds[ $act ] ) {
        unset( $p[ 'act' ] );
        unset( $p[ 'resptype' ] );

        res_json(
            $c->_load_cmd( $act, $p, NULL, NULL ) );
    }
    else {
        resmsg( "unknown_act", $act );
    }

}
else if ( $verb == "POST" ) {
    switch ( $act ) {
        case "upload" :
            if ( $_FILES[ "pic" ][ 'tmp_name' ] ) {
                $rst = $c->upload( $_POST[ 'status' ], $_FILES[ "pic"][ "tmp_name" ] );
            }
            else {
                $rst = $c->update( $_POST[ 'status' ] );
            }
            output_exit( gen_app_rst( $rst, "发表成功" ) );

        case "repost" :
            $id = $_REQUEST[ 'id' ];
            $rst = $c->repost( $id, $_POST[ 'status' ] );
            output_exit( gen_app_rst( $rst, "转发成功", array( "id" => $id ) ) );

        case "comment" :
            $id = $_REQUEST[ 'id' ];
            $rst = $c->send_comment( $id, $_POST[ 'comment' ] );
            output_exit( gen_app_rst( $rst, "评论成功", array( "id" => $id ) ) );

        case "destroy" :
            $id = $_REQUEST[ 'id' ];
            $rst = $c->destroy( $id );
            output_exit( gen_app_rst( $rst, "删除成功", array( "id" => $id ) ) );

        case "fav":
            $id = $_REQUEST[ 'id' ];
            $rst = $c->add_to_favorites( $id );
            output_exit( gen_app_rst( $rst, "收藏成功", array( "id" => $id ) ) );

        case "pub":
            $msg = $_GET[ 'msg' ];
            $albumname = $_GET[ 'albumname' ];

            $data = file_get_contents("php://input");
            !$data && resmsg( "nodata", "nodata" );

            $data = unjson( $data );
            !$data && resmsg( "invalid", "invalid" );


            $layout = $data[ 'layout' ];
            $page = $data[ 'page' ];

/*
 *             $albumhtml = json_to_html( $page );
 * 
 *             $useravatar = $_SESSION[ 'user' ][ 'profile_image_url' ];
 *             $username = $_SESSION[ 'user' ][ 'name' ];
 *             $uid = $_SESSION[ 'user' ][ 'id' ];
 * 
 *             $html = <<<HTML
 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
 * <html>
 *     <head>
 *         <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
 *         <link rel="stylesheet" type="text/css" href="http://vweb-pub.stor.sinaapp.com/albumtmpl.css" />
 *         <script language="javascript" type="text/javascript" src="http://lib.sinaapp.com/js/jquery/1.4.4/jquery.min.js"></script>
 *         <script language="javascript" type="text/javascript" src="http://vweb-pub.stor.sinaapp.com/plugin/scrollto/jquery.scrollTo.js"></script>
 *         <title>$albumname</title>
 *     </head>
 *     <body>
 *         <div id="title">
 *             <div class="left"><a href="#" class="prod">Vlbum</a></div>
 *             <span class="user"><img src="$useravatar" alt="alt"/><a href="#" class="username">$username</a></span>
 *             <span class="sep">&nbsp;</span>
 *             <span class="albname">$albumname</span>
 *             <span class="sep">&nbsp;</span>
 *             <a href="#" class="f_repost">分享到微博</a>
 *         </div>
 *         <div id="sae">
 *             <a href="http://sae.sina.com.cn" target="_blank"><img
 *                 src="http://static.sae.sina.com.cn/image/poweredby/poweredby.png" title="Powered by Sina App Engine" /></a>
 *         </div>
 *         <div id="page" class="ui-sortable">$albumhtml</div>
 *     </body>
 *     <script language="javascript" type="text/javascript" src="http://vweb-pub.stor.sinaapp.com/album.js"></script>
 * </html>
 * HTML;
 */

/*
 *             $albumpath = "$uid/$albumname.html";
 * 
 *             $s = new SaeStorage();
 *             $url = $s->write( 'pub' , $albumpath , $html );
 *             !$url && resmsg( 'createalbum', 'createalbum' );
 */

            $fn = mkimg_local( $layout, 'jpg' );
            !$fn && resmsg( 'mkimg', 'mkimg' );

            // $msg = "《$albumname》-- $msg $url";
            // $msg = "《$albumname》-- $msg";
            $msg = "$msg";

            $rst = $c->upload( $msg, $fn );
            output_exit( gen_app_rst( $rst, "发布成功" ) );

            break;
    }
}

?>
