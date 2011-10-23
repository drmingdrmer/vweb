<?
class Tweet {

    public $tweet;

    public $text;
    public $urls;
    public $properties;

    static function extract_urls( $text ) {

        $matches = array();
        preg_match_all( '/http:\/\/(sinaurl|t)\.cn\/[a-zA-Z0-9_]+/', $text, $matches );

        $urls = array();
        foreach ($matches[ 0 ] as $m) {
            array_push( $urls, $m );
        }

        return $urls;
    }

    function __construct( &$t ) {

        $this->tweet = $t;
        $this->urls = Tweet::extract_urls( $this->tweet[ 'text' ] );
        $this->text = $t[ 'text' ];
        $this->extract_properties();

        dok( "Success created tweet: " . $this->tweet[ 'id' ] . " " . $this->text );
    }

    function is_satisfied( $expect ) {

        $expected = explode( ' ', $expect );
        foreach ($expected as $exp) {

            if ( ! $this->properties[ $exp ] ) {
                dd( "Expectance not satisified: $exp" );
                return false;
            }
        }

        dd( "Satisfied: " . $expect );
        return true;
    }

    function extract_properties() {

        $this->properties = array(
            'img' => isset( $this->tweet[ 'bmiddle_pic' ] ),
            'links' => count( $this->urls ) > 0,
            'any' => true,
        );

        $presen = "";
        foreach ($this->properties as $n=>$v) {
            if ( $v ) {
                $presen .= " $n";
            }
            $this->properties[ "-$n" ] = !$v;
        }

        dinfo( "props: $presen" );
    }
}

?>
