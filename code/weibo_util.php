<?
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );

function gen_app_rst( $rst, $okmsg, $info = NULL ) {
    if ( $rst ) {
        if ( ! $rst[ 'error_code' ] ) {
            $ret = array( "rst" => "ok", "info" => $info,
                "msg" => $okmsg, "data" => $rst);
        }
        else {
            $ret = array( "rst" => "unknown", "info" => $info,
                "error_code"=> $rst[ 'error_code' ],
                "msg" => $rst[ 'error' ]);

            if ( $rst[ 'error_code' ] == 400 ) {
                $ret[ 'rst' ] = 'weiboerror';
            }
        }
    }
    else {
        $ret = array( "rst" => "load", "info" => $info,
            "msg" => "微薄接口调用失败");
    }
    return $ret;
}

class MySaeTClient extends SaeTClient
{
    // TODO rename it to _get_cmd because it handles only "GET" request. "POST" request hasnt been tested.
    function _load_cmd( $cmd, $p, $okmsg='No Message', $info=NULL ) {
        def( $p, 'page', 1 );
        def( $p, 'count', 20 );

        $url = "http://api.t.sina.com.cn/$cmd.json";
        $rst = $this->oauth->get($url , $p );
        return gen_app_rst( $rst, $okmsg, $info );
    }
}


?>
