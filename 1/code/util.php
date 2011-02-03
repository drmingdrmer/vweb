<?
function json( $v ) {
    return json_encode( $v );
}
function unjson( $s ) {
    return json_decode( $s, true );
}
function resjson( $v ) {
    echo json( $v );
    exit();
}
function resmsg( $rst, $msg ) {
    resjson( array( "rst" => $rst, "msg" => $msg ) );
}
?>
