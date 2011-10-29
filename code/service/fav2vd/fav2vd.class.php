<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

class Fav2VD {
    public static $defaultPolicy = array(
        'img' => 'img',
        'links' => "links",
    );


    public $t;
    public $vd;

    public $s2page;
    public $s2img;

    public $cache;
    public $context;

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

        $this->context = array(
            'sha1_allowed' => true,
            'cache' => $this->cache,
        );
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

        $t = new Tweet( $tweet );

        dinfo( "policy: " . print_r( $this->policy, true ) );

        foreach ( $this->policy as $expStr=>$acts ) {

            if ( $t->is_satisfied( $expStr ) ) {

                $acts = explode( ' ', $acts );
                foreach ($acts as $a) {

                    $meth = "execute_$a";
                    if ( ! $this->$meth( $t ) ) {
                        return;
                    }
                }
            }
        }
    }

    function execute_img( &$t ) {

        $m = new ImgFetcher( $this->context );
        $url = $t->tweet[ 'bmiddle_pic' ];

        // TODO use sha1

        dinfo( "Execute img: $url on tweet: {$t->tweet[ 'id' ]}" );

        if ( $m->fetch( $url ) ) {

            $meta = $m->meta;
            $content = $m->content;

            $nowdate = date( "Y_m_d" );
            $nowtime = date( "His");

            $fn = vdname_normallize( firstline( $t->text ) );
            $fn .= ".$nowtime." . get_ext( $meta[ 'mimetype' ] );

            $path = "/V2V/photo_$nowdate/$fn";

            return $this->vd->putfile( $path, $content );
        }

        return false;
    }

    function execute_links( &$t ) {

        $urls = $t->urls;
        dinfo( "Execute links: " . implode( ' ', $urls ) );

        foreach ($urls as $url) {
            $r = $this->save_url( $url );
        }

        return true;
    }

    function save_url( $url ) {

        dd( "Saving: $url" );

        if ( $this->only ) {
            if ( $this->only == $url ) {
                $mob = new InstaMobilizer( $url );
            }
            else {
                return;
            }
        }
        else {
            $mob = new InstaMobilizer( $url, $this->context );
        }


        if ( $this->save_url_sha1( $url ) !== false ) {
            return true;
        }


        if ( ! $mob->mobilize() ) {
            derror( "Error: Processing: $url" );
            derror( "httpCode: " . $mob->fetcher->httpCode );
            derror( "Error: " . $mob->error );
            foreach ($mob->fetcher->responseHeaders as $h=>$v) {
                derror( "$h: $v" );
            }
            return;
        }

        dok( "Mobilized: $url" );

        $title = $mob->meta[ 'title' ];
        $path = $this->link_path( $title );

        $r = $this->vd->putfile( $path, $mob->content );
        $r[ 'mob' ] = $mob;

        return $r;
    }

    function save_url_sha1( $url ) {

        if ( ! $this->context[ 'sha1_allowed' ] ) {
            dd( "Upload by sha1 is not allowed" );
            return false;
        }

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
        return false;
    }

    function link_path( $title ) {
        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $path = "/V2V/article_$nowdate/$title.$nowtime.html";
        return $path;
    }

}

?>
