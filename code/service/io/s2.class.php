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





/*
 * function s2_sample() {
 *     require_once('saes3.ex.class.php');
 * 
 *     $s = new SaeS3();
 *     $url = $s->write( 'domain' , 'test/test.txt' , 'the content!' );
 *     // will return 'http://domain.appname.s3.sinaapp.com/test/test.txt'
 * 
 *     echo $s->getUrl( 'domain' , 'test/test.txt' );
 *     // will echo 'http://domain.appname.s3.sinaapp.com/test/test.txt'
 * 
 *     echo file_get_contents($url);
 *     // will echo 'the content!';
 * 
 * 
 * }
 */

?>
