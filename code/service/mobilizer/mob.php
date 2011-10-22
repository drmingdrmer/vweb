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

        dd( "Common ancestor: " . firstline( $parent->plaintext ) );

        return $parent;
    }

    function largest_texts( &$node ) {

        $es = $node->find( "text" );

        dd( "Nodes found: " . count( $es ) );

        $largests = array();
        foreach ($es as $e) {

            $text = trim( $e->innertext );
            $len = strlen( $text );

            $x = array( 'e'=> $e, 'size'=> $len );

            array_push( $largests, $x );
            usort( $largests, '_cmp' );

            if ( count( $largests ) > 3 ) {
                $q = array_shift( $largests );
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

class CachedFetcher {

    public $metaCache;
    public $contCache;

    function __construct( &$metaCache = NULL, &$contCache = NULL ) {
        $this->metaCache = $metaCache;
        $this->contCache = $contCache;
    }

    function fetch( $key ) {

        if ( $this->metaCache ) {

            $rmeta = $this->metaCache->read( $key );

            if ( $rmeta && $this->contCache ) {

                $rcont = $this->contCache->read( $key );

                if ( $rmeta && $cont ) {
                    dinfo( "Read from cache: $key: " . print_r( $rmeta ) );
                    return array( 'meta'=>$rmeta, 'content'=>$rcont );
                }
            }
        }


        dd( "Fetching: $key" );

        $r = $this->do_fetch( $key );

        if ( $r[ 'meta' ] && $r[ 'content' ]
                && $this->contCache
                && $rcont === false ) {

            $rw = $this->contCache->write( $key, $r[ 'content' ] );

            if ( $rw && $this->metaCache && $rmeta === false ) {
                $this->metaCache->write( $key, $r[ 'meta' ] );
            }
        }

        return $r;
    }

    function do_fetch( $key ) {
    }
}

class ImgFetcher extends CachedFetcher {

    function __construct( &$cache = NULL ) {
        if ( $cache ) {
            parent::__construct( $cache->meta, $cache->img );
        }
        else {
            parent::__construct();
        }
    }

    function do_fetch( $url ) {


        $f = no_redirect_fetch();
        $cont = $f->fetch( $url );

        if ( $f->httpCode() == "200" ) {
            $headers = $f->responseHeaders();
            $mt = $headers[ 'Content-Type' ];
            dinfo( "Fetched: $url mimetyp: $mt" );
            return array( 'meta'=>array( 'mimetype'=>$mt ), 'content'=>$cont );
        }
        else {
            derror( "Error: fetching image:$url httpCode=" . $f->httpCode()  );
            return array();
        }
    }
}

/*
 * class PageFetch extends CachedFetcher {
 * 
 *     function __construct( &$cache = NULL ) {
 *         if ( $cache ) {
 *             parent::__construct( $cache->meta, $cache->page );
 *         }
 *         else {
 *             parent::__construct();
 *         }
 *     }
 * 
 *     function do_fetch( $url ) {
 * 
 *         $f = no_redirect_fetch();
 *         $cont = $f->fetch( $url );
 * 
 *         if ( $f->httpCode() == "200" ) {
 *             $headers = $f->responseHeaders();
 *             $mt = $headers[ 'Content-Type' ];
 *             dinfo( "Fetched: $url mimetyp: $mt" );
 *             return array( 'meta'=>array( 'mimetype'=>$mt ), 'content'=>$cont );
 *         }
 *         else {
 *             derror( "Error: fetching image:$url httpCode=" . $f->httpCode()  );
 *             return array();
 *         }
 *     }
 * }
 */


class Mobilizer {

    public $url;

    public $error;

    public $meta;
    public $content;

    public $enconding;

    public $httpCode;
    public $responseHeaders;


    protected $cache;
    protected $html;

    function __construct( $url, &$cache = NULL ) {
        $this->url = $url;
        $this->meta[ 'realurl' ] = $url;
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

        $meta = $this->cache->meta->read( $this->url );
        if ( $meta === false ) {
            dinfo( "No meta in cache:{$this->url}" );
            return false;
        }

        dinfo( "Success read from meta:" . print_r( $meta, true ) );

        $this->meta[ 'title' ] = $meta[ 'title' ];
        $this->meta[ 'realurl' ] = $meta[ 'realurl' ];

        dinfo( "title: {$this->meta[ 'title' ]}" );
        dinfo( "realurl: {$this->meta[ 'realurl' ]}" );

        $cont = $this->cache->page->read( $this->url );
        if ( $cont === false ) {
            dinfo( "Failed read sto from cache:{$this->url}" );
            return false;
        }

        dinfo( 'Success read page content from cache, length=' . strlen( $cont ) );

        $this->content = $cont;
        $this->httpCode = "200";

        return true;
    }

    function cache_write() {

        if ( ! $this->cache ) {
            return false;
        }

        dd( "Success: write cache" );

        dd( "To write {$this->url}, length=" . strlen( $this->content ) );
        $r = $this->cache->page->write( $this->url, $this->content );
        if ( $r === false ) {
            dd( "Failed to write page to pagecache=" . print_r( $r ) );
            return false;
        }

        dinfo( "Success: written page to sto, length=" . strlen( $this->content ) );

        if ( $this->cache->meta->write(
            $this->url, $this->meta ) === false ) {
                derror( "Failed to write to meta" );
                return false;
        }

        dinfo( "Success: written page meta " . print_r( $meta, true ) );

        return true;

    }

    function fetch() {
    }

    function processhtml() {

        $html = $this->html = new simple_html_dom();
        // echo $this->content;

        $html->load( $this->content, true, false );
        $this->detect_enc();
        $this->extract_title();

        if ( ! $this->check_valid() ) {
            return false;
        }

        $this->html_cleanup();
        $this->convert_links();
        $this->html_embed_img();

        $h = new HtmlProcessor( $html );
        $h->article_as_body();

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
                    dd( "gb2312" );
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

        $this->meta[ 'title' ] = vdname_normallize( $title );

        dinfo( "Title: $title" );

        return true;
    }

    function html_embed_img() {

        $es = $this->html->find( "img" );

        foreach ($es as &$e) {

            $src = $e->getAttribute( 'src' );

            $f = new ImgFetcher( $this->cache );
            $r = $f->fetch( $src );
            $mt = $r[ 'meta' ][ 'mimetype' ];
            $cont = $r[ 'content' ];


            if ( $mt ) {
                $data_uri = data_uri( $cont, $mt );
                dd( "data_uri: " . firstline( $data_uri ) );

                $e->setAttribute( 'src', $data_uri );
                dinfo( "Success: image embedded:$src" );
            }
            else {
                derror( "Failed to fetch image from $src" );
            }
        }
    }

    function convert_links() {
    }

    function html_finalize() {
        $url = $this->meta[ 'realurl' ];
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

        $es = $html->find( "img" );
        foreach ($es as $e) {
            $e->removeAttribute('alt');
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
            derror( "Fetching page: {$this->url}" );
            return false;
        }

        $this->meta[ 'mimetype' ] = $this->responseHeaders[ 'Content-Type' ];

        return true;
    }

    function check_valid() {
        // instapaper failed to fetch this url
        if ( $this->meta[ 'title' ] == 'Not_available' ) {
            $this->error = 'instaError';
            dd( "title seem to be invalid doc:{$this->meta[ 'title' ]}" );
            return false;
        }
        dd( "Success: title: {$this->meta[ 'title' ]}" );
        return true;
    }

    function html_cleanup() {
        $html = $this->html;

        html_remove( $html, "script,link,comment,style" );
        html_remove( $html, "#text_controls_toggle,#text_controls,#editing_controls" );

        $e = $html->find( ".top a", 0 );
        if ( $e ) {
            $this->meta[ 'realurl' ] = $e->getAttribute( 'href' );
            dinfo( "realurl: {$this->meta[ 'realurl' ]}" );
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
