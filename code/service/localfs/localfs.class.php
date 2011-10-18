<?

class LocalFs
{
    $pref = "any";

    function __construct( $pref ) {
        $this->pref = $pref;
    }

    function _key( $key ) {
        return $this->pref . ":$key";
    }

    function path( $key ) {
        return SAE_TMP_PATH . $this->_key( $key );
    }

    function read( $key ) {
        $localfn = $this->path( $key );
        return file_get_contents( $localfn );
    }

    function write( $key, $cont ) {
        $localfn = $this->path( $key );
        $r = file_put_contents( $localfn, $cont );
        return $r;
    }
}

class LocalImg extends LocalFs {
    $pref = "img:";
}

?>
