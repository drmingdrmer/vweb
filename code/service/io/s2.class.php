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
        dd( "To write to S2:$path length=" . strlen( $cont ) );
        $url = parent::write( $this->dom, $path, $cont );
        if ( $url !== false ) {
            return true;
        }
        else {
            return false;
        }
    }

    function read( $path ) {
        $path = $this->path( $path );
        $url = $this->getUrl( $this->dom, $path );
        $cont = file_get_contents( $url );
        return $cont;
    }
}

class Page extends S2 {
    public $pref = "page";
}

class Img extends S2 {
    public $pref = "img";
}

?>
