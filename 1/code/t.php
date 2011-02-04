<? 

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'img.php' );

header('Content-Type:text/html; charset=utf-8');

$c = new SaeTClient( WB_AKEY, WB_SKEY,
    $_SESSION['last_key']['oauth_token'],
    $_SESSION['last_key']['oauth_token_secret']  );

// $_SESSION[ 'last_key' ][ 'user_id' ]


$verb = $_SERVER[ 'REQUEST_METHOD' ];
$act = $_GET[ 'act' ];
!$act && resmsg( 'noact', 'noact' );

if ( $verb == "GET" ) {

    $page = $_GET[ 'page' ];
    $count = $_GET[ 'count' ];
    $since_id = $_GET[ 'since_id' ];
    $max_id = $_GET[ 'max_id' ];

    switch ( $act ) {
        case "friends_timeline" :
            $rst = $c->friends_timeline(  );
            if ( $rst ) {
                !$rst[ 'error_code' ]
                    && resjson( array( "rst" => "ok", "data" => $rst) ) 
                    || resmsg( "load", $rst[ 'error' ] );
            }
            else {
                resmsg( "load", "unknown" );
            }
            break;

        default:
            resmsg( "unknown_act", $act );
    }
}
else if ( $verb == "POST" ) {
    switch ( $act ) {
        case "pub" :
            $msg = $_GET[ 'msg' ];
            // $msg = trim( $msg );

            $data = file_get_contents("php://input");
            !$data && resmsg( "nodata", "nodata" );

            $data = unjson( $data );
            !$data && resmsg( "invalid", "invalid" );

            // var_dump( $data );
            $fn = mkimg_local( $data, 'jpg' );
            !$fn && resmsg( 'mkimg', 'mkimg' );

            $s = new SaeStorage();
            $url = $s->write( 'pub' , "tmp.jpg" , file_get_contents( $fn ) );

            resmsg( "ok", "no pub" );

            /*
             * $url && resjson( array( "rst" => "ok", "url" => $url ) )
             *     || resmsg( 'save', $s->errmsg() );
             */

            $r = $c->upload( $msg, $fn );
            $r && resmsg( "ok", "published" ) 
                || resmsg( "publish", "publish" );


            break;
    }
}

/*
 * $ms  = $c->show_user( null ); // done
 * 
 * var_dump(  );
 */

?>
