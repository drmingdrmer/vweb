<?

include_once( 'saet.ex.class.php' );

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


function gen_app_rst( $rst, $okmsg, $info = NULL ) {
    if ( $rst ) {
        if ( ! $rst[ 'error_code' ] ) {
            $ret = rok( $rst, $okmsg, $info );
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

class T extends SaeTClient
{
    public $cmd;
    public $r;

    function __construct( &$acctoken )
    {
        parent::__construct( WB_AKEY, WB_SKEY,
            $acctoken['oauth_token'],
            $acctoken['oauth_token_secret'] );
    }

    function me() {
        return $this->_cmd( 'account/verify_credentials' );
    }

    // TODO rename it to _get_cmd because it handles only "GET" request. "POST" request hasnt been tested.
    function _load_cmd( $cmd, $p, $okmsg='No Message', $info=NULL ) {
        $this->_cmd( $cmd, $p );
        return gen_app_rst( $this->r, $okmsg, $info );
    }

    function _cmd( $cmd, $p = NULL ) {

        $this->cmd = $cmd;
        $this->r = NULL;

        if ( $p === NULL ) {
            $p = array();
        }
        def( $p, 'page', 1 );
        def( $p, 'count', 20 );

        // $url = "http://api.weibo.com/2/$cmd.json";
        $url = "http://api.t.sina.com.cn/$cmd.json";

        $rst = $this->r = $this->oauth->get($url , $p );
        dd( "cmd=$cmd, rst=" . print_r( $rst, true ) );
        if ( $rst ) {
            return $rst;
        }
        else {
            return false;
        }
    }
}
?>
