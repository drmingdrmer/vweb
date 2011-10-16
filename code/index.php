<?php

session_start();

include_once( 'config.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'mysql.php' );
include_once( 'acc.php' );


$defaultPage = 'vlbum.html';

$redirectPage = $_GET[ 'r' ] ? $_GET[ 'r' ] : $defaultPage;

if ( $_GET[ 'acc' ] == 'flush' ) { 
    unset($_SESSION[ 'acctoken' ]);
    unset($_SESSION[ 'reqtoken' ]);
}


$acctoken = sess_get_acctoken();

if( $acctoken ) {
    load_user_to_sess( $acctoken );
    header("Location: $redirectPage");
    exit();
}


$verifier = $_REQUEST['oauth_verifier'];
if ( $verifier ) {
    do_verify( $verifier );
}

$o = new SaeTOAuth( WB_AKEY , WB_SKEY );

$proto = is_https() ? 'https://' : 'http://';
$keys = $o->getRequestToken();
$aurl = $o->getAuthorizeURL( $keys['oauth_token'], false,
    $proto . $_SERVER['HTTP_HOST'] . "/index.php?r=$redirectPage");

$_SESSION['reqtoken'] = $keys;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Vlbum</title>
    </head>
    <body>
        <div id="wrap" style="text-align:center;">
            <p style="font-size:18px; padding:24px;">将微博中喜欢的图片，整理成组图，分享给朋友。</p>
            <!-- <p>轻松收集，更多的分享。</p> -->
            <img style="border:1px solid #ccc; padding:4px;" src="img/intro.jpg" alt=""/>
            <div id="auth" style="padding:40px;">
                <a href="<?=$aurl?>"><img src="http://www.sinaimg.cn/blog/developer/wiki/32.png" alt="用微博账号登陆"/></a>
            </div>
        </div>
    </body>
</html>

