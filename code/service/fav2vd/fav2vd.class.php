<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );


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

class Fav2VD {
    public $t;
    public $vd;
    public $pages = Page();
    public $imgs = Img();
    public 

    function __construct( &$t, &$vd, $todo = NULL ) {
        $this->t = $t;
        $this->vd = $vd;
    }

    function dump() {

        $r = $this->t->_load_cmd( 'favorites', array(), NULL, NULL );
        // TODO check error
        $favs = $r[ 'data' ];

        dinfo( "OK: Loaded favorites: " . count( $favs ) . " entries" );

        foreach ($favs as $fav) {
            var_dump( $fav );
            dd( '' );
            exit();

            $this->save_tweet_urls( $fav );

            if ( isset( $fav[ 'retweeted_status' ] ) ) {
                $this->save_tweet_urls( $fav[ 'retweeted_status' ] );
            }

        }

    }

    function save_tweet_urls( $tweet ) {

        $text = $tweet[ 'text' ];
        dd( '<hr />' );
        dinfo( "Processing:" );
        dinfo( "$text" );

        $urls = T::extract_urls( $text );
        dinfo( "OK: Extracted " . count( $urls ) . " urls" );

        foreach ($urls as $url) {
            // if ( $url == "http://t.cn/asaazB" ) {
                dinfo( "Fetching url: $url" );
                $r = $this->save_url( $url );
            // }
        }
    }
    function save_url( $url ) {

        $mob = new InstaMobilizer( $url, new StoVisitor( Page ), new MetaVisitor() );
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

        $r = $this->vd->putfile( $path, $mob->content );
        return $r;
        /*
         * if ( isok( $r ) ) {
         *     dinfo( "OK: saved at vdisk.weibo.com: $path" );
         * }
         * else {
         *     dinfo( "Failed: while saving $path" );
         * }
         */
    }

}

function doit() {

    $acc = new Account();
    $acc->redirect = "y.php";

    if ( $acc->use_sess() && $acc->t_to_sess() ) {
        $acctoken = $acc->acctoken;

        $c = new T( $acctoken );

        $vdisk = new VD();
        $r = $vdisk->login( 'drdr.xp@gmail.com', '748748' );

        $fv = new FV( $c, $vdisk );

        $r = $fv->dump();

    }
    else {
        $acc->start_auth();
    }


}

doit();

?>
