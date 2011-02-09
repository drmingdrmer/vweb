<?php

session_start();

include_once( 'config.php' );
include_once( 'lib/vDisk.class.php' );
include_once( 'util.php' );


function keeptoken( &$vdisk ) {
    $r = $vdisk->keep_token( $_SESSION[ 'token' ] );

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

$verb = $_SERVER[ 'REQUEST_METHOD' ];

if ( $verb == "POST" ) {

    $username = $_POST[ 'username' ];
    $password = $_POST[ 'password' ];

    $vdisk = new vDisk(VWEB_VD_KEY, VWEB_VD_SEC);

    $r = $vdisk->get_token($username, $password);

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
        $_SESSION['token'] = $vdisk->token;
        echo "{\"rst\" : \"ok\"}";
    }
    else {
        echo "{\"rst\" : \"fail\", \"msg\" : \"" . $r[ 'err_msg' ] . "\"}";
    }
}
else if ( $verb == "GET" ) {

    $act = $_GET[ 'act' ];

    if ( $act == "keeptoken" ) {

        $vdisk = new vDisk(VWEB_VD_KEY, VWEB_VD_SEC);
        keeptoken( $vdisk );
    }
}
else if ( $verb == "PUT" ) {


    $path = $_REQUEST[ 'path' ];

    if ( !$path ) {
        resmsg( "invalid_path", "不合法路径:'$path'" );
    }

    if ( $path[ 0 ] != "/" ) {
        $path = "/" . $path;
    }

    $elts = explode( "/", $path );
    $fn = array_pop( $elts );
    $parent = implode( "/", $elts );

    $localTail = rand() . "__tmp__";


    /*
     * NOTE: SAE_TMP_PATH does not support sub-dir
     */
    $localfn = SAE_TMP_PATH . $localTail;
    $fdata = file_get_contents("php://input");
    if ( !$fdata ) {
        echo "{\"rst\" : \"empty_body\", \"msg\" : \"不能保存空文件\"}";
        exit();
    }


    $r = file_put_contents( $localfn, $fdata );
    if ( !$r ) {
        echo "{\"rst\" : \"fail\", \"msg\" : \"不能保存本地临时文件:'$localfn'\"}";
        exit();
    }



    $vdisk = new vDisk(VWEB_VD_KEY, VWEB_VD_SEC);

    // $r = $vdisk->get_token( "drdr.xp@gmail.com", "123qwe" );
    // TODO 
    $vdisk->keep_token( $_SESSION[ 'token' ] )
        || resmsg( "invalid_token", "请重新登录" );

    $r = $vdisk->upload_file( $localfn, $parent, 'yes' );
    if ( $r && $r[ 'err_code' ] == 0 ) {

        $fid = $r[ 'data' ][ 'fid' ];
        $dirid = $r[ 'data' ][ 'dir_id' ];

        $r = $vdisk->move_file( $fid, $dirid, $fn );

        if ( $r && $r[ 'err_code' ] == 0 ) {
            resjson( array(
                "rst" => "ok",
                "path" => "$path",
                "fid" => "{$r['data']['fid']}", 
                "msg" => "成功保存到$path"
            ) );
        }
        else {
            // existed. delete it first
            $oldfid = get_fid( $vdisk, $path );
            if ( $oldfid ) {
                $r = $vdisk->delete_file( $oldfid );
                if ( $r && $r[ 'err_code' ] == 0 ) {
                }
            }


            $r = $vdisk->move_file( $fid, $dirid, $fn );

            if ( $r && $r[ 'err_code' ] == 0 ) {
                resjson( array(
                    "rst" => "ok",
                    "path" => "$path",
                    "fid" => "{$r['data']['fid']}", 
                    "msg" => "成功保存到$path"
                ) );
            }
            else {
                resmsg( "move", "{$r['err_msg']} 动作:重命名fid:'$fid'到'$fn'" );
            }

        }
    }
    else if ( $r && $r[ 'err_code' ] == 702 ) {
        // invalid token
        resmsg( "invalid_token", "请重新登录" );
        
    }
    else {
        resmsg( "upload", "{$r['err_msg']} 动作:上传'$localfn'到'$parent'" );
    }

    $r = unlink( $localfn );
}

function move_file( &$vdisk, $fid, $desc ) {
    
}



// $r = $vdisk->upload_share_file('文件.txt', 0);
// $r = $vdisk->get_list(0);
// $r = $vdisk->get_quota();
// $r = $vdisk->upload_with_md5('测试.pdf', '03d5717869bb075e3bad73b527fabc8a');
// $r = $vdisk->get_file_info(219379);
// $r = $vdisk->create_dir('测试一下');
// $r = $vdisk->delete_dir(35647);
// $r = $vdisk->delete_file(123);
// $r = $vdisk->copy_file(219379, 0, '副本.txt');
// $r = $vdisk->move_file(219379, 0, '副本.txt');
// $r = $vdisk->rename_file(219379, '新的新的新的.z');
// $r = $vdisk->rename_dir(3929, '新的新的新的');
// $r = $vdisk->move_dir(3929, "我的图片们", 0);

?>
