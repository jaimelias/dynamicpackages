<?php 

class dynamicpackages_Metapost{
	public static function package_save($post_id) 
	{
		if(defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if(! isset( $_POST['package_nonce'] ) || ! wp_verify_nonce( $_POST['package_nonce'], '_package_nonce' ) ) return;
		if(! current_user_can( 'edit_post', $post_id ) ) return;
		
		if(isset( $_POST['package_fixed_price']))
			update_post_meta( $post_id, 'package_fixed_price', esc_attr($_POST['package_fixed_price']));
		
		if(isset( $_POST['package_show_pricing']))
			update_post_meta( $post_id, 'package_show_pricing', esc_attr($_POST['package_show_pricing']));		
		
		if(isset($_POST['package_package_type']))
		{
			if(intval($_POST['package_package_type']) == 2)
			{
				update_post_meta( $post_id, 'package_duration', 1);
				update_post_meta( $post_id, 'package_length_unit', 2);
				
				if(isset($_POST['package_duration_max']))
				{
					if(intval($_POST['package_duration_max']) > 1)
					{
						update_post_meta( $post_id, 'package_duration_max', esc_attr($_POST['package_duration_max']));
					}
					else
					{
						update_post_meta( $post_id, 'package_duration_max', 2);
					}
				}				
			}
			else if(intval($_POST['package_package_type']) == 3)
			{
				update_post_meta( $post_id, 'package_duration', 1);
				update_post_meta( $post_id, 'package_length_unit', 1);
				
				if(isset($_POST['package_duration_max']))
				{
					if(intval($_POST['package_duration_max']) > 1)
					{
						update_post_meta( $post_id, 'package_duration_max', esc_attr($_POST['package_duration_max']));
					}
					else
					{
						update_post_meta( $post_id, 'package_duration_max', 2);
					}
				}				
			}
			else
			{
				if(isset($_POST['package_duration']))
				{
					update_post_meta( $post_id, 'package_duration', esc_attr($_POST['package_duration']));
				}
				if (isset($_POST['package_length_unit']))
				{
					update_post_meta( $post_id, 'package_length_unit', esc_attr($_POST['package_length_unit']));
				}
				if(isset($_POST['package_duration_max']))
				{
					update_post_meta( $post_id, 'package_duration_max', esc_attr($_POST['package_duration_max']));
				}
			}
		}	
		
		
		if(isset( $_POST['package_display']))
			update_post_meta( $post_id, 'package_display', esc_attr($_POST['package_display']));		
		if(isset( $_POST['package_trip_code']))
			update_post_meta( $post_id, 'package_trip_code', esc_attr($_POST['package_trip_code']));
		if(isset( $_POST['package_starting_at_unit']))
			update_post_meta( $post_id, 'package_starting_at_unit', esc_attr($_POST['package_starting_at_unit']));
		if(isset( $_POST['package_auto_booking']))
			update_post_meta( $post_id, 'package_auto_booking', esc_attr($_POST['package_auto_booking']));
		
		if(isset( $_POST['package_disabled_num']))
			update_post_meta( $post_id, 'package_disabled_num', esc_attr($_POST['package_disabled_num']));
		if(isset( $_POST['package_disabled_dates']))
			update_post_meta( $post_id, 'package_disabled_dates', esc_attr($_POST['package_disabled_dates']));		
		

		//package_event_date
		if(isset( $_POST['package_event_date']))
		{
			update_post_meta( $post_id, 'package_event_date', esc_attr($_POST['package_event_date']));
			
			if($_POST['package_event_date'] != '')
			{
				update_post_meta($post_id, 'package_date', esc_attr($_POST['package_event_date']));
			}
			else
			{
				dynamicpackages_Public::event_date_update($post_id);
			}
			
		}
		
						
		if(isset( $_POST['package_booking_from']))
		{
			update_post_meta( $post_id, 'package_booking_from', esc_attr($_POST['package_booking_from']));
		}
		
		//package_booking_to require else with 365 days
		if(isset( $_POST['package_booking_to']))
		{
			if(intval($_POST['package_booking_to']) > 0)
			{
				update_post_meta( $post_id, 'package_booking_to', esc_attr($_POST['package_booking_to']));
			}
			else
			{
				update_post_meta( $post_id, 'package_booking_to', 365 );
			}			
		}
		
		if(isset( $_POST['package_badge']))
			update_post_meta( $post_id, 'package_badge', esc_attr($_POST['package_badge']));	
		if(isset( $_POST['package_badge_color']))
			update_post_meta( $post_id, 'package_badge_color', esc_attr($_POST['package_badge_color']));
		
		//min_persons require else with 1
		if(isset( $_POST['package_min_persons']))
		{
			if(intval($_POST['package_min_persons']) > 0)
			{
				update_post_meta( $post_id, 'package_min_persons', esc_attr($_POST['package_min_persons']));
			}
			else
			{
				update_post_meta( $post_id, 'package_min_persons', esc_attr( 1 ) );
			}			
		}

		//max_persons require else with 1
		if(isset( $_POST['package_max_persons']))
		{
			if(intval($_POST['package_max_persons']) > 0)
			{
				update_post_meta( $post_id, 'package_max_persons', esc_attr($_POST['package_max_persons']));
			}
			else
			{
				update_post_meta( $post_id, 'package_max_persons', esc_attr( 1 ) );
			}			
		}
		
		//package_increase_persons
		if(isset( $_POST['package_increase_persons']))
		{
			update_post_meta( $post_id, 'package_increase_persons', esc_attr($_POST['package_increase_persons']));
		}		

		if(isset( $_POST['package_payment']))
		{
			update_post_meta( $post_id, 'package_payment', esc_attr($_POST['package_payment']));
		}
		if(isset( $_POST['package_deposit']))
		{
			if(intval($_POST['package_deposit']) > 0)
			{
				update_post_meta( $post_id, 'package_deposit', esc_attr($_POST['package_deposit']));
			}
			else
			{
				update_post_meta( $post_id, 'package_deposit', esc_attr( 25 ) );
			}			
		}		
		
		if(isset( $_POST['package_num_seasons']))
			update_post_meta( $post_id, 'package_num_seasons', esc_attr($_POST['package_num_seasons']));			
		if(isset( $_POST['package_seasons_chart']))
			update_post_meta( $post_id, 'package_seasons_chart', esc_attr($_POST['package_seasons_chart']));	
		if(isset( $_POST['package_price_chart']))
			update_post_meta( $post_id, 'package_price_chart', esc_attr($_POST['package_price_chart']));	
		
		if(isset( $_POST['package_occupancy_chart']))
			update_post_meta( $post_id, 'package_occupancy_chart', esc_attr($_POST['package_occupancy_chart']));		
		
		//package_free require else with 0
		if(isset( $_POST['package_free']))
		{
			if(intval($_POST['package_free']) > 0)
			{
				update_post_meta( $post_id, 'package_free', esc_attr($_POST['package_free']));
			}	
			else
			{
				update_post_meta( $post_id, 'package_free', esc_attr( 0 ) );	
			}
		}

		
		//package_discount require else with 0		
		if(isset( $_POST['package_discount']))
		{
			if(intval($_POST['package_discount']) > 0)
			{
				update_post_meta( $post_id, 'package_discount', esc_attr($_POST['package_discount']));
			}
			else
			{
				update_post_meta( $post_id, 'package_discount', esc_attr( 0 ) );
			}			
		}

		//package_package_type	
		if(isset( $_POST['package_package_type']))
			update_post_meta( $post_id, 'package_package_type', esc_attr($_POST['package_package_type']));		
		if(isset( $_POST['package_by_hour']))
			update_post_meta( $post_id, 'package_by_hour', esc_attr($_POST['package_by_hour']));	
		if(isset( $_POST['package_max_hour']))
			update_post_meta( $post_id, 'package_max_hour', esc_attr($_POST['package_max_hour']));		
		if(isset( $_POST['package_min_hour']))
			update_post_meta( $post_id, 'package_min_hour', esc_attr($_POST['package_min_hour']));
		if(isset( $_POST['package_check_in_hour']))
			update_post_meta( $post_id, 'package_check_in_hour', esc_attr($_POST['package_check_in_hour']));
		if(isset( $_POST['package_departure_hour']))
			update_post_meta( $post_id, 'package_departure_hour', esc_attr($_POST['package_departure_hour']));
		if(isset( $_POST['package_departure_address']))
			update_post_meta( $post_id, 'package_departure_address', esc_attr($_POST['package_departure_address']));	
		
		//provider
		if(isset( $_POST['package_provider_name']))
			update_post_meta( $post_id, 'package_provider_name', esc_attr($_POST['package_provider_name']));	
		if(isset( $_POST['package_provider_email']))
			update_post_meta( $post_id, 'package_provider_email', esc_attr($_POST['package_provider_email'] ));	
		if(isset( $_POST['package_provider_tel']))
			update_post_meta( $post_id, 'package_provider_tel', esc_attr($_POST['package_provider_tel']));	
		if(isset( $_POST['package_provider_mobile']))
			update_post_meta( $post_id, 'package_provider_mobile', esc_attr($_POST['package_provider_mobile'] ));			
		
		global $polylang; 
		$language_list = array();
		
		if(isset($polylang))
		{
			$languages = PLL()->model->get_languages_list();
			
			for($x = 0; $x < count($languages); $x++)
			{
				foreach($languages[$x] as $key => $value)
				{
					if($key == 'slug')
					{
						if(isset( $_POST['package_provider_message_'.$value]))
						{
							update_post_meta( $post_id, 'package_provider_message_'.$value, esc_attr($_POST['package_provider_message_'.$value] ));						
						}
						if(isset( $_POST['package_child_title_'.$value]))
						{
							update_post_meta( $post_id, 'package_child_title_'.$value, esc_attr($_POST['package_child_title_'.$value] ));						
						}						
					}
				}
			}
		}
		else
		{
			if(isset( $_POST['package_provider_message']))
			{
				update_post_meta( $post_id, 'package_provider_message', esc_attr($_POST['package_provider_message'] ));					
			}
			if(isset( $_POST['package_child_title']))
			{
				update_post_meta( $post_id, 'package_child_title', esc_attr($_POST['package_child_title'] ));				
			}			
		}		

		
		
		// ALL THE CHECKBOXES REQUIRE AN ELSE
		//monday
		if(isset( $_POST['package_day_mon']))
		{
			update_post_meta( $post_id, 'package_day_mon', esc_attr($_POST['package_day_mon']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_mon', '' );	
		}
		
		//tuesday
		if(isset( $_POST['package_day_tue']))
		{
			update_post_meta( $post_id, 'package_day_tue', esc_attr($_POST['package_day_tue']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_tue', '' );	
		}		
		
		//wednesday
		if(isset( $_POST['package_day_wed']))
		{
			update_post_meta( $post_id, 'package_day_wed', esc_attr($_POST['package_day_wed']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_wed', '' );	
		}			
		
		//thursday
		if(isset( $_POST['package_day_thu']))
		{
			update_post_meta( $post_id, 'package_day_thu', esc_attr($_POST['package_day_thu']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_thu', '' );	
		}			
		
		//friday
		if(isset( $_POST['package_day_fri']))
		{
			update_post_meta( $post_id, 'package_day_fri', esc_attr($_POST['package_day_fri']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_fri', '' );	
		}			
		
		//saturday
		if(isset( $_POST['package_day_sat']))
		{
			update_post_meta( $post_id, 'package_day_sat', esc_attr($_POST['package_day_sat']));	
		}
		else
		{
			update_post_meta( $post_id, 'package_day_sat', '' );	
		}			
		
		//sunday
		if(isset( $_POST['package_day_sun']))
		{
			update_post_meta( $post_id, 'package_day_sun', esc_attr($_POST['package_day_sun']));		
		}
		else
		{
			update_post_meta( $post_id, 'package_day_sun', '' );	
		}	

		//coupons
		if(isset( $_POST['package_coupons']))
		{
			update_post_meta( $post_id, 'package_coupons', esc_attr($_POST['package_coupons']));
		}			
		if(isset( $_POST['package_max_coupons']))
		{
			update_post_meta( $post_id, 'package_max_coupons', esc_attr($_POST['package_max_coupons']));
		}
		
		//starting at
		if(isset( $_POST['package_starting_at']))
		{
			update_post_meta( $post_id, 'package_starting_at', esc_attr( dy_utilities::starting_at() ) );
		}			
		
		
	}	
}

?>