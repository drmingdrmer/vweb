<? 

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );

header('Content-Type:text/html; charset=utf-8');

$c = new SaeTClient( WB_AKEY, WB_SKEY,
    $_SESSION['last_key']['oauth_token'],
    $_SESSION['last_key']['oauth_token_secret']  );

// $_SESSION[ 'last_key' ][ 'user_id' ]


$verb = $_SERVER[ 'REQUEST_METHOD' ];

if ( $verb == "GET" ) {

    $act = $_GET[ 'act' ];
    !$act && resmsg( 'noact', 'noact' );

    $page = $_GET[ 'page' ];
    $count = $_GET[ 'count' ];
    $since_id = $_GET[ 'since_id' ];
    $max_id = $_GET[ 'max_id' ];

    switch ( $act ) {
        case "friends_timeline" :
            $rst = $c->friends_timeline(  );
            $rst && resjson( array( "rst" => "ok", "data" => $rst) ) 
                || resmsg( "load", "unknown" );
            break;

        default:
            resmsg( "unknown_act", $act );
    }
}

/*
 * $ms  = $c->show_user( null ); // done
 * 
 * var_dump(  );
 */

?>
