<?php

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );


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
        $acc->start_auth();
    }
}
else if ( $acc->use_sess() ) {
    if ( ! $acc->do_work() ) {
        $acc->start_auth();
    }
}
else {
    $acc->start_auth();
}


?>
