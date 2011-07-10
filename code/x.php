<?

session_start();
include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );
include_once( 'img.php' );

header('Content-Type:text/html; charset=utf-8');
$s = new SaeStorage();


$flist = array(
    'albumtmpl.css',
    'plugin/scrollto/jquery.scrollTo.js',
    'album.js',
);

foreach ($flist as $f) {
    $r = $s->write( 'pub', $f, file_get_contents( $f ) );
    echo "write file: $f : $r <br />";
}


?>
