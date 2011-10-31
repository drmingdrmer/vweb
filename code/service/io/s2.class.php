<?
// require_once('saes3.ex.class.php');
include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
// include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/SaeS3.class.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/SinaStorageService.php" );

class S2 {

    public $dom = "xp";
    public $pref = "nopref";
    public $rst;


    function path( $path ) {
        return $this->pref . ":" . $path;
    }

    function write( $path, &$cont ) {

        $path = $this->path( $path );
        $o = $this->s2();

        $r = $o->uploadFile( $path, $cont, strlen( $cont ),
            "text/html", $rst );
        $this->rst = $o->result_info;

        if ( $r ) {
            return true;
        }
        else {
            derror( "Failure writing to S2:$path length=" . strlen( $cont ) );
            derror( "res:" . print_r( $this->rst, true ) );
            return false;
        }
    }

    function read( $path ) {

        $path = $this->path( $path );
        $o = $this->s2();

        $r = $o->getFile( $path, $rst );
        $this->rst = $o->result_info;

        if ( $r ) {
            return $rst;
        }
        else {
            dinfo( "Failure reading from S2:$path" );
            dinfo( "res:" . print_r( $this->rst, true ) );
            return false;
        }
    }

    function read_meta( $path ) {

        $path = $this->path( $path );
        $o = $this->s2();

        $r = $o->getMeta( $path, $rst );
        $this->rst = $o->result_info;

        if ( $r ) {
            dd( "s2 meta is:" . print_r( $rst, true ) );
            return $rst;
        }
        else {
            derror( "Failure reading from S2:$path" );
            derror( "res:" . print_r( $this->rst, true ) );
            return false;
        }
    }

    function s2() {
        $dom = $this->dom . ".{$_SERVER[ 'HTTP_APPNAME' ]}";
        $o = new SinaStorageService( $dom, 'sae,'.SAE_ACCESSKEY, SAE_SECRETKEY );
        $o->setAuth( true );
        return $o;
    }
}

class Page extends S2 {
    public $pref = "page";
}

class Img extends S2 {
    public $pref = "img";
}

?>
