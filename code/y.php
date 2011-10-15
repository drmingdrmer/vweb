<?
session_start();

include_once( 'mob.php' );
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'vdlib.php' );



$c = new MySaeTClient( WB_AKEY, WB_SKEY,
    $_SESSION['last_key']['oauth_token'],
    $_SESSION['last_key']['oauth_token_secret']  );


$favs = $c->_load_cmd( 'favorites', array(), NULL, NULL );
$favs = $favs[ 'data' ];

foreach ($favs as $fav) {
    $text = $fav[ 'text' ];
    $parts = explode( " ", $text );
    foreach ($parts as $p) {
        if ( startsWith( $p, 'http://' ) ) {
            $url = $p;
            break;
        }
    }

}


$con = byfetch( $url );

?><a href="<?=$url?>"><?=$url?></a><?
// echo $con;


$vdisk = new MyVDisk(VWEB_VD_KEY, VWEB_VD_SEC);

$r = login( $vdisk, 'drdr.xp@gmail.com', '748748' );
echo $r;
var_dump( $r );
echo "<br />";

$path = '/yy/aa/bb/cc/xp4.html';
$r = putfile( $vdisk, $path, $con );
echo $r;
var_dump( $r );
echo "<br />";

/*
 * $r = listdir( $vdisk, '/' );
 * echo $r;
 * var_dump( $r );
 * echo "<br />";
 */





?>
