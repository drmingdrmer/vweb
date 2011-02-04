<? 

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );

header('Content-Type:text/html; charset=utf-8');

$c = new SaeTClient( WB_AKEY , WB_SKEY , $_SESSION['last_key']['oauth_token'] , $_SESSION['last_key']['oauth_token_secret']  );
$ms  = $c->show_user( null ); // done

var_dump( $ms );

?>
