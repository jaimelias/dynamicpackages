<?php

class Dynamic_Packages_Providers {

    function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->name = 'package_provider';
        add_action('init', array(&$this, 'register_taxonomy'));
        add_action('init', array(&$this, 'handle_create_edit'));
    }

    public function handle_create_edit()
    {
        //handles edit and save
		add_action($this->name.'_edit_form_fields', array(&$this, 'form'), 10, 2);	
		add_action( 'create_'.$this->name, array(&$this, 'handle_save'), 10, 2);
		add_action( 'edited_'.$this->name, array(&$this, 'handle_save'), 10, 2);
    }

	public function handle_save($term_id) {
		
		if(!current_user_can( 'edit_posts' )) return;
		
		if(isset($_POST[$this->name.'_language']))
		{
			update_term_meta($term_id, $this->name.'_language', sanitize_text_field($_POST[$this->name.'_language']));
		}
		if(isset($_POST[$this->name.'_emails']))
		{
			update_term_meta($term_id, $this->name.'_emails', esc_textarea($this->sanitize_items_per_line('sanitize_email', $_POST[$this->name.'_emails'])));
		}
	}

	public function sanitize_items_per_line($sanitize_func, $str)
	{
		$str = html_entity_decode($str);
		$emails = explode("\r\n", $str);		
		$arr = array_slice(array_unique(array_filter(array_map($sanitize_func, $emails))), 0, 10) ;

		return implode("\r\n", $arr);
	}

	public function textarea_items_per_line($term_id, $sanitize_func = 'sanitize_text_field')
	{
		$emails = get_term_meta($term_id, $this->name.'_emails', true);
		return '<textarea rows="10" name="'.esc_attr($this->name.'_emails').'">'.esc_textarea($this->sanitize_items_per_line($sanitize_func, $emails)).'</textarea>';
	}

	public function language_select($term_id)
	{
		$output = '';
		$languages = dy_utilities::get_languages();
		$count_languages = count($languages);
		$language = get_term_meta($term_id, $this->name.'_language', true);
		$language = ($language) ? $language : dy_utilities::current_language();

		if($count_languages > 1)
		{
			$options = '';

			for($x = 0; $x < $count_languages; $x++)
			{
				$value = $languages[$x];
				$selected = ($value === $language) ? ' selected ' : '';
				$options .= '<option '.esc_attr($selected).' value="'.esc_attr($value).'">'.esc_html($value).'</option>';
			}
			
			$output = '<select name="'.esc_attr($this->name.'_language').'">'.$options.'</select>';
		}
		else
		{
			$value = $languages[0];
			$output = '<input name="'.esc_attr($this->name.'_language').'" value="'.esc_attr($value).'" disabled/>';
		}

		return $output;
	}

	public function row($name, $label, $field, $description = null)
	{
		if($description)
		{
			$description = '<br/><p class="description">'.esc_html($description).'</p>';
		}
		return '<tr class="form-field"><th scope="row" valign="top"><label for="'.esc_attr($name).'">'.esc_html($label).'</label></th><td>'.$field.$description.'</td></tr>';
	}

    public function form($term)
    {
		$rows = '';
        $term_id = $term->term_id;
        $language_select = $this->language_select($term_id);
		$rows .= $this->row(
			$this->name.'_language', 
			__('Provider Language', 'dynamicpackages'), 
			$this->language_select($term_id)
		);
		$rows .= $this->row(
			$this->name.'_emails', 
			__('Provider Emails', 'dynamicpackages'), 
			$this->textarea_items_per_line($term_id, 'sanitize_email'),
			__('1 email per line. Up to 10 emails maximum.', 'dynamicpackages')
		);
		echo $rows;
    }

	public function register_taxonomy() {
		
		$labels = array(
			'name' => __( 'Providers', 'dynamicpackages'),
			'singular_name' => __( 'Provider', 'dynamicpackages')
		);		

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'public' => true,
			'show_in_rest' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => false
		);

		register_taxonomy( $this->name, array( 'packages' ), $args );
	}
}

?>