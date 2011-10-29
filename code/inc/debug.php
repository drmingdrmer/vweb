<?

$levels = array(
    'debug'=>10,
    'info'=>8,
    'ok'=>6,
    'warn'=>4,
    'error'=>2,
);

$_debugLevel = 'debug';
// $_debugLevel = 'warn';

if ( $_GET[ 'level' ] ) {
    $_debugLevel = $_GET[ 'level' ];
}

$_debugLevel = $levels[ $_debugLevel ];

$_debugFn = '.';

function _pos() {
    $bts = debug_backtrace();
    array_shift($bts);
    array_shift($bts);
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

function _write_pos( $msg ) {
    _pos();
    _write( $msg );
}

function if_log( $lvl ) {
    global $_debugLevel;
    global $levels;
    return $_debugLevel >= $levels[ $lvl ];
}

function _write_pos_if( $lvl, $msg ) {
    $LVL = strtoupper( $lvl );
    if_log( $lvl ) && _write_pos( "[ $LVL ] $msg" );
}

function dd( $msg ) { _write_pos_if( 'debug', $msg ); }
function dinfo( $msg ) { _write_pos_if( 'info', $msg ); }
function dok( $msg ) { _write_pos_if( 'ok', $msg ); }
function dwarn( $msg ) { _write_pos_if( 'warn', $msg ); }
function derror( $msg ) { _write_pos_if( 'error', $msg ); }

?>
