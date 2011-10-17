<?
// include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );

class Visitor {

    function get_key( $key ) {
        return md5( $key );
    }

    function write( $key, $cont ) {
        $key = $this->get_key( $key );
        return $this->do_write( $key, $cont );
    }

    function read( $key ) {
        $key = $this->get_key( $key );
        return $this->do_read( $key );
    }
}
class StoVisitor extends Visitor{

    public $engine;

    function __construct( $clz ) {
        $this->engine = new $clz();
    }

    function do_write( $key, $cont ) {
        return $this->engine->write( $key, $cont );
    }

    function do_read( $key ) {
        return $this->engine->read( $key );
    }

}

class MetaVisitor extends Visitor{

    function do_write( $key, $arr ) {
        $my = new My();
        $r = $my->page_add( $key, $arr[ 'title' ], $arr[ 'realurl' ] );
        return isok( $r );
    }

    function do_read( $key ) {
        $my = new My();
        $r = $my->page_get( $key );
        if ( isok( $r ) ) {
            $arr = $r[ 'data' ][ 0 ];
            return $arr;
        }
        else {
            return false;
        }
    }
}
?>
