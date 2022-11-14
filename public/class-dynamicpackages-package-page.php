<?php

if ( !defined( 'WPINC' ) ) exit;


class Dynamicpackages_Package_Page {

    public function __construct()
    {
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        if(is_singular('packages') && !is_booking_page() && !is_checkout_page())
        {
            wp_enqueue_script('dynamicpackages', $this->plugin_dir_url_file . 'js/dynamicpackages-package-page.js', array('landing-cookies'), time(), true );
        }
    }
}

?>