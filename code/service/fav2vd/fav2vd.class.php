<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mysql/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

class Fav2VD {
    public static $defaultPolicy = array(
        'any' => 'tweet',
        'img' => 'img',
        'link' => "link",
    );


    public $t;
    public $vd;

    public $s2page;
    public $s2img;

    public $cache;
    public $context;

    public $policy;

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

        $this->policy = Fav2VD::$defaultPolicy;

        $this->context = array(
            'sha1_allowed' => true,
            'root_path' => '微盘收藏',
            'tweet_path_pref' => '原文',
            'img_path_pref' => '图片',
            'link_path_pref' => '外部链接文章',
            'cache' => $this->cache,
        );
    }

    function dump() {

        $r = $this->t->_cmd( 'favorites' );
        if ( ! $r ) {
            derror( "Loading favorites Failed, r=" . print_r( $this->t->r ) );
            return false;
        }

        $favs = $r;
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

    function execute_tweet( &$t ) {
        $text = $t->text;

        $html = <<<EOT
<style>
* { font-family: sans-serif; font-size:16px; }
img { max-width:100%; }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=no; minimum-scale=1.0; maximum-scale=1.0;" />
EOT;
        $html .= "<p>$text</p>";

        $url = $t->tweet[ 'bmiddle_pic' ];

        if ( $url ) {

            $m = new ImgFetcher( $this->context );
            if ( $m->fetch( $url ) ) {

                $meta = $m->meta;
                $content = $m->content;

                $con = data_uri( $content, $meta[ 'mimetype' ] );
                $html = $html . "<p><img src='$con' /></p>";
            }
            else {
                return false;
            }
        }

        $path = $this->tweet_path( $text );

        return $this->vd->putfile( $path, $html );
    }

    function execute_img( &$t ) {

        $m = new ImgFetcher( $this->context );
        $url = $t->tweet[ 'bmiddle_pic' ];

        // TODO use sha1

        dinfo( "Execute img: $url on tweet: {$t->tweet[ 'id' ]}" );

        if ( $m->fetch( $url ) ) {

            $meta = $m->meta;
            $content = $m->content;

            $path = $this->img_path( $t->text,  get_ext( $meta[ 'mimetype' ] ) );

            return $this->vd->putfile( $path, $content );
        }

        return false;
    }

    function execute_link( &$t ) {

        $urls = $t->urls;
        dinfo( "Execute link: " . implode( ' ', $urls ) );

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
        return $this->_vd_path( $this->context[ 'link_path_pref' ], $title, 'html' );
    }

    function tweet_path( $title ) {
        return $this->_vd_path( $this->context[ 'tweet_path_pref' ], $title, 'html' );
    }

    function img_path( $title, $ext ) {
        return $this->_vd_path( $this->context[ 'img_path_pref' ], $title, $ext );
    }

    function _vd_path( $pref, $title, $ext ) {

        $title = vdname_normallize( firstline( $title ) );

        $nowdate = date( "Y_m_d" );
        $nowtime = date( "His");

        $fn = "$title.$nowtime.$ext";

        $r = $this->context[ 'root_path' ];
        $path = "/$r/{$pref}_{$nowdate}/$fn";

        return $path;
    }

}

?>
