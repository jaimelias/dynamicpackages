<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Taxonomy_Add_Ons
{

	static $cache = [];

	function __construct()
	{
		$this->init();
	}
	
	public function init()
	{
		$this->name = 'package_add_ons';
		add_action('init', [$this, 'add_ons']);
		add_action('dy_checkout_items', [$this, 'checkout_items'], 10);
		add_filter('dy_included_add_ons_list', [$this, 'included_add_ons_list']);
		add_filter('dy_included_add_ons_arr', [$this, 'included_add_ons_arr']);
		add_filter('dy_has_add_ons', [$this, 'has_add_ons']);
		add_filter('dy_get_add_ons', [$this, 'get_add_ons']);
	}
	
	public function add_ons()
	{
		add_action($this->name.'_edit_form_fields', [$this, 'add_ons_form'], 10, 2);
		add_action( 'create_'.$this->name, [$this, 'save_term'], 10, 2);
		add_action( 'edited_'.$this->name, [$this, 'save_term'], 10, 2);
	}
	

	public function save_term($term_id) {
		
		$term_id = absint($term_id);

		if($term_id === 0 || !current_user_can('edit_term', $term_id))
		{
			return;
		}
				
		$def_lang_term_id = $term_id;
		
		if(function_exists('pll_current_language') && function_exists('pll_default_language') && function_exists('pll_get_term'))
		{
			$current_language = pll_current_language();
			$default_language = pll_default_language();

			if($current_language != $default_language)
			{	
				$translated_term_id = pll_get_term($def_lang_term_id, $default_language);

				if($translated_term_id > 0)
				{
					$def_lang_term_id = $translated_term_id;
				}
			}
		}
		
		if(post_has('tax_add_ons'))
		{	
			update_term_meta($def_lang_term_id, 'tax_add_ons', secure_post('tax_add_ons'));
		}	
		if(post_has('tax_add_ons_max'))
		{
			$tax_add_ons_max = secure_post('tax_add_ons_max', 1, 'absint');
			$tax_add_ons_max = min(100, max(1, $tax_add_ons_max));

			update_term_meta($def_lang_term_id, 'tax_add_ons_max', $tax_add_ons_max);
		}

		if(post_has('tax_add_ons_type'))
		{
			$tax_add_ons_type = secure_post('tax_add_ons_type', 0, 'absint');

			if(in_array($tax_add_ons_type, [0, 1, 2, 3], true)) {
				update_term_meta($def_lang_term_id, 'tax_add_ons_type', $tax_add_ons_type);
			}
			else {
				write_log("Invalid tax_add_ons_type=$tax_add_ons_type in Dynamicpackages_Taxonomy_Add_Ons::save.");
			}
		}	
	}
	
	public function add_ons_form($term)
	{
		$term_id = absint($term->term_id);

		if(
			function_exists('pll_current_language')
			&& function_exists('pll_default_language')
			&& function_exists('pll_get_term')
		)
		{
			$current_language = pll_current_language();
			$default_language = pll_default_language();

			if($current_language !== $default_language)
			{
				$def_lang_term_id = pll_get_term(
					$term_id,
					$default_language
				);

				if($def_lang_term_id)
				{
					$term_id = $def_lang_term_id;
				}
			}
		}

		$row = dy_taxonomy_form_row(
			'tax_add_ons_type',
			__('Type of Add-on', 'dynamicpackages'),
			__(
				'Variable price works only on multi-day and daily rental packages. If the package is calculated per night 1 additional day will be added to this add-on as long as this add-on is variable.',
				'dynamicpackages'
			)
		);

		dy_select_term_meta::custom([
			'term_id' => $term_id,
			'key'     => 'tax_add_ons_type',
			'options' => [
				0 => __('Price is fixed', 'dynamicpackages'),
				1 => __('Variable duration price', 'dynamicpackages'),
				2 => __('Variable duration price + 1', 'dynamicpackages'),
				3 => __('Transport (charged each way)', 'dynamicpackages'),
			],
			'prepend' => $row->prepend,
			'append'  => $row->append,
		]);

		$row = dy_taxonomy_form_row(
			'tax_add_ons_max',
			__('Maximum Number of participants', 'dynamicpackages')
		);

		dy_select_term_meta::min_max([
			'term_id' => $term_id,
			'key'     => 'tax_add_ons_max',
			'min'     => 1,
			'max'     => 500,
			'step'    => 1,
			'prepend' => $row->prepend,
			'append'  => $row->append,
		]);

		$row = dy_taxonomy_form_row(
			'tax_add_ons',
			__('Prices Per Person', 'dynamicpackages')
		);

		echo $row->prepend;

		echo handsontable([
			'container' => 'tax_add_ons_c',
			'textarea'  => 'tax_add_ons',
			'headers'   => [
				__('Prices', 'dynamicpackages'),
			],
			'type'       => ['currency'],
			'min'        => 'tax_add_ons_max',
			'max'        => 'tax_add_ons_max',
			'value'      => dy_get_value_term_meta([
				'term_id' => $term_id,
				'key'     => 'tax_add_ons',
			]),
		]);

		echo $row->append;
	}
	
	public function has_add_ons()
	{

		$the_id = get_dy_id();
		$cache_key = 'dy_has_add_ons_' . $the_id;

		if(array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$add_ons = $this->get_add_ons();
		
		return self::$cache[$cache_key] = is_array($add_ons) && count($add_ons) > 0;
	}
	
	public function checkout_items()
	{
		if(is_booking_page())
		{
			$output = '';
			$terms = $this->get_add_ons();
			$add_ons_arr = [];
			$addon_colspan = (!wp_is_mobile()) ? 2 : 1;

			if(is_array($terms))
			{
				$add_ons_package_id = sprintf(
					'dy_add_ons_%s',
					get_dy_id()
				);

				if(isset($_COOKIE[$add_ons_package_id]))
				{
					$add_ons_value = $_COOKIE[$add_ons_package_id];

					if($add_ons_value)
					{
						$add_ons_arr = explode(',', $add_ons_value);
					}
				}

				$terms_count = count($terms);

				for($x = 0; $x < $terms_count; $x++)
				{
					$term = $terms[$x];
					$term_id = $term['id'];
					$price = (float) $term['price'];

					if($price > 0.0)
					{
						$selected = (in_array($term_id, $add_ons_arr)) ? 'selected' : '';
						$description = $term['description'];

						$label = sprintf(
							'<span>%s</span> <br/><small class="semibold">%s</small>',
							esc_html($term['name']),
							esc_html(
								sprintf(
									'%s %s',
									wrap_money_full($price),
									__('per person', 'dynamicpackages')
								)
							)
						);

						if(!empty($description))
						{
							$label .= sprintf(
								'<br/><small>%s</small>',
								esc_html($description)
							);
						}

						$output .= sprintf(
							'<tr><td colspan="%s">%s</td><td><select class="add_ons width-100 border-box small" data-id="%s"><option value="0">%s</option><option value="1" %s>%s</option></select></td></tr>',
							esc_attr($addon_colspan),
							$label,
							esc_attr($term_id),
							esc_html(__('No', 'dynamicpackages')),
							$selected,
							esc_html(__('Yes', 'dynamicpackages'))
						);
					}
				}
			}

			echo $output;
		}
	}


	public function get_add_ons()
	{
		static $cache = [];
		
		$the_id = get_dy_id();
		$cache_key = 'dy_get_add_ons_' . $the_id;

		$post = get_post($the_id);

		if(!is_post_type_packages()) {
			return [];
		}

		if (isset($cache[$cache_key])) {
			return $cache[$cache_key];
		}

		$output = [];

		global $polylang;
		
		$package_type = dy_utilities::get_package_type($post->ID);
		$parent_terms = [];

		$pax_idx = max(0, absint(dy_utilities::pax_num()) - 1);
		
		$default_language = isset($polylang) ? pll_default_language() : null;
		$def_lang = !isset($polylang) || pll_current_language() === $default_language;

		$current_terms = get_the_terms($post->ID, $this->name);
		$current_terms = (is_array($current_terms)) ? $current_terms : [];

		if(property_exists($post, 'post_parent') && $post->post_parent > 0)
		{
			$parent_terms = get_the_terms($post->post_parent, $this->name);
			$parent_terms = (is_array($parent_terms)) ? $parent_terms : [];
		}			
		
		$terms = array_unique(array_merge($current_terms, $parent_terms), SORT_REGULAR );
		
		foreach($terms as $term)
		{
			$term_id = $term->term_id;
			$name = $term->name;
			$price = 0;
			
			if (!$def_lang) {
				$default_term_id = pll_get_term($term_id, $default_language);

				if ($default_term_id) {
					$term_id = $default_term_id;
				}
			}
			
			$add_ons_price = json_decode(html_entity_decode(get_term_meta($term_id, 'tax_add_ons', true)), true);
			
			$add_on_type = (int) get_term_meta($term_id, 'tax_add_ons_type', true);
			
			if (isset($add_ons_price['tax_add_ons_c'][$pax_idx][0])) {
				$price = (float) $add_ons_price['tax_add_ons_c'][$pax_idx][0];
			}
			
			if($add_on_type > 0)
			{
				//multi-day or rental per day
				if($package_type === 'multi-day' || $package_type === 'rental-per-day')
				{
					
					$package_duration = max(1, absint(dy_utilities::get_min_nights()));
					
					if($add_on_type === 2)
					{
						$package_duration = $package_duration + 1;
					}
					
					$price = $price * $package_duration;
				}
				else if($package_type === 'transport')
				{
					$package_duration = 1;
					$booking_date = dy_utilities::booking_date();
					$end_date = dy_utilities::end_date();
					$additional_duration = (int) dy_utilities::get_multi_day_duration($booking_date, $end_date);
					$package_duration = $package_duration + $additional_duration;

					if($add_on_type === 2)
					{
						$package_duration = $package_duration + 1;
					}
					else if($add_on_type === 3 && !empty($end_date))
					{
						$package_duration = 2; //charged each way
					}

					$price = $price * $package_duration;
				}
			}
			
			if($price > 0)
			{
				$output[] = [
					'id'          => $term_id,
					'price'       => $price,
					'name'        => $name,
					'description' => $term->description,
				];
			}			
		}

		return $cache[$cache_key] = $output;
	}


	public function included_add_ons_arr($output = [])
	{
		$add_ons_post = secure_post('add_ons');

		if (!$this->has_add_ons() || empty($add_ons_post)) {
			return $output;
		}

		$add_ons = $this->get_add_ons();

		if (!is_array($add_ons)) {
			return $output;
		}

		$add_ons_included = explode(',', $add_ons_post);

		foreach ($add_ons as $add_on) {
			if (in_array($add_on['id'], $add_ons_included, true)) {
				$output[] = $add_on;
			}
		}

		return $output;
	}
	
	public function included_add_ons_list($separator = null)
	{
		$included_add_ons = $this->included_add_ons_arr();

		if (empty($included_add_ons)) {
			return '';
		}

		$output = '';
		$prefix = !empty($separator) ? esc_html($separator) . ' ' : '';

		foreach ($included_add_ons as $add_on) {
			$description = esc_html($add_on['description']);
			$description_separator = !empty($description) ? ': ' : '';

			$output .= sprintf(
				'<hr height="1" style="height:1px; border:0 none; color: #eeeeee; background-color: #eeeeee;" /><strong style="color:#666666;">%s%s%s</strong>%s',
				$prefix,
				esc_html($add_on['name']),
				$description_separator,
				$description
			);
		}

		return $output;
	}
	
}

?>