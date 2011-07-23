<?

mb_internal_encoding( 'utf-8' );

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

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    $start  = $length * -1; //negative
    return (substr($haystack, $start) === $needle);
}

function img_by_url( $src ){
    $img = file_get_contents( $src );
    $img = imagecreatefromstring( $img );
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
    $imgbgcolor = imagecolorallocate( $bg,  255,  255,  255);
    $fontcolor = imagecolorallocate( $bg, 0, 0, 0 );
    imagefill( $bg, 0, 0, $imgbgcolor );


    foreach ($d as $e) {

        if ( $e[ 'bgcolor' ] ) {
            $img = imagecreatetruecolor( $e[ 'w' ], $e[ 'h' ] );
            $r = imagecopy( $bg, $img,
                $e[ 'l' ], $e[ 't' ], 0, 0,
                $e[ 'w' ], $e[ 'h' ] );
            if ( !$r ) {
                return array( 'rst'=>'mkImageBlock' );
            }
        }

        if ( $e[ 'text' ] ) {
            $s = wrap_text( $e[ 'text' ], $e[ 'w' ] / 14.0 * 2 );

            // pixel to ponit convertion: 1.6667
            $r = imagettftext( $bg, 14 / 1.6667, 0,
                $e[ 'l' ], $e[ 't' ] + 14, // x, y is the left-bottom point of the first char
                $fontcolor, SAE_Font_Sun,
                $s
            );
            if ( !$r ) {
                return array( 'rst'=>'mkImageText' );
            }
        }
    }

    foreach ($d as $e) {

        if ( $e[ 'img' ] ) {

            $img = img_by_url( $e[ 'img' ] );

            $imgw = imagesx( $img );
            $imgh = imagesy( $img );

            $r = imagecopyresized( $bg, $img,
                $e[ 'l' ], $e[ 't' ], 0, 0,
                $e[ 'w' ], $e[ 'h' ], $imgw, $imgh );

            if ( !$r ) {
                return array( 'rst'=>'mkImageSub', 'data'=>$e[ 'img' ] );
            }

        }
    }


    $r = imagejpeg( $bg, $fn );
    if ( !$r ) {
        return array( 'rst'=>'createJpeg' );
    }

    $cont = file_get_contents( $fn );

    return array( 'rst'=>'ok' );
}

function mkimg_local( $data, $tp ) {

    $localTail = rand() . ".jpg";
    $localfn = SAE_TMP_PATH . $localTail;

    $r = mkimg( $data, $tp, $localfn );
    if ( $r[ 'rst' ] == 'ok' ) {
        return array( 'rst'=>'ok', 'data'=>$localfn );
    }
    else {
        return $r;
    }

}
?>
