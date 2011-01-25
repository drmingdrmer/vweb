<?php

session_start();

include_once( 'config.php' );
include_once( 'vDisk.class.php' );

$username = 'drdr.xp@gmail.com';
$password = '123qwe';


$vdisk = new vDisk(VWEB_VD_KEY, VWEB_VD_SEC);

$r = $vdisk->get_token($username, $password);

$_SESSION['token'] = $vdisk->token;

echo $vdisk->token;

$r = $vdisk->keep_token();


// $r = $vdisk->upload_share_file('文件.txt', 0);
$r = $vdisk->get_list(0);
// $r = $vdisk->get_quota();
// $r = $vdisk->upload_with_md5('测试.pdf', '03d5717869bb075e3bad73b527fabc8a');
// $r = $vdisk->get_file_info(219379);
// $r = $vdisk->create_dir('测试一下');
// $r = $vdisk->delete_dir(35647);
// $r = $vdisk->delete_file(123);
// $r = $vdisk->copy_file(219379, 0, '副本.txt');
// $r = $vdisk->move_file(219379, 0, '副本.txt');
// $r = $vdisk->rename_file(219379, '新的新的新的.z');
// $r = $vdisk->rename_dir(3929, '新的新的新的');
// $r = $vdisk->move_dir(3929, "我的图片们", 0);

print_r( $r );

?>
