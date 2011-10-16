<?
session_start();

include_once( 'config.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'vdlib.php' );
include_once( 'mob.php' );


function doit() {

    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $_SESSION['last_key']['oauth_token'],
        $_SESSION['last_key']['oauth_token_secret']  );

    $vdisk = new MyVDisk(VWEB_VD_KEY, VWEB_VD_SEC);
    $r = login( $vdisk, 'drdr.xp@gmail.com', '748748' );

    $favs = $c->_load_cmd( 'favorites', array(), NULL, NULL );
    $favs = $favs[ 'data' ];


    foreach ($favs as $fav) {

        save_tweet_links_to_vdisk( $vdisk, $fav );

        if ( isset( $fav[ 'retweeted_status' ] ) ) {
            save_tweet_links_to_vdisk( $vdisk, $fav[ 'retweeted_status' ] );
        }

    }
}

function save_tweet_links_to_vdisk( &$vdisk, $t ) {
    $text = $t[ 'text' ];

    echo "$text<br />\n";
    $urls = extract_urls( $text );

    var_dump( $urls );
    echo "<br/>\n";
    echo "<br/>\n";

    foreach ($urls as $url) {
        if ( $url == "http://t.cn/asaazB" ) {
            echo "$url<br/>\n";
            $r = vd_save( $vdisk, $url );
        }
    }
}


function vd_save( &$vdisk, $url ) {

    $entry = mob_insta( $url );
    if ( $entry[ 'err_code' ] != 0 ) {
        echo "error fetching $url<br/>\n";
        return;
    }

    $title = $entry[ 'title' ];
    $url = $entry[ 'url' ];

    $nowdate = date( "Y_m_d" );
    $nowtime = date( "His");
    $path = "/V2V/$nowdate/$title.$nowtime.html";
    echo $path . "<br/>\n";
    $r = putfile( $vdisk, $path, $entry[ 'html' ] );

    // echo $entry[ 'html' ];
    // exit();
}

doit();

?>
