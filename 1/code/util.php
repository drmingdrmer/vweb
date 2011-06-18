<?
function json( $v ) {
    return json_encode( $v );
}
function unjson( $s ) {
    return json_decode( $s, true );
}
function resjson( $v ) {
    res_json( $v );
}
function res_json( $v ) {
    header('Content-Type:application/json; charset=utf-8');
    echo json( $v );
    exit();
}
function res_cb( $v, $cb ) {
    echo "<script>window.parent.$cb($v);</script>";
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
