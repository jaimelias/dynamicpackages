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

        add_action('init', [$this, 'catch_redirect_success'], 99);
        add_filter('the_content', [$this, 'the_content'], 99);
        add_filter('the_title', [$this, 'the_title'], 99);
        add_filter('wp_title', [$this, 'wp_title'], 99);
        add_filter('get_the_excerpt', [$this, 'the_excerpt'], 99);
    }

    public function catch_redirect_success() {

        //function secure_get($key) === $_GET[$key]
        //function secure_cookie($key) === $_COOKIE[$key]

        if(
            $_SERVER['REQUEST_METHOD'] !== 'GET' 
            || !is_booking_page()
            || secure_get('stripe_status') !== 'success'
            || empty($this->sec_key)
        ) return;

        if(!secure_get('hash')) return;

        $package_hash = (string) secure_get('hash');
        $md5_package_hash = md5($package_hash);
        $session_key = "stripe_session_id_{$md5_package_hash}";

        if(empty(secure_cookie($session_key))) return;

        $session_id = secure_cookie($session_key);

       
        write_log($session_id);

        //Stripe::setApiKey($this->sec_key);


        //$this->order_status = 'paid';
    }

    public function the_content($content) {

        if($this->order_status === 'paid') {

        }

        return $content;
    }
    public function the_title($title) {

        if($this->order_status === 'paid') {
            
        }

        return $title;
    }
    public function wp_title($title) {

        if($this->order_status === 'paid') {
            
        }

        return $title;
    }
    public function the_excerpt($excerpt) {

        if($this->order_status === 'paid') {
            
        }

        return $excerpt;
    }

}

?>