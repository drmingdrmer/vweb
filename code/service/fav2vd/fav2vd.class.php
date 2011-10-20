<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


class Cache {
    public $page;
    public $img;
    public $meta;
    function __construct( $p, $i, $m ) {
        $this->page = $p;
        $this->img = $i;
        $this->meta = $m;
    }
}

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

    function __construct( &$t, &$vd, $only = NULL ) {
        $this->t = $t;
        $this->vd = $vd;
        $this->only = $only;

        $localpage = new LocalPage();
        $page = new EngineVisitor( $localpage, new EngineVisitor( new Page() ) );

        $localimg = new LocalImg();
        $img = new EngineVisitor( $localimg, new EngineVisitor( new Img() ) );

        $meta = new MetaVisitor();

        $this->cache = new Cache( $page, $img, $meta );
        dd( "this.cache.meta" );
        dd( print_r( $this->cache->meta, true ) );
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

        /*
         * if ( $tweet[ 'id' ] != '10741346747' ) {
         *     return;
         * }
         */

        // dinfo( "fav=" . print_r( $tweet, true ) );

        $text = $tweet[ 'text' ];

        dd( '<hr />' );
        dinfo( "favorite: $text" );

        $urls = T::extract_urls( $text );

        dinfo( "OK: Extracted " . count( $urls ) . " urls" );

        foreach ($urls as $url) {
            // if ( $url == "http://t.cn/heIjkx" ) {
                dinfo( "Processing: $url" );
                $r = $this->save_url( $url );
                // exit();
            // }
        }

    }
    function save_url( $url ) {

        if ( $this->only ) {
            if ( $this->only == $url ) {
                $mob = new InstaMobilizer( $url );
            }
            else {
                return;
            }
        }
        else {
            $mob = new InstaMobilizer( $url, $this->cache );
        }

        if ( ! $mob->mobilize() ) {
            dinfo( "Error: Processing: $url" );
            dinfo( "httpCode: " . $mob->httpCode );
            dinfo( "Error: " . $mob->error );
            foreach ($mob->responseHeaders as $h=>$v) {
                dinfo( "$h: $v" );
            }
            return;
        }

        dinfo( "Mobilized: $url" );


        $title = $mob->title;
        $url = $mob->realurl;

        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $path = "/V2V/$nowdate/$title.$nowtime.html";

        $r = $this->vd->putfile( $path, $mob->content );

        /*
         * echo $mob->content;
         * exit();
         */

        return $r;
    }

}

?>
