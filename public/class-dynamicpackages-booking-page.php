<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Booking_Page {

    public function __construct($version)
    {
		$this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
		add_action('parse_query', array(&$this, 'load_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }

    public function is_valid()
    {
        $output = false;

        if(is_singular('packages') && is_booking_page() && !is_checkout_page())
        {
            $output = true;
        }

        if(!$output)
        {
			global $post;
			
            if(is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'package_contact'))
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

			wp_enqueue_script('dynamicpackages-booking', $this->plugin_dir_url_file . 'js/dynamicpackages-booking-page.js', array( 'jquery', 'dy-core-utilities', 'recaptcha-v3'), $this->version, true );
            wp_add_inline_script('dynamicpackages-booking', $this->checkout_args(), 'before');
            wp_localize_script('dynamicpackages-booking', 'dyPackageBookingArgs', $strings);
        }
    }


	public function checkout_args()
	{
		global $post;

		$description = $this->get_description();
		$coupon_code = null;
		$coupon_discount = null;
		
		if(dy_validators::validate_coupon())
		{
			$coupon_code = dy_utilities::get_coupon('code');
			$coupon_discount = dy_utilities::get_coupon('discount');
			$description = $description.'. '.__('Coupon', 'dynamicpackages').' '.$coupon_code.' '.'. '.$coupon_discount.'% '.__('off', 'dynamicpackages');
		}
		
		$add_ons = apply_filters('dy_get_add_ons', null);
		
		$regular_amount = floatval(dy_utilities::total('regular'));
		$amount = floatval(dy_utilities::total());
		$payment_amount = floatval(dy_utilities::payment_amount());

		$data = array(
			'post_id' => intval($post->ID),
			'hash' => sanitize_text_field($_GET['hash']),
			'description' => esc_html($description),
			'coupon_code' => esc_html($coupon_code),
			'coupon_discount' => floatval($coupon_discount),
			'coupon_discount_amount' => (floatval($coupon_discount) > 0 ) ? ($regular_amount - $amount) : 0,
			'total' => $payment_amount,
			'booking_date' => (isset($_GET['booking_date'])) ? sanitize_text_field($_GET['booking_date']) : null,
			'booking_extra' => (isset($_GET['booking_extra'])) ? sanitize_text_field($_GET['booking_extra']) : null,
			'booking_hour' => esc_html(dy_utilities::hour()),
			'end_date' => (isset($_GET['end_date'])) ? $_GET['end_date'] : null,
			'return_hour' => esc_html(dy_utilities::return_hour()),
			'duration' => esc_html(dy_utilities::show_duration()),
			'pax_num' => intval(dy_utilities::pax_num()),
			'pax_regular' => (isset($_GET['pax_regular'])) ? intval($_GET['pax_regular']) : 0,
			'pax_discount' => (isset($_GET['pax_discount'])) ? intval($_GET['pax_discount']) : 0,
			'pax_free' => (isset($_GET['pax_free']) ? intval($_GET['pax_free']) : 0),
			'package_code' => esc_html(package_field('package_trip_code')),
			'title' => esc_html($post->post_title),
			'package_type' => esc_html($this->get_type()),
			'categories' => dy_utilities::get_taxo_names('package_category'),
			'locations' => dy_utilities::get_taxo_names('package_location'),
			'package_not_included' => esc_html(dy_utilities::implode_taxo_names('package_not_included')),
			'package_included' => esc_html(dy_utilities::implode_taxo_names('package_included')),
			'TERMS_CONDITIONS' => $this->accept(),
			'package_url' => esc_url(get_permalink()),
			'hash' => (isset($_GET['hash'])) ? sanitize_text_field($_GET['hash']) : null,
			'currency_name' => currency_name(),
			'currency_symbol' => currency_symbol(),
			'outstanding' => floatval($this->outstanding()),
			'amount' => $amount,
			'regular_amount' => $regular_amount,
			'payment_type' => esc_html($this->payment_type()),
			'deposit' => floatval(dy_utilities::get_deposit()),
			'add_ons' => $add_ons
		);
		
		return 'const defaultCheckoutArgs = '.json_encode($data).';';			
	}


	
	public function get_description()
	{
		$output = apply_filters('dy_description', null);
		
		if(dy_validators::has_deposit())
		{
			$deposit = dy_utilities::payment_amount();
			$total = dy_utilities::total();
			$outstanding = $total-$deposit;
			$output .= ' - '.__('deposit', 'dynamicpackages').' '.currency_symbol().money($deposit).' - '.__('outstanding balance', 'dynamicpackages').' '.currency_symbol().money($outstanding);					
		}
		return $output;
	}
	
	
	public function get_type()
	{
		$output = 'one day';
		
		if(package_field( 'package_package_type' ) == 1)
		{
			$output = 'multi-day';
		}
		else if(package_field( 'package_package_type' ) == 2)
		{
			$output = 'per day';
		}
		else if(package_field( 'package_package_type' ) == 3)
		{
			$output = 'per hour';
		}
		return $output;
	}
	
	public function accept()
	{
		$output = array();
		$terms = dy_utilities::get_taxonomies('package_terms_conditions');
		
		if(is_array($terms))
		{
			if(count($terms) > 0)
			{
				$terms_conditions = $terms;
				$terms_conditions_clean = array();

				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$terms_conditions_item = array();
					$terms_conditions_item['term_taxonomy_id'] = $terms_conditions[$x]->term_taxonomy_id;
					$terms_conditions_item['name'] = $terms_conditions[$x]->name;
					$terms_conditions_item['url'] = get_term_link($terms_conditions[$x]->term_taxonomy_id);
					array_push($terms_conditions_clean, $terms_conditions_item);
				}
				$output = $terms_conditions_clean;
			}			
		}

		return $output;
	}
	
	public function outstanding()
	{
		global $dy_outstanding;
		$output = 0;
		
		if(isset($dy_outstanding))
		{
			$output = $dy_outstanding;
		}
		else
		{
			//outstanding balance
			$total = dy_utilities::total();
			$amount = $total;
			
			
			if(package_field('package_payment' ) == 1)
			{
				$deposit = floatval(dy_utilities::get_deposit());
				
				if($deposit > 0)
				{
					$amount = floatval(dy_utilities::total())*(floatval($deposit)*0.01);
					$output = floatval($total)-$amount;					
				}					
			}

			$GLOBALS['dy_outstanding'] = $output;
		}
		return $output;
	}
	
	public function payment_type()
	{
		global $dy_payment_type;
		$output = 'full';
		
		if(isset($dy_payment_type))
		{
			$output = $dy_payment_type;
		}
		else
		{
			if(package_field('package_payment' ) == 1 && intval(package_field('package_auto_booking')) == 1)
			{
				$output = 'deposit';
			}
			
			$GLOBALS['dy_payment_type'] = $dy_payment_type;
		}
		return $output;
	}

	public function load_scripts($query)
	{
		global $post;
		$load_recaptcha = false;
		$load_request_form_utilities = false;

		if(isset($post))
		{
			if(is_a($post, 'WP_Post') && has_shortcode( $post->post_content, 'package_contact'))
			{
				$load_recaptcha = true;
				$load_request_form_utilities = true;
			}
		}
		if(isset($query->query_vars['packages']))
		{
			if($query->query_vars['packages'])
			{
				if(is_booking_page())
				{
					$load_recaptcha = true;
					$load_request_form_utilities = true;
				}
			}
		}

		if($load_recaptcha)
		{
			$GLOBALS['dy_load_recaptcha_scripts'] = true;
		}
		if($load_request_form_utilities)
		{
			$GLOBALS['dy_load_request_form_utilities_scripts'] = true;
		}
	}

}

?>