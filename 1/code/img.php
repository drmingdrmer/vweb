<?

mb_internal_encoding( 'utf-8' );

include_once( "util.php" );

define( "TransGif", "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\xff\x00\xc0\xc0\xc0\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x01\x01\x32\x00\x3b" );


function wrap( $s, $nchar ) {
    $i = 0;
    $w = 0;
    $rst = "";
    while ( true ) {
        $c = mb_substr( $s, $i, 1 );
        if ( $c === "" ) {
            break;
        }

        $cwidth = strlen( $c ) > 1 ? 2 : 1;

        if ( $w + $cwidth > $nchar ) {
            $rst = $rst . "\n";
            $w = 0;
        }

        $rst = $rst . $c;
        $w += $cwidth;

        $i += 1;
    }

    return $rst;
}
function textgif( $s, $w, $h, $font ) {

    $s = wrap( $s, $w / $font[ "size" ] * 2 );
    var_dump( $s, $w, $h );

    $img = new SaeImage();
    $img->setData( TransGif );
    $img->resize( $w, $h );
    $img->annotate( $s, 1, SAE_NorthWest, $font );

    return $img->exec( 'png' );
}
function mkimg( $data, $tp, $isout ) {
    $FONT = array(
        "size" => 14,
        "color" => "black" );

    $d = $data[ 'd' ];
    $w = $data[ 'w' ];
    $h = $data[ 'h' ];
    $bgcolor = $data[ 'bgcolor' ];

    $comp = array();
    foreach ($d as $e) {
        if ( $e[ 'text' ] ) {
            $font = $FONT;

            // $e[ 'color' ] && $font[ 'color' ] = $e[ 'color' ];

            $img = textgif( $e[ 'text' ], $e[ 'w' ], $e[ 'h' ], $font );
            $sub = array( $img, $e[ 'l' ], -$e[ 't' ], 1, SAE_TOP_LEFT );
            array_push( $comp, $sub );
        }
    }


    $img = new SaeImage();
    $img->clean();
    $img->setData( $comp );
    $img->composite( $w, $h, $bgcolor );

    return $img->exec( $tp, $isout );
}
function mkimg_local( $data, $tp ) {
    $imgdata = mkimg( $data, $tp, false );

    $localTail = rand() . "__tmp__";
    $localfn = SAE_TMP_PATH . $localTail;

    $r = file_put_contents( $localfn, $imgdata );
    if ( $r ) {
        return $localfn;
    }
    else {
        return false;
    }
}


/*
 * // var_dump($img->errno(), $img->errmsg());
 * 
 * session_start();
 * 
 * 
 * $verb = $_SERVER[ 'REQUEST_METHOD' ];
 * 
 * if ( $verb == "POST" ) {
 * 
 *     $act = $_GET[ 'act' ];
 * 
 *     if ( $act == "mkimg" ) {
 * 
 *         $fnTail = $_GET[ 'path' ];
 *         !$fnTail && resmsg( "nopath", "nopath" );
 * 
 *         $data = file_get_contents("php://input");
 *         !$data && resmsg( "nodata", "nodata" );
 * 
 *         $data = unjson( $data );
 *         !$data && resmsg( "invalid", "invalid" );
 * 
 * 
 *         $tp = 'jpg';
 *         $imgdata = mkimg( $data, $tp, false );
 * 
 *         $uid = $_SESSION[ "last_key" ][ 'user_id' ];
 *         $fn = "$uid/$fnTail";
 * 
 *         $fn = "abc";
 * 
 *         $s = new SaeStorage();
 *         $url = $s->write( 'pub' , "$fn.$tp" , $imgdata );
 *         $url && resjson( array( "rst" => "ok", "url" => $url ) )
 *             || resmsg( 'save', $s->errmsg() );
 *     }
 * }
 */

?>
