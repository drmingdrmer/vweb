<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );

class Meta {

    function write( $key, $arr ) {

        $my = new MyPage();

        $r = $my->add( $key,
            $arr[ 'title' ], $arr[ 'realurl' ], $arr[ 'mimetype' ] );

        $x = isok( $r );
        if ( $x ) {
            dd( "Written to meta: $key " . print_r( $arr, true ) );
        }
        else {
            dd( "Failed writing to meta: $key: " . print_r( $arr, true ) );
        }
        return $x;
    }

    function read( $key ) {

        $my = new MyPage();

        $r = $my->get( $key );


        if ( hasdata( $r ) ) {
            $arr = $r[ 'data' ][ 0 ];
            dd( "Read meta: $key: " . print_r( $arr, true ) );
            return $arr;
        }
        else {
            dd( "Failed reading meta: $key: " );
            return false;
        }
    }
}

?>
