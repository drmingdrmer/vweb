<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mysql/mysql.php" );

class Meta {

    function write( $key, $arr ) {

        $my = new MyPage();

        $arr[ 'url' ] = $key;
        $r = $my->add( $arr );


        $x = isok( $r );
        if ( $r && $my->affected() ) {
            dinfo( "Success written to meta: $key " . print_r( $arr, true ) );
        }
        else {
            derror( "Failed writing to meta: $key: " . print_r( $arr, true ) );
            derror( "Writing meta r=" . print_r( $r, true ) );
        }
        return $x;
    }

    function read( $key ) {

        $my = new MyPage();

        $r = $my->get( $key );


        if ( count( $r ) > 0 ) {
            $arr = $r[ 0 ];
            dinfo( "Read meta: $key: " . print_r( $arr, true ) );
            return $arr;
        }
        else {
            dd( "Failed reading meta: $key: " );
            return false;
        }
    }
}

?>
