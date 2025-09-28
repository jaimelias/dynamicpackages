<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Errors_Page {

    private static $cache = [];

    function __construct() {
        //the_content, wp_title, pre_get_document_title, the_title
        $priority = DY_IS_ERROR_PAGE_PRIORITY;

        add_action('wp_head', array(&$this, 'meta_tags'), $priority );
        add_filter('the_content', array(&$this, 'the_content'), $priority);
		add_filter('the_content', array(&$this, 'the_content'), $priority);
		add_filter('pre_get_document_title', array(&$this, 'wp_title'), $priority);
		add_filter('wp_title', array(&$this, 'wp_title'), $priority);
		add_filter('the_title', array(&$this, 'the_title'), $priority);
        add_filter('get_the_excerpt', array(&$this, 'get_the_excerpt'), $priority);
    }

	public function meta_tags()
	{
		global $dy_request_invalids;

		if(isset($dy_request_invalids))
		{		
            echo '<meta name="robots" content="noindex, nofollow" />';
            return;
		}
	}

    public function the_content($content) {

        global $dy_request_invalids;

		if(isset($dy_request_invalids))
		{
            return sprintf('<p class="minimal_alert">%s</p>', json_encode($dy_request_invalids));
		}

        return $content;
    }

    public function wp_title($title) {

		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			return __('Error', 'dynamicpackages');
		}

        return $title;
    }

    public function the_title($title) {

        global $dy_request_invalids;

		if(isset($dy_request_invalids))
		{
			return __('Error', 'dynamicpackages');
		}

        return $title;
    }

    public function get_the_excerpt($excerpt) {

        global $dy_request_invalids;

		if(isset($dy_request_invalids))
		{
			return '';
		}

        return $excerpt;
    }
}

?>