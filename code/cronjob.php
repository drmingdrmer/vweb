<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

// TODO config log-to-db

function dump_user( $uid ) {

    $acc = new Account();

    if ( $acc->use_db( $uid ) ) {

        if ( $acc->vd_login() ) {

            $fv = new Fav2VD( $acc->t, $acc->vd );

            $poli = $acc->myuser->favPolicy( $uid );
            if ( $poli ) {
                $fv->policy = $poli;
            }

            $r = $fv->dump();

            if ( $r ) {
                // write back status.
                // reset interval and next check time
            }
        }
    }
    else {
        // TODO log failure auth
    }
}

function list_users_need_dump() {
    $myuser = new MyUser();
    $needToCheck = $myuser->users_need_check( 10 );
    foreach ($needToCheck as $user) {
        $uid = $user[ 'userid' ];
        $r = dump_user( $uid );
    }
}


function main() {
    list_users_need_dump();
}

main();

?>
