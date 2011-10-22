<?
require_once('saes3.ex.class.php');
include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


class S2 extends SaeS3 {

    public $dom = "xp";
    public $pref = "nopref";

    function path( $path ) {
        return $this->pref . ":" . $path;
    }

    function write( $path, &$cont ) {
        $path = $this->path( $path );


        $url = parent::write( $this->dom, $path, $cont );
        if ( $url !== false ) {
            dinfo( "Success written to S2:$path length=" . strlen( $cont ) );
            return true;
        }
        else {
            derror( "Failed writing to S2:$path length=" . strlen( $cont ) );
            derror( "Error: " . $this->errmsg()  );
            return false;
        }
    }

    function read( $path ) {

        $path = $this->path( $path );

        $attr = $this->getAttr( $this->dom, $path );
        dd( "s2 file attr: " . print_r( $attr, true ) );

        if ( $attr ) {
            $url = $this->getUrl( $this->dom, $path );
            $cont = file_get_contents( $url );
            dinfo( "Success read from s2: $path: " . strlen( $cont ) );
            return $cont;
        }
        else {
            dd( "Failed reading from s2: $path" );
            return false;
        }

    }
}

class Page extends S2 {
    public $pref = "page";
}

class Img extends S2 {
    public $pref = "img";
}

?>
