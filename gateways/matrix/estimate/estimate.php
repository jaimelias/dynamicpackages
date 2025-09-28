<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class estimate_request{
	
	private static $cache = [];

	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		
		add_action('init', array(&$this, 'init'));
		add_filter('dy_list_gateways', array(&$this, 'add_gateway'), 10);
	}

	public function init()
	{
		$this->valid_recaptcha = validate_recaptcha();
		$this->id = 'estimate_request';
		$this->name = __('request an estimate', 'dynamicpackages');
		$this->brands = [$this->name];
		$this->type = 'alt';
		$this->name_button = __('Get estimate', 'dynamicpackages');
		$this->color = '#444';
		$this->background_color = '#ccc';
		$this->only_estimate = __('Get a quote in seconds! Quick, easy, and hassle-free. Just ask, and your estimate will be in your inbox in no time.', 'dynamicpackages');
		$this->icon = '<span class="dashicons dashicons-email"></span>';
		$this->gateway_coupon = '';
	}
	
	
	public function is_active()
	{
		$output = false;
		$cache_key = $this->id.'_is_active';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$output = true;

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	public function show()
	{
		$output = false;
		$cache_key = $this->id.'_show';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(is_singular('packages') && $this->is_active())
		{
			if($this->is_valid())
			{
				$output = true;
			}

			//store output in $cache
			self::$cache[$cache_key] = $output;
		}


		return $output;
	}
	
	public function is_valid()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		
		if($this->is_active())
		{
			$output = true;
		}
		
        
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public function add_gateway($array)
	{
		
		$add = false;
		
		if($this->show())
		{
			if(is_singular('packages') && !is_confirmation_page())
			{
				$add = true;
			}
		}
		
		if($add)
		{
			$array[$this->id] = array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'color' => $this->color,
                'background_color' => $this->background_color,
				'brands' => $this->brands,
				'branding' => $this->branding(),
			'icon' => $this->icon,
'gateway_coupon' => $this->gateway_coupon
            );
		}
		
		return $array;	
	}

	public function branding()
	{
		return '<p class="large">'.__('Request a Quote', 'dynamicpackages').'</p>';
	}
}