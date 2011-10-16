<?

include_once( $_SERVER["DOCUMENT_ROOT"] . "/vweb.php" );

function extract_urls( $text ) {
    $matches = array();
    preg_match_all( '/http:\/\/(sinaurl|t)\.cn\/[a-zA-Z0-9_]+/', $text, $matches );

    $urls = array();
    foreach ($matches[ 0 ] as $m) {
        array_push( $urls, $m );
    }

    return $urls;
}
?>
