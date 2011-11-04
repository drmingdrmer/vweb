<? 

$f = new SaeFetchurl();
$f->setHeader( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1' );

echo $f->fetch( "http://sinastorage.com/?extra&op=selfip.js" );
echo gethostbyname( "sinastorage.com" );

?>


