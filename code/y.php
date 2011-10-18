<?
session_start();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>y</title>
    </head>
</html><?


include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );


/*
 * include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
 * print_r( new MetaVisitor() );
 * exit();
 */

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
