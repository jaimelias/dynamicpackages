<?php

class Dynamic_Packages_Tables{

	function __construct()
	{
		$this->init();
	}

	public function init()
	{
		add_action('wp', array(&$this, 'args'));
		add_action('dy_package_price_table', array(&$this, 'price_table'), 100);
	}

	public function args()
	{
		$this->price_chart = dy_utilities::get_price_chart();
		$this->occupancy_chart = dy_utilities::get_occupancy_chart();
		$this->show_pricing = intval(package_field('package_show_pricing'));

		$this->min = intval(package_field( 'package_min_persons' ));
		$this->max = intval(package_field('package_max_persons'));
		$this->duration = intval(package_field('package_duration'));
		$this->package_type = intval(package_field('package_package_type'));
		$this->price_type = intval(package_field('package_fixed_price'));		

		//validators
		$this->is_parent_with_no_child = dy_validators::is_parent_with_no_child();
		$this->is_child = dy_validators::is_child();

		//utilities
		$this->currency_symbol = dy_utilities::currency_symbol();
	}

	public function price_table()
	{
		$output = '';
		$which_var = 'dy_package_price_table';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}

		else
		{
			if($this->show_pricing === 0 && ($this->is_parent_with_no_child || $this->is_child))
			{
				$this->max = ($this->price_type === 1) ? 1 : $this->max;
				$hide_table = false;
				$price_label = __('Prices', 'dynamicpackages').' '.apply_filters('dy_package_price_type', null).' (USD)';
				
				$output .= '<div class="table-vertical-responsive bottom-20"><table class="pure-table pure-table-bordered text-center"><thead class="small uppercase"><tr><th colspan="2">'.esc_html($price_label).'</th></tr></thead><tbody class="small">';			
				
				if(is_array($this->price_chart))
				{
					for($x = 0; $x < count($this->price_chart); $x++)
					{
						if(($x+1) >= $this->min && ($x+1) <= $this->max)
						{
							$person = floatval($this->min);
							$price = 0;
							$base_price = 0;
							$occupancy_price = 0;
							
							if(isset($this->price_chart[$x][0]))
							{
								if($this->price_chart[$x][0] != '')
								{
									$base_price = floatval($this->price_chart[$x][0]);
								}						
							}
							
							if(is_array($this->occupancy_chart))
							{
								$occupancy_chart = (array_key_exists('occupancy_chart', $this->occupancy_chart)) 
									? $this->occupancy_chart['occupancy_chart'] 
									: array();

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
							}

							if($this->price_type == 0)
							{
								if($this->package_type == 1)
								{
									if(intval($this->max) == 0)
									{
										$occupancy_price =  $this->duration * $occupancy_price;
									}
									
									$price =  $base_price + $occupancy_price;
								}
								else
								{
									$price = $base_price;
								}
							}
							else
							{						
								if($this->package_type == 1)
								{
									if(intval($this->max) == 0)
									{
										$occupancy_price = $this->duration * $occupancy_price;
									}
									
									$price =  ($base_price + $occupancy_price) * $person;
								}
								else if($this->package_type == 0)
								{
									$price = $base_price * $person;
								}
								else if($this->package_type == 2 && $this->package_type == 3)
								{
									$price = $base_price * $this->duration * $person;
								}
								else
								{
									$price = $base_price * $person;
								}
							}
							
							if($x == 0)
							{
								if($this->max == 1 && package_field( 'package_max_persons' ) > $this->max)
								{
									$person .= ' - '.package_field( 'package_max_persons' );
								}							
								
								$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
								$row .= '<td><span>'.esc_html($this->currency_symbol).'</span><span>'.esc_html(number_format($price, 2, '.', ',')).'</span>';
								
																
								$row .= '</td></tr>';
							}
							elseif($x == (count($this->price_chart)-1))
							{				
								$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
								$row .= '<td><span>'.esc_html($this->currency_symbol).'</span><span>'.esc_html(number_format($price, 2, '.', ',')).'</span>';
								$row .= '</td></tr>';
							}
							else
							{
								$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
								$row .= '<td>'.esc_html($this->currency_symbol.number_format($price, 2, '.', ','));					
								$row .= '</td></tr>';						
							}

							$output .= $row;	
							$this->min++;
						}
					}
				}
					
				$output .= '</tbody>';
				$output .= '</table></div>';
			}

			$GLOBALS[$which_var] = $output;
		}

		
		
		echo $output;
	}
}

?>