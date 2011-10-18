<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class Visitor {

    function get_key( $key ) {
        dd( "key is:$key" );
        $r = md5( $key );
        dd( "md5 key=$r" );
        return $r;
    }

    function write( $key, &$cont ) {
        $key = $this->get_key( $key );
        if ( gettype( $cont ) == 'string' ) {
            dd( "To write $key, " . strlen( $cont ) );
        }
        else if ( gettype( $cont ) == 'array' ) {
            dd( "To write $key, " . print_r( $cont, true ) );
        }
        return $this->do_write( $key, $cont );
    }

    function read( $key ) {
        $key = $this->get_key( $key );
        return $this->do_read( $key );
    }
}


class EngineVisitor extends Visitor{

    public $engine;
    public $child

    function __construct( &$engine, &$child = NULL ) {
        $this->engine = $engine;
        $this->child = $child;
    }

    function write( $key, &$cont ) {
        $r = parent::write( $key, $cont );
        if ( $r ) {
            $this->child->write( $key, $cont );
        }

        return $r;
    }

    function read( $key ) {
        $r = parent::read( $key );
        if ( $r === false ) {
            $r = $this->child->read( $key );
            if ( $r === false ) {
                return false;
            }
            else {
                parent::write( $key, $r );
                return $r;
            }
        }
        else {
            return $r;
        }
    }

    function do_write( $key, &$cont ) {
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
        dd( "read page from mysql:$key" );
        $r = $my->page_get( $key );
        if ( hasdata( $r ) ) {
            $arr = $r[ 'data' ][ 0 ];
            return $arr;
        }
        else {
            return false;
        }
    }
}
?>
