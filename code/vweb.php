<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/config.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

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


function isok( $r ) {
    if ( ! $r ) {
        return false;
    }

    if ( isset( $r[ 'rst' ] ) ) {
        return $r[ 'rst' ] == 'ok';
    }

    if ( isset( $r[ 'err_code' ] ) ) {
        return $r[ 'err_code' ] === 0;
    }

    if ( isset( $r[ 'error_code' ] ) ) {
        return $r[ 'error_code' ] === 0;
    }

    if ( isset( $r[ 'code' ] ) ) {
        return $r[ 'code' ] === 0;
    }

    echo "unknown $r";
    throw new Exception( 'unrecognized ' . print_r( $r, true ) );

}

function hasdata( $r ) {
    return isok( $r ) && count( $r[ 'data' ] ) > 0;
}

?>
