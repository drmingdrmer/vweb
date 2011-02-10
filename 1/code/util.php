<?
function json( $v ) {
    return json_encode( $v );
}
function unjson( $s ) {
    return json_decode( $s, true );
}
function resjson( $v ) {
    header('Content-Type:application/json; charset=utf-8');
    echo json( $v );
    exit();
}
function resmsg( $rst, $msg ) {
    resjson( array( "rst" => $rst, "msg" => $msg ) );
}
function def( &$arr, $key, $val ) {
    if ( !isset( $arr[ $key ] ) ) {
        $arr[ $key ] = $val;
    }
}
?>
