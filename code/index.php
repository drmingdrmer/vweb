<?php

session_start();

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );



function load_user_to_sess() {
    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $_SESSION['last_key']['oauth_token'],
        $_SESSION['last_key']['oauth_token_secret']  );

    $rst = $c->_load_cmd( 'account/verify_credentials', array() );

    if ( $rst[ 'rst' ] == 'ok' ) {
        $_SESSION[ 'user' ] = $rst[ 'data' ];
    }
    return $rst;
}


$defaultPage = 'm.html';

$redirectPage = $_GET[ 'r' ] ? $_GET[ 'r' ] : $defaultPage;


if( isset($_SESSION['last_key']) ) {
    load_user_to_sess();
    header("Location: $redirectPage");
    exit();
}


$verifier = $_REQUEST['oauth_verifier'];
if ( $verifier ) {
    $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
        $_SESSION['keys']['oauth_token'],
        $_SESSION['keys']['oauth_token_secret'] );

    $_SESSION['last_key'] = $o->getAccessToken( $verifier ) ;

    load_user_to_sess();
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
