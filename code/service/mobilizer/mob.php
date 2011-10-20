<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/simple_html_dom.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class HtmlProcessor {
    public $html;
    function __construct( &$html ) {
        $this->html = $html;
    }

    function article_as_body() {

        $main = $this->major_node( $this->html->find( 'body', 0 ) );

        $e = $this->html->find( 'body', 0 );

        $e->innertext = $main->outertext;
    }

    function major_node( &$root, $stk = "" ) {

        $largests = $this->largest_texts( $root );

        $maxsize = $largests[ count( $largests ) - 1 ][ 'size' ];

        $nodes = array();

        foreach ($largests as $o) {

            if ( $o[ 'size' ] >= $maxsize / 5 ) {
                dd( "Chapter: {$o['size']}; " .  firstline( $o[ 'e' ]->innertext ) );
                array_push( $nodes, $o );
            }
        }

        $parent = $this->common_ancestor( $nodes, $root );

        dd( "Common ancestor: " . firstline( $parent->innertext ) );

        return $parent;
    }

    function largest_texts( &$node ) {

        $es = $node->find( "text" );

        dd( "Nodes found: " . count( $es ) );

        $largests = array();
        foreach ($es as $e) {

            $text = trim( $e->innertext );
            $len = strlen( $text );

            dd( "Found: " . firstline( $text ) );

            $x = array( 'e'=> $e, 'size'=> $len );

            array_push( $largests, $x );
            usort( $largests, '_cmp' );

            if ( count( $largests ) > 3 ) {
                $q = array_shift( $largests );
                dd( "Shifted: " . firstline( $q[ 'e' ]->innertext ) );
            }
        }

        return $largests;
    }

    function common_ancestor( &$nodes, &$root ) {

        if ( count( $nodes ) > 1 ) {
            $paths = array();
            foreach ($nodes as $o) {
                $path = array();
                $e = $o[ 'e' ];
                while ( $e != $root ) {
                    array_unshift( $path, $e );
                    $e = $e->parent();
                };

                array_push( $paths, $path );
            }

            $root = $this->common_path_ancestor( $paths );
        }
        else {
            $root = $nodes[ 0 ][ 'e' ];
        }

        return $root;
    }

    function common_path_ancestor( &$paths ) {

        for ( $i = 0; $i < 100; $i++ ) {

            $p = $paths[ 0 ][ $i ];

            foreach ($paths as $path) {
                if ( $i >= count( $path ) || $p !== $path[ $i ] ) {
                    return $path[ $i-1 ];
                }
            }
        }

        // shouldn't arrive here
        return false;
    }
}

class Mobilizer {

    public $url;
    public $realurl;

    public $error;
    public $title;
    public $content;
    public $enconding;

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

        $arr = $this->cache->meta->read( $this->url );
        if ( $arr === false ) {
            dinfo( "No meta in cache:{$this->url}" );
            return false;
        }

        dinfo( "OK read from meta:" . print_r( $arr, true ) );

        $this->title = $arr[ 'title' ];
        $this->realurl = $arr[ 'realurl' ];

        dinfo( "title: {$this->title}" );
        dinfo( "realurl: {$this->realurl}" );

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
        $r = $this->cache->page->write( $this->url, $this->content );
        if ( $r === false ) {
            dd( "Failed to write page to pagecache=" . print_r( $r ) );
            return false;
        }

        dinfo( "OK: written page to sto, length=" . strlen( $this->content ) );

        $meta = array(
                'title'=>$this->title,
                'realurl'=>$this->realurl );
        if ( $this->cache->meta->write(
            $this->url, $meta ) === false ) {
                derror( "Failed to write to meta" );
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
        $this->detect_enc();
        $this->extract_title();

        if ( ! $this->check_valid() ) {
            return false;
        }

        $this->html_cleanup();

        $h = new HtmlProcessor( $html );
        $h->article_as_body();

        $this->convert_links();
        $this->html_embed_img();

        $this->html_finalize();

        return true;
    }

    function detect_enc() {

        $html = $this->html;

        $metas = $html->find( "meta" );
        foreach ($metas as $m) {

            if ( $m->getAttribute( 'http-equiv' ) == 'Content-Type' ) {

                $c = $m->getAttribute( "content" );
                if ( $c == "text/html; charset=gb2312" ) {
                    $this->enconding='gb2312';
                    $m->setAttribute( "content", "text/html; charset=utf-8" );
                }

            }
        }

    }

    function check_valid() {
        return true;
    }

    function extract_title() {
        $e = $this->html->find( "title", 0 );
        $title = $e->innertext;
        if ( $this->enconding == 'gb2312' ) {
            $title = iconv( $this->enconding, 'UTF-8', $title );
        }

        dd( "Raw title=$title" );

        $this->title = preg_replace( '/[><\/:?*\\ \-_"]+/', '_', $title );

        dinfo( "Title: $title" );

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
                    dinfo( "read cached image: $src" );
                    $cont = $r;
                    $mtype = "image/jpeg";
                }
            }

            if ( ! $mtype ) {

                $f = no_redirect_fetch();
                $cont = $f->fetch( $src );

                if ( $f->httpCode() == "200" ) {
                    $mtype = "image/jpeg";
                    if ( $this->cache ) {
                        $this->cache->img->write( $src, $cont );
                    }
                }
                else {
                    derror( "Error: fetching image:$src httpCode=" . $f->httpCode()  );
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

    function convert_links() {
    }

    function html_finalize() {
        $url = $this->realurl;
        if ( strlen( $url ) > 30 ) {
            $u = parse_url( $url );
            $urltext = $u[ 'host' ] . '/...';
        }
        else {
            $urltext = $url;
        }


        $content = $this->html->save();

        if ( $this->enconding == 'gb2312' ) {
            $content = iconv( $this->enconding, 'utf-8', $content );
        }


        $prepand = <<<EOT
<style>
* { font-family: sans-serif; font-size:16px; }
img { max-width:100%; }
a { text-decoration:none; }
h1,h2,h3,h4,h5,h6 { font-weight:bold; }
h1 { font-size:20px; }
h2 { font-size:18px; }
h3 { font-size:16px; }
h4 { font-size:14px; }
blockquote{ margin:0 0 0 10px; }
pre { white-space: normal; }
</style>
<p style='font-size:14px;'>原文：<a style='margin:10px;' href='$url'>$urltext</a></p>
EOT;

        $append = <<<EOT
<p>Powered by </p>
EOT;

        $this->content = $prepand . $content;
        return true;
    }
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

    function html_cleanup() {

        $html = $this->html;

        html_remove( $html, "script,link,style,comment,textarea,input,iframe" );
        html_remove( $html, ".topnav,.nav,.banner,.footer,.bottom" );

        $es = $html->find( "[style]" );
        foreach ($es as $e) {
            $e->removeAttribute('style');
        }

        return true;
    }
}

class InstaMobilizer extends Mobilizer
{
    function fetch() {
        $f = $this->fetcher = new SaeFetchurl();
        $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
        $f->setAllowRedirect( false );

        $url = urlencode( $this->url );
        $this->content = $f->fetch( "http://www.instapaper.com/m?u=$url" );

        $this->httpCode = $f->httpCode();
        $this->responseHeaders = $f->responseHeaders();

        if ( $this->httpCode != '200' ) {
            derror( "Fetching page: $url" );
            return false;
        }

        return true;
    }

    function check_valid() {
        // instapaper failed to fetch this url
        if ( $this->title == 'Not_available' ) {
            $this->error = 'instaError';
            dd( "title seem to be invalid doc:{$this->title}" );
            return false;
        }
        dd( "OK: title: {$this->title}" );
        return true;
    }

    function html_cleanup() {
        $html = $this->html;

        html_remove( $html, "script,link,comment,style" );
        html_remove( $html, "#text_controls_toggle,#text_controls,#editing_controls" );

        $e = $html->find( ".top a", 0 );
        if ( $e ) {
            $this->realurl = $e->getAttribute( 'href' );
            dinfo( "realurl: {$this->realurl}" );
        }

        html_remove( $html, ".top,.bottom" );

        return true;
    }

    function convert_links() {
        $html = $this->html;

        $t = 'http://www.instapaper.com/m?u=';
        $es = $html->find( "a" );

        foreach ($es as $e) {

            $href = $e->getAttribute( "href" );

            if ( startsWith( $href, $t ) ) {
                $href = substr( $href, strlen( $t ) );
                $href = urldecode( $href );
                $e->setAttribute( 'href', $href );
            }
        }
    }
}


function _cmp( $a, $b ) {
    return $a[ 'size' ] - $b[ 'size' ];
}

function no_redirect_fetch() {
    $f = new SaeFetchurl();
    $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
    $f->setAllowRedirect( false );
    return $f;
}

function html_remove( &$html, $selector ) {
    $es = $html->find( $selector );
    foreach ($es as $e) {
        $e->outertext = "";
    }
}


?>
