<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class estimate_request{
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		
		add_action('init', array(&$this, 'init'));
		add_filter('gateway_buttons', array(&$this, 'button'), 1);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 10);
	}

	public function init()
	{
		$this->valid_recaptcha = validate_recaptcha();
		$this->id = 'estimate_request';
		$this->name = __('request an estimate', 'dynamicpackages');
		$this->type = 'alt';
		$this->name_button = __('Get estimate', 'dynamicpackages');
		$this->color = '#444';
		$this->background_color = '#ccc';
		$this->only_estimate = __('Get a quote in seconds! Quick, easy, and hassle-free. Just ask, and your estimate will be in your inbox in no time.', 'dynamicpackages');
	}
	
	
	public function is_active()
	{
		$output = false;
		$which_var = $this->id.'_is_active';
		global $$which_var; 
		
		if(isset($$which_var))
		{
			$output =  $$which_var;
		}
		else
		{
			$output = true;
			$GLOBALS[$which_var] = $output;
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		$which_var = $this->id.'_show';
		global $$which_var; 
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$output = true;
				}

				$GLOBALS[$which_var] = $output;
			}			
		}
		return $output;
	}
	
	public function is_valid()
	{
		$output = false;
		$which_var = $this->id . '_is_valid';
		global $$which_var;
		
		if(isset($$which_var))
		{
			return $$which_var;
		}
		else
		{
			if($this->is_active())
			{
				$output = true;
			}
			
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
	
	public function button($output)
	{
		if($this->show() && in_array($this->name, $this->list_gateways_cb()) && !dy_validators::validate_coupon())
		{
			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 with_'.esc_html($this->id).' rounded" type="button"><span class="dashicons dashicons-email"></span> '.esc_html($this->name_button).'</button>';
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	
	public function add_gateway($array)
	{
		
		$add = false;
		
		if($this->show())
		{
			if(is_singular('packages') && !is_checkout_page())
			{
				$add = true;
			}
		}
		
		if($add)
		{
			$array[] = $this->name;
		}
		
		return $array;	
	}

	public function branding()
	{
		return '<p class="large">'.__('Request a Quote', 'dynamicpackages').'</p>';
	}
}