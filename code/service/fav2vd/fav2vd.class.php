<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

class Fav2VD {
    public $t;
    public $vd;

    public $cache;

    public $policy = array(
        'img' => 'img',
        'links' => "links",
        // 'video' => "ignore end",
        // 'music' => "music remove end",
        // 'img -links' => "img remove end",
    );

    function __construct( &$t, &$vd, $only = NULL ) {
        $this->t = $t;
        $this->vd = $vd;
        $this->only = $only;

        $page = new EngineVisitor( new LocalPage(),
                                   new EngineVisitor(
                                       new Page() ) );

        $img = new EngineVisitor( new LocalImg(),
                                  new EngineVisitor(
                                      new Img() ) );

        $meta = new EngineVisitor( new Mem(),
                                   new EngineVisitor(
                                      new Meta() ) );

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

            $this->process_tweet( $fav );

            if ( isset( $fav[ 'retweeted_status' ] ) ) {
                $this->process_tweet( $fav[ 'retweeted_status' ] );
            }
        }
    }

    function process_tweet( &$tweet ) {

        // dinfo( "fav=" . print_r( $tweet, true ) );

        $text = $tweet[ 'text' ];
        $urls = $tweet[ '_urls' ] = T::extract_urls( $text );

        $props = array(
            'img' => isset( $tweet[ 'bmiddle_pic' ] ),
            'links' => count( $urls ) > 0,
            'any' => true,
        );
        $presen = "";
        foreach ($props as $n=>$v) {
            if ( $v ) {
                $presen .= " $n";
            }
            $props[ "-$n" ] = !$v;
        }

        dinfo( "props: $presen" );
        dinfo( "policy: " . print_r( $this->policy, true ) );


        foreach ( $this->policy as $expStr=>$acts ) {

            if ( ! $this->is_satisfied( $props, $expStr ) ) {
                dinfo( "Expectance failed: $expStr" );
                continue;
            }

            $acts = explode( ' ', $acts );
            foreach ($acts as $a) {
                dinfo( "Execute action: $a on tweet id={$tweet[ 'id' ]}" );

                $meth = "execute_$a";
                if ( ! $this->$meth( $tweet ) ) {
                    return;
                }
            }
        }
    }

    function is_satisfied( &$props, $expStr ) {

        $expected = explode( ' ', $expStr );

        foreach ($expected as $exp) {

            if ( ! $props[ $exp ] ) {
                dd( "Expectance not satisified: $exp" );
                return false;
            }
        }

        dinfo( "Satisfied: " . $expStr );
        return true;
    }

    function execute_img( &$tweet ) {

        dd( "execute_img on tweet: " . print_r( $tweet, true ) );

        $m = new ImgFetcher( $this->cache );
        $url = $tweet[ 'bmiddle_pic' ];

        dd( "Image url: $url" );

        $r = $m->fetch( $url );
        if ( $r[ 'meta' ][ 'mimetype' ] ) {

            $nowdate = date( "Y_m_d" );
            $nowtime = date( "His");

            dd( "{$r[ 'meta' ][ 'mimetype' ]}" );
            $fn = firstline( $tweet[ 'text' ] ) . ".$nowtime." . get_ext( $r[ 'meta' ][ 'mimetype' ] );
            $path = "/V2V/photo_$nowdate/$fn";

            $r = $this->vd->putfile( $path, $r[ 'content' ] );
        }
        else {
            // TODO error
        }
    }

    function execute_links( &$tweet ) {

        $text = $tweet[ 'text' ];
        $urls = $tweet[ '_urls' ];

        dd( '<hr />' );
        dinfo( "favorite: $text" );


        dinfo( "OK: Extracted " . count( $urls ) . " urls" );

        foreach ($urls as $url) {
            // if ( $url == "http://t.cn/heIjkx" ) {
                dinfo( "Processing: $url" );
                $r = $this->save_url( $url );
                // exit();
            // }
        }

        return true;
    }

    function save_url( $url ) {

        // bmiddle_pic

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
            derror( "Error: Processing: $url" );
            derror( "httpCode: " . $mob->httpCode );
            derror( "Error: " . $mob->error );
            foreach ($mob->responseHeaders as $h=>$v) {
                derror( "$h: $v" );
            }
            return;
        }

        dinfo( "Mobilized: $url" );


        $title = $mob->title;
        $url = $mob->realurl;

        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $path = "/V2V/article_$nowdate/$title.$nowtime.html";

        $r = $this->vd->putfile( $path, $mob->content );

        /*
         * echo $mob->content;
         * exit();
         */

        $r[ 'mob' ] = $mob;
        return $r;
    }

}

?>
