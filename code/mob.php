<?

include_once( "util.php" );
include_once( "lib/simple_html_dom.php" );


function byfetch( $url ) {

    $base = $url;

    $f = new SaeFetchurl();
    $f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );
    $cont = $f->fetch( $url );

    $html = new simple_html_dom();
    $html->load( $cont );

    $es = $html->find( "script,link,style,meta,comment,textarea,input,iframe" );
    foreach ($es as $e) {
        $e->outertext = "";
    }

    $es = $html->find( "title" );
    $es[ 0 ]->tag = "h1";

    return $html->save();

}



?>
