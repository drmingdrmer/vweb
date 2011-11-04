<?

class MysqlLog {

    static public $userid = 0;

    static public function write( $level, $text ) {

        $mysql = new SaeMysql();
        $table = 'fav2vdlog';

        $uid = intval( MysqlLog::$userid );
        $level = intval( $level );
        $text = $mysql->escape( $text );


        $sql = "INSERT  INTO `$table` ( `userid` , `level` , `text` ) VALUES ( $uid, $level, '$text' ) ";
        $mysql->runSql( $sql );
        if( $mysql->errno() != 0 )
        {
            die( "Error:" . $mysql->errmsg() );
        }

        $mysql->closeDb();
    }

    static public function listlog( $level, $n = 100 ) {

        $mysql = new SaeMysql();
        $table = 'fav2vdlog';

        $uid = intval( MysqlLog::$userid );
        $level = intval( $level );
        $n = intval( $n );

        $sql = "SELECT * FROM `$table` WHERE `userid`=$uid AND `level`<=$level LIMIT $n";
        $data = $mysql->getData( $sql );
        $mysql->closeDb();

        return $data;
    }
}

?>
