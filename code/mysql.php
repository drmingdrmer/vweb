<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

function serialize_token( &$tok ) {
    $t = "{$tok[ 'oauth_token' ]}:{$tok[ 'oauth_token_secret' ]}";
    return $t;
}

function unserialize_token( $tok ) {
    $tok = explode( ':', $tok );
    return array( 'oauth_token'=>$tok[ 0 ], 'oauth_token_secret'=>$tok[ 1 ] );
}

class MyRaw {
    private $my;

    function __construct() {
        $this->my = new SaeMysql();
    }

    function __destruct() {
        $this->my->closeDb();
    }

    function one( $table, &$arr ) {

        $id = $arr[ 'id' ];
        $id = intval( $id );

        $cond = "";
        foreach ($arr as $k=>$v) {
            if ( gettype( $v ) == 'integer' ) {
                $cond .= "AND `$k`=$v";
            }
            else if ( gettype( $v ) == 'string' ) {
                $cond .= "AND `$k`='" . $this->my->escape( $v ) . "'";
            }
        }

        $cond = substr( $cond, 4 );

        $sql = "SELECT * FROM `$table` where $cond LIMIT 1" ;

        dd( "sql for 'one' is $sql" );

        return $this->select( $sql );
    }

    function select( $sql ) {
        $data = $this->my->getData( $sql );
        dd( "select result set size=" . count( $data ) );
        return $this->isok()
            ? $this->r_select( $data ) : $this->err();
    }

    function update( $sql ) {
        $this->my->runSql( $sql );
        return $this->isok() ? $this->r_update() : $this->err();
    }

    function insert( $sql ) {
        $this->my->runSql( $sql );
        return ( $this->isok() && $this->affected() )
            ? $this->r_insert() : $this->err();
    }

    function isok() {
        return $this->my->errno() === 0;
    }

    function affected() {
        return $this->my->affectedRows() > 0;
    }

    function err() {
        $r = array( 'err_code'=>$this->my->errno(), 'msg'=>$this->my->errmsg() );
        return $r;
    }

    function r_select( &$data ) {
        $r = array( 'err_code'=>0, 'data'=>$data );
        return $r;
    }

    function r_update() {
        $r = array( 'err_code'=>0, 'affected_rows'=>$this->my->affectedRows() );
        return $r;
    }

    function r_insert() {
        $r = array( 'err_code'=>0, 'last_id'=>$this->my->lastId() );
        return $r;
    }

    function escape_array( $arr ) {
        $r = array();
        foreach ($arr as $k=>$v) {
            $r[ $k ] = $this->my->escape( $v );
        }
        return $r;
    }

    function sql_values( $arr ) {
        $ks = "";
        $vs = "";
        foreach ($arr as $k=>$v) {

            $ks .= ", `$k`";

            if ( gettype( $v ) == 'integer' ) {
                $vs .= ", $v";
            }
            else if ( gettype( $v ) == 'string' ) {
                $v = $this->my->escape( $v );
                $vs .= ", '$v'";
            }
        }

        $ks = substr( $ks, 2 );
        $vs = substr( $vs, 2 );

        return "( $ks ) VALUES ( $vs )";
    }

}

class My extends MyRaw {

    function user_add( &$user ) {
        $sql = "INSERT INTO `user` " . $this->sql_values( $user );
        return $this->insert( $sql );
    }

    function user( $id ) {
        $u = array( 'id'=>intval( $id ) );
        $r = $this->one( 'user', $u );

        if ( $this->isok() ) {
            $r[ 'data' ][ 0 ][ 't_acctoken' ] = unserialize_token( $r[ 'data' ][ 0 ][ 't_acctoken' ] );
        }

        return $r;
    }

    function t_acctoken( $id, &$acctoken = NULL ) {
        $id = intval( $id );

        if ( $acctoken === NULL ) {
            $r = $this->user( $id );
            if ( isok( $r ) ) {
                return $r[ 'data' ][ 0 ][ 'acctoken' ];
            }
        }
        else {
            $tok = serialize_token( $acctoken );
            $sql = "UPDATE `user` SET `t_acctoken`='$tok' WHERE `id`=$id";
            return $this->update( $sql );
        }
    }

    function page_add( $url, $title, $realurl ) {
        $sql = "INSERT INTO `page` " . $this->sql_values(
            array(
                'url'=>$url,
                'title'=>$title,
                'realurl'=>$realurl,
            ) );
        return $this->insert( $sql );
    }

    function page_get( $url ) {
        $arr = array( 'url'=>$url );
        return $this->one( 'page', $arr );
    }
}



function sample() {
    $mysql = new SaeMysql();

    $sql = "SELECT * FROM `user` LIMIT 10";
    $data = $mysql->getData( $sql );
    $name = strip_tags( $_REQUEST['name'] );
    $age = intval( $_REQUEST['age'] );

    $sql = "INSERT  INTO `user` ( `name` , `age` , `regtime` ) VALUES ( '"  . $mysql->escape( $name ) . "' , '" . intval( $age ) . "' , NOW() ) ";
    $mysql->runSql( $sql );
    if( $mysql->errno() != 0 )
    {
        die( "Error:" . $mysql->errmsg() );
    }

    $mysql->closeDb();
}
?>
