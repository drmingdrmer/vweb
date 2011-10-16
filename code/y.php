<?
session_start();

include_once( $_SERVER["DOCUMENT_ROOT"] . "/acc.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/t.class.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/t/weibo_util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mobilizer/mob.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/vd/vdlib.php" );


function dd( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function dinfo( $msg ) {
    echo "$msg<br/>\n";
    ob_flush();
    flush();
}

function doit() {

    $acc = new Account();
    $acc->redirect = "y.php";

    if ( $acc->use_sess() && $acc->t_to_sess() ) {
        $acctoken = $acc->acctoken;

        $c = new T( $acctoken );

        $vdisk = new MyVDisk();
        $r = login( $vdisk, 'drdr.xp@gmail.com', '748748' );

        $favs = $c->_load_cmd( 'favorites', array(), NULL, NULL );
        // TODO check error
        $favs = $favs[ 'data' ];

        dinfo( "OK: Loaded favorites: " . count( $favs ) . " entries" );

        foreach ($favs as $fav) {

            save_tweet_links_to_vdisk( $vdisk, $fav );

            if ( isset( $fav[ 'retweeted_status' ] ) ) {
                save_tweet_links_to_vdisk( $vdisk, $fav[ 'retweeted_status' ] );
            }

        }
    }
    else {
        $acc->start_auth();
    }


}

function save_tweet_links_to_vdisk( &$vdisk, $t ) {

    $text = $t[ 'text' ];
    dd( '<hr />' );
    dinfo( "Processing:" );
    dinfo( "$text" );

    $urls = extract_urls( $text );
    dinfo( "OK: Extracted " . count( $urls ) . " urls" );

    foreach ($urls as $url) {
        // if ( $url == "http://t.cn/asaazB" ) {
            dinfo( "Fetching url: $url" );
            $r = vd_save_url( $vdisk, $url );
        // }
    }
}


function vd_save_url( &$vdisk, $url ) {

    $mob = new InstaMobilizer( $url );
    if ( ! $mob->mobilize() ) {
        dinfo( "Error: Fetching $url" );
        dinfo( "httpCode:" . $mob->httpCode );
        dinfo( "error:" . $mob->error );
        foreach ($mob->responseHeaders as $h=>$v) {
            dinfo( "$h: $v" );
        }
        return;
    }

    dinfo( "OK: fetched: $url" );

    $title = $mob->title;
    $url = $mob->realurl;

    $nowdate = date( "Y_m_d" );
    $nowtime = date( "His");

    $path = "/V2V/$nowdate/$title.$nowtime.html";
    dinfo( "Saving: to $path" );

    $r = putfile( $vdisk, $path, $mob->content );
    if ( $r[ 'err_code' ] == 0 ) {
        dinfo( "OK: saved at vdisk.weibo.com: $path" );
    }
    else {
        dinfo( "Failed: while saving $path" );
    }
}

doit();

?>
