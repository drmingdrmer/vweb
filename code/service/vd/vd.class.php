<?php


include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/vdex.php" );

class VD extends vDisk {
    static public function filename_normalize( $name ) {
        // convert invalid char to _
        // $name = preg_replace( '/[@#><\/:?*\\ \-_"]+/', '_', $name );
        $name = mb_ereg_replace( '[@#><\/:?*\\ \-_"]+', '_', $name );

        // strip leading and trailing _
        $name = mb_ereg_replace( '^_|_$', '', $name );

        return $name;
    }

    private $_pathcache;
    private $_flistcache;
    public $token;


    public function __construct() {
        parent::__construct( VWEB_VD_KEY, VWEB_VD_SEC );
        $this->_pathcache = array( '/'=>0 );
        $this->_flistcache = array();
    }

    function fix_path( $path ) {
        if ( $path[ 0 ] != "/" ) {
            $path = "/" . $path;
        }
        return $path;
    }

    function sess_save() {
        if ( $this->token ) {
            $_SESSION[ 'vdtoken' ] = $this->token;
        }
    }

    function login( $username, $password ) {

        $r = $this->get_token($username, $password, 'sinat');
        if ( isok( $r ) ) {
            $this->token = $r[ 'data' ][ 'token' ];
            return true;
        }
        else {
            return false;
        }

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
            return $localfn;
        }
        else {
            $msg = "Failed writing temp file: $localfn size=" . strlen( $cont );
            derror( $msg );
            return false;
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
            $this->_update_flistcache( $dir_id );
            dok( "SHA1 uploaded: $path" );
            return $r;
        }
        else {
            dd( "Failure SHA1 upload: $path" );
            return false;
        }
    }

    function putfile( $path, &$fdata ) {

        $path = $this->fix_path( $path );
        $parent = dirname( $path );
        $fn = substr( $path, strlen( $parent ) + 1 );


        $r = $this->mkdir_p( $parent );
        if ( ! isok( $r ) ) {
            return $r;
        }
        $dir_id = $r[ 'data' ][ 'id' ];
        dok( "Dir created with id:$dir_id, for $parent" );

        $f = $this->get_f( $dir_id, $fn );
        if ( $f ) {
            if ( $f[ 'sha1' ] == $sha1 ) {
                dok( "File existed: $path, $sha1" );
                return true;
            }
            else {
                $fn = $this->get_valid_fn( $dir_id, $fn );
            }
        }


        $sha1 = hash( 'sha1', $fdata );
        if ( $this->putfile_by_sha1( $path, $sha1 ) ) {
            return true;
        }

        dinfo( "VD path=$path" );




        if ( ! ( $localfn = $this->write_local_tmp( $fdata ) ) ) {
            return false;
        }

        $r = $this->upload_file( $localfn, $dir_id, 'yes' );

        if ( isok( $r ) ) {

            $fid = $r[ 'data' ][ 'fid' ];
            dinfo( "fid: $fid" );


            $r = $this->move_file( $fid, $dir_id, $fn );
            if ( isok( $r ) ) {
                $this->_update_flistcache( $dir_id );
                dok( "Upload(moveed) to $path" );
            }
            else {
                derror( "Failure to move $localfn to $fn in dir: $dir_id" );
                derror( "Result:" . print_r( $r, true ) );
                derror( "errno=" . $this->errno() );
                derror( "error=" . $this->error() );
                return false;
            }
        }
        else {
            derror( "Failure to upload $localfn, at dir: $dir_id" );
            derror( "Result:" . print_r( $r, true ) );
        }


        unlink( $localfn );
        return $r;
    }

    public function get_valid_fn( $dir_id, $fn ) {

        for ( $i = 0; $i < 100; $i++ ) {

            $f = $this->get_f( $dir_id, $fn );
            if ( $f ) {
                $parts = explode( ".", $fn );
                if ( count( $parts ) > 1 ) {
                    $ext = array_pop( $parts );
                    $parts[ count( $parts ) - 1 ] .= "_";
                    $fn = implode( '.', $parts ) . ".$ext";
                }
                else {
                    $fn .= "_";
                }
            }
            else {
                dinfo( "Found a valid fn: $fn" );
                return $fn;
            }
        }

        return false;
    }

    public function get_dirid_with_path( $path ) {

        if ( isset($this->_pathcache[ $path ]) ) {
            return array(
                'err_code'=>0,
                'data' => array( 'id'=>$this->_pathcache[ $path ], )
            );
        }

        $r = parent::get_dirid_with_path( $path );

        dd( print_r( $r, true ) );

        if ( $r && $r[ 'err_code' ] == 0 ) {
            $dirid = $r[ 'data' ][ 'id' ];
            $this->_pathcache[ $path ] = $dirid;

            dd( "got $path at dir_id: " . $dirid  );
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
            if ( isok( $r ) ) {
                $dir_id = $r[ 'data' ][ 'id' ];
                continue;
            }

            $r = $this->create_dir( $e, $dir_id );
            if ( isok( $r ) ) {
                $dir_id = $r[ 'data' ][ 'dir_id' ];
                dd( "Dir created: $path $dir_id" );
                $this->_pathcache[ $path ] = $dir_id;
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

    function get_f( $dir_id, $fn ) {
        if ( isset( $this->_flistcache[ $dir_id ] ) ) {
            return $this->_flistcache[ $dir_id ][ $fn ];
        }

        if ( $this->_get_list( $dir_id ) ) {
            return $this->_flistcache[ $dir_id ][ $fn ];
        }
        return false;
    }

    function _get_list( $dir_id ) {
        $flist = parent::get_list( $dir_id );
        if ( $flist ) {
            $this->_flistcache[ $dir_id ] = array();
            foreach ($flist as $e) {
                $this->_flistcache[ $dir_id ][ $e[ 'name' ] ] = $e;
            }
        }

        return $flist;
    }

    function _update_flistcache( $dir_id ) {
        unset( $this->_flistcache[ $dir_id ] );
        $this->_get_list( $dir_id );
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
