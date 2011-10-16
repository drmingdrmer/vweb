<?php

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/t.class.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );

class Account
{
    public $acctoken;
    public $redirect = DEFAULT_INDEX;

    function __construct( &$acctoken = NULL ) {
        $this->acctoken = $acctoken;
    }

    function sess_flush() {
        unset($_SESSION[ 'acctoken' ]);
        unset($_SESSION[ 'reqtoken' ]);
    }

    function sess_has_acctoken() {
        return isset( $_SESSION[ 'acctoken' ] );
    }

    function t_to_sess() {
        $r = $this->me();
        if ( isok( $r ) ) {
            $_SESSION[ 'user' ] = $r[ 'data' ];
            // TODO error messages
            return true;
        }
        else {
            return false;
        }

    }

    function generate_acctoken( &$reqtoken, $verifier ) {

        $o = new SaeTOAuth( WB_AKEY, WB_SKEY,
            $reqtoken['oauth_token'],
            $reqtoken['oauth_token_secret'] );

        $this->acctoken = $o->getAccessToken( $verifier ) ;
    }

    function use_sess() {
        if ( $this->sess_has_acctoken() ) {
            $this->acctoken = $_SESSION[ 'acctoken' ];
            return true;
        }
        else {
            return false;
        }
    }

    function use_db( $id ) {
        $my = new My();
        $this->acctoken = $my->t_acctoken( $id );
        return true;
    }

    function verify( $verifier ) {

        $reqtoken = $_SESSION[ 'reqtoken' ];
        $this->generate_acctoken( $reqtoken, $verifier );

        $r = $this->me();

        if ( isok( $r ) ) {

            $user = $r[ 'data' ];

            $this->save_user( $user );

            $r = $this->save_acctoken( $user );
            // TODO error handling

        }

        return $r;
    }

    function save_acctoken( &$user ) {

        $_SESSION['acctoken'] = $this->acctoken;

        $my = new My();
        $r = $my->t_acctoken( $u[ 'id' ], $this->acctoken );

        return $r;
    }

    function save_user( &$user ) {

        $_SESSION[ 'user' ] = $user;

        $my = new My();
        $u = array( 'id'=>$user[ 'id' ], );
        $r = $my->user_add( $u );

        return $r;
    }

    function init_oauth() {

        $o = new SaeTOAuth( WB_AKEY , WB_SKEY );

        $proto = is_https() ? 'https://' : 'http://';

        $reqtoken = $o->getRequestToken();

        $redirect = $this->redirect;

        $url = $o->getAuthorizeURL( $reqtoken['oauth_token'], false,
            $proto . $_SERVER['HTTP_HOST'] . "/index.php?r=$redirect");

        $_SESSION['reqtoken'] = $reqtoken;
        return $url;
    }

    function do_work() {
        if ( $this->t_to_sess() ) {
            $this->redirect();
            return true;
        }
        else {
            return false;
        }
    }

    function redirect() {
        $r = $this->redirect;
        header("Location: $r");
        exit();
    }

    function me() {
        $c = new T( $this->acctoken );
        $r = $c->me();
        return $r;
    }

}

?>
