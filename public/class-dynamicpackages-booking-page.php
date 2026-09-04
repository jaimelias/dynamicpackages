<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Booking_Page {

	static $cache = [];

    public function __construct($version)
    {
		$this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
		add_action('wp', array($this, 'load_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function is_valid()
    {
        $output = false;

        if(is_singular('packages') && is_booking_page() && !is_confirmation_page())
        {
            $output = true;
        }

        if(!$output)
        {
			global $post;
			
            if(($post instanceof WP_Post) && has_shortcode( $post->post_content, 'package_contact'))
            {
                $output = true;
            }
        } 
        
        return $output;
    }

    public function enqueue_scripts()
    {
        if($this->is_valid())
        {
			$strings = array(
				'submit_error' => __('Error: please correct the invalid fields in color red.', 'dynamicpackages')
			);

			wp_enqueue_script('dynamicpackages-booking', $this->plugin_dir_url_file . 'js/dynamicpackages-booking-page.js', array( 'jquery', 'dy-core-utilities', 'turnstile-compat'), $this->version, true );
            wp_add_inline_script('dynamicpackages-booking', $this->checkout_args(), 'before');
            wp_localize_script('dynamicpackages-booking', 'dyPackageBookingArgs', $strings);
        }
    }


	public function checkout_args()
	{
		global $post;

		$description = $this->get_description();
		$coupon_code = null;
		$coupon_discount = 0;
		
		if (dy_validators::validate_coupon()) {
			$coupon_params = dy_utilities::get_active_coupon_params();
			$coupon_code = $coupon_params->code;
			$coupon_discount = (float) $coupon_params->discount;

			$description .= sprintf(
				'. %s %s . %s%% %s',
				__('Coupon', 'dynamicpackages'),
				$coupon_code,
				$coupon_discount,
				__('off', 'dynamicpackages')
			);
		}

		
		$add_ons = (array) apply_filters('dy_get_add_ons', []);
		$regular_amount = (float) dy_utilities::total('regular');
		$amount = (float) dy_utilities::total();
		$payment_amount = (float) dy_utilities::payment_amount();

		$data = array(
			'post_id' => (int) $post->ID,
			'description' => $description,
			'coupon_code' => $coupon_code,
			'coupon_discount' => $coupon_discount,
			'coupon_discount_amount' => ($coupon_discount > 0 ) ? ($regular_amount - $amount) : 0,
			'total' => $payment_amount,
			'booking_date' => secure_get('booking_date', null),
			'booking_extra' => secure_get('booking_extra', null),
			'booking_hour' => esc_html(dy_utilities::start_hour()),
			'end_date' => secure_get('end_date', null),
			'return_hour' => (string) dy_utilities::return_hour(),
			'duration' => (string) dy_utilities::show_duration(),
			'pax_num' => (int) dy_utilities::pax_num(),
			'pax_regular' => secure_get('pax_regular', 0, 'absint'),
			'pax_discount' => secure_get('pax_discount', 0, 'absint'),
			'pax_free' => secure_get('pax_free', 0, 'absint'),
			'package_code' => (string) package_field('package_trip_code'),
			'title' => (string) $post->post_title,
			'package_type' => (string) dy_utilities::get_package_type($post->ID),
			'categories' => (array) dy_utilities::get_taxo_names('package_category', $post->ID),
			'locations' => (array) dy_utilities::get_taxo_names('package_location', $post->ID),
			'package_not_included' => (string) dy_utilities::implode_taxo_names('package_not_included', __('or', 'dynamicpackages'), '❌'),
			'package_included' => (string) dy_utilities::implode_taxo_names('package_included', __('and', 'dynamicpackages'), '✅'),
			'TERMS_CONDITIONS' => (array) $this->accept(),
			'url' => get_permalink(),
			'booking_url' => current_url_full(),
			'currency_name' => currency_name(),
			'currency_symbol' => currency_symbol(),
			'outstanding' => (float) dy_utilities::outstanding_amount(),
			'amount' => $amount,
			'regular_amount' => $regular_amount,
			'payment_type' => dy_utilities::payment_type(),
			'deposit' => (float) dy_utilities::get_deposit(),
			'add_ons' => $add_ons
		);

		return 'const defaultCheckoutArgs = '.wp_json_encode($data).';';			
	}


	
	public function get_description()
	{
		$output = (string) apply_filters('dy_description', null);

		if (dy_validators::has_deposit()) {
			$deposit = (float) dy_utilities::payment_amount();
			$total = (float) dy_utilities::total();
			$outstanding = $total - $deposit;

			$output .= sprintf(
				' - %s %s - %s %s',
				__('deposit', 'dynamicpackages'),
				wrap_money_full($deposit),
				__('outstanding balance', 'dynamicpackages'),
				wrap_money_full($outstanding)
			);
		}

		return $output;
	}

	
	public function accept() : array {
		$terms = (array) dy_utilities::get_taxonomies('package_terms_conditions');

		if (empty($terms)) {
			return [];
		}

		return array_map(function ($term) {
			return [
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'name'             => $term->name,
				'url'              => get_term_link($term->term_taxonomy_id),
			];
		}, $terms);
	}

	public function load_scripts($query)
	{
		global $post;

		$load_turnstile = false;
		$load_request_form_utilities = false;

		if($post instanceof WP_Post)
		{
			if(has_shortcode($post->post_content, 'package_contact'))
			{
				$load_turnstile = true;
				$load_request_form_utilities = true;
			}
		}

		if(isset($query->query_vars['packages']) && $query->query_vars['packages'])
		{
			if(is_booking_page())
			{
				$load_turnstile = true;
				$load_request_form_utilities = true;
			}
		}

		if($load_turnstile)
		{
			$GLOBALS['dy_load_turnstile_scripts'] = true;
		}

		if($load_request_form_utilities)
		{
			$GLOBALS['dy_load_request_form_utilities_scripts'] = true;
		}
	}

}

?>