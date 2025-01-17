<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Confirmation_Page {

    public function __construct($version)
    {
        $this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        //fix bug
        add_action('init', array(&$this, 'set_post_on_checkout_page'));

        //scripts
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }

	public function set_post_on_checkout_page()
	{
		global $post;
		
		if(is_checkout_page() && !isset($post))
		{
			$GLOBALS['post'] = get_post(get_dy_id());
		}
	}

    public function enqueue_scripts()
    {
        if(is_checkout_page())
        {
			$strings = array(
				'textCopiedToClipBoard' => __('Copied to Clipboard!', 'dynamicpackages')
			);

            wp_enqueue_script('dynamicpackages-confirmation', $this->plugin_dir_url_file . 'js/dynamicpackages-confirmation-page.js', array( 'jquery'), $this->version, true );
			wp_localize_script('dynamicpackages-confirmation', 'dyPackageConfirmationArgs', $strings);
        }
    }
}

?>