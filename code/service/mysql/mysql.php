<?


include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


class MyRaw extends SaeMysql{

    public $my;

    // public $sql;

    function __destruct() {
        $this->closeDb();
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
                $cond .= "AND `$k`='" . $this->escape( $v ) . "'";
            }
        }

        $cond = substr( $cond, 4 );

        $sql = "SELECT * FROM `$table` where $cond LIMIT 1" ;

        dd( "sql for 'one' is $sql" );

        $r = $this->select( $sql );
        if ( $this->hasdata( $r ) ) {
            return $r[ 0 ];
        }
        else {
            return false;
        }
    }

    function select( $sql ) {
        return $this->getData( $sql );
    }

    function update( $sql ) {
        dd( $sql );
        $this->runSql( $sql );
        return $this->errno() === 0;
    }

    function delete( $sql ) {
        $this->runSql( $sql );
        return $this->errno() === 0;
    }

    function insert( $sql ) {
        $this->runSql( $sql );
        return $this->errno() === 0;
    }

    function hasdata( $d ) {
        return count( $d ) > 0;
    }

    function isok() {
        return $this->errno() === 0;
    }

    function affected() {
        return $this->affectedRows() > 0;
    }

    function escape_array( $arr ) {
        $r = array();
        foreach ($arr as $k=>$v) {
            $r[ $k ] = $this->escape( $v );
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
                $v = $this->escape( $v );
                $vs .= ", '$v'";
            }
            else {
                $vs .= ", NULL";
            }
        }

        $ks = substr( $ks, 2 );
        $vs = substr( $vs, 2 );

        return "( $ks ) VALUES ( $vs )";
    }
}

class My extends MyRaw {

    public $table = '';

    function add( &$arr ) {
        $sql = "INSERT INTO `{$this->table}` " . $this->sql_values( $arr );
        return parent::insert( $sql );
    }

    function byid( $id ) {
        $id = intval( $id );
        $arr = array( "{$this->table}id"=>$id );
        return parent::one( $this->table, $arr );
    }

    function add_ondup_inc( &$arr, $counter ) {
        $sql = "INSERT INTO `{$this->table}` " . $this->sql_values( $arr ) . " ON DUPLICATE KEY UPDATE `$counter`=`$counter`+1";
        return parent::insert( $sql );
    }

    function update_byid( $id, $key, $val ) {
        $id = intval( $id );
        $key = $this->escape( $key );
        $val = $this->escape( $val );
        $sql = "UPDATE `{$this->table}` SET `$key`='$val' WHERE `{$this->table}id`=$id";
        return parent::update( $sql );
    }

    function col_access( $id, $col, $formatter, $val = NULL ) {

        $id = intval( $id );

        if ( NULL === $val ) {

            $r = $this->byid( $id );
            if ( $r ) {
                dd( "col returned:" . print_r( $r, true ) );
                if ( $formatter !== NULL ) {
                    return $formatter::dec( $r[ $col ] );
                }
                else {
                    return $r[ $col ];
                }
            }
            else {
                return false;
            }
        }
        else {
            if ( NULL !== $formatter ) {
                $val = $formatter::enc( $val );
            }
            return parent::update_byid( $id, $col, $val );
        }
    }
}

class MyUser extends My {

    public $table = 'user';

    function users_need_check( $n = 10 ) {
        $n = intval( $n );
        $sql = "SELECT userid FROM `{$this->table}` WHERE `nextActionTime`<NOW() LIMIT $n";
        return parent::select( $sql );
    }

    function t_acctoken( $id, &$acctoken = NULL ) {
        $id = intval( $id );

        if ( $acctoken === NULL ) {
            $r = $this->byid( $id );
            if ( $r ) {
                return $this->unserialize_token( $r[ 't_acctoken' ] );
            }
            else {
                return false;
            }
        }
        else {
            $tok = $this->serialize_token( $acctoken );
            $sql = "UPDATE `user` SET `t_acctoken`='$tok' WHERE `userid`=$id";
            return parent::update( $sql );
        }
    }


    function favPolicy( $id, $pol = NULL ) {
        return $this->col_access( $id, 'favPolicy', Json, $pol );
    }

    function vdacc( $id, $vdacc = NULL ) {
        if ( NULL === $vdacc ) {
            $r = $this->byid( $id );
            if ( $r ) {
                return explode( ':', $r[ 'vdacc' ], 2 );
            }
            else {
                return false;
            }
        }
        else {
            if ( gettype( $vdacc ) == 'array' ) {
                $vdacc = implode( ':', $vdacc );
            }
            return parent::update_byid( $id, 'vdacc', $vdacc );
        }
    }

    function serialize_token( &$tok ) {
        $t = "{$tok[ 'oauth_token' ]}:{$tok[ 'oauth_token_secret' ]}";
        return $t;
    }

    function unserialize_token( $tok ) {
        $tok = explode( ':', $tok );
        return array( 'oauth_token'=>$tok[ 0 ], 'oauth_token_secret'=>$tok[ 1 ] );
    }
}

class MyPage extends My {
    public $table = 'page';

    function add( $arr ) {
        return parent::add_ondup_inc( $arr, 'count' );
    }

    function del( $url ) {
        $sql = "DELETE FROM `{$this->table}` where `url`='" . $this->escape( $url ) . "'";
        return $this->delete( $sql );
    }

    function flush() {
        $sql = "DELETE FROM `{$this->table}`";
        return $this->delete( $sql );
    }

    function get( $url ) {
        $arr = array( 'url'=>$url );
        return $this->one( 'page', $arr );
    }
}

class Log extends My {

    public $table = 'fav2vdlog';

    function add( $userid, $level, $text ) {
        $arr = array(
            'userid'=>$userid,
            'level'=>$level,
            'text'=>$text,
        );
        return parent::add( $arr );
    }

    function select( $userid ) {
        $sql = "SELECT * FROM `{$this->table}` where `userid`=$userid";
        return parent::select( $sql );
    }
}



?>
