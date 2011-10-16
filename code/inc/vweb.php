<?

include_once( 'config.php' );
include_once( 'util.php' );

class VwebError extends Exception {};

class NetworkError extends VwebError {};

class OAuthError extends VwebError {};

class TOAuthError extends OAuthError {};

class TExpiredToken extends TOAuthError {};



function vresult( $r, &$data = NULL, $msg = '',  &$info = NULL ) {
    return array(
        'rst' => $r,
        'data' => $data,
        'msg' => $msg,
        'info' => $info,
    );
}

function rok( &$data = NULL, $msg = '', &$info = NULL ) {
    return vresult( 'ok', $data, $msg, $info );
}

function rerr( $msg = '', &$info = NULL ) {
    return vresult( 'err', $msg, $info );
}

function rdata( &$data, $msg = '', &$info = NULL ) {
    return rok( $data, $msg, $info );
}


?>
