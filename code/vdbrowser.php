<?

session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

// TODO config log-to-db


    /*
     * $myuser = new MyUser();
     * $needToCheck = $myuser->users_need_check( 10 );
     * foreach ($needToCheck as $user) {
     *     $uid = $user[ 'userid' ];
     *     $r = dump_user( $uid );
     * }
     */

function ln( $msg ) {
    echo "$msg<br/>\n";
}
$acc = new Account();

if ( $acc->use_sess() ) {

    if ( $acc->vd_login() ) {
        $vd = $acc->vd;
        $dir_id = 0;

        $fs = $vd->get_list( $dir_id );
        $fs = $fs[ 'data' ];
        foreach ($fs as $f) {
            // $sig => hash_hmac('sha256', "account={$username}&appkey={$this->appkey}&password={$password}&time={$time}", $this->appsecret, false)
            $url = "";
            $line = <<<EOT
<a target="_blank" href="{$url}">{$f[ 'name' ]}</a>
EOT;
            ln( $line );

            dd( print_r( $f, true ) );
        }
    }
    else {
        derror( "vd login error" );
    }
}
else {
    $acc->start_auth();
}

/*
 * function list_users_need_dump() {
 * }
 * 
 * 
 * function main() {
 *     list_users_need_dump();
 * }
 * 
 * main();
 */

?>
