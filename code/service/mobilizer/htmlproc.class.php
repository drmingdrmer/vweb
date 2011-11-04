<?
include_once( $_SERVER["DOCUMENT_ROOT"] . "/lib/simple_html_dom.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/util.php" );
include_once( $_SERVER["DOCUMENT_ROOT"] . "/inc/debug.php" );

class HtmlProcessor {

    public $html;
    public $meta;
    public $content;

    public $context;

    public $error;

    function __construct( &$meta, &$cont, &$context ) {
        $this->meta = $meta;
        $this->content = $cont;
        $this->context = $context;

        $this->html = new simple_html_dom();
        $this->html->load( $cont, true, false );
    }

    function process() {
        $r = $this->do_process();
        return $r;
    }

}

class HtmlEnc extends HtmlProcessor {

    public $encoding = 'utf-8';
    function do_process() {

        $metas = $this->html->find( "meta" );
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

        $content = $this->html->save();

        if ( $this->enconding == 'gb2312' ) {
            $content = iconv( $this->enconding, 'utf-8', $content );
        }
        $this->content = $content;

        return true;
    }
}

class HtmlPageMeta extends HtmlProcessor {

    function do_process() {

        $e = $this->html->find( "title", 0 );
        $title = $e->innertext;

        dd( "Raw title: $title" );

        $this->meta[ 'title' ] = $title;
        return true;
    }
}

class InstaHtmlPageMeta extends HtmlPageMeta {

    function do_process() {

        if ( parent::do_process() === true ) {

            if ( $this->meta[ 'title' ] == 'Not_available' ) {
                $this->error = 'instaError';
                dd( "title seem to be invalid doc:{$this->meta[ 'title' ]}" );
                return false;
            }
            dd( "Success: title is valid: {$this->meta[ 'title' ]}" );
            return true;
        }

        return false;
    }
}

class HtmlCleaner extends HtmlProcessor {

    function do_process() {

        $html = $this->html;

/*
 *         html_remove( $html, "script,link,style,comment,textarea,input,iframe" );
 *         html_remove( $html, ".topnav,.nav,.banner,.footer,.bottom" );
 *
 *         $es = $html->find( "[style]" );
 *         foreach ($es as $e) {
 *             $e->removeAttribute('style');
 *         }
 *
 *         $es = $html->find( "img" );
 *         foreach ($es as $e) {
 *             $e->removeAttribute('alt');
 *         }
 */


        html_remove( $html, "script,link,comment,style" );
        html_remove( $html, "#text_controls_toggle,#text_controls,#editing_controls" );

        $e = $html->find( ".top a", 0 );
        if ( $e ) {
            $this->meta[ 'realurl' ] = $e->getAttribute( 'href' );
            dinfo( "Success extracted realurl: {$this->meta[ 'realurl' ]}" );
        }

        html_remove( $html, ".top,.bottom" );



        $this->content = $this->html->save();
        return true;
    }
}

class HtmlLinkConverter extends HtmlProcessor {

    function do_process() {
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

        $this->content = $this->html->save();
        return true;
    }
}

class HtmlImgEmbed extends HtmlProcessor {

    function do_process() {

        $es = $this->html->find( "img" );

        foreach ($es as &$e) {

            $src = $e->getAttribute( 'src' );

            $f = new ImgFetcher( $this->context );
            if ( ! $f->fetch( $src ) ) {
                $this->error = $f->error;
                return false;
            }

            $mt = $f->meta[ 'mimetype' ];
            $cont = $f->content;


            if ( $mt ) {
                $data_uri = data_uri( $cont, $mt );
                dd( "data_uri: " . firstline( $data_uri ) );

                $e->setAttribute( 'src', $data_uri );
                dok( "Image embedded:$src" );
            }
            else {
                derror( "Failed to fetch image from $src" );
            }
        }

        $this->content = $this->html->save();
        return true;
    }
}

class HtmlFinalizer extends HtmlProcessor {

    function do_process() {

        $url = $this->meta[ 'realurl' ];
        if ( strlen( $url ) > 30 ) {
            $u = parse_url( $url );
            $urltext = $u[ 'host' ] . '/...';
        }
        else {
            $urltext = $url;
        }


        $content = $this->html->save();

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

class HtmlMajorArticle extends HtmlProcessor {

    function do_process() {

        $main = $this->major_node( $this->html->find( 'body', 0 ) );
        $e = $this->html->find( 'body', 0 );

        $e->innertext = $main->outertext;
        $this->content = $this->html->save();
        return true;
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

function html_remove( &$html, $selector ) {
    $es = $html->find( $selector );
    foreach ($es as $e) {
        $e->outertext = "";
    }
}

function _cmp( $a, $b ) {
    return $a[ 'size' ] - $b[ 'size' ];
}
?>
