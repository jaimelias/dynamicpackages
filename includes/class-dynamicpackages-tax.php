<?php

class dy_Tax_Mod
{
	function __construct()
	{
		$this->init();
	}
	
	public function init()
	{
		add_action('init', array(&$this, 'add_ons'));
		add_action('admin_init', array(&$this, 'title_modifier'), 10, 2);
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue'));
		add_action('dy_checkout_items', array(&$this, 'checkout_items'), 10);
		add_filter('dy_included_add_ons_list', array(&$this, 'included_add_ons_list'));
	}
	
	public static function enqueue()
	{
		if(isset($_GET['taxonomy']) && isset($_GET['tag_ID']) && isset($_GET['post_type']))
		{
			if($_GET['taxonomy'] == 'package_add_ons' && $_GET['taxonomy'] != '' && $_GET['post_type'] == 'post')
			{
				dy_Admin::handsontable();
				wp_enqueue_script('dynamicpackages', plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-admin.js', array('jquery', 'handsontableJS'), time(), true);
			}
		}
	}
	
	public static function add_ons()
	{
		$tax = 'package_add_ons';
		add_action($tax.'_edit_form_fields', array('dy_Tax_Mod', 'add_ons_form'), 10, 2);
		add_action( 'create_'.$tax, array('dy_Tax_Mod', 'save'), 10, 2);
		add_action( 'edited_'.$tax, array('dy_Tax_Mod', 'save'), 10, 2);		
	}
	public static function title_modifier()
	{
		$taxonomies = array('package_category', 'package_location');
		
		for($x = 0; $x < count($taxonomies); $x++)
		{
			$tax = $taxonomies[$x];
			add_action($tax.'_edit_form_fields', array('dy_Tax_Mod', 'title_form'), 10, 2);
			add_action( 'create_'.$tax, array('dy_Tax_Mod', 'save'), 10, 2);
			add_action( 'edited_'.$tax, array('dy_Tax_Mod', 'save'), 10, 2);
		}
	}	
	public static function title_form($term){
	 
		$tax_title_modifier = get_term_meta( $term->term_id, 'tax_title_modifier', true);	
		?>
		<tr class="form-field">
		<th scope="row" valign="top"><label for="tax_title_modifier"><?php esc_html_e( 'Title Modifier', 'dynamicpackages' ); ?></label></th>
			<td>
				<input type="text" name="tax_title_modifier" id="tax_title_modifier" value="<?php echo $tax_title_modifier; ?>">
			</td>
		</tr>
	<?php
	}
		

	public static function save($term_id) {
		
		if(!current_user_can( 'edit_posts' )) return;
		
		global $polylang;
		
		$def_term_id = $term_id;
		
		if(isset($polylang))
		{
			if(pll_current_language() != pll_default_language())
			{	
				if(pll_get_term($def_term_id, pll_default_language()))
				{
					$def_term_id = pll_get_term($def_term_id, pll_default_language());
				}
			}
		}		
		
		if(isset($_POST['tax_title_modifier']))
		{
			update_term_meta($term_id, 'tax_title_modifier', sanitize_text_field($_POST['tax_title_modifier']));
		}
		
		if(isset($_POST['tax_add_ons']))
		{	
			update_term_meta($def_term_id, 'tax_add_ons', sanitize_text_field($_POST['tax_add_ons']));
		}	
		if(isset($_POST['tax_add_ons_max']))
		{
			$tax_add_ons_max = intval(sanitize_text_field($_POST['tax_add_ons_max']));
			
			if($tax_add_ons_max < 1)
			{
				$tax_add_ons_max = 1;
			}
			update_term_meta($def_term_id, 'tax_add_ons_max', $tax_add_ons_max);
		}
		if(isset($_POST['tax_add_ons_type']))
		{
			$tax_add_ons_type = intval(sanitize_text_field($_POST['tax_add_ons_type']));
			update_term_meta($def_term_id, 'tax_add_ons_type', $tax_add_ons_type);
		}	
	}
	
	

	public static function add_ons_form($term){
	 
		global $polylang;
		$form = '';
		$term_id = $term->term_id;
		$args = array();
		
		$args['tax_add_ons_type'] = array(
			'tag' => 'select', 
			'type' => 'number', 
			'label' => 'Type of Add-on', 
			'options' => array(
				__('The price is fixed and does not change', 'dynamicpackages'), 
				__('The price varies depending on the duration', 'dynamicpackages')
			), 
			'description' => __('Variable price works only on multi-day and daily rental packages. If the package is calculated per night 1 additional day will be added to this add-on as long as this add-on is variable.', 'dynamicpackages')
		);
		
		$args['tax_add_ons_max'] = array(
			'tag' => 'select',
			'type' => 'number',
			'label' => __('Maximum Number of participants', 'dynamicpackages')
		);
		
		$args['tax_add_ons'] = array(
			'tag' => 'textarea', 
			'class' => 'hidden', 
			'label' => __('Prices Per Person', 'dynamicpackages'), 
			'handsontable' => true
		);

		if(isset($polylang))
		{
			if(pll_current_language() != pll_default_language())
			{
				$def_term_id = pll_get_term($term_id, pll_default_language());
				
				if($def_term_id)
				{
					$term_id = $def_term_id;
				}
			}
		}
		
		foreach($args as $k => $v)
		{
			if(array_key_exists('tag', $args[$k]) && array_key_exists('label', $args[$k]))
			{
				$field = '';
				$name = $k;
				$id = $k;
				$class = '';
				$value = get_term_meta($term_id, $k, true);
				$input = '';
				$input .= ' name="'.$k.'" ';
				$input .= ' id="'.$k.'" ';
				$label = $args[$k]['label'];
				
				if(array_key_exists('class', $args[$k]))
				{
					$input .= ' class="'.$args[$k]['class'].'" ';
				}
				if(array_key_exists('type', $args[$k]))
				{
					if($args[$k]['type'] == 'number')
					{
						$input .= ' type="number" ';
						
						if(array_key_exists('min', $args[$k]))
						{
							$input .= ' min="'.$args[$k]['min'].'" ';
						}
					}
				}
				
				if($args[$k]['tag'] == 'input')
				{
					$input .= ' value="'.$value.'" ';
					$field = '<input '.$input.'/>';
				}
				else if($args[$k]['tag'] == 'select')
				{
					$options = '';
					
					if(array_key_exists('options', $args[$k]))
					{
						if(is_array($args[$k]['options']))
						{
							if(count($args[$k]['options']) > 0)
							{
								for($o = 0; $o < count($args[$k]['options']); $o++)
								{
									$is_selected = ($value == $o) ? 'selected="selected"' : '';
									$options .= '<option value="'.esc_html($o).'" '.$is_selected.'>'.esc_html($args[$k]['options'][$o]).'</option>';
								}
							}
						}
					}
					else
					{
						for($x = 0; $x < 100; $x++)
						{
							$is_selected = ($value == ($x+1)) ? 'selected="selected"' : '';
							$options .= '<option  '.$is_selected.'>'.($x+1).'</option>';
						}						
					}
					

					
					$select = '<select '.$input.'>'.$options.'</select>';
					$field = $select;
				}
				else if($args[$k]['tag'] == 'textarea')
				{
					if(array_key_exists('handsontable', $args[$k]))
					{
						if($args[$k]['handsontable'] == true)
						{							
							$field = dy_utilities::handsontable(array(
								'container' => $k.'_c',
								'textarea' => $k,
								'headers' => array(__('Prices', 'dynamicpackages')),
								'type' => array('currency'),
								'min' => 'tax_add_ons_max',
								'max' => 'tax_add_ons_max',
								'value' => $value
							));
						}
						else
						{
							$field = '<textarea '.$input.'>'.$value.'</textarea>';
						}
					}
					else
					{
						$field = '<textarea '.$input.'>'.$value.'</textarea>';
					}
					
				}
				
				if(array_key_exists('description', $args[$k]))
				{
					if($args[$k]['description'] != '')
					{
						$field .= '<br/><p class="description">'.$args[$k]['description'].'</p>';
					}
				}
				
			}
			else
			{
				$err = '';
				
				if(!array_key_exists('tag', $args[$k]))
				{
					$err .= '<br/>tag key not found';
				}
				if(!array_key_exists('label', $args[$k]))
				{
					$err .= '<br/>label key not found';
				}				
				
				$label = 'Invalid Field';
				$field = '<strong>'.$k.':</strong>'.$err;
			}
			$field = '<tr class="form-field"><th scope="row" valign="top"><label for="'.$k.'">'.esc_html($label).'</label></th><td>'.$field.'</td></tr>';

			$form .= $field;
		}
		
		echo $form;
	}
	
	public static function has_add_ons()
	{
		$output = false;
		global $dy_has_add_ons;
		
		if(isset($dy_has_add_ons))
		{
			$output = $dy_has_add_ons;
		}
		else
		{
			$add_ons = self::get_add_ons();
			
			if(is_array($add_ons))
			{
				if(count($add_ons) > 0)
				{
					$output = true;
					$GLOBALS['dy_has_add_ons'] = $output;
				}
			}
		}
		return $output;
	}
	
	public static function checkout_items()
	{
		if(is_booking_page())
		{
			$output = '';
			$pax = intval(dy_utilities::pax_num()) - 1;
			$terms = self::get_add_ons();
			
			if(is_array($terms))
			{
				for($x = 0; $x < count($terms); $x++)
				{
					$term_id = $terms[$x]['id'];
					$label = '<span>'.esc_html($terms[$x]['name']).'</span>';
					$price = $terms[$x]['price'];
					$description = $terms[$x]['description'];
					
					$label .= ' <br/><small class="semibold">'.esc_html(dy_utilities::currency_symbol().number_format($price, 2, '.', ',').' '.__('per person', 'dynamicpackages')).'</small>';

					if($description != '')
					{
						$label .= '<br/><small>'.esc_html($description).'</small>';
					}
					
					if(intval($price) > 0)
					{
						$output .= '<tr><td colspan="2">'.$label.'</td><td><select class="add_ons width-100 border-box small" data-id="'.$term_id.'"><option value="0" selected>'.esc_html(__('No', 'dynamicpackages')).'</option><option value="1">'.esc_html(__('Yes', 'dynamicpackages')).'</option></select></td></tr>';
					}					
				}
			}
			echo $output;
		}
	}
	public static function get_add_ons()
	{
		global $polylang;
		global $post;
		$the_id = $post->ID;
		$package_type = package_field('package_package_type');
		$package_unit = package_field('package_length_unit');
		
		if(property_exists($post, 'post_parent') && !has_term('', 'package_add_ons', $the_id))
		{
			if($post->post_parent > 0)
			{
				$the_id = $post->post_parent;
			}
		}
		
		$output = array();
		$def_lang = true;
		$pax = intval(dy_utilities::pax_num()) - 1;
		
		if($polylang)
		{
			if(pll_current_language() != pll_default_language())
			{
				$def_lang = false;
			}				
		}
		
		$terms = get_the_terms($the_id, 'package_add_ons');
		
		if(is_array($terms))
		{
			foreach($terms as $term)
			{
				$term_id = $term->term_id;
				$name = $term->name;
				$price = 0;
				
				if($def_lang === false)
				{
					$term_id = pll_get_term($term_id, pll_default_language());
				}
				
				$add_ons_price = json_decode(html_entity_decode(get_term_meta($term_id, 'tax_add_ons', true)), true);
				
				$type = get_term_meta($term_id, 'tax_add_ons_type', true);				
				
				if(is_array($add_ons_price))
				{
					if(array_key_exists('tax_add_ons_c', $add_ons_price))
					{
						$add_ons_price = $add_ons_price['tax_add_ons_c'];
						
						if(isset($add_ons_price[$pax]))
						{
							$price = $add_ons_price[$pax][0];
						}
					}
				}
				
				if(intval($type) == 1)
				{
					if($package_type == 1 || $package_type == 2)
					{
						$package_duration = (isset($_REQUEST['booking_extra'])) ? intval(sanitize_text_field($_REQUEST['booking_extra'])) : 1;
						
						if($package_unit > 1)
						{
							if($package_unit == 3)
							{
								$package_duration = $package_duration + 1;
							}
							
							$price = $price * $package_duration;
						}
					}
				}
				
				if($price > 0)
				{
					array_push($output, array(
							'id' => $term_id, 
							'price' => floatval(dy_utilities::currency_format($price)), 
							'name' => $name,
							'description' => $term->description
						)
					);					
				}			
			}			
		}
		return $output;	
	}
	
	public function included_add_ons_list($output)
	{
		if(dy_Tax_Mod::has_add_ons() && isset($_POST['add_ons']))
		{
			$add_ons = dy_Tax_Mod::get_add_ons();
			$add_ons_included = explode(',', sanitize_text_field($_POST['add_ons']));
			$add_ons_count = count($add_ons);
			
			if(is_array($add_ons) && is_array($add_ons_included))
			{
				for($x = 0; $x < $add_ons_count; $x++)
				{
					if(in_array($add_ons[$x]['id'], $add_ons_included))
					{
						$separator = ($add_ons[$x]['description']) ? ': ' : null;
						$output .= '<br/><strong style="color:#666666;">'.$add_ons[$x]['name'].$separator.'</strong>' . $add_ons[$x]['description'];
					}
				}					
			}			
		}

		return $output;
	}
	
}

?>