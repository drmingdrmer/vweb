<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class Visitor {

    function __construct() {
    }

    function write( $key, &$cont ) {
        return $this->do_write( $key, $cont );
    }

    function read( $key ) {
        return $this->do_read( $key );
    }
}

class EngineVisitor extends Visitor{

    public $engine;
    public $child;

    function __construct( &$engine, &$child = NULL ) {
        $this->engine = $engine;
        $this->child = $child;
    }

    function write( $key, &$cont ) {

        $r = parent::write( $key, $cont );

        if ( $r ) {
            if ( $this->child !== NULL ) {
                $this->child->write( $key, $cont );
            }
        }

        return $r;
    }

    function read( $key ) {

        $r = parent::read( $key );
        if ( $r !== false ) {
            return $r;
        }

        if ( $this->child === NULL ) {
            return $r;
        }

        $r = $this->child->read( $key );
        if ( $r === false ) {
            return false;
        }
        else {
            parent::write( $key, $r );
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

class MD5EngineVisitor extends EngineVisitor {

    function get_key( $key ) {
        $r = md5( $key );
        dd( "Convert key $key to $r" );
        return $r;
    }

    function write( $key, &$cont ) {
        return parent::write( $this->get_key( $key ), $cont );
    }

    function read( $key ) {
        return parent::read( $this->get_key( $key ) );
    }
}

/*
 * class MetaVisitor extends Visitor{
 * 
 *     function do_write( $key, $arr ) {
 * 
 *         $my = new MyPage();
 *         $r = $my->add( $key,
 *             $arr[ 'title' ], $arr[ 'realurl' ], $arr[ 'mimetype' ] );
 * 
 *         return isok( $r );
 *     }
 * 
 *     function do_read( $key ) {
 *         $my = new MyPage();
 *         dd( "read page from mysql:$key" );
 *         $r = $my->get( $key );
 *         if ( hasdata( $r ) ) {
 *             $arr = $r[ 'data' ][ 0 ];
 *             return $arr;
 *         }
 *         else {
 *             return false;
 *         }
 *     }
 * }
 */
?>
