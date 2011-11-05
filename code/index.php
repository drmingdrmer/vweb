<?php

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
Logging::set_level( 'error' );



$acc = new Account();
$acc->default_work_page( DEFAULT_INDEX );

if ( $_GET[ 'acc' ] == 'flush' ) {
    $acc->sess_flush();
    echo "flushed";
    exit();
}

if ( $acc->has_verifier() ) {
    if ( $acc->verify() ) {
        dd( "verify ok" );
        $acc->goto_work_page();
    }
    else {
        // illegal verifier
        $acc->start_auth();
    }
}
else if ( $acc->use_sess() ) {
    $acc->goto_work_page();
}
else {
    $acc->start_auth();
}


?>
