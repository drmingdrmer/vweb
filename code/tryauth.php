<?php

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/weibo_util.php" );


function dd( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function f() {
    $f = new SaeFetchurl();
    $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
    $f->setAllowRedirect( false );
    return $f;
}

/*
 * $verifier = $_REQUEST['oauth_verifier'];
 * if ( $verifier ) {
 *     $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
 *         $_SESSION['keys']['oauth_token'],
 *         $_SESSION['keys']['oauth_token_secret'] );
 * 
 *     $_SESSION['last_key'] = $o->getAccessToken( $verifier ) ;
 * 
 *     load_user_to_sess();
 *     header("Location: $redirectPage");
 *     exit();
 * }
 */


$o = new SaeTOAuth( WB_AKEY , WB_SKEY );

$reqtoken = $o->getRequestToken();
dd( "request token" );
var_dump( $reqtoken );
dd( "" );


$proto = is_https() ? 'https://' : 'http://';
$aurl = $o->getAuthorizeURL( $reqtoken['oauth_token'], false,
    $proto . $_SERVER['HTTP_HOST'] . "/null.php?r=$redirectPage");

dd( "calling: $aurl" );
$f = f();
$url = $aurl;

$n = 10;
while ( $n > 0 ) {
    $n = $n - 1;
    $cont = $f->fetch( $url );
    $c = $f->httpCode();

    if ( $c == "200" ) {
        dd( "200 found" );
        echo $cont;
        break;
    }
    else if ( $c == "302" ) {
        dd( "302 found:" );
        $headers = $f->responseHeaders();
        $url = $headers[ 'Location' ];
        dd( "redirect to $url" );
    }
    else {
        dd( "code is $c" );
        break;
    }
}


?>
