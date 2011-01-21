<?php

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
header('Content-Type:text/html; charset=utf-8');


$o = new SaeTOAuth( WB_AKEY , WB_SKEY , $_SESSION['keys']['oauth_token'] , $_SESSION['keys']['oauth_token_secret']  );

$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;

$_SESSION['last_key'] = $last_key;


?>
授权完成,<a href="weibolist.php">进入你的微博列表页面</a>
