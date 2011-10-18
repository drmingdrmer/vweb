<? 
include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );

$my = new MyPage();

$my->flush();


$s2 = new S2();

$files = $s2->getList( 'xp' );
$files = $files[ 'f' ];
// var_dump( $files );
foreach ($files as $f) {
    $s2->delete( 'xp', $f );
    echo "removed $f<br/>\n";
}



?>
