<?php

session_start();

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
// include_once( 'ss.php' );

$defaultPage = 'm.html';

$redirectPage = $_GET[ 'r' ] ? $_GET[ 'r' ] : $defaultPage;


if( isset($_SESSION['last_key']) ) {
    header("Location: $redirectPage");
    exit();
}


$verifier = $_REQUEST['oauth_verifier'];
if ( $verifier ) {
    $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
        $_SESSION['keys']['oauth_token'],
        $_SESSION['keys']['oauth_token_secret'] );

    $_SESSION['last_key'] = $o->getAccessToken( $verifier ) ;

    header("Location: $redirectPage");
}
else {
    $o = new SaeTOAuth( WB_AKEY , WB_SKEY  );

    $proto = is_https() ? 'https://' : 'http://';
    $keys = $o->getRequestToken();
    $aurl = $o->getAuthorizeURL( $keys['oauth_token'], false,
        $proto . $_SERVER['HTTP_HOST'] . "/index.php?r=$redirectPage");

    $_SESSION['keys'] = $keys;

    ?> <a href="<?=$aurl?>">Use Oauth to login</a> <?
}

?>
