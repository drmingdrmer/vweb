<?php

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/weibo_util.php" );

function t_load_user( &$acctoken ) {
    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $acctoken['oauth_token'],
        $acctoken['oauth_token_secret']  );

    $rst = $c->_load_cmd( 'account/verify_credentials', array() );

    return $rst;
}

if ( isset( $_GET[ 'id' ] ) ) {
    $id = $_GET[ 'id' ];
    $r = get_user( $id );
    var_dump( $r );

    $user = $r[ 'data' ][ 0 ];
    $acctoken = $user[ 't_acctoken' ];

    $r = t_load_user( $acctoken );
    var_dump( $r );

}


?>
