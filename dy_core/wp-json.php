<?php 


if ( !defined( 'WPINC' ) ) exit;


class Dynamic_Core_WP_JSON {


    public function __construct()
    {
        add_action( 'rest_api_init', array(&$this, 'core_args') );
    }

    public function core_args()
    {
        register_rest_route( 'dy-core', 'args', array(
            'methods' => 'GET',
            'callback' => array(&$this, 'core_args_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function core_args_callback($req)
    {
        $utc_time = date('Y-m-d H:i:s', time());
        $dy_nonce = wp_create_nonce('dy_nonce');
        
        return array('dy_nonce' => $dy_nonce, 'utc_date_time' => $utc_time);
    }
}

new Dynamic_Core_WP_JSON();

?>