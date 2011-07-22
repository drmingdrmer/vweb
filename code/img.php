<?

mb_internal_encoding( 'utf-8' );

include_once( "lib/saeimg.php" );
include_once( "util.php" );

define( "TransGif", "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\xff\x00\xc0\xc0\xc0\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x01\x01\x32\x00\x3b" );


function wrap_text( $s, $nchar ) {
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

    $s = wrap_text( $s, $w / $font[ "size" ] * 2 );

    $img = new SaeImage();
    $img->setData( TransGif );
    $img->resize( $w, $h );
    $img->annotate( $s, 1, SAE_NorthWest, $font );

    return $img->exec( 'png' );
}
function square( $w, $h, $color ) {

    $img = new SaeImage();
    $img->setData( array( array( TransGif, 0, 0, 1, SAE_TOP_LEFT ) ) );
    $img->composite( $w, $h, $color );

    return $img->exec( 'png' );
}

function fiximg( $src, $w, $h ) {
    $data = file_get_contents( $src );
    $img = new SaeImage();
    $img->setData( $data );
    $img->resize( $w, $h );

    return $img->exec( 'png' );
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    $start  = $length * -1; //negative
    return (substr($haystack, $start) === $needle);
}

function img_by_url( $src ){ 
    if ( endsWith( $src, '.jpg' ) ) {
        $img = imagecreatefromjpeg( $src );
    }
    else if ( endsWith( $src, '.gif' ) ) {
        $img = imagecreatefromgif( $src );
    }
    else if ( endsWith( $src, '.png' ) ) {
        $img = imagecreatefrompng( $src );
    }
    else {
        return false;
    }
    return $img;
}
function mkimg( $data, $tp, $fn ) {
    $FONT = array(
        "size" => 14,
        "color" => "black" );

    $d = $data[ 'd' ];
    $w = $data[ 'w' ];
    $h = $data[ 'h' ];
    $bgcolor = $data[ 'bgcolor' ];

    $comp = array();
    $info = array();


    $bg = imagecreatetruecolor( $w, $h );
    $bgcolor = imagecolorallocate( $bg,  255,  255,  255);
    imagefill( $bg, 0, 0, $bgcolor );

    foreach ($d as $e) {
        if ( $e[ 'bgcolor' ] ) {
            $img = imagecreatetruecolor( $e[ 'w' ], $e[ 'h' ] );
            $r = imagecopy( $bg, $img,
                $e[ 'l' ], $e[ 't' ], 0, 0,
                $e[ 'w' ], $e[ 'h' ] );
        }

        if ( $e[ 'img' ] ) {

            $img = img_by_url( $e[ 'img' ] );

            $w = imagesx( $img );
            $h = imagesy( $img );

            $r = imagecopyresized( $bg, $img,
                $e[ 'l' ], $e[ 't' ], 0, 0,
                $e[ 'w' ], $e[ 'h' ], $w, $h );

        }

        if ( $e[ 'text' ] ) {
            $font = $FONT;

            // $e[ 'color' ] && $font[ 'color' ] = $e[ 'color' ];

            $img = textgif( $e[ 'text' ], $e[ 'w' ] + $tPad, $e[ 'h' ], $font );
            $sub = array( $img, $e[ 'l' ], -$e[ 't' ], 1, SAE_TOP_LEFT );
            array_push( $comp, $sub );
        }
    }


    $r = imagejpeg( $bg, $fn );

    return array( 'rst'=>'ok', 'data'=>'' );
}

function mkimg_local( $data, $tp ) {

    $localTail = rand() . "__tmp__";
    $localfn = SAE_TMP_PATH . $localTail;

    $r = mkimg( $data, $tp, $localfn );

    return array( 'rst'=>'ok', 'data'=>$localfn );

    if ( $r[ 'rst' ] == 'ok' ) {

        $imgdata = $r[ 'data' ];

        $r = file_put_contents( $localfn, $imgdata );
        if ( $r ) {
            return array( 'rst'=>'ok', 'data'=>$localfn );
        }
        else {
            return array( 'rst'=>'writeLocalImage' );
        }
    }
    else {
        return $r;
    }

}
?>
