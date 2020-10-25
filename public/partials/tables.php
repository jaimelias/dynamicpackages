<?php

class dynamicpackages_Tables{

	public static function package_price_table()
	{
		$price_chart = dy_utilities::get_price_chart();
		
		if(intval(package_field( 'package_show_pricing' )) == 0)
		{
			$min = package_field( 'package_min_persons' );
			$max = package_field( 'package_max_persons' );
			$duration = intval(package_field('package_duration'));
			$package_type = intval(package_field('package_package_type'));
			$duration_max = package_field('package_duration_max');
			$price_type = package_field( 'package_fixed_price' );
			$package_occupancy_chart = json_decode(html_entity_decode(package_field( 'package_occupancy_chart' )), true);	
			$package_occupancy_chart = (is_array($package_occupancy_chart)) ? $package_occupancy_chart['occupancy_chart'] : null;	
			$hide_table = false;
			
			if(intval($price_type) == 1)
			{
				$max = 1;
			}				
		
			$price_label = __('Prices', 'dynamicpackages').' '.dy_Public::price_type().' (USD)';
			
			
			
			
			$table = '<div class="table-vertical-responsive bottom-20"><table class="pure-table pure-table-bordered text-center"><thead class="small uppercase"><tr><th colspan="2">'.esc_html($price_label).'</th></tr></thead><tbody class="small">';			
			
			for($x = 0; $x < count($price_chart); $x++)
			{
				if(($x+1) >= $min && ($x+1) <= $max)
				{
					$person = floatval($min);
					$price = 0;
					$base_price = 0;
					$occupancy_price = 0;
					
					if(isset($price_chart[$x][0]))
					{
						if($price_chart[$x][0] != '')
						{
							$base_price = floatval($price_chart[$x][0]);
						}						
					}
					
					if(is_array($package_occupancy_chart))
					{
						if(count($package_occupancy_chart) > 0)
						{
							if(isset($package_occupancy_chart[$x][0]))
							{
								if($package_occupancy_chart[$x][0] != 0)
								{
									$occupancy_price = floatval($package_occupancy_chart[$x][0]);
								}						
							}							
						}
					}

					if($price_type == 0)
					{
						if($package_type == 1)
						{
							if(intval($max) == 0)
							{
								$occupancy_price =  $duration * $occupancy_price;
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
						if($package_type == 1)
						{
							if(intval($max) == 0)
							{
								$occupancy_price = $duration * $occupancy_price;
							}
							
							$price =  ($base_price + $occupancy_price) * $person;
						}
						else if($package_type == 0)
						{
							$price = $base_price * $person;
						}
						else if($package_type == 2 && $package_type == 3)
						{
							$price = $base_price * $duration * $person;
						}
						else
						{
							$price = $base_price * $person;
						}
					}
					
					if($x == 0)
					{
						if($max == 1 && package_field( 'package_max_persons' ) > $max)
						{
							$person .= ' - '.package_field( 'package_max_persons' );
						}							
						
						$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
						$row .= '<td><span>'.esc_html(dy_utilities::currency_symbol()).'</span><span>'.esc_html(number_format($price, 2, '.', ',')).'</span>';
						
														
						$row .= '</td></tr>';
					}
					elseif($x == (count($price_chart)-1))
					{				
						$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
						$row .= '<td><span>'.esc_html(dy_utilities::currency_symbol()).'</span><span>'.esc_html(number_format($price, 2, '.', ',')).'</span>';
						$row .= '</td></tr>';
					}
					else
					{
						$row = '<tr><td><i class="fas fa-male" ></i> '.esc_html($person).'</td>';
						$row .= '<td>'.esc_html(dy_utilities::currency_symbol().number_format($price, 2, '.', ','));					
						$row .= '</td></tr>';						
					}

					$table .= $row;	
					$min++;
				}
			}
				$table .= '</tbody>';			
	
			$table .= '</table></div>';
			echo $table;
		}		
		
	}
}

?>