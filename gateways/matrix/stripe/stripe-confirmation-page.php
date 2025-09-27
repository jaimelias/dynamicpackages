<?php
if ( !defined('WPINC') ) exit;

#[AllowDynamicProperties]
class stripe_gateway_confirmation_page {

    private static $cache = [];

    function __construct($id, $mode, $sec_key) {

        $this->id = $id;
        $this->mode = $mode;
        $this->sec_key = $sec_key;
        $this->order_status = 'pending';

        add_action('init', [$this, 'catch_redirect_success'], PHP_INT_MAX);
        add_filter('the_content', [$this, 'the_content'], PHP_INT_MAX);
        add_filter('the_title', [$this, 'the_title'], PHP_INT_MAX);
        add_filter('pre_get_document_title', [$this, 'wp_title'], PHP_INT_MAX);
        add_filter('get_the_excerpt', [$this, 'the_excerpt'], PHP_INT_MAX);
    }

    public function is_confirmation_page() {

        //function secure_get($key) === $_GET[$key]

        if(
            $_SERVER['REQUEST_METHOD'] !== 'GET' 
            || !is_booking_page()
            || !secure_get('hash')
            || secure_get('stripe_status') !== 'success'
            || empty($this->sec_key)
            || empty(secure_get('stripe_checkout_id'))
        ) return false;

        return true;
    }

    public function get_session_id() {

        if($this->is_confirmation_page() === false) return '';

        $checkout_id = secure_get('stripe_checkout_id');
        $session_id = (string) get_transient("stripe_checkout_id_{$checkout_id}");
        
        return $session_id;
    }

    public function catch_redirect_success() {

        if($this->is_confirmation_page() === false) return;
        
        $session_id = $this->get_session_id();

        if(empty($session_id)) return;
    
        \Stripe\Stripe::setApiKey($this->sec_key);

        try {
            $session = \Stripe\Checkout\Session::retrieve($session_id);

            if ($session && $session->payment_status === 'paid') {
                // Payment was confirmed
                $this->order_status = 'paid';
            } else {
                $this->order_status = 'pending'; // fallback
            }
        } catch (\Exception $e) {
            write_log('Stripe verify error: ' . $e->getMessage());
            $this->order_status = 'error';
        }

    }

    public function the_content($content) {

        if($this->order_status !== 'paid' || $this->is_confirmation_page() === false) return $content;


        $lines = [];

        $lines[] = esc_html( __('Payment successful', 'dynamicpackages') );
        $lines[] = esc_html( __('Thanks! Your payment has been received.', 'dynamicpackages') );

        $session_id = $this->get_session_id();

        if ($session_id) {
            $lines[] = esc_html( sprintf(
                __('Reference: %s', 'dynamicpackages'),
                $session_id
            ));
        }

        $home_url = esc_url( home_url('/') );
        $continue_label = esc_html( __('Continue', 'dynamicpackages') );

        //stripe listen --forward-to localhost:8888/wordpress/wp-json/dy-core/stripe-webhook

        // Simple, unstyled markup
        return sprintf(
            "<div>%s</div><div>%s</div>%s<div><a href=\"%s\">%s</a></div>",
            $lines[0],
            $lines[1],
            isset($lines[2]) ? sprintf('<div>%s</div>', $lines[2]) : '',
            $home_url,
            $continue_label
        );

    }
    public function the_title($title) {

        if(!in_the_loop()) return $title;
        if($this->order_status !== 'paid' || $this->is_confirmation_page() === false) return $title;

        return __('Payment successful', 'dynamicpackages');
    }
    public function wp_title($title) {

        if($this->order_status !== 'paid' || $this->is_confirmation_page() === false) return $title;

        return __('Payment successful', 'dynamicpackages');;
    }
    public function the_excerpt($excerpt) {

        if($this->order_status !== 'paid' || $this->is_confirmation_page() === false) return $excerpt;

        return __('Your payment was processed successfully.', 'dynamicpackages');
    }

}

?>