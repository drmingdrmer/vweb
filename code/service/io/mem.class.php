<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class Mem {

    public $table;

    function write( $key, $arr ) {
        $this->table[ $key ] = arr_clone( $arr );
        dd( "written to mem: $key" );
        return true;
    }

    function read( $key ) {
        dd( "read from mem: $key:" . print_r( $this->table[ $key ], true ) );
        if ( isset( $this->table[ $key ] ) ) {
            return $this->table[ $key ];
        }
        else {
            return false;
        }
    }
}

?>
