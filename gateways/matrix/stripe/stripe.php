<?php
if ( !defined('WPINC') ) exit;

#[AllowDynamicProperties]
class stripe_gateway {

    private static $cache = [];

    function __construct($plugin_id) {
        $this->plugin_id = $plugin_id;
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'settings_init'), 1);
        add_action('admin_menu', array($this, 'add_settings_page'), 100);

        // Render / flow integration (follow patterns in cuanto.php & yappy_direct.php)
        add_filter('dy_list_gateways', array($this, 'add_gateway'), 3);
        add_action('template_redirect', array($this, 'create_session_and_redirect'));
    }

    public function init() {
        $this->is_paid = false;
        $this->valid_recaptcha = validate_recaptcha();
        $this->order_status = 'pending';
        $this->id = 'stripe_gateway';
        $this->name = 'Stripe';
        $this->brands = ['Visa', 'Mastercard'];
        $this->cards_accepted = implode_last($this->brands, __('o', 'dynamicpackages'));
        $this->type = 'card-off-site';
        $this->mode = get_option($this->id . '_mode') ?: 'test';
        $this->sec_key_live = get_option($this->id . '_live_secret');
        $this->sec_key_test = get_option($this->id . '_test_secret');
        $this->webhook_secret = get_option($this->id . '_webhook_secret');
        $this->min = (float)(get_option($this->id . '_min') ?: 5);
        $this->max = (float)(get_option($this->id . '_max') ?: 99999);
        $this->show = (int) get_option($this->id . '_show'); // follow your other gateways
        $this->color = '#fff';
        $this->background_color = '#635bff'; // Stripe purple

        $this->sec_key = ($this->mode === 'live') ? $this->sec_key_live : $this->sec_key_test;
        $this->plugin_dir_url = plugin_dir_url(__DIR__);
        $this->icon = '<span class="dashicons dashicons-cart"></span>';
        $this->gateway_coupon = 'STRIPE';

        new stripe_gateway_webhook($this->id, $this->mode, $this->sec_key, $this->webhook_secret);
        new stripe_gateway_confirmation_page($this->id, $this->mode, $this->sec_key);
    }

    /* ---------- Admin settings ---------- */

    public function settings_init() {
        register_setting($this->id . '_settings', $this->id . '_mode', 'esc_html');

        register_setting($this->id . '_settings', $this->id . '_live_secret', 'esc_html');
        register_setting($this->id . '_settings', $this->id . '_test_secret', 'esc_html');
        register_setting($this->id . '_settings', $this->id . '_webhook_secret', 'esc_html');

        register_setting($this->id . '_settings', $this->id . '_show', 'intval');
        register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
        register_setting($this->id . '_settings', $this->id . '_max', 'floatval');

        add_settings_section(
            $this->id . '_settings_section',
            esc_html(__('Stripe Settings', 'dynamicpackages')),
            '',
            $this->id . '_settings'
        );

        // Add fields similar to Yappy/Cuanto style (re-use your input helpers if you have them)
        add_settings_field($this->id . '_mode', __('Mode', 'dynamicpackages'), array($this, 'input_mode'), $this->id . '_settings', $this->id . '_settings_section');

        add_settings_field($this->id . '_test_secret', 'Test Secret Key', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_test_secret']);
        add_settings_field($this->id . '_live_secret', 'Live Secret Key', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_live_secret']);
        add_settings_field($this->id . '_webhook_secret', 'Webhook Signing Secret', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_webhook_secret']);

        add_settings_field($this->id . '_show', __('Where to show', 'dynamicpackages'), array($this, 'input_show'), $this->id . '_settings', $this->id . '_settings_section');
        add_settings_field($this->id . '_min', __('Min Amount', 'dynamicpackages'), array($this, 'input_number'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_min', 'step'=>'0.01']);
        add_settings_field($this->id . '_max', __('Max Amount', 'dynamicpackages'), array($this, 'input_number'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_max', 'step'=>'0.01']);
    }

    public function add_settings_page() {
        add_submenu_page($this->plugin_id, $this->name, '💸 '. $this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Stripe</h1><form method="post" action="options.php">';
        settings_fields($this->id . '_settings');
        do_settings_sections($this->id . '_settings');
        submit_button();
        echo '</form></div>';
    }

    /* ---------- Visibility like other gateways ---------- */

    public function is_active() { return !empty($this->sec_key); }

    public function show() {
        $cache_key = $this->id . '_show';
        if (isset(self::$cache[$cache_key])) return self::$cache[$cache_key];
        $out = false;

        if (is_singular('packages') && $this->is_active()) {
            if ($this->is_valid()) $out = true;
        }
        if (is_confirmation_page() && $this->is_active() && dy_validators::validate_request()) {
            $out = true;
        }
        return self::$cache[$cache_key] = $out;
    }

    public function is_valid() {
        $cache_key = $this->id . '_is_valid';
        if (isset(self::$cache[$cache_key])) return self::$cache[$cache_key];

        $out = false;
        if ($this->is_active()) {
            $min = (float)$this->min; $max = (float)$this->max;
            $payment = package_field('package_payment');
            $deposit = dy_validators::has_deposit();
            $amount = dy_utilities::total();

            if ($amount >= $min && $amount <= $max) {
                if ($payment == $this->show || $this->show == 0) {
                    $out = $deposit ? true : true; // adopt logic from yappy/paguelo to honor deposit/full rules
                }
            }
        }
        return self::$cache[$cache_key] = $out;
    }

    /* ---------- Listing + Button ---------- */

    public function add_gateway($array) {
        if ($this->show()) {
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

    /* ---------- Render / Flow ---------- */
    public function create_session_and_redirect() {

		if ( is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_cron() ) {
			return;
		}

		if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

        if(
            dy_validators::validate_request() === false 
            || $this->is_request_submitted() === false 
            || $this->valid_recaptcha === false
        ) {
            return;
        }

        $metadata = $this->get_stipe_session_metadata();
        $amount = dy_utilities::payment_amount();
        $currency = strtolower(currency_name());
        \Stripe\Stripe::setApiKey($this->sec_key);

        $customer = \Stripe\Customer::create([
            'email' => secure_post('email'),
            'name' => trim(secure_post('first_name') . ' ' . secure_post('lastname'))
        ]);

        $booking_url = html_entity_decode(secure_post('booking_url'));
        $package_id = (int) secure_post('dy_id');
        $checkout_id = $this->generate_checkout_key($package_id, $customer->id);
        
        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'adaptive_pricing' => [
                'enabled' => false//this options removes the pab and local currencies
            ],
            'customer' => $customer->id,
            'success_url' => add_query_arg(['stripe_status' => 'success', 'stripe_checkout_id' => $checkout_id], $booking_url),
            'cancel_url' => add_query_arg(['stripe_status' => 'cancel'], $booking_url),
            'metadata' => $metadata,
            'line_items'=> [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round($amount * 100),
                    'product_data' => [
                        'name' => $this->get_stripe_item_title($package_id),
                        'description' => $this->get_stripe_item_description(), // Stripe allows longer here
                    ]
                ]
            ]]
        ]);

        // stripe listen --forward-to localhost:8888/wordpress/wp-json/dy-core/stripe-webhook

        set_transient("stripe_checkout_id_{$checkout_id}", $session->id, (6 * HOUR_IN_SECONDS) );

        wp_redirect($session->url, 303);
        exit;
    }

    public function generate_checkout_key($package_id, $customer_id) {
        $package_hash = (string) secure_post('hash');
        $amount = dy_utilities::payment_amount();
        $title = $this->get_stripe_item_title($package_id);
        $description = $this->get_stripe_item_description();

        return md5($package_hash . $amount . $title . $description . $customer_id);    
    }

    public function get_stipe_session_metadata() {

        $metadata = [];

        foreach($_POST as $key => $val) {

            if($key === 'g-recaptcha-response') continue;
            if($key === 'hash') continue;

            $metadata[$key] = secure_post($key);
        }

        return $metadata;
    }

    public function get_stripe_item_title($package_id) {

        $cache_key = 'dy_get_stripe_item_title';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $title = get_the_title();
        
        $payment_type = (int) package_field('package_payment', $package_id);
        $deposit_amount = ($payment_type === 0) ? 0 : (float) package_field('package_deposit', $package_id);

        if($payment_type === 1 && $deposit_amount > 0) {
            $title = sprintf(__('%s deposit : %s', 'dynamicpackages'), "{$deposit_amount}%", $title);
        }

        $title = mb_strimwidth($title, 0, 127, '');

        self::$cache[$cache_key] = $title;
        
        return $title;
    }

    public function get_stripe_item_description() {

        $cache_key = 'dy_get_stripe_item_description';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $description = (string) apply_filters('dy_description', '');
        $add_ons = (array) apply_filters('dy_included_add_ons_arr', []);
        $not_included = (string) dy_utilities::implode_taxo_names('package_not_included', __('or', 'dynamicpackages'), '❌');
        $included = (string) dy_utilities::implode_taxo_names('package_included', __('and', 'dynamicpackages'), '✅');

        if(is_array($add_ons) && count($add_ons) > 0) {

            $description .= sprintf(__(". ➡️ %s: "), __('Add-ons', 'dynamicpackages'));
            $add_ons_arr = array_map(function($add_on_item) {
                return $add_on_item['name'];
            }, $add_ons);

            $description .= implode_last($add_ons_arr, __('and', 'dynamicpackages'), '👍');
        }

        if(!empty($included)) {
            $description .= sprintf(__(". ➡️ %s: "), __('Included', 'dynamicpackages'));
            $description .= $included;
        }

        if(!empty($not_included)) {
            $description .= sprintf(__(". ➡️ %s: "), __('Not Included', 'dynamicpackages'));
            $description .= $not_included;
        }

        $description = mb_strimwidth($description, 0, 2000, '');

        self::$cache[$cache_key] = $description;
        
        return $description;
    }

	public function is_request_submitted()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid_request';
		global $dy_request_invalids;
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(is_confirmation_page() && !isset($dy_request_invalids))
		{
			if(secure_post('dy_request') === $this->id && dy_utilities::payment_amount() > 1)
			{
				$output = true;
			}
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}


    /* ---------- Small input helpers (reuse your existing ones if available) ---------- */

    public function input_text($args) {
        $name = $args['name'];
        $val = esc_attr(get_option($name));
        echo '<input type="text" name="'.esc_attr($name).'" value="'.$val.'" class="regular-text" />';
    }
    public function input_number($args) {
        $name = $args['name']; $step = $args['step'] ?? '1';
        $val = esc_attr(get_option($name));
        echo '<input type="number" step="'.esc_attr($step).'" name="'.esc_attr($name).'" value="'.$val.'" class="small-text" />';
    }
    public function input_show() {
        // mirror Yappy/PayPal (e.g., 0=both, 1=deposit only, 2=full only) if that’s how you do it
        $name = $this->id . '_show';
        $val = (int)get_option($name);
        echo '<select name="'.esc_attr($name).'">
                <option value="0" '.selected($val,0,false).'>'.esc_html__('Show Everywhere','dynamicpackages').'</option>
                <option value="1" '.selected($val,1,false).'>'.esc_html__('Deposit Only','dynamicpackages').'</option>
                <option value="2" '.selected($val,2,false).'>'.esc_html__('Full Payment Only','dynamicpackages').'</option>
             </select>';
    }
    public function input_mode() {
        $name = $this->id . '_mode';
        $val = get_option($name) ?: 'test';
        echo '<select name="'.esc_attr($name).'">
                <option value="test" '.selected($val,'test',false).'>Test</option>
                <option value="live" '.selected($val,'live',false).'>Live</option>
             </select>';
    }
    public function input_type() {
        $name = $this->id . '_type';
        $val = get_option($name) ?: 'card-on-site';
        echo '<select name="'.esc_attr($name).'">
                <option value="card-on-site" '.selected($val,'card-on-site',false).'>On-site (Stripe Elements)</option>
                <option value="card-off-site" '.selected($val,'card-off-site',false).'>Off-site (Stripe Checkout)</option>
             </select>';
    }

	public function branding()
	{
		$output = '<p><img src="'.esc_url($this->plugin_dir_url.'assets/visa-mastercard.svg').'" width="250" height="50" /></p>';
		$output .= '<p class="large text-muted">'.sprintf(__('Pay with %s thanks to %s', 'dynamicpackages'), $this->cards_accepted, $this->name).'</p>';
		return $output;
	}

}

?>