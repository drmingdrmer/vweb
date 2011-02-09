<?php

header("Location: tlogin.php");
exit();


session_start();

if( isset($_SESSION['last_key']) ) {
    header("Location: weibolist.php");
    exit();
}

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );



$o = new SaeTOAuth( WB_AKEY , WB_SKEY  );

$port = '';

if( $_SERVER['SERVER_PORT'] != 80 ) $port = ':'.$_SERVER['SERVER_PORT'];
$proto=is_https()?'https://':'http://';
$keys = $o->getRequestToken();
$aurl = $o->getAuthorizeURL( $keys['oauth_token'],
    false , $proto .$_SERVER['HTTP_HOST'] . '/callback.php');
    /* $_SERVER['HTTP_APPVERSION'] .'.'. $_SERVER['HTTP_APPNAME'] . '.sinaapp.com' . $port*/

$_SESSION['keys'] = $keys;


?>
<a href="<?=$aurl?>">Use Oauth to login</a>
