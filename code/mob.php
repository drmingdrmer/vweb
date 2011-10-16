<?

include_once( "lib/simple_html_dom.php" );
include_once( "util.php" );

class InstaMobilizer
{
    public $url;
    public $realurl;

    public $error;
    public $title;
    public $content;

    private $fetcher;
    public $httpCode;
    public $responseHeaders;

    private $html;


    function __construct( $url ) {
        $this->url = $url;
        $this->realurl = $url;
    }

    function mobilize() {
        if ( !$this->fetch() ) {
            return false;
        }
        if ( !$this->processhtml() ) {
            return false;
        }
        return true;
    }

    function fetch() {
        $f = $this->fetcher = new SaeFetchurl();
        $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );

        $url = urlencode( $this->url );
        $this->content = $f->fetch( "http://www.instapaper.com/m?u=$url" );

        $this->httpCode = $f->httpCode();
        $this->responseHeaders = $f->responseHeaders();

        if ( $this->httpCode != '200' ) {
            return false;
        }

        return true;
    }

    function processhtml() {

        $html = $this->html = new simple_html_dom();

        $html->load( $this->content );
        $this->extract_title();

        if ( ! $this->check_valid() ) {
            return false;
        }

        $this->html_cleanup();
        html_embed_img( $html );
        $this->html_finalize();

        return true;
    }

    function check_valid() {
        // instapaper failed to fetch this url
        if ( $this->title == 'Not available' ) {
            $this->error = 'instaError';
            return false;
        }
        return true;
    }

    function html_finalize() {
        $url = $this->realurl;

        $content = $this->html->save();
        $content = "<a style='margin:10px;' href='$url'>$url</a>" .$content;
        $content .= <<<'EOT'
<style>
    img { max-width:100%; }
</style>
EOT;
        $this->content = $content;
        return true;
    }

    function html_cleanup() {
        $html = $this->html;

        html_remove( $html, "script,link,comment" );
        html_remove( $html, "#text_controls_toggle,#text_controls,#editing_controls" );

        $e = $html->find( ".top a", 0 );
        $this->realurl = $e->getAttribute( 'href' );

        html_remove( $html, ".top,.bottom" );

        return true;
    }

    function extract_title() {
        $e = $this->html->find( "title", 0 );
        $title = $e->innertext;
        $this->title = preg_replace( '/[><\/:?*\\ \-_"]+/', '_', $title );
        return true;
    }


}

function byfetch( $url ) {

    while ( true ) {

        $old = $url;

        $f = newfetch();
        $cont = $f->fetch( $url );
        $code = $f->httpCode();


        if ( $code == "200" ) {
            break;
        }
        else if ( $code == "302" ) {
            $headers = $f->responseHeaders();
            // TODO save url redirect
            $url = url_redirect( $old, $headers[ 'Location' ] );
        }
        else {
            return array( 'err_code'=>1, 'httpCode'=>$code, 'title'=>$title, 'url'=>$url, 'html'=>$cont );
        }
    }

    $r = reformat_html( $cont );
    $r[ 'url' ] = $url;
    return $r;
}

function newfetch() {
    $f = new SaeFetchurl();
    $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
    $f->setAllowRedirect( false );
    return $f;
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

function html_remove( &$html, $selector ) {
    $es = $html->find( $selector );
    foreach ($es as $e) {
        $e->outertext = "";
    }
}

function html_embed_img( &$html ) {

    $es = $html->find( "img" );

    foreach ($es as $e) {

        $src=$e->getAttribute( 'src' );

        $f = newfetch();
        $cont = $f->fetch( $src );

        if ( $f->httpCode() == "200" ) {

            $mtype = "image/jpeg";
            $e->setAttribute( 'src', data_uri( $cont, $mtype ) );
        }

    }
}

function reformat_html( $text ) {
    $html = new simple_html_dom();
    $html->load( $text );

    $es = $html->find( "script,link,style,comment,textarea,input,iframe" );
    foreach ($es as $e) {
        $e->outertext = "";
    }

    $es = $html->find( ".topnav,.nav,.banner,.footer,.bottom" );
    foreach ($es as $e) {
        $e->outertext = "";
    }

    $es = $html->find( "[style]" );
    foreach ($es as $e) {
        $e->removeAttribute('style');
    }


    $metas = $html->find( "meta" );
    foreach ($metas as $m) {
        if ( $m->getAttribute( 'http-equiv' ) == 'Content-Type' ) {
            
            $c = $m->getAttribute( "content" );
            echo "content=$c<br/>\n";
            if ( $c == "text/html; charset=gb2312" ) {
                $enc='gb2312';
                $m->setAttribute( "content", "text/html; charset=utf-8" );
            }

        }
    }

    $es = $html->find( "title" );
    $e = $es[ 0 ];

    $title = $e->innertext;
    if ( $enc == 'gb2312' ) {
        $title = iconv( $enc, 'UTF-8', $title );
    }

    $title = preg_replace( '/[><\/:?*\\ "]+/', '_', $title );

    echo "$title<br/>\n";

    $ese->tag = "h1";

    echo "enc=$enc<br/>\n";

    $content = $html->save();
    if ( $enc == 'gb2312' ) {
        $content = iconv( $enc, 'utf-8', $content );
    }


    return array( 'err_code'=>0, 'title'=>$title, 'html'=>$content );
}

?>
