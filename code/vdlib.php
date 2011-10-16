<?php


include_once( 'config.php' );
// include_once( 'lib/vDisk.class.php' );
include_once( 'lib/vdex.php' );
include_once( 'util.php' );

class MyVDisk extends vDisk {
    private $_pathcache = array();


    public function __construct($app_key, $app_secret) {
        parent::__construct( $app_key, $app_secret );
        $this->_pathcache = array( '/'=>0 );
    }

    public function get_dirid_with_path( $path ) {


        if ( isset($this->_pathcache[ $path ]) ) {
            return array(
                'err_code'=>0,
                'data' => array( 'id'=>$this->_pathcache[ $path ], )
            );
        }

        $r = parent::get_dirid_with_path( $path );

        if ( $r && $r[ 'err_code' ] == 0 ) {
            $dirid = $r[ 'data' ][ 'id' ];
            $this->_pathcache[ $path ] = $dirid;
        }

        return $r;
    }

    public function mkdir_p( $path, $dir_id = 0 ) {

        if ( startsWith( $path, '/' ) ) {
            $path = substr( $path, 1 );
            $dir_id = 0;
        }

        $elts = explode( "/", $path );
        $path = "";

        foreach ($elts as $e) {

            $path = "$path/$e";

            $r = $this->get_dirid_with_path( $path );
            if ( $r && $r[ 'err_code' ] == 0 ) {
                $dir_id = $r[ 'data' ][ 'id' ];
                continue;
            }

            $r = $this->create_dir( $e, $dir_id );
            if ( $r && $r[ 'err_code' ] == 0 ) {
                $dir_id = $r[ 'data' ][ 'dir_id' ];
                $this->_pathcache[ $path ] = $dir_id;
                continue;
            }
            else {
                return $r;
            }
        }

        // fix key name issue
        if ( ! isset( $r[ 'data' ][ 'id' ] ) ) {
            $r[ 'data' ][ 'id' ] = $r[ 'data' ][ 'dir_id' ];
        }
        return $r;
    }
}

function keeptoken( &$vdisk ) {
    $r = $vdisk->keep_token( $_SESSION[ 'vd_token' ] );

    /*
     * Expected error $r:
     * Array
     * (
     *     [err_code] => 702
     *     [err_msg] => invalid token:fff
     * )
     */

    if ( $r && $r[ 'err_code' ] == 0 ) {
        resmsg( "ok", "成功" );
    }
    else {
        resmsg( "invalid_token", $r[ 'err_msg' ] );
    }

}
function get_fid( &$vdisk, $path ) {
    $elts = explode( "/", $path );
    $fn = array_pop( $elts );
    $dir = implode( "/", $elts );


    $r = $vdisk->get_dirid_with_path( $dir );

    if ( $r && $r[ 'err_code' ] == 0 ) {

        $dirid = $r[ 'data' ][ 'id' ];

        // TODO page size and page number is not supported by vdisk sdk
        $r = $vdisk->get_list( $dirid );
        if ( $r && $r[ 'err_code' ] == 0 ) {
            $lst = $r[ 'data' ];
            for ( $i = 0; $i < count( $lst ); $i++ ) {
                if ( $lst[ $i ][ 'name' ] == $fn ) {
                    return $lst[ $i ][ 'id' ];
                }
            }
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }

    return false;
}
function login( &$vdisk, $username, $password ) {

    $r = $vdisk->get_token($username, $password, 'sinat');

    /*
     * Expected $r:
     * Array
     * (
     *     [err_code] => 0
     *     [err_msg] => success
     *     [data] => Array
     *         (
     *             [token] => 173ed052044c6031e248471aa85617d4
     *             [uid] => 102
     *             [time] => 1295959811
     *             [is_active] => 1
     *             [appkey] => 202032
     *         )
     *
     * )
     *
     */

    if ( $r && $r[ 'err_code' ] == 0 ) {
        $_SESSION['vd_token'] = $vdisk->token;
        return true;
    }
    else {
        return false;
    }
}


function putfile( &$vdisk, $path, &$fdata ) {

    if ( $path[ 0 ] != "/" ) {
        $path = "/" . $path;
    }

    $elts = explode( "/", $path );
    $fn = array_pop( $elts );
    $parent = implode( '/', $elts );

    echo "put file at $parent<br/>\n";

    $r = $vdisk->mkdir_p( $parent );
    if ( $r && $r[ 'err_code' ] == 0 ) {
        $dir_id = $r[ 'data' ][ 'id' ];
    }
    else {
        return $r;
    }

    $localTail = rand() . "__tmp__";


    /*
     * NOTE: SAE_TMP_PATH does not support sub-dir
     */
    $localfn = SAE_TMP_PATH . $localTail;

    $r = file_put_contents( $localfn, $fdata );
    if ( !$r ) {
        echo "{\"rst\" : \"fail\", \"msg\" : \"不能保存本地临时文件:'$localfn'\"}";
        echo "data lennth=".strlen( $fdata );
        var_dump( $r );
        exit();
    }



    $r = $vdisk->upload_file( $localfn, $dir_id, 'yes' );
    if ( $r && $r[ 'err_code' ] == 0 ) {

        $fid = $r[ 'data' ][ 'fid' ];

        $r = $vdisk->move_file( $fid, $dir_id, $fn );

        if ( $r && $r[ 'err_code' ] == 0 ) {
            unlink( $localfn );
            return array( 'err_code' => 0 );
        }
        else {
            var_dump( $r );
            return array( 'err_code' => 1 );

            // existed. delete it first
            $oldfid = get_fid( $vdisk, $path );
            if ( $oldfid ) {
                $r = $vdisk->delete_file( $oldfid );
                if ( $r && $r[ 'err_code' ] == 0 ) {
                }
            }


            $r = $vdisk->move_file( $fid, $dirid, $fn );

            if ( $r && $r[ 'err_code' ] == 0 ) {
                unlink( $localfn );
                resjson( array(
                    "rst" => "ok",
                    "path" => "$path",
                    "fid" => "{$r['data']['fid']}",
                    "msg" => "成功保存到$path"
                ) );
            }
            else {
                unlink( $localfn );
                resmsg( "move", "{$r['err_msg']} 动作:重命名fid:'$fid'到'$fn'" );
            }

        }
    }
    else if ( $r && $r[ 'err_code' ] == 702 ) {
        // invalid token
        // NOTE: actually vdisk SDK does not return 702, but False!
        // Thus following statement will never be executed.
        unlink( $localfn );
        resmsg( "invalid_token", "请重新登录" );

    }
    else {
        unlink( $localfn );
        resmsg( "upload", "{$r['err_msg']} 动作:上传'$localfn'到'$parent'" );
    }

}
function listdir( &$vdisk, $relpath ) {
    if ( $_GET[ 'dirid' ] ) {
        $dirid = $_GET[ 'dirid' ];
    }
    else {
        $path = "$relpath";

        $r = $vdisk->get_dirid_with_path( $path );
        !$r && resmsg( "invalid_path", "非法路径:$path" );
        if ( $r[ 'err_code' ] != 0 ) {
            // TODO no such dir
            resmsg( "ok", array() );
        }

        $dirid = $r[ 'data' ][ 'id' ];
    }

    $r = $vdisk->get_list( $dirid );
    !$r && resmsg( "list", "列目录失败" );

    if ( $r[ 'err_code' ] != 0 ) {
        resmsg( "list", $r[ 'err_msg' ] );
    }

    return array( "rst" => "ok", "data" => $r[ 'data' ] );
}
function load( &$vdisk ) {
    $fid = $_GET[ 'fid' ];
    if ( $fid ) {
        $r = $vdisk->get_file_info( $fid );
        if ( $r && $r[ 'err_code' ] == 0 ) {
            $cont = file_get_contents( $r[ 'data' ][ 'url' ] );
            resjson( array( "rst" => "ok", "html" => $cont ) );
        }
        else {
            resmsg( "get_file_info", "取得文件信息失败" );
        }
    }
}

function move_file( &$vdisk, $fid, $desc ) {

}




?>
