<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/simple_html_dom.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );


class Mobilizer {

    public $url;
    public $realurl;

    public $error;
    public $title;
    public $content;

    public $httpCode;
    public $responseHeaders;

    protected $cache;
    protected $html;

    function __construct( $url, &$cache = NULL ) {
        $this->url = $url;
        $this->realurl = $url;
        $this->cache = $cache;
    }

    function mobilize() {
        if ( $this->cache_read() ) {
            return true;
        }

        if ( !$this->fetch() ) {
            return false;
        }
        if ( !$this->processhtml() ) {
            return false;
        }

        $this->cache_write();

        return true;
    }

    function cache_read() {
        if ( ! $this->cache ) {
            return false;
        }

        dd( "OK: try read cache" );

        dd( "cache.meata is" );
        dd( print_r( $this->cache->meta, true ) );
        $arr = $this->cache->meta->read( $this->url );
        if ( $arr === false ) {
            dinfo( "Failed read meta from cache:{$this->url}" );
            return false;
        }

        dinfo( "OK read meta:" . print_r( $arr, true ) );

        $this->title = $arr[ 'title' ];
        $this->realurl = $arr[ 'realurl' ];

        dinfo( "title={$this->title}" );
        dinfo( "realurl={$this->realurl}" );

        $cont = $this->cache->page->read( $this->url );
        if ( $cont === false ) {
            dinfo( "Failed read sto from cache:{$this->url}" );
            return false;
        }

        dinfo( 'OK read page content from cache, length=' . strlen( $cont ) );

        $this->content = $cont;
        $this->httpCode = "200";

        return true;
    }

    function cache_write() {

        if ( ! $this->cache ) {
            return false;
        }

        dd( "OK: write cache" );

        dd( "To write {$this->url}, length=" . strlen( $this->content ) );
        if ( $this->cache->page->write( $this->url, $this->content ) !== true ) {
            return false;
        }

        dinfo( "OK: written page to sto, length=" . strlen( $this->content ) );

        $meta = array(
                'title'=>$this->title,
                'realurl'=>$this->realurl );
        if ( $this->cache->meta->write(
            $this->url, $meta ) !== true ) {
            return false;
        }

        dinfo( "OK: written page meta " . print_r( $meta, true ) );

        return true;

    }

    function fetch() {
    }

    function processhtml() {

        $html = $this->html = new simple_html_dom();

        $html->load( $this->content );
        $this->extract_title();

        if ( ! $this->check_valid() ) {
            return false;
        }

        $this->html_cleanup();
        $this->html_embed_img();
        $this->html_finalize();

        return true;
    }

    function extract_title() {
        $e = $this->html->find( "title", 0 );
        $title = $e->innertext;

        dinfo( "Raw title=$title" );

        $this->title = preg_replace( '/[><\/:?*\\ \-_"]+/', '_', $title );

        dinfo( "Stripped title=$title" );

        return true;
    }

    function html_embed_img() {

        $es = $this->html->find( "img" );

        foreach ($es as $e) {

            $src=$e->getAttribute( 'src' );

            $cont = NULL;
            $mtype = NULL;

            if ( $this->cache ) {
                $r = $this->cache->img->read( $src );
                if ( $r !== false ) {
                    dinfo( "read image from cache:$src" );
                    $cont = $r;
                    $mtype = "image/jpeg";
                }
            }

            if ( ! $mtype ) {
                $f = newfetch();
                $cont = $f->fetch( $src );

                if ( $f->httpCode() == "200" ) {
                    $mtype = "image/jpeg";
                    $this->cache->img->write( $src, $cont );
                }
                else {
                    dinfo( "Error: fetching image:$src httpCode=" . $f->httpCode()  );
                }
            }


            if ( $mtype ) {
                $e->setAttribute( 'src', data_uri( $cont, $mtype ) );
                dinfo( "OK: image embedded:$src" );
            }
            else {
                derror( "Failed to fetch image from $src" );
            }

        }
    }
}

class InstaMobilizer extends Mobilizer
{

    private $fetcher;


    function fetch() {
        $f = $this->fetcher = new SaeFetchurl();
        $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
        $f->setAllowRedirect( false );

        $url = urlencode( $this->url );
        $this->content = $f->fetch( "http://www.instapaper.com/m?u=$url" );

        $this->httpCode = $f->httpCode();
        $this->responseHeaders = $f->responseHeaders();

        if ( $this->httpCode != '200' ) {
            return false;
        }

        return true;
    }

    function check_valid() {
        // instapaper failed to fetch this url
        if ( $this->title == 'Not available' ) {
            $this->error = 'instaError';
            dinfo( "title seem to be invalid doc:{$this->title}" );
            return false;
        }
        dinfo( "OK: title: {$this->title}" );
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
        if ( $e ) {
            $this->realurl = $e->getAttribute( 'href' );
            dinfo( "OK: extracted realurl from html={$this->realurl}" );
        }

        html_remove( $html, ".top,.bottom" );


        $this->cleanup( $html->find( '#story', 0 ), '' );

        return true;
    }

    function cleanup( &$e, $stk ) {

        $arr = array();
        $max = 0;

        $cs = $e->children();
        if ( count( $cs ) == 0 ) {
            return;
        }

        foreach ($cs as $c) {
            $it = $c->innertext;
            $l = strlen( $it );
            array_push( $arr, array(
                'e' => $c, 
                'len'=>$l, 
            ) );

            if ( $max < $l ) {
                $max = $l;
            }
        }

        $thre = $max / 10;

        $n = count( $arr );

        foreach ($arr as $entry) {
            if ( $entry[ 'len' ] < $thre ) {
                dd( "[ $stk ] Removed: " . $entry[ 'e' ]->innertext );
                $entry[ 'e' ]->outertext = '';
                $entry[ 'removed' ] = true;
                $n -= 1;
            }
            else {
                break;
            }
        }

        dd( "[ $stk ] n=$n" );

        if ( $n <= 3 ) {
            foreach ($arr as $entry) {
                if ( $entry[ 'removed' ] !== true ) {
                    $this->cleanup( $entry[ 'e' ], $stk . " > {$entry[ 'e' ]->tag}" );
                }
            }
        }
        else {
            dd( "[ $stk ] Stop striping with more than 3 big part" );
        }
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
