<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Tables{

	private static $cache = [];

	function __construct()
	{
		if(is_admin())
		{
			return;
		}

		$this->init();
	}

	public function init()
	{
		add_action('wp', array(&$this, 'args'));
		add_action('dy_price_table', array(&$this, 'price_table'), 100);
	}

	public function args()
	{

		if(!is_singular('packages'))
		{
			return true;
		}

		$the_id = get_dy_id();

		$this->price_chart = dy_utilities::get_price_chart($the_id);
		$this->occupancy_chart = dy_utilities::get_occupancy_chart($the_id);
		$this->show_pricing = intval(package_field('package_show_pricing'));
		$this->min_persons = intval(package_field( 'package_min_persons' ));
		$this->max_persons = intval(package_field('package_max_persons'));
		$this->duration = intval(package_field('package_duration'));
		$this->package_type = dy_utilities::get_package_type();
		$this->price_type = intval(package_field('package_fixed_price'));
		$this->has_children = dy_validators::has_children();
		$this->currency_symbol = currency_symbol();
	}

	public function price_table()
	{
		if(!is_singular('packages'))
		{
			return true;
		}

		$output = '';
		$cache_key = 'dy_price_table';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if($this->has_children || $this->show_pricing === 1)
		{
			return '';
		}

		$show_rows = ($this->price_type === 0) ? true : false;
		$valid_table = false;
		$price_table = array();
		$rows = '';			
		$occupancy_chart = (is_array($this->occupancy_chart)) 
			? (array_key_exists('occupancy_chart', $this->occupancy_chart)) 
			? $this->occupancy_chart['occupancy_chart'] 
			: array() 
			: array();	

		if(is_array($this->price_chart))
		{
			for($x = 0; $x < count($this->price_chart); $x++)
			{
				$person = $x + 1;
				$price = 0;
				$base_price = 0;
				$occupancy_price = 0;
				
				if(isset($this->price_chart[$x][0]))
				{
					if(!empty($this->price_chart[$x][0]))
					{
						$base_price = floatval($this->price_chart[$x][0]);

						//this fix hides the table to avoid showing incorrect prices
						// if the package is multi-day the table will attempt to calculate the price of one unit
						// if there base price is > 0 then the system will calculate incorrectly adding up the base + occupancy
						if($this->package_type === 'multi-day' && $base_price > 0)
						{
							break;
						}
					}						
				}
				
				if(count($occupancy_chart) > 0)
				{
					if(isset($occupancy_chart[$x][0]))
					{
						if($occupancy_chart[$x][0] != 0)
						{
							$occupancy_price = floatval($occupancy_chart[$x][0]);
						}						
					}							
				}

				if($this->package_type === 'multi-day')
				{
					$price =  $base_price + $occupancy_price;
				}
				else if($this->package_type === 'one-day')
				{
					$price = $base_price;
				}
				else if($this->package_type === 'rental-per-day' && $this->package_type === 'rental-per-hour')
				{
					$price = $base_price * $this->duration;
				}
				else
				{
					$price = $base_price;
				}

				$sum_price = $price * $person;

				if($this->price_type === 1)
				{
					$price = $sum_price;
				}

				array_push($price_table, $price);

				if($price)
				{
					$valid_table = true;
				}
			}

			$count_price_table = count($price_table);

			if($count_price_table > 0 && $valid_table)
			{
				$max_price = max(array_filter($price_table));
				$min_price = min(array_filter($price_table));
				
				if($this->price_type === 1 && isset($price_table[$this->min_persons - 1]))
				{
					if($price_table[$this->min_persons - 1] > 0)
					{
						$min_price = $price_table[$this->min_persons - 1];
					}
				}

				$diff_percentage = ((($max_price - $min_price) / $min_price) * 100);

				if($this->price_type === 1)
				{
					$row = '';
					$price_label = $this->currency_symbol . money(intval($min_price));
					$td = '<td colspan="2">';
					if($diff_percentage > 5) 
					{
						$person_label = $this->min_persons . ' - ' . $this->max_persons;
						$price_label .= ' - ' . $this->currency_symbol . money(intval($max_price));
						$row .= '<tr><td><span class="dashicons dashicons-admin-users"></span> '.esc_html($person_label).'</td>';
					}
					else
					{
						$td = '<td>';
					}

					$row .= '<td>'.esc_html($price_label).'</td></tr>';
					$rows .= $row;
				}
				else
				{
					$show_one = ($diff_percentage < 5 || $count_price_table <= 1) ? true : false;

					for($x = 0; $x < $count_price_table; $x++)
					{
						$row = '<tr>';
						$td = '<td>';
						$person = $x+1;

						if($person >= $this->min_persons && $person <= $this->max_persons)
						{
							$price = $price_table[$x];

							if($show_one === false)
							{
								$row .= '<td><span class="dashicons dashicons-admin-users"></span> '.esc_html($person).'</td>';
							}
							else
							{
								if($price === 0)
								{
									$price = $min_price;
								}	
								
								$td = '<td colspan="2">';
							}
							
							if($show_rows)
							{
								$row .= $td . esc_html($this->currency_symbol . money($price)).'</td>';
								$row .= '</tr>';
								$rows .= $row;	
							}

							if($show_one)
							{
								$show_rows = false;
							}
						}
					}
				}

				if($rows)
				{
					$price_title = __('Price', 'dynamicpackages').' '.apply_filters('dy_price_type', null);
					$output = '<div class="table-vertical-responsive bottom-20"><table class="pure-table pure-table-bordered text-center width-100"><thead class="small uppercase"><tr><th colspan="2">'.esc_html($price_title).' (USD)</th></tr></thead><tbody class="small">';
					$output .= $rows;
					$output .= '</tbody>';
					$output .= '</table></div>';
				}

			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		
		echo $output;
	}
}

?>