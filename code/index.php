<?php

session_start();

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
// include_once( 'ss.php' );

class MySaeTClient extends SaeTClient
{
    function _load_cmd( $cmd, $p )
    {
        def( $p, 'page', 1 );
        def( $p, 'count', 20 );

        $url = "http://api.t.sina.com.cn/$cmd.json";
        $rst = $this->oauth->get($url , $p );
        return $rst;
    }
}

function load_user() {
    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $_SESSION['last_key']['oauth_token'],
        $_SESSION['last_key']['oauth_token_secret']  );

    $rst = $c->_load_cmd( 'account/verify_credentials', array() );

    if ( $rst ) {
        if ( ! $rst[ 'error_code' ] ) {
            $_SESSION[ 'user' ] = $rst;
        }
        else {
            echo "{$rst['error']}\n";
            exit();
        }
    }
    else {
        echo "调用weibo接口失败。请刷新\n";
        exit();
    }
}


$defaultPage = 'm.html';

$redirectPage = $_GET[ 'r' ] ? $_GET[ 'r' ] : $defaultPage;


if( isset($_SESSION['last_key']) ) {
    load_user();
    header("Location: $redirectPage");
    exit();
}


$verifier = $_REQUEST['oauth_verifier'];
if ( $verifier ) {
    $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
        $_SESSION['keys']['oauth_token'],
        $_SESSION['keys']['oauth_token_secret'] );

    $_SESSION['last_key'] = $o->getAccessToken( $verifier ) ;

    load_user();
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
