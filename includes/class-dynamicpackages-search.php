<?php 

class dy_Search {

	public function __construct()
	{
		add_action('pre_get_posts', array('dy_Search', 'pre_get_posts'));
	}
    public static function pre_get_posts( $q ) {

		if(is_object($q) && $q->is_main_query())
		{
			if($q->get( 'search_tax_query' ) === true)
			{
				if ($q->get( 'tax_query' ) && $q->get( 's' ) ) {
					add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 1);
				}				
			}
			
		}
    }

    public static function posts_groupby( $groupby ) {
        return '';
    }
}

?>