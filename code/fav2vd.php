<?
session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

function show_vd_acc( &$user ) {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/accountinfo.php" );
}

function show_vd_login() {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/vdlogin.php" );
}

function show_options() {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/fav2vd_options.php" );
}

function doit() {

    $acc = new Account();

    if ( $acc->use_sess() ) {

        $acctoken = $acc->acctoken;

        $t = new T( $acctoken );

        $myuser = new MyUser();
        $user = $myuser->byid( $acc->user[ 'id' ] );

        $vdacc = $user[ 'vdacc' ];

        if ( $vdacc ) {
            $vdacc = explode( ':', $vdacc );

            $vdisk = new VD();
            if ( $vdisk->login( $vdacc[ 0 ], $vdacc[ 1 ] ) ) {
                // TODO 
                show_vd_acc( $user );

                // TODO 
                show_options();
            }
            else {
                // TODO 
                show_vd_login();
            }

        }
        else {
            show_vd_login();
        }

    }
    else {
        $acc->start_auth();
    }
}

doit();

?>
