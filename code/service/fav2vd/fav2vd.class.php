<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mysql/mysql.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/all.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/filetype.php" );

class VDPath {

    public $root;
    public $pref;

    function __construct( $root, $pref ) {
        $this->root = $root;
        $this->pref = $pref;
    }

    function path( $title, $ext ) {

        $title = VD::filename_normalize( firstline( $title ) );

        // $nowdate = date( "Y年_m月_d" );
        $nowdate = date( "Y年_m月" );
        $nowtime = date( "His");

        $fn = "$title.$nowtime.$ext";

        $path = "/{$this->root}/{$this->pref}_{$nowdate}/$fn";

        return $path;
    }
}

class TweetProcessor {

    public $t;
    public $context;
    public $path_gen;
    public $service;

    function __construct( &$t, &$context ) {
        $this->t = $t;
        $this->context = $context;
        $this->path_gen = $context[ 'path_gen' ];
        $this->service = $context[ 'service' ];
    }

    function process() {}
}

class tweet_TweetProcessor extends TweetProcessor {

    function process() {

        // TODO by sha1

        $t = $this->t;

        if ( $t->isRetweeted ) {
            // retweeted has been included in parent tweet
            return true;
        }

        $text = $t->text;

        $html = <<<EOT
<style>
* { font-family: sans-serif; font-size:16px; }
img { max-width:100%; }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=no; minimum-scale=1.0; maximum-scale=1.0;" />
<p><a href='{$t->userurl}'>{$t->username}</a>: {$t->richtext}</p>
EOT;

        dok( print_r( $t->tweet, true ) );

        $url = $t->tweet[ 'bmiddle_pic' ];

        if ( $t->subTweet ) {
            $tsub = $t->subTweet->text;
            $html .= "<p style='margin-left:20px'><a href='{$t->subTweet->userurl}'>{$t->subTweet->username}</a>: {$t->subTweet->richtext}</p>";

            $url = $t->subTweet->tweet[ 'bmiddle_pic' ];
        }


        if ( $url ) {

            $m = new ImgFetcher( $this->context );
            if ( $m->fetch( $url ) ) {

                $meta = $m->meta;
                $content = $m->content;

                $con = data_uri( $content, $meta[ 'mimetype' ] );
                $html .= "<p><img src='$con' /></p>";
            }
            else {
                return false;
            }
        }

        $path = $this->context[ 'path_gen' ][ 'tweet' ]->path( $text . $tsub, 'html' );

        return $this->service[ 'vd' ]->putfile( $path, $html );
    }
}

class img_TweetProcessor extends TweetProcessor {

    function process() {
        $t = $this->t;

        $m = new ImgFetcher( $this->context );
        $url = $t->tweet[ 'bmiddle_pic' ];

        // TODO use sha1

        dinfo( "Execute img: $url on tweet: {$t->tweet[ 'id' ]}" );

        if ( $m->fetch( $url ) ) {

            $meta = $m->meta;
            $content = $m->content;

            $path = $this->context[ 'path_gen' ][ 'img' ]->path(
                    $t->text,  get_ext( $meta[ 'mimetype' ] ) );

            return $this->context[ 'service' ][ 'vd' ]->putfile( $path, $content );
        }

        return false;
    }
}

class link_TweetProcessor extends TweetProcessor {

    function process() {

        $urls = $this->t->urls;

        dinfo( "Execute link: " . implode( ' ', $urls ) );

        foreach ($urls as $url) {
            $r = $this->save_url( $url );
            // TODO error handling
        }

        return true;
    }

    function save_url( $url ) {

        dd( "Saving: $url" );

        $mob = new InstaMobilizer( $url, $this->context );

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
            return false;
        }

        dok( "Mobilized: $url" );

        $title = $mob->meta[ 'title' ];
        $path = $this->context[ 'path_gen' ][ 'link' ]->path( $title, 'html' );

        $r = $this->context[ 'service' ][ 'vd' ]->putfile( $path, $mob->content );
        $r[ 'mob' ] = $mob;

        return $r;
    }

    function save_url_sha1( $url ) {

        if ( ! $this->context[ 'conf' ][ 'sha1_allowed' ] ) {
            dd( "Upload by sha1 is not allowed" );
            return false;
        }

        $meta = $this->context[ 'cache' ]->meta->read( $url );
        if ( $meta === false ) {
            return false;
        }

        $title = $meta[ 'title' ];

        // TODO abstract access is better
        $pagemeta = $this->context[ 'service' ][ 's2page' ]->read_meta( md5( $url ) );
        dd( "pagemeta: " . print_r( $pagemeta, true ) );

        if ( $pagemeta === false ) {
            return false;
        }

        $sha1 = $pagemeta[ 'Content-SHA1' ];

        $path = $this->context[ 'path_gen' ][ 'link' ]->path( $title, 'html' );

        return $this->context[ 'service' ][ 'vd' ]->putfile_by_sha1( $path, $sha1 );
    }
}


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

    private $path_gen;

    function __construct( &$t, &$vd ) {
        $this->t = $t;
        $this->vd = $vd;

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


        /*
         * TODO move t and vd to context
         * TODO add conf to context
         */
        $path = array(
            'root' => '微盘收藏',
            'tweet' => '原文',
            'img' => '图片',
            'link' => '外部链接文章',
        );

        $this->path_gen = array();

        foreach ($path as $p=>$n) {
            if ( $p != 'root' ) {
                $this->path_gen[ $p ] = new VDPath( $path[ 'root' ], $n );
            }
        }

        $this->context = array(
            'path_gen' => $this->path_gen,
            'cache' => $this->cache,

            'service' => array(
                't' => $this->t,
                'vd' => $this->vd,
                's2page'=>$this->s2page,
                's2img'=>$this->s2img,
            ),

            'conf' => array(
                'sha1_allowed' => true,
            ),
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

            $t = new Tweet( $fav );

            $r = $this->process_tweet( $t );

            if ( $t->subTweet ) {
                $r = $this->process_tweet( $t->subTweet );
            }
        }
    }

    function process_tweet( &$t ) {

        dinfo( "policy: " . print_r( $this->policy, true ) );

        foreach ( $this->policy as $expStr=>$acts ) {

            if ( $t->is_satisfied( $expStr ) ) {

                $acts = explode( ' ', $acts );
                foreach ($acts as $a) {

                    $p = "{$a}_TweetProcessor";
                    $proc = new $p( $t, $this->context );

                    $r = $proc->process();

                    if ( ! $r ) {
                        // TODO handle error message
                        return false;
                    }
                }
            }
        }
        return true;
    }
}


?>
