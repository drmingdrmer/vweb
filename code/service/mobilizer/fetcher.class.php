<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class CachedFetcher {

    public $context;
    public $cacheNameSpace = 'page';

    public $metaCache;
    public $contCache;


    public $meta;
    public $content;

    public $hasMetaCache;
    public $hasContentCache;

    public $error;

    function __construct( &$context ) {
        $this->context = $context;
        $this->metaCache = $context[ 'cache' ]->meta;

        $ns = $this->cacheNameSpace;
        dd( "$ns" );
        $this->contCache = $context[ 'cache' ]->$ns;
    }

    function set( &$m = NULL, &$c = NULL ) {
        $this->meta = $m;
        $this->content = $c;
    }

    function new_fetch( $redirect = false ) {
        $f = new SaeFetchurl();
        $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
        $f->setAllowRedirect( $redirect );
        return $f;
    }

    function fetch( $key ) {

        $this->set();
        $this->hasMetaCache = false;
        $this->hasContentCache = false;

        if ( $this->try_read_cache( $key ) !== false ) {
            return array( 'meta'=>$this->meta, 'content'=>$this->content );
        }

        dd( "Fetching: $key" );

        $r = $this->do_fetch( $key );
        if ( $r !== false ) {
            $this->try_write_cache( $key );
        }

        return $r;
    }

    function try_read_cache( $key ) {

        if ( $this->metaCache ) {

            $rmeta = $this->metaCache->read( $key );
            $this->hasMetaCache = $rmeta !== false;

            if ( $rmeta && $this->contCache ) {

                $rcont = $this->contCache->read( $key );
                $this->hasContentCache = $rcont !== false;

                if ( $rmeta && $cont ) {

                    dinfo( "Read from cache: $key: " . print_r( $rmeta ) );

                    $this->set( $rmeta, $rcont );
                    return true;
                }
            }
        }

        return false;
    }

    function try_write_cache( $key ) {

        if ( $this->meta && $this->content
                && $this->contCache
                && ( ! $this->hasContentCache ) ) {

            $rw = $this->contCache->write( $key, $this->content );
            dd( "write content cache result: " . print_r( $rw, true ) );

            if ( $rw && $this->metaCache && ( ! $this->hasMetaCache ) ) {
                $rm = $this->metaCache->write( $key, $this->meta );
                dd( "write meta cache result: " . print_r( $rm, true ) );
            }
        }
    }

    function do_fetch( $key ) {
        return false;
    }
}

class ImgFetcher extends CachedFetcher {

    public $cacheNameSpace = 'img';

    function do_fetch( $url ) {

        $f = $this->new_fetch();
        $cont = $f->fetch( $url );

        if ( $f->httpCode() == "200" ) {

            $headers = $f->responseHeaders();

            $mt = $headers[ 'Content-Type' ];
            dinfo( "Fetched: $url mimetyp: $mt" );

            $this->meta = array( 'mimetype'=>$mt );
            $this->content = $cont;

            dok( "Img Fetched: $url" );
            return true;
        }
        else {
            derror( "Fetching image:$url httpCode=" . $f->httpCode()  );
            return false;
        }
    }
}

class PageFetcher extends CachedFetcher {

    public $cacheNameSpace = 'page';

    public $processors;

    public $fetcher;
    public $httpCode;
    public $responseHeaders;

    private $url;

    function __construct( $processors, &$context ) {
        parent::__construct( $context );

        $this->processors = $processors;
    }

    function http_fetch( $url ) {
        $f = $this->fetcher = $this->new_fetch();

        $url = urlencode( $url );
        $this->content = $f->fetch( "http://www.instapaper.com/m?u=$url" );
        $this->httpCode = $f->httpCode();
        $this->responseHeaders = $f->responseHeaders();
    }

    function do_fetch( $url ) {

        $this->url = $url;
        $this->http_fetch( $url );

        if ( $this->httpCode == "200" ) {

            dok( "Page fetched: $url" );

            $r = $this->process_response();
            if ( $r ) {
            }
            return $r;
        }
        else {
            derror( "Failed fetching page: $url httpCode=" . $this->httpCode  );
            return false;
        }
    }

    function process_response() {

        $f = $this->fetcher;

        $meta = $this->meta = array(
            'mimetype'=>$this->responseHeaders[ 'Content-Type' ] );

        dinfo( "Success fetched: $url mimetyp: {$meta[ 'mimetype' ]}" );

        if ( $this->processors ) {

            foreach ($this->processors as $pro) {
                $p = new $pro( $meta, $this->content, $this->context );
                $r = $p->process();
                if ( $r ) {
                    $meta = $this->meta = $p->meta;
                    $cont = $this->content = $p->content;
                }
                else {
                    $this->error = $p->error;
                    return false;
                }
            }
        }

        dok( "Processed: {$this->url}" );
        return true;
    }
}

?>
