<?

include_once( 'config.php' );
include_once( 'saet.ex.class.php' );
include_once( 'util.php' );

header('Content-Type:text/html; charset=utf-8');


$vlbumjsfn = 'js/vlbum.js';
$vlbumjs = array(
    "js/jquery-1.4.4.min.js",
    "js/jquery-ui-1.8.9.custom.min.js",
    "plugin/tmpl/jquery.tmpl.js",
    "js/jquery.form-defaults.js",
    "js/core.js",
    "js/vweb.js",
    "js/vweb.backend.weibo.js",
    "js/vweb.ui.js",
    "js/vweb.ui.appmsg.js",
    "js/vweb.ui.main.js",
    "js/vweb.ui.main.maintool.js",
    "js/vweb.ui.main.edit.js",
    "js/vweb.ui.t.js",
    "js/vweb.ui.t.list.js",
    "js/vweb.ui.t.my.js",
    "js/vweb.ui.t.my.friend.js",
    "js/vweb.ui.t.paging.js",
    "js/vweb.bootstrap.js",
);


$js = '';
foreach ($vlbumjs as $f) {
    $js .= file_get_contents( $f ) . ";\n";
}

file_put_contents( $vlbumjsfn, $js );





$tostorage = array(
    'albumtmpl.css',
    'plugin/scrollto/jquery.scrollTo.js',
    'album.js',
);

$s = new SaeStorage();
foreach ($tostorage as $f) {
    $r = $s->write( 'pub', $f, file_get_contents( $f ) );
    echo "write file: $f : $r <br />";
}


?>
