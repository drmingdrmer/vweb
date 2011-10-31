<?
session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

function show_vd_acc( &$vwebUser ) {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/accountinfo.php" );
}

function show_vd_login() {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/vdlogin.php" );
}

function show_options() {
    include( $_SERVER["DOCUMENT_ROOT"] . "/template/fav2vd_options.php" );
}

function show_save_db_error() {
    echo "error saving vd acc to db";
}

function show_vd_login_error() {
    echo "login error";
}


class Controller {

    public $error;
    public $acc;

    function __construct( &$acc ) {
        $this->acc = $acc;
    }

    function doit() {

        $op = $_GET[ 'op' ];

        if ( method_exists( $this, $op ) ) {
            return $this->$op();
        }
    }

    function policy() {

        dd( "handling policy" );

        $act  = $_REQUEST[ 'act' ];
        $pkey = $_REQUEST[ 'pkey' ];
        $pval = $_REQUEST[ 'pval' ];

        $myuser = new MyUser();

        $poli = $myuser->favPolicy( $this->acc->t_user[ 'id' ] );
        if ( NULL === $poli ) {
            dd( "policy is NULL!" );
            $poli = Fav2VD::$defaultPolicy;
        }


        if ( $act == 'add' ) {
            $poli[ $pkey ] = $pval;
        }
        else {
            unset( $poli[ $pkey ] );
        }


        $r = $myuser->favPolicy( $this->acc->t_user[ 'id' ], $poli );
        if ( $r ) {
            dd( "Save policy OK:" . Json::enc( $poli ) );
        }
        else {
            dd( "Failure to save" );
        }

        return false;
    }

    function vdlogin() {

        $n = $_REQUEST[ 'username' ];
        $p = $_REQUEST[ 'password' ];

        $vdisk = new VD();
        if ( $vdisk->login( $n, $p ) ) {

            $myuser = new MyUser();
            if ( $myuser->vdacc( $this->acc->t_user[ 'id' ], "$n:$p" ) ) {
                $_SESSION[ 'vdtoken' ] = $vdisk->token;
                return true;
            }
            else {
                show_save_db_error();
                return false;
            }
        }
        else {
            show_vd_login_error();
            return false;
        }
    }
}

function doit() {

    $acc = new Account();

    if ( ! $acc->use_sess() ) {
        $acc->start_auth();
        return false;
    }


    if ( isset( $_GET[ 'op' ] ) ) {

        $op = new Controller( $acc );

        if ( ! $op->doit() ) {
            return false;
        }
    }


    $myuser = new MyUser();

    $vwebUser = $myuser->byid( $acc->t_user[ 'id' ] );

    $vdacc = $vwebUser[ 'vdacc' ];

    if ( $vdacc ) {

        $vdacc = explode( ':', $vdacc );

        $vdisk = new VD();

        if ( $vdisk->login( $vdacc[ 0 ], $vdacc[ 1 ] ) ) {

            show_vd_acc( $vwebUser );
            show_options();
            return true;

        }
    }

    show_vd_login();
    return false;
}

doit();

?>
