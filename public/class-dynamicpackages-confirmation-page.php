<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Confirmation_Page {

    public function __construct($version)
    {
        $this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        //fix bug
        add_action('init', array($this, 'set_post_on_checkout_page'));

        //scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function set_post_on_checkout_page(): void
    {
        if (
            secure_server('REQUEST_METHOD') !== 'POST'
            || is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
        ) {
            return;
        }


		$dy_request = secure_post('dy_request', '', 'sanitize_key');
		$all_dy_request_types = dy_utilities::all_dy_request_types();

		if(!in_array($dy_request, $all_dy_request_types, true)) {
			return;
		}

        $dy_id = secure_post('dy_id', null, 'intval');

        if (!is_int($dy_id) || $dy_id <= 0) {
            return;
        }

        //do not use "global $post", use $GLOBALS['post'] for the guard and a separate local variable. 
        $current_post = $GLOBALS['post'] ?? null;

        if ($current_post instanceof WP_Post) {
            if ((int) $current_post->ID !== $dy_id) {
                write_log(
                    sprintf(
                        'Existing post %d does not match submitted post %d.',
                        (int) $current_post->ID,
                        $dy_id
                    ),
                    false,
                    false,
                    'WARNING'
                );

                wp_die(
                    'Invalid package request.',
                    '',
                    ['response' => 400]
                );
            }

            return;
        }

        $requested_post = get_post($dy_id);

        if (
            !($requested_post instanceof WP_Post)
            || $requested_post->post_type !== 'packages'
            || $requested_post->post_status !== 'publish'
        ) {
            return;
        }

        $GLOBALS['post'] = $requested_post;
    }

    public function enqueue_scripts()
    {
        if(is_confirmation_page())
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