<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


class Fav2VD {
    public $t;
    public $vd;

    public $cache;

    public $policy = array(
        'img' => 'img',
        'video' => "ignore end",
        'music' => "music remove end",
        'img -links' => "img remove end",
        '*' => "links",
    );

    function __construct( &$t, &$vd, $todo = NULL ) {
        $this->t = $t;
        $this->vd = $vd;
        $this->cache = array(
            'imgs'  => new StoVisitor( new Img() ),
            'pages' => new StoVisitor( new Page() ),
            'meta'  => new MetaVisitor(),
        );
    }

    function dump() {

        $r = $this->t->_load_cmd( 'favorites', array(), NULL, NULL );
        // TODO check error
        $favs = $r[ 'data' ];

        dinfo( "OK: Loaded favorites: " . count( $favs ) . " entries" );

        foreach ($favs as $fav) {
            $this->save_tweet_urls( $fav );

            if ( isset( $fav[ 'retweeted_status' ] ) ) {
                $this->save_tweet_urls( $fav[ 'retweeted_status' ] );
            }

        }

    }

    function save_tweet_urls( $tweet ) {

        dinfo( "fav=" . print_r( $tweet, true ) );
        $text = $tweet[ 'text' ];
        dd( '<hr />' );
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

        $mob = new InstaMobilizer( $url, $this->cache );
        if ( ! $mob->mobilize() ) {
            dinfo( "Error: Fetching $url" );
            dinfo( "httpCode:" . $mob->httpCode );
            dinfo( "error:" . $mob->error );
            foreach ($mob->responseHeaders as $h=>$v) {
                dinfo( "$h: $v" );
            }
            return;
        }

        dinfo( "OK: mobilized: $url" );


        $title = $mob->title;
        $url = $mob->realurl;

        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $path = "/V2V/$nowdate/$title.$nowtime.html";
        dinfo( "Saving: to $path" );

        $r = $this->vd->putfile( $path, $mob->content );

        // exit();

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

?>