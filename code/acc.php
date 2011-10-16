<?php

session_start();

include_once( 'config.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'mysql.php' );


function t_load_user( &$acctoken ) {
    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $acctoken['oauth_token'],
        $acctoken['oauth_token_secret']  );

    $rst = $c->_load_cmd( 'account/verify_credentials', array() );

    return $rst;
}

function load_user_to_sess( &$acctoken ) {

    $rst = t_load_user( $acctoken );

    if ( $rst[ 'rst' ] == 'ok' ) {
        $_SESSION[ 'user' ] = $rst[ 'data' ];
    }
    return $rst;
}

function generate_acctoken( &$reqtoken, $verifier ) {
    $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
        $reqtoken['oauth_token'],
        $reqtoken['oauth_token_secret'] );

    return $o->getAccessToken( $verifier ) ;
}

function sess_get_acctoken() {
    return $_SESSION[ 'acctoken' ];
}

function do_verify( $verifier ) {
    $reqtoken = $_SESSION[ 'reqtoken' ];
    $acctoken = generate_acctoken( $reqtoken, $verifier );


    $r = t_load_user( $acctoken );
    if ( $r[ 'rst' ] == 'ok' ) {

        $_SESSION['acctoken'] = $acctoken;

        $user = $r[ 'data' ];
        $u = array(
            'id'=>$user[ 'id' ],
        );

        $r0 = add_user( $u );
        var_dump( $r0 );
        $r1 = update_t_acctoken( $u[ 'id' ], $acctoken );
        var_dump( $r0 );

        $_SESSION[ 'user' ] = $r[ 'data' ];
    }

    return $r;
}


?>
