<?

class PageLog {
    static public function write( $lvl, $msg ) {
        $frm = Logging::_frame();
        echo "<span style='color:#999;width:400px;display:inline-block;'> $frm </span>";

        echo "$msg<br/>\n";
        ob_flush();
        flush();
    }
}

class Logging {

    static public $levels = array(
        'debug' => 10,
        'info'  => 8,
        'ok'    => 6,
        'warn'  => 4,
        'error' => 2,
    );

    static public $level = 10;

    static public $engine = PageLog;

    static public function set_engine( $e ) {
        Logging::$engine = $e;
    }

    static public function set_level( $lvl ) {
        Logging::$level = Logging::$levels[ $lvl ];
    }

    static public function do_log( $lvl, $msg ) {
        $LVL = strtoupper( $lvl );
        if ( Logging::$level >= Logging::$levels[ $lvl ] ) {
            $e = Logging::$engine;
            echo "engine is :" . print_r( $e, true );
            $e::write( Logging::$levels[ $lvl ], "[ $LVL ] $msg" );
        }
    }

    static public function _frame() {

        $bts = debug_backtrace();

        do {
            $bt = array_shift($bts);
            $fn = $bt[ 'file' ];
        } while ( $fn == __FILE__ );


        $line = $bt[ 'line' ];
        $fn = $bt[ 'file' ];
        $fn = substr( $fn, strlen( $_SERVER[ "DOCUMENT_ROOT" ] ) );


        $bt = array_shift( $bts );

        $object = $bt['object'];
        if (is_object($object)) {
             $clz = get_class($object);
        }
        else {
            $clz = '';
        }

        $now = strftime("%H:%M:%S");
        return "$now [ $fn:$line $clz ]";
    }
}

function dd( $msg ) { Logging::do_log( 'debug', $msg ); }
function dinfo( $msg ) { Logging::do_log( 'info', $msg ); }
function dok( $msg ) { Logging::do_log( 'ok', $msg ); }
function dwarn( $msg ) { Logging::do_log( 'warn', $msg ); }
function derror( $msg ) { Logging::do_log( 'error', $msg ); }


if ( $_GET[ 'level' ] ) {
    Logging::set_level( $_GET[ 'level' ] );
}

?>
