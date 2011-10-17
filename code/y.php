<?
session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

function doit() {

    $acc = new Account();
    $acc->redirect = "y.php";

    if ( $acc->use_sess() && $acc->t_to_sess() ) {
        $acctoken = $acc->acctoken;

        $t = new T( $acctoken );

        $vdisk = new VD();
        $r = $vdisk->login( 'drdr.xp@gmail.com', '748748' );

        $fv = new Fav2VD( $t, $vdisk );

        $r = $fv->dump();

    }
    else {
        $acc->start_auth();
    }
}

doit();

?>
