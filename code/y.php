<?
session_start();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>y</title>
    </head>
</html><?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/mysqllog.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/fav2vd/fav2vd.class.php" );


$acc = new Account();

$myuser = new MyUser();
$needToCheck = $myuser->users_need_check( 10 );
foreach ($needToCheck as $user) {
    $uid = $user[ 'userid' ];
    if ( $acc->use_db( $uid ) ) {

        if ( $acc->vd_login() ) {

            $r = $acc->vd->get_dirid_with_path( '/微盘收藏/原文_2011_11_04/电影《惊魂半小时》：讲述了一个极为蹊跷的故事，说的是一个送披萨的小子被两个罪犯劫.014653.html' );

            var_dump( $r );
        }
    }
}

?>
