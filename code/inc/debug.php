<?

$levels = array(
    'debug'=>10,
    'info'=>8,
    'warn'=>6,
    'error'=>4,
);

$_debugLevel = 'debug';

if ( $_GET[ 'level' ] ) {
    $_debugLevel = $_GET[ 'level' ];
}

$_debugLevel = $levels[ $_debugLevel ];

$_debugFn = '.';

function _pos() {
    $bt = debug_backtrace();
    array_shift($bt);
    $bt = array_shift($bt);

    $fn = $bt[ 'file' ];
    $fn = substr( $fn, strlen( $_SERVER[ "DOCUMENT_ROOT" ] ) );

    $clz = $bt[ 'class' ];

    $now = strftime("%H:%M:%S");
    echo "<span style='color:#999;width:300px;display:inline-block;'>[ $fn:{$bt['line']} $clz ]</span> $now ";
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

function derror( $msg ) {
    _pos();
    _write( "[ ERROR ] $msg" );
}
?>
