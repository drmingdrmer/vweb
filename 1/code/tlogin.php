<?php

session_start();

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );

if( isset($_SESSION['last_key']) ) {
    header("Location: m.html");
    exit();
}

$verifier = $_REQUEST['oauth_verifier'];
if ( $verifier ) {
    $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
        $_SESSION['keys']['oauth_token'],
        $_SESSION['keys']['oauth_token_secret'] );

    $last_key = $o->getAccessToken( $verifier ) ;

    $_SESSION['last_key'] = $last_key;

    // var_dump( $verifier );
    // var_dump( $_SESSION );

    header("Location: m.html");
}
else {
    $o = new SaeTOAuth( WB_AKEY , WB_SKEY  );

    $proto = is_https() ? 'https://' : 'http://';
    $keys = $o->getRequestToken();
    $aurl = $o->getAuthorizeURL( $keys['oauth_token'], false,
        $proto . $_SERVER['HTTP_HOST'] . '/tlogin.php');

    $_SESSION['keys'] = $keys;

    ?> <a href="<?=$aurl?>">Use Oauth to login</a> <?
}

?>
