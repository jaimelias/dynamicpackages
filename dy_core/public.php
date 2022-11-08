<?php

if ( !defined( 'WPINC' ) ) exit;

class Dynamic_Core_Public {
    
    public function __construct()
    {
        add_shortcode('whatsapp', array(&$this, 'whatsapp_button'));
        add_action( 'wp_head', array(&$this, 'gtm_tracking_script'));
        add_action( 'minimal_pre_body', array(&$this, 'gtm_tracking_iframe'));
        add_action( 'wp_head', array(&$this, 'gtag_tracking_script'));
        add_action( 'wp_head', array(&$this, 'facebook_pixel_tracking_script'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        global $dy_load_recaptcha_scripts;

        if(isset($dy_load_recaptcha_scripts))
        {
            wp_enqueue_script('recaptcha-v3', 'https://www.google.com/recaptcha/api.js', '', 'async_defer', true);
        }
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

        <!-- Start Google - Global Site Tag (GTAG) -->

        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html($value); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_html($value); ?>');
        </script>
        
        <!-- End Google - Global Site Tag (GTAG) -->

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