<?

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

function add_user( &$user ) {

    $mysql = new SaeMysql();

    $sql = "INSERT INTO `user` " . sql_values( $mysql, $user );
    var_dump( $sql );
    $mysql->runSql( $sql );

    if( $mysql->errno() != 0 )
    {
        $r = array( 'err_code'=>$mysql->errno(), 'msg'=>$mysql->errmsg() );
    }
    else {
        $r = array( 'err_code'=>0, 'last_id'=>$mysql->lastId() );
    }

    $mysql->closeDb();

    return $r;
}

function _update( $sql ) {
    $mysql = new SaeMysql();

    $mysql->runSql( $sql );

    if( $mysql->errno() != 0 )
    {
        $r = array( 'err_code'=>$mysql->errno(), 'msg'=>$mysql->errmsg() );
    }
    else {
        $r = array( 'err_code'=>0, 'affected_rows'=>$mysql->affectedRows() );
    }

    $mysql->closeDb();

    return $r;
}

function serialize_token( &$tok ) {
    $t = "{$tok[ 'oauth_token' ]}:{$tok[ 'oauth_token_secret' ]}";
    return $t;
}

function unserialize_token( $tok ) {
    $tok = explode( ':', $tok );
    return array( 'oauth_token'=>$tok[ 0 ], 'oauth_token_secret'=>$tok[ 1 ] );
}

function update_t_acctoken( $id, &$acctoken ) {
    $id = intval( $id );
    $tok = serialize_token( $acctoken );
    $sql = "UPDATE `user` SET `t_acctoken`='$tok' WHERE `id`=$id";
    return _update( $sql );
}

function _get1( $table, &$arr ) {

    $mysql = new SaeMysql();

    $id = $arr[ 'id' ];
    $id = intval( $id );

    $sql = "SELECT * FROM `$table` where `id`=" . $id . " LIMIT 1" ;
    $data = $mysql->getData( $sql );


    if ( $mysql->errno() == 0 && count( $data ) == 1 ) {
        $r = array( 'err_code'=>0, 'data'=>$data );
    }
    else {
        $r = array( 'err_code'=>$mysql->errno(), 'msg'=>$mysql->errmsg() );
    }

    $mysql->closeDb();

    return $r;
}

function get_user( $id ) {

    $u = array( 'id'=>intval( $id ) );
    $r = _get1( 'user', $u );
    if ( $r[ 'err_code' ] == 0 ) {
        $r[ 'data' ][ 0 ][ 't_acctoken' ] = unserialize_token( $r[ 'data' ][ 0 ][ 't_acctoken' ] );
    }

    return $r;
}

function sql_values( &$mysql, $arr ) {
    $ks = "";
    $vs = "";
    foreach ($arr as $k=>$v) {

        $ks .= ", `$k`";

        if ( gettype( $v ) == 'integer' ) {
            $vs .= ", $v";
        }
        else if ( gettype( $v ) == 'string' ) {
            $v = $mysql->escape( $v );
            $vs .= ", '$v'";
        }
    }

    $ks = substr( $ks, 2 );
    $vs = substr( $vs, 2 );

    return "( $ks ) VALUES ( $vs )";
}

function escape_array( &$mysql, $arr ) {
    $r = array();
    foreach ($arr as $k=>$v) {
        $r[ $k ] = $mysql->escape( $v );
    }

    return $r;
}
?>
