<?
class Tweet {

    public $tweet;

    public $uid;
    public $username;
    public $userurl;

    public $text;
    public $urls;

    public $isRetweeted;
    public $subTweet;

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


        $this->richtext = preg_replace( '/(http:\/\/(?:sinaurl|t)\.cn\/[a-zA-Z0-9_]+)/',
            "<a target='_blank' href='$1'>$1</a>", $this->text );

        $this->richtext = mb_ereg_replace( '@([_a-zA-Z0-9一-龥\-]+)',
            "<a target='_blank' href='http://weibo.com/n/\\1'>@\\1</a>", $this->richtext );

        $this->richtext = preg_replace( '/#([^#]+)#/',
            "<a target='_blank' href='http://s.weibo.com/weibo/\\1'>#\\1#</a>", $this->richtext );


        $this->uid = $t[ 'user' ][ 'id' ];
        $this->username = $t[ 'user' ][ 'screen_name' ];
        $this->userurl = "http://weibo.com/u/{$this->uid}";

        if ( isset( $t[ 'retweeted_status' ] ) ) {
            $this->subTweet = new Tweet( $t[ 'retweeted_status' ] );
            $this->subTweet->isRetweeted = true;
        }

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

        dinfo( "Tweet properties: $presen" );
    }
}

?>
