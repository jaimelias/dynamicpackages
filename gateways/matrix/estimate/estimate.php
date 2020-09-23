<?php

class estimate_request{
	
	function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_action('init', array(&$this, 'args'));
		add_filter('gateway_buttons', array(&$this, 'button'), 1);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 10);
		add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 102);		
	}
	
	public function args()
	{
		$this->gateway_name = 'estimate_request';
		$this->gateway_title = __('request an estimate', 'dynamicpackages');
		$this->gateway_title_button = __('Get estimate', 'dynamicpackages');
		$this->color = '#444';
		$this->background_color = '#ccc';
	}
	
	
	public function is_active()
	{
		$output = false;
		global $estimate_is_active;
		
		if(isset($estimate_is_active))
		{
			$output = true;
		}
		else
		{
			$GLOBALS[$this->gateway_name . '_is_active'] = true;
			$output = true;
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		global $estimate_show;
		
		if(isset($estimate_show))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS[$this->gateway_name . '_show'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		$which_var = $this->gateway_name . '_is_valid_request';
		global $$which_var;
		global $dy_request_invalids;
		
		if(isset($$which_var))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']) && !isset($dy_request_invalids))
			{
				if($_POST['dy_request'] == $this->gateway_name && dy_utilities::payment_amount() > 1)
				{
					$output = true;
					$GLOBALS[$which_var] = true;	
				}
			}		
		}
		
		return $output;
	}
	
	public function is_valid()
	{
		$output = false;
		global $estimate_is_valid;
		
		if(isset($estimate_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() )
			{
				$output = true;
			}
			
			if($output == true){
				$GLOBALS[$this->gateway_name . '_is_valid'] = true;
			}
		}
		return $output;
	}
	
	public function button($output)
	{
		if($this->show() && in_array($this->gateway_title, $this->list_gateways_cb()))
		{
			$output .= ' <button style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 with_'.esc_html($this->gateway_name).' rounded" type="button"><i class="fas fa-envelope"></i> '.esc_html($this->gateway_title_button).'</button>';
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	
	public function add_gateway($array)
	{
		global $dy_valid_recaptcha;
		$add = false;
		
		if($this->show() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$add = true;
		}
		
		if(isset($dy_valid_recaptcha) && isset($_POST['dy_request']) && dy_Validators::is_request_valid())
		{
			if($_POST['dy_request'] == 'request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->gateway_title;
		}
		
		return $array;	
	}
	
	public function scripts()
	{
		if($this->show())
		{
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');
		}
	}

	public function js()
	{
		ob_start();
		?>
		jQuery(function(){
			jQuery('.with_<?php esc_html_e($this->gateway_name); ?>').click(function()
			{
				jQuery('#dynamic_form').removeClass('hidden');
				jQuery('#dynamic_form').find('input[name="name"]').focus();
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					console.log('InitiateCheckout');
					fbq('track', 'InitiateCheckout');
				}
				
				//google analytics
				if(typeof ga !== typeof undefined)
				{
					var eventArgs = {};
					eventArgs.eventAction = 'Click';
					eventArgs.eventLabel = '<?php echo esc_html($this->gateway_name); ?>';
					eventArgs.eventCategory = 'Gateway';
					ga('send', 'event', eventArgs);	
				}				
				
			});
		});
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}	
}