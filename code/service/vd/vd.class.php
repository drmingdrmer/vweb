<?php


include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/vdex.php" );

class VD extends vDisk {

    private $_pathcache;
    public $token;


    public function __construct() {
        parent::__construct( VWEB_VD_KEY, VWEB_VD_SEC );
        $this->_pathcache = array( '/'=>0 );
    }

    function fix_path( $path ) {
        if ( $path[ 0 ] != "/" ) {
            $path = "/" . $path;
        }
        return $path;
    }

    function login( $username, $password ) {

        $r = $this->get_token($username, $password, 'sinat');

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

        return $r;
    }

    function write_local_tmp( &$cont ) {
        $localTail = rand() . "__tmp__";
        /*
         * NOTE: SAE_TMP_PATH does not support sub-dir
         */
        $localfn = SAE_TMP_PATH . $localTail;

        $r = file_put_contents( $localfn, $cont );
        if ( $r ) {
            dinfo( "Written temp file: $localfn" );
            return array( 'err_code'=>0, 'data'=>array( 'filename'=>$localfn ) );
        }
        else {
            $msg = "Failed writing temp file: $localfn size=" . strlen( $cont );
            derror( $msg );
            return array( 'err_code'=>'write_local_tmp', 'msg'=>$msg );
        }
    }

    function putfile_by_sha1( $path, $sha1 ) {

        $path = $this->fix_path( $path );

        dinfo( "VD path=$path" );

        $parent = dirname( $path );
        $fn = substr( $path, strlen( $parent ) + 1 );


        $r = $this->mkdir_p( $parent );
        if ( ! isok( $r ) ) {
            return $r;
        }

        $dir_id = $r[ 'data' ][ 'id' ];

        $r = $this->upload_with_sha1( $fn, $sha1, $dir_id );
        dd( "Upload with sha1: " . print_r( $r, true ) );

        if ( isok( $r ) ) {
            dok( "Uploaded with sha1: $path" );
        }
        else {
            dd( "Failed uploaded with sha1: $path" );
        }

        return $r;
    }

    function putfile( $path, &$fdata ) {

        $path = $this->fix_path( $path );

        dinfo( "VD path=$path" );

        $parent = dirname( $path );
        $fn = substr( $path, strlen( $parent ) + 1 );


        $r = $this->mkdir_p( $parent );
        if ( ! isok( $r ) ) {
            return $r;
        }

        $dir_id = $r[ 'data' ][ 'id' ];

        $sha1 = hash( 'sha1', $fdata );
        $r = $this->upload_with_sha1( $fn, $sha1, $dir_id );
        dd( "Upload with sha1: " . print_r( $r, true ) );

        if ( isok( $r ) ) {
            dok( "Uploaded with sha1: $path" );
            return $r;
        }


        $r = $this->write_local_tmp( $fdata );
        if ( ! isok( $r ) ) {
            return $r;
        }

        $localfn = $r[ 'data' ][ 'filename' ];

        $r = $this->upload_file( $localfn, $dir_id, 'yes' );

        if ( isok( $r ) ) {

            $fid = $r[ 'data' ][ 'fid' ];
            dinfo( "fid: $fid" );

            $r = $this->move_file( $fid, $dir_id, $fn );
            if ( isok( $r ) ) {
                dok( "Uploaded to $path" );
            }
        }

        if ( ! isok( $r ) ) {
            derror( "Failed uploading to $path" );
        }

        unlink( $localfn );
        return $r;
    }

/*
 *     public function get_dirid_with_path( $path ) {
 * 
 *         if ( isset($this->_pathcache[ $path ]) ) {
 *             return array(
 *                 'err_code'=>0,
 *                 'data' => array( 'id'=>$this->_pathcache[ $path ], )
 *             );
 *         }
 * 
 *         $r = parent::get_dirid_with_path( $path );
 * 
 *         if ( $r && $r[ 'err_code' ] == 0 ) {
 *             $dirid = $r[ 'data' ][ 'id' ];
 *             $this->_pathcache[ $path ] = $dirid;
 *         }
 * 
 *         return $r;
 *     }
 */

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
            dd( print_r( $r, true ) );
            if ( isok( $r ) ) {
                dd( "got $path at dir_id: " . $r[ 'data' ][ 'id' ]  );
                $dir_id = $r[ 'data' ][ 'id' ];
                continue;
            }

            $r = $this->create_dir( $e, $dir_id );
            if ( isok( $r ) ) {
                $dir_id = $r[ 'data' ][ 'dir_id' ];
                $this->_pathcache[ $path ] = $dir_id;
                continue;
            }
            else {
                derror( "Failed creating dir $e at dir_id=$dir_id" );
                return $r;
            }
        }

        // fix key name issue
        if ( ! isset( $r[ 'data' ][ 'id' ] ) ) {
            $r[ 'data' ][ 'id' ] = $r[ 'data' ][ 'dir_id' ];
        }

        dinfo( "Dir created: $path dir_id={$r[ 'data' ][ 'id' ]}" );
        return $r;
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

?>
