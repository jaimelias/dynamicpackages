<?php

class Dynamicpackages_Taxonomy_Providers {

    function __construct()
    {
		$this->name = 'package_provider';
        add_action('init', array(&$this, 'handle_create_edit'));
    }

    public function handle_create_edit()
    {
        //handles edit and save
		add_action($this->name.'_edit_form_fields', array(&$this, 'form'), 10, 2);
		add_action( 'create_'.$this->name, array(&$this, 'handle_save'), 10, 2);
		add_action( 'edited_'.$this->name, array(&$this, 'handle_save'), 10, 2);
		add_filter('dy_list_providers', array(&$this, 'get_providers'));
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

	public function admin_taxonomy_form_row($name, $label, $field, $description = null)
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
		$rows .= $this->admin_taxonomy_form_row($this->name.'_language', __('Provider Language', 'dynamicpackages'), $this->language_select($term_id));
		$rows .= $this->admin_taxonomy_form_row($this->name.'_emails', __('Provider Emails', 'dynamicpackages'), $this->textarea_items_per_line($term_id, 'sanitize_email'), __('1 email per line. Up to 10 emails maximum.', 'dynamicpackages'));
		echo $rows;
    }

	public function email_str_row_to_array($str)
	{
		$output = array();

		if($str)
		{
			$emails = explode("\r\n", html_entity_decode($str));		
			$output = array_slice(array_unique(array_filter(array_map('sanitize_email', $emails))), 0, 10);
		}


		return $output;
	}

	public function get_providers($output = array())
	{
		$which_var = $this->name.'_get_emails';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			global $post;

			if(isset($post))
			{
				$terms = get_the_terms($post->ID, $this->name);

				if ( ! empty( $terms ) && ! is_wp_error( $terms ) )
				{
					foreach ( $terms as $t )
					{
						$emails_str = get_term_meta($t->term_id, 'package_provider_emails', true);
						$emails = $this->email_str_row_to_array($emails_str);

						if(!array_key_exists($t->name, $output))
						{
							$output[$t->name] = array(
								'emails' => array()
							);
						}

						for($x = 0; $x < count($emails); $x++)
						{
							array_push($output[$t->name]['emails'], $emails[$x]);
						}
					}
				}
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
}

?>