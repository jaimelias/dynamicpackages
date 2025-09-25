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

        // For content replacement or redirects if needed:
        add_filter('dy_request_the_content', array($this, 'filter_content'), 100);
        add_filter('wp_headers', array($this, 'maybe_redirect'));

        // AJAX endpoints to create intents / sessions securely:
        add_action('wp_ajax_dy_stripe_create_pi', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_nopriv_dy_stripe_create_pi', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_dy_stripe_create_checkout', array($this, 'ajax_create_checkout_session'));
        add_action('wp_ajax_nopriv_dy_stripe_create_checkout', array($this, 'ajax_create_checkout_session'));

        // Webhook endpoint (map to a page or custom rewrite); can also use a dedicated URL:
        add_action('init', array($this, 'maybe_handle_webhook'));
    }

    public function init() {
        $this->order_status   = 'pending';
        $this->id             = 'stripe_gateway';
        $this->name           = 'Stripe';
        $this->brands = ['Visa', 'Mastercard'];
        $this->cards_accepted = implode_last($this->brands, __('o', 'dynamicpackages'));
        // Allow admin to choose: 'card-on-site' (Elements) or 'card-off-site' (Checkout)
        $this->type           = get_option($this->id . '_type') ?: 'card-on-site';

        $this->mode           = get_option($this->id . '_mode') ?: 'test';
        $this->pubkey_live    = get_option($this->id . '_live_publishable');
        $this->seckey_live    = get_option($this->id . '_live_secret');
        $this->pubkey_test    = get_option($this->id . '_test_publishable');
        $this->seckey_test    = get_option($this->id . '_test_secret');
        $this->webhook_secret = get_option($this->id . '_webhook_secret');

        $this->min            = (float)(get_option($this->id . '_min') ?: 5);
        $this->max            = (float)(get_option($this->id . '_max') ?: 99999);
        $this->show           = (int) get_option($this->id . '_show'); // follow your other gateways
        $this->color          = '#fff';
        $this->background_color = '#635bff'; // Stripe purple

        $this->pubkey = ($this->mode === 'live') ? $this->pubkey_live : $this->pubkey_test;
        $this->seckey = ($this->mode === 'live') ? $this->seckey_live : $this->seckey_test;
        $this->plugin_dir_url = plugin_dir_url(__DIR__);
        $this->icon = '<span class="dashicons dashicons-cart"></span>';
        $this->gateway_coupon = 'STRIPE';
    }

    /* ---------- Admin settings ---------- */

    public function settings_init() {
        register_setting($this->id . '_settings', $this->id . '_mode', 'esc_html');
        register_setting($this->id . '_settings', $this->id . '_type', 'esc_html');

        register_setting($this->id . '_settings', $this->id . '_live_publishable', 'esc_html');
        register_setting($this->id . '_settings', $this->id . '_live_secret', 'esc_html');
        register_setting($this->id . '_settings', $this->id . '_test_publishable', 'esc_html');
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
        add_settings_field($this->id . '_type', __('Checkout Type', 'dynamicpackages'), array($this, 'input_type'), $this->id . '_settings', $this->id . '_settings_section');

        add_settings_field($this->id . '_test_publishable', 'Test Publishable Key', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_test_publishable']);
        add_settings_field($this->id . '_test_secret', 'Test Secret Key', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_test_secret']);
        add_settings_field($this->id . '_live_publishable', 'Live Publishable Key', array($this, 'input_text'), $this->id . '_settings', $this->id . '_settings_section', ['name' => $this->id . '_live_publishable']);
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

    public function is_active() { return !empty($this->pubkey) && !empty($this->seckey); }

    public function show() {
        $cache_key = $this->id . '_show';
        if (isset(self::$cache[$cache_key])) return self::$cache[$cache_key];
        $out = false;

        if (is_singular('packages') && $this->is_active()) {
            if ($this->is_valid()) $out = true;
        }
        if (is_checkout_page() && $this->is_active() && dy_validators::validate_request()) {
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

    public function filter_content($content) {
        // On-site: inject Elements container + JS when this gateway selected
        // Off-site: maybe show a “Continue to Stripe” with a small recap
        return $content;
    }

    public function maybe_redirect($headers) {
        // For off-site redirections if you copy Cuanto’s pattern
        return $headers;
    }

    /* ---------- AJAX: create intent / checkout session ---------- */

    public function ajax_create_payment_intent() {
        check_ajax_referer('dy_checkout', 'nonce');
        if (!$this->is_active()) wp_send_json_error(['message' => 'Stripe inactive']);

        // calculate amount using your helpers
        $amount = dy_utilities::total(); // in major units; Stripe needs cents for most currencies
        $currency = strtolower(get_option('dy_currency') ?: 'usd');

        // Init Stripe SDK
        if (!class_exists('\\Stripe\\Stripe')) {
            // require_once path to vendor if you embed the SDK
        }
        \Stripe\Stripe::setApiKey($this->seckey);

        $pi = \Stripe\PaymentIntent::create([
            'amount' => round($amount * 100),
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'package_id' => get_the_ID(),
                'order_ref'  => uniqid('dy_'),
            ],
        ]);

        wp_send_json_success(['clientSecret' => $pi->client_secret]);
    }

    public function ajax_create_checkout_session() {
        check_ajax_referer('dy_checkout', 'nonce');
        if (!$this->is_active()) wp_send_json_error(['message' => 'Stripe inactive']);

        $amount = dy_utilities::total();
        $currency = strtolower(get_option('dy_currency') ?: 'usd');

        \Stripe\Stripe::setApiKey($this->seckey);
        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => get_the_title()],
                    'unit_amount' => round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => add_query_arg(['gateway' => $this->id, 'status' => 'success'], get_permalink()),
            'cancel_url'  => add_query_arg(['gateway' => $this->id, 'status' => 'cancel'], get_permalink()),
            'metadata'    => ['package_id' => get_the_ID()],
        ]);

        wp_send_json_success(['url' => $session->url]);
    }

    /* ---------- Webhook ---------- */

    public function maybe_handle_webhook() {
        // If you map /?dy_stripe_webhook=1
        if (isset($_GET['dy_stripe_webhook'])) {
            $payload = file_get_contents('php://input');
            $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            $secret = $this->webhook_secret;

            try {
                if (!class_exists('\\Stripe\\Webhook')) {
                    // require vendor
                }
                $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
            } catch (\Exception $e) {
                status_header(400); echo 'Invalid'; exit;
            }

            switch ($event->type) {
                case 'payment_intent.succeeded':
                case 'checkout.session.completed':
                    // Mark booking paid, email, etc. Use your existing helpers (like others do).
                    // dy_form_actions / mailers you already use in other gateways.
                    break;
                case 'payment_intent.payment_failed':
                    // Handle failure if needed
                    break;
            }
            status_header(200); echo 'OK'; exit;
        }
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