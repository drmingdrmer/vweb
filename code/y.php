<?
session_start();

include_once( 'config.php' );
include_once( 'util.php' );
include_once( 'weibo_util.php' );
include_once( 'vdlib.php' );
include_once( 'mob.php' );


function dd( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function doit() {

    $c = new MySaeTClient( WB_AKEY, WB_SKEY,
        $_SESSION['last_key']['oauth_token'],
        $_SESSION['last_key']['oauth_token_secret']  );

    $vdisk = new MyVDisk(VWEB_VD_KEY, VWEB_VD_SEC);
    $r = login( $vdisk, 'drdr.xp@gmail.com', '748748' );

    $favs = $c->_load_cmd( 'favorites', array(), NULL, NULL );
    $favs = $favs[ 'data' ];

    dd( "OK: Loaded favorites: " . count( $favs ) . " entries" );

    foreach ($favs as $fav) {

        save_tweet_links_to_vdisk( $vdisk, $fav );

        if ( isset( $fav[ 'retweeted_status' ] ) ) {
            save_tweet_links_to_vdisk( $vdisk, $fav[ 'retweeted_status' ] );
        }

    }
}

function save_tweet_links_to_vdisk( &$vdisk, $t ) {

    $text = $t[ 'text' ];
    dd( "Processing:" );
    dd( "$text" );

    $urls = extract_urls( $text );
    dd( "OK: Extracted " . count( $urls ) . " urls" );

    foreach ($urls as $url) {
        if ( $url == "http://t.cn/asaazB" ) {
            dd( "Fetching url: $url" );
            $r = vd_save_url( $vdisk, $url );
        }
    }
}


function vd_save_url( &$vdisk, $url ) {

    $mob = new InstaMobilizer( $url );
    if ( ! $mob->mobilize() ) {
        dd( "Error: Fetching $url" );
        // TODO more message
        return;
    }

    dd( "OK: fetched: $url" );

    $title = $mob->title;
    $url = $mob->realurl;

    $nowdate = date( "Y_m_d" );
    $nowtime = date( "His");

    $path = "/V2V/$nowdate/$title.$nowtime.html";
    dd( "Saving: to $path" );

    $r = putfile( $vdisk, $path, $mob->content );
    if ( $r[ 'err_code' ] == 0 ) {
        dd( "OK: saved at vdisk.weibo.com: $path" );
    }
    else {
        dd( "Failed: while saving $path" );
    }
}

doit();

?>