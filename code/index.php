<?php

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );

function start_auth( &$acc ) {
    $aurl = $acc->init_oauth();
    include( "oauthindex.php" );
}

function main() {

    $verifier = $_REQUEST['oauth_verifier'];

    $acc = new Account();
    $acc->redirect = $_GET[ 'r' ] ? $_GET[ 'r' ] : DEFAULT_INDEX;

    if ( $_GET[ 'acc' ] == 'flush' ) {
        $acc->sess_flush();
        echo "flushed";
        exit();
    }

    if ( $verifier ) {
        $acc->verify( $verifier );
        if ( ! $acc->do_work() ) {
            start_auth( $acc );
        }
    }
    else if ( $acc->use_sess() ) {
        if ( ! $acc->do_work() ) {
            start_auth( $acc );
        }
    }
    else {
        start_auth( $acc );
    }

}

main();

?>
