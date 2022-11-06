<?php

if ( !defined( 'WPINC' ) ) exit;

class Dynamic_Core_Public {
    
    public function __construct()
    {
        add_shortcode('whatsapp', array(&$this, 'whatsapp_button'));
    }

	public function whatsapp_button($content = '')
	{
		return whatsapp_button();
	}

}


new Dynamic_Core_Public();

?>