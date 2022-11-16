<?php

if ( !defined( 'WPINC' ) ) exit;

class Dynamic_Core_Public {
    
    public function __construct()
    {
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
        $this->dirname_file = dirname( __FILE__ );

        add_shortcode('whatsapp', array(&$this, 'whatsapp_button'));
        add_action( 'wp_head', array(&$this, 'gtm_tracking_script'));
        add_action( 'minimal_pre_body', array(&$this, 'gtm_tracking_iframe'));
        add_action( 'wp_head', array(&$this, 'gtag_tracking_script'));
        add_action( 'wp_head', array(&$this, 'gtag_conversion_script'));
        add_action( 'wp_head', array(&$this, 'facebook_pixel_tracking_script'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
    }

    public function enqueue_scripts()
    {
        global $dy_load_recaptcha_scripts;
        global $dy_load_picker_scripts;
        global $dy_load_request_form_utilities_scripts;

        wp_enqueue_script('landing-cookies', $this->plugin_dir_url_file . 'js/cookies.js', array('jquery'), 'async_defer', true);
        wp_enqueue_script('sha512', $this->plugin_dir_url_file . 'js/sha512.js', '', 'async_defer', true);
        wp_enqueue_script('dy-core-utilities', $this->plugin_dir_url_file . 'js/utilities.js', array('sha512', 'jquery', 'landing-cookies'), time(), true);
        wp_add_inline_script('dy-core-utilities', $this->args(), 'before');

        if(isset($dy_load_recaptcha_scripts))
        {
            wp_enqueue_script('recaptcha-v3', 'https://www.google.com/recaptcha/api.js', '', 'async_defer', true);
        }

        //picker start

        if(isset($dy_load_picker_scripts))
        {
            wp_enqueue_script( 'picker-js', $this->plugin_dir_url_file . 'js/picker/picker.js', array('jquery'), '3.6.2', true);
            wp_enqueue_script( 'picker-date-js', $this->plugin_dir_url_file . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.6.2', true);
            wp_enqueue_script( 'picker-time-js', $this->plugin_dir_url_file . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.6.2', true);	
            wp_enqueue_script( 'picker-legacy', $this->plugin_dir_url_file . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.6.2', true);

            $picker_translation = 'js/picker/translations/'.get_locale().'.js';
                    
            if(file_exists($this->dirname_file.'/'.$picker_translation))
            {
                wp_enqueue_script( 'picker-time-translation', $this->plugin_dir_url_file.$picker_translation, array('jquery', 'picker-js'), '3.6.2', true);
            }	
            //picker end
        }


        
        if(isset($dy_load_request_form_utilities_scripts))
        {
            wp_enqueue_script('dy-core-request-form-utilities', $this->plugin_dir_url_file . 'js/request-form-utilities.js', array('jquery', 'landing-cookies'), time(), false);
        }
    }

    public function enqueue_styles()
    {
        global $dy_load_picker_scripts;

        if(isset($dy_load_picker_scripts))
        {
            wp_enqueue_style( 'picker-css', $this->plugin_dir_url_file . 'css/picker/default.css', array(), '3.6.2', 'all');
            wp_add_inline_style('picker-css', $this->get_inline_file('css/picker/default.date.css'));
            wp_add_inline_style('picker-css', $this->get_inline_file('css/picker/default.time.css'));
        }
    }
	
	public function get_inline_file($sheet_dir)
	{
		ob_start();
		require_once($this->dirname_file . '/'. $sheet_dir);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
    
    public function args()
    {
        $args = array(
            'homeUrl' => home_url(),
            'permalink' => get_the_permalink(),
            'pluginUrl' => $this->plugin_dir_url_file,
            'lang' => current_language(),
            'ipGeoLocation' => array(
                'token' => get_option('dy_ipgeolocation_api_token')
            )
        );

        return 'const dyCoreArgs = '.json_encode($args).';';
    }

    public function gtm_tracking_script()
    {
        $value = get_option('dy_gtm_tracking_id');

        if($value): ?>

        <!-- Start Google - Global Tag Manager (GMT) -->
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','<?php echo esc_html($value); ?>');
        </script>
        <!-- End Google - Global Tag Manager (GMT) -->

        <?php endif;
    }

    public function gtm_tracking_iframe()
    {
        $value = get_option('dy_gtm_tracking_id');

        if($value): ?>

        <!-- Start Google - Global Tag Manager (GMT) noscript-->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_html($value); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google - Global Tag Manager (GMT) noscript -->

        <?php endif;
    }

    public function gtag_tracking_script()
    {
        $value = get_option('dy_gtag_tracking_id');

        if($value): ?>

        <!-- Start Google - Analytics GA4 (GTAG) -->

        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html($value); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_html($value); ?>');
        </script>
        
        <!-- End Google - Analytics GA4 (GTAG) -->

        <?php endif;       
    }

    public function gtag_conversion_script()
    {
        $value = get_option('dy_gtag_conversion_id');

        if($value): ?>

        <!-- Start Google - Ads Conversion (GTAG) -->

        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html($value); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_html($value); ?>');
        </script>
        
        <!-- End Google - Ads Conversion (GTAG) -->

        <?php endif;       
    }

    public function facebook_pixel_tracking_script()
    {
        $value = get_option('dy_facebook_pixel_id');

        if($value): ?>

        <!-- Start Facebook Pixel -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window,document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '<?php echo esc_html(get_theme_mod('facebook_pixel_id')); ?>'); 
            fbq('track', 'PageView');
		</script>
        <!-- End Facebook Pixel -->

        <?php endif;           
    }


	public function whatsapp_button($content = '')
	{
		return whatsapp_button();
	}

}


new Dynamic_Core_Public();

?>