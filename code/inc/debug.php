<?

$levels = array(
    'debug'=>10,
    'info'=>8,
    'ok'=>6,
    'warn'=>4,
    'error'=>2,
);

$_debugLevel = 'debug';

if ( $_GET[ 'level' ] ) {
    $_debugLevel = $_GET[ 'level' ];
}

$_debugLevel = $levels[ $_debugLevel ];

$_debugFn = '.';

function _pos() {
    $bts = debug_backtrace();
    array_shift($bts);
    $bt = array_shift($bts);

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
    echo "<span style='color:#999;width:400px;display:inline-block;'>$now [ $fn:$line $clz ]</span>";
}

function _write( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function dd( $msg ) {
    global $_debugLevel;
    global $levels;
    if ( $_debugLevel < $levels[ 'debug' ] ) {
        return;
    }
    _pos();
    _write( "[ DEBUG ] $msg" );
}

function dinfo( $msg ) {
    if ( $_debugLevel < $levels[ 'info' ] ) {
        return;
    }
    _pos();
    _write( "[ INFO ] $msg" );
}

function dok( $msg ) {
    if ( $_debugLevel < $levels[ 'ok' ] ) {
        return;
    }
    _pos();
    _write( "[ OK ] $msg" );
}

function derror( $msg ) {
    _pos();
    _write( "[ ERROR ] $msg" );
}
?>
