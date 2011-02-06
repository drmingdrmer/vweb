<? 

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'img.php' );

header('Content-Type:text/html; charset=utf-8');

class MySaeTClient extends SaeTClient
{
    function friends_timeline( $page = 1, $count = 20, $since_id = NULL, $max_id = NULL, $feature = 0 )
    {
        $params = array();
        if ($since_id) {
            $this->id_format($since_id);
            $params['since_id'] = $since_id;
        }
        if ($max_id) {
            $this->id_format($max_id);
            $params['max_id'] = $max_id;
        }
        $params[ 'feature' ] = $feature;

        return $this->request_with_pager('http://api.t.sina.com.cn/statuses/home_timeline.json', $page, $count, $params );
    }

} 

$c = new MySaeTClient( WB_AKEY, WB_SKEY,
    $_SESSION['last_key']['oauth_token'],
    $_SESSION['last_key']['oauth_token_secret']  );

// $_SESSION[ 'last_key' ][ 'user_id' ]


$verb = $_SERVER[ 'REQUEST_METHOD' ];
$act = $_GET[ 'act' ];
!$act && resmsg( 'noact', 'noact' );

if ( $verb == "GET" ) {

    $p = $_GET;
    def( $p, 'page', 1 );
    def( $p, 'count', 20 );
    def( $p, 'since_id', NULL );
    def( $p, 'max_id', NULL );
    def( $p, 'feature', 0 );


    switch ( $act ) {
        case "friends_timeline" :
            $rst = $c->friends_timeline( $p[ 'page' ], $p[ 'count' ],
                $p[ 'since_id' ], $p[ 'max_id' ], $p[ 'feature' ] );

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
