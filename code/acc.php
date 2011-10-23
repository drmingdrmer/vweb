<?php

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/t.class.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );

class Account
{
    public $acctoken;
    public $user;
    public $redirect = DEFAULT_INDEX;
    public $error;

    public $workPage;

    function __construct( &$acctoken = NULL ) {
        $this->acctoken = $acctoken;
        $this->default_work_page( $_SERVER[ 'SCRIPT_URL' ] );
    }

    function default_work_page( $p ) {
         $this->workPage = $_GET[ 'r' ] ? $_GET[ 'r' ] : $p;
    }

    function use_sess() {
        if ( $this->sess_has_acctoken() ) {
            $this->acctoken = $_SESSION[ 'acctoken' ];
            return $this->t_load_user();
        }
        else {
            $this->error = array( 'rst'=>'NoTokenInSession' );
            return false;
        }
    }

    function use_db( $id ) {
        $myuser = new MyUser();
        $this->acctoken = $myuser->t_acctoken( $id );
        if ( ! $this->acctoken ) {
            $this->error = array( "rst"=>"dbError" );
            return false;
        }
        return $this->t_load_user();
    }

    function goto_work_page() {
        dd( "goto_work_page: {$this->workPage}" );
        if ( $this->workPage ) {
            header("Location: {$this->workPage}");
            exit();
        }
        else {
            return false;
        }
    }

    function t_load_user() {

        $c = new T( $this->acctoken );
        $r = $c->me();
        dd( "load me: " . print_r( $r, true ) );
        if ( $r ){
            $this->user = $c->r[ 'data' ];
            return $this->save_user();
        }
        return false;
    }

    function has_verifier() {
        return isset( $_REQUEST['oauth_verifier'] );
    }

    function verify( $verifier = NULL ) {

        if ( NULL === $verifier ) {
            $verifier = $_REQUEST['oauth_verifier'];
        }

        $reqtoken = $_SESSION[ 'reqtoken' ];
        $this->_generate_acctoken( $reqtoken, $verifier );

        dd( "verified, access token: " .print_r( $this->acctoken, true ) );

        return $this->t_load_user();
    }

    function save_user() {

        $user = $this->user;

        $_SESSION[ 'user' ] = $user;
        $_SESSION['acctoken'] = $this->acctoken;

        $myuser = new MyUser();

        $u = array( 'userid'=>$user[ 'id' ], );

        // simplify it
        // TODO if user exists, return ok
        $r = $myuser->add( $u );

        $r = $myuser->t_acctoken( $u[ 'userid' ], $this->acctoken );

        dd( "save user result: " . print_r( $r, true ) );

        return $r;
    }

    function start_auth() {
        $aurl = $this->_init_oauth();
        include( "oauthindex.php" );
        exit();
    }

    function sess_flush() {
        unset($_SESSION[ 'acctoken' ]);
        unset($_SESSION[ 'reqtoken' ]);
    }

    function sess_has_acctoken() {
        return isset( $_SESSION[ 'acctoken' ] );
    }

    function _init_oauth() {

        $o = new SaeTOAuth( WB_AKEY , WB_SKEY );

        $proto = is_https() ? 'https://' : 'http://';
        $reqtoken = $o->getRequestToken();

        $url = $o->getAuthorizeURL( $reqtoken['oauth_token'], false,
            $proto . $_SERVER['HTTP_HOST'] . "/index.php?r={$this->workPage}");

        $_SESSION['reqtoken'] = $reqtoken;
        return $url;
    }
    function _generate_acctoken( &$reqtoken, $verifier ) {

        $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
            $reqtoken['oauth_token'],
            $reqtoken['oauth_token_secret'] );

        $this->acctoken = $o->getAccessToken( $verifier ) ;
    }
}

?>
