<? 
class Cache {

    public $page;
    public $img;
    public $meta;

    function __construct( $p, $i, $m ) {
        $this->page = $p;
        $this->img = $i;
        $this->meta = $m;
    }
}

?>
