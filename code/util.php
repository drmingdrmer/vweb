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
    $v = json( $v );
    echo "<script>window.parent.$cb($v);</script>";
    exit();
}

function resmsg( $rst, $msg ) {
    res_json( array( "rst" => $rst, "msg" => $msg ) );
}
function def( &$arr, $key, $val ) {
    if ( !isset( $arr[ $key ] ) ) {
        $arr[ $key ] = $val;
    }
}
function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    $start  = $length * -1; //negative
    return (substr($haystack, $start) === $needle);
}


?>
