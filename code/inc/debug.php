<?


function _pos() {
    $bt = debug_backtrace();
    array_shift($bt);
    $bt = array_shift($bt);

    $fn = $bt[ 'file' ];
    $fn = substr( $fn, strlen( $_SERVER[ "DOCUMENT_ROOT" ] ) );

    echo "<span style='color:#999;width:300px;display:inline-block;'>[ $fn:{$bt['line']} ]</span> ";
}

function _write( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function dd( $msg ) {
    _pos();
    _write( "[ DEBUG ] $msg" );
}

function dinfo( $msg ) {
    _pos();
    _write( "[ INFO ] $msg" );
}

function derror( $msg ) {
    _pos();
    _write( "[ ERROR ] $msg" );
}
?>
