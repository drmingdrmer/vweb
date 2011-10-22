<?

class LocalFs
{
    public $pref = "any";

    function __construct( $pref = NULL ) {
        if ( $pref !== NULL ) {
            $this->pref = $pref;
        }
    }

    function _key( $key ) {
        return $this->pref . ":$key";
    }

    function path( $key ) {
        return SAE_TMP_PATH . $this->_key( $key );
    }

    function read( $key ) {
        $localfn = $this->path( $key );
        if ( file_exists( $localfn ) ) {
            $r = file_get_contents( $localfn );
            dinfo( "Success Read from local fs: $key" );
            return $r;
        }
        else {
            dd( "Failed reading from local fs: $key" );
            return false;
        }
    }

    function write( $key, $cont ) {

        $localfn = $this->path( $key );
        $r = file_put_contents( $localfn, $cont );

        dinfo( "Success written to local fs: $key" );

        return $r;
    }
}

class LocalPage extends LocalFs {
    public $pref = "page:";
}

class LocalImg extends LocalFs {
    public $pref = "img:";
}

?>
