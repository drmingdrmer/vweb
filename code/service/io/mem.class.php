<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class Mem {

    public $table;

    function write( $key, $arr ) {
        $this->table[ $key ] = arr_clone( $arr );
        dinfo( "Success written to mem: $key" );
        return true;
    }

    function read( $key ) {
        if ( isset( $this->table[ $key ] ) ) {
            dinfo( "Success read from mem: $key:" . print_r( $this->table[ $key ], true ) );
            return $this->table[ $key ];
        }
        else {
            dd( "Failed reading from mem: $key" );
            return false;
        }
    }
}

?>
