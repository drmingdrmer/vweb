<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/simple_html_dom.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mobilizer/fetcher.class.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/service/mobilizer/htmlproc.class.php" );


class Mobilizer {

    public $url;

    public $error;
    public $meta;
    public $content;

    public $fetcher;
    public $processors;

    protected $cache;

    function __construct( $url, &$context = NULL ) {
        $this->url = $url;
        $this->context = $context;
    }

    function mobilize() {

        $this->fetcher = new PageFetcher( $this->processors, $this->context );

        if ( $this->fetcher->fetch( $this->url ) === false ) {
            $this->error = $this->fetcher->error;
            return false;
        }

        $this->meta = $this->fetcher->meta;
        $this->content = $this->fetcher->content;

        return true;
    }
}

class InstaMobilizer extends Mobilizer {
    public $processors = array(
        HtmlEnc,
        InstaHtmlPageMeta,
        HtmlCleaner,
        HtmlLinkConverter,
        HtmlImgEmbed,
        HtmlMajorArticle,
        HtmlFinalizer,
    );
}

class DirectMobilizer extends Mobilizer
{
    function fetch() {

        $url = $this->url;

        while ( true ) {

            $old = $url;

            $f = no_redirect_fetch();
            $cont = $this->content = $f->fetch( $url );
            $code = $this->httpCode = $f->httpCode();
            $headers = $this->responseHeaders = $f->responseHeaders();

            if ( $code == "200" ) {
                break;
            }
            else if ( $code == "302" ) {
                $url = $this->url_redirect( $old, $headers[ 'Location' ] );
            }
            else {
                $this->error = "fetch";
                $this->responseHeaders = $f->responseHeaders();
                derror( "Fetching Error: $code " . print_r( $headers, true ) );
                return false;
            }
        }

        return true;
    }

    function url_redirect( $old, $url ) {
        $u = parse_url( $old );
        $pre = $u[ 'scheme' ] + '://' + $u[ 'host' ] + ':' + $u[ 'port' ];

        if ( startsWith( $url, '/' ) ) {
            $url = $pre + $url;
        }
        else if ( startsWith( $url, 'http://' ) or startsWith( $url, 'https://' ) ) {
            /* nothing to do */
        }
        else {
            $base = explode( '/', $u[ 'path' ] );
            array_pop( $base );
            $url = $pre + implode( '/', $base ) + '/' + $url;
        }
        return $url;
    }
}
?>
