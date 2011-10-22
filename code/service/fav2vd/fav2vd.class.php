<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

class Fav2VD {
    public $t;
    public $vd;

    public $s2page;
    public $s2img;
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
        $this->s2page = new Page();
        $this->s2img = new Img();

        $page = new MD5EngineVisitor( new LocalPage(),
                                      new EngineVisitor(
                                          $this->s2page ) );

        $img = new MD5EngineVisitor( new LocalImg(),
                                     new EngineVisitor(
                                         $this->s2img ) );

        $meta = new MD5EngineVisitor( new Mem(),
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


        $text = $tweet[ 'text' ];
        $urls = $tweet[ '_urls' ] = T::extract_urls( $text );

        dinfo( "process tweet: " . $tweet[ 'id' ] . " $text" );

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
                continue;
            }


            $acts = explode( ' ', $acts );
            foreach ($acts as $a) {

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

        dd( "Satisfied: " . $expStr );
        return true;
    }

    function execute_img( &$tweet ) {

        $m = new ImgFetcher( $this->cache );
        $url = $tweet[ 'bmiddle_pic' ];

        dinfo( "Execute img: $url on tweet: {$tweet[ 'id' ]}" );

        $r = $m->fetch( $url );
        if ( $r[ 'meta' ][ 'mimetype' ] ) {

            $nowdate = date( "Y_m_d" );
            $nowtime = date( "His");

            $fn = vdname_normallize( firstline( $tweet[ 'text' ] ) );
            $fn .= ".$nowtime." . get_ext( $r[ 'meta' ][ 'mimetype' ] );

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

        dinfo( "Execute links: " . implode( ' ', $urls ) );

        foreach ($urls as $url) {
            dinfo( "Processing: $url" );
            $r = $this->save_url( $url );
        }

        return true;
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
        $r[ 'mob' ] = $mob;


        $meta = $this->cache->meta->read( $url );
        if ( $meta !== false ) {

            $title = $meta[ 'title' ];

            $pagemeta = $this->s2page->read_meta( md5( $url ) );
            dd( "pagemeta: " . print_r( $pagemeta, true ) );

            if ( $pagemeta !== false ) {

                $sha1 = $pagemeta[ 'Content-SHA1' ];

                $path = $this->link_path( $title );

                $r = $this->vd->putfile_by_sha1( $path, $sha1 );
                if ( $r !== false ) {
                    return $r;
                }
            }
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

        $title = $mob->meta[ 'title' ];
        $path = $this->link_path( $title );

        $r = $this->vd->putfile( $path, $mob->content );

        return $r;
    }

    function link_path( $title ) {
        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $path = "/V2V/article_$nowdate/$title.$nowtime.html";
        return $path;
    }

}

?>
