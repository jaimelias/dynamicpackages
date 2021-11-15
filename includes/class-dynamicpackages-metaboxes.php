<?php

class dy_Metaboxes
{

	public function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_action('add_meta_boxes', array(&$this, 'package_add_meta_box'));
	}
	public function package_add_meta_box() {
		
		
		add_meta_box(
			'package-a',
			__( 'Description', 'dynamicpackages' ),
			array(&$this, 'package_description_html'),
			'packages',
			'normal',
			'default'
		);			
		add_meta_box(
			'package-b',
			__( 'Pricing Controls', 'dynamicpackages' ),
			array(&$this, 'package_pricing_html'),
			'packages',
			'normal',
			'default'
		);

		if(!dy_validators::has_children())
		{
			add_meta_box(
				'package-c',
				__( 'Rates', 'dynamicpackages' ),
				array(&$this, 'package_rates_html'),
				'packages',
				'normal',
				'default'
			);				
		}


		add_meta_box(
			'package-d',
			__( 'Availability', 'dynamicpackages' ),
			array(&$this, 'package_availability_html'),
			'packages',
			'normal',
			'default'
		);		
		
		
		if(!dy_validators::is_child())
		{			
			add_meta_box(
				'package-e',
				__( 'Departure', 'dynamicpackages' ),
				array(&$this, 'package_departure_html'),
				'packages',
				'normal',
				'default'
			);
			add_meta_box(
				'package-f',
				__( 'Provider', 'dynamicpackages' ),
				array(&$this, 'package_provider_html'),
				'packages',
				'normal',
				'default'
			);	
			add_meta_box(
				'package-g',
				__( 'Coupons', 'dynamicpackages' ),
				array(&$this, 'package_coupon_html'),
				'packages',
				'normal',
				'default'
			);				
		}	
	}
	
	
	public static function select_number($name, $min = 1, $max = 20, $attr = '')
	{
		$options = '';
		$value = intval(package_field($name));
		
		for($x = $min; $x < $max; $x++)
		{
			$selected = '';
			
			if($value == $x)
			{
				$selected = 'selected';
			}

			$options .= '<option '.$selected.'>'.$x.'</option>';
		}
		
		echo '<select id="'.$name.'" name="'.$name.'" '.$attr.'>'.$options.'</select>';
		
	}
	
	public static function package_coupon_html($post) { ?>
		<p><label><?php _e( 'Number of coupons', 'dynamicpackages' ); ?> <?php self::select_number('package_max_coupons', 1, 10); ?></label></p>
		
		<?php 
		
		$args = array(
					'container' => 'coupons',
					'textarea' => 'package_coupons',
					'headers' => array(__( 'Code', 'dynamicpackages'), __('Discount (%)', 'dynamicpackages'), __( 'Expiration', 'dynamicpackages' ), __( 'Publish', 'dynamicpackages' ), __( 'Min. Duration', 'dynamicpackages' ), __( 'Max. Duration', 'dynamicpackages' )),
					'type' => array('text', 'numeric', 'date', 'checkbox', 'numeric', 'numeric'),
					'min' => 'package_max_coupons',
					'max' => 'package_max_coupons',
					'value' => package_field('package_coupons'),
				);		
		
		echo dy_utilities::handsontable($args); ?>
		
	<?php
	}
	
	public static function package_provider_html( $post) { ?>

		<p>
			<label for="package_provider_name"><?php _e( 'Name', 'dynamicpackages' ); ?></label></br>
			<input type="text" name="package_provider_name" id="package_provider_name" value="<?php echo package_field( 'package_provider_name' ); ?>">
		</p>
		
		<p>
			<label for="package_provider_email"><?php _e( 'Email', 'dynamicpackages' ); ?></label></br>
			<input type="email" name="package_provider_email" id="package_provider_email" value="<?php echo package_field( 'package_provider_email' ); ?>">
		</p>

		<p>
			<label for="package_provider_tel"><?php _e( 'Telephone', 'dynamicpackages' ); ?></label></br>
			<input type="text" name="package_provider_tel" id="package_provider_tel" value="<?php echo package_field( 'package_provider_tel' ); ?>">
		</p>

		<p>
			<label for="package_provider_mobile"><?php _e( 'Mobile Phone', 'dynamicpackages' ); ?></label></br>
			<input type="text" name="package_provider_mobile" id="package_provider_mobile" value="<?php echo package_field( 'package_provider_mobile' ); ?>">
		</p>	

		<?php 
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
							array_push($language_list, $value);
							
							?>
								<p>
									<label for="package_confirmation_message_<?php echo esc_html($value); ?>"><?php _e( 'Confirmation Message', 'dynamicpackages' ); ?> - <?php echo esc_html($value);?></label></br>
									<textarea cols="40" rows="6" type="text" name="package_confirmation_message_<?php echo esc_html($value); ?>" id="package_confirmation_message_<?php echo esc_html($value); ?>"><?php echo package_field( 'package_confirmation_message_'.$value ); ?></textarea>
								</p>	
							<?php
						}
					}	
				}
			}
			else
			{
				?>
					<p>
						<label for="package_confirmation_message"><?php _e( 'Confirmation Message', 'dynamicpackages' ); ?></label></br>
						<textarea cols="40" rows="6" type="text" name="package_confirmation_message" id="package_confirmation_message"><?php echo package_field( 'package_confirmation_message' ); ?></textarea>
					</p>				
				<?php
			}
		?>
		
		<?php
	}

	
	public static function package_departure_html( $post) { ?>

		<?php if(dy_validators::is_package_transport()): ?>
			<h3><?php esc_html_e('Departure', 'dynamicpackages'); ?></h3>
		<?php endif; ?>

		<p>
			<label for="package_check_in_hour"><?php _e( 'Check-in Hour', 'dynamicpackages' ); ?></label></br>
			<input class="timepicker" type="text" name="package_check_in_hour" id="package_check_in_hour" value="<?php echo package_field( 'package_check_in_hour' ); ?>">
		</p>
		<p>
			<label for="package_start_hour"><?php _e( 'Departure Hour', 'dynamicpackages' ); ?></label></br>
			<input class="timepicker" type="text" name="package_start_hour" id="package_start_hour" value="<?php echo package_field( 'package_start_hour' ); ?>">
		</p>				
		<p>
			<label for="package_start_address"><?php _e( 'Departure Address', 'dynamicpackages' ); ?></label></br>
			<textarea cols="60" type="text" name="package_start_address" id="package_start_address"><?php echo package_field( 'package_start_address' ); ?></textarea>
		</p>

		<?php if(dy_validators::is_package_transport()): ?>
			<h3><?php esc_html_e('Return', 'dynamicpackages'); ?></h3>
			
			<p>
				<label for="package_check_in_end_hour"><?php _e( 'Check-in Hour', 'dynamicpackages' ); ?></label></br>
				<input class="timepicker" type="text" name="package_check_in_end_hour" id="package_check_in_end_hour" value="<?php echo package_field( 'package_check_in_end_hour' ); ?>">
			</p>
			<p>
				<label for="package_return_hour"><?php _e( 'Departure Hour', 'dynamicpackages' ); ?></label></br>
				<input class="timepicker" type="text" name="package_return_hour" id="package_return_hour" value="<?php echo package_field( 'package_return_hour' ); ?>">
			</p>				
			<p>
				<label for="package_return_address"><?php _e( 'Departure Address', 'dynamicpackages' ); ?></label></br>
				<textarea cols="60" type="text" name="package_return_address" id="package_return_address"><?php echo package_field( 'package_return_address' ); ?></textarea>
			</p>			
			
		<?php endif; ?>


		
		<?php
	}
	
	
	public static function package_pricing_html($post) { ?>
		
		<?php 
			
			$disable_child = '';
			$is_child = dy_validators::is_child();
			$package_type = package_field('package_package_type');
			$show_pricing = package_field('package_show_pricing');
			$auto_booking = package_field('package_auto_booking');
			$payment = package_field('package_payment');
			$deposit = package_field('package_deposit');
			
			if($is_child)
			{
				$disable_child = 'disabled';
			}
		?>
		
		
		
		<?php if(!$is_child) : ?>	
				<p>
					<label for="package_show_pricing"><?php _e( 'Show Price Table', 'dynamicpackages' ); ?></label><br />
					<select name="package_show_pricing" id="package_show_pricing">
						<option value="0" <?php echo ($show_pricing == 0 ) ? 'selected' : ''; ?> ><?php echo esc_html(__('Yes', 'dynamicpackages')); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>			
						<option value="1" <?php echo ($show_pricing == 1 ) ? 'selected' : ''; ?> ><?php echo esc_html(__('No', 'dynamicpackages')); ?></option>			
					</select>
				</p>

				<p>
					<label for="package_auto_booking"><?php _e( 'Enable Automatic Booking', 'dynamicpackages' ); ?></label><br />
					<select name="package_auto_booking" id="package_auto_booking">
						<option value="0" <?php echo ($auto_booking == 0 ) ? 'selected' : ''; ?> >No</option>
						<option value="1" <?php echo ($auto_booking == 1 ) ? 'selected' : ''; ?> >Yes</option>
					</select>
				</p>				
			<?php endif; ?>
			
			<?php if($is_child || dy_validators::is_parent_with_no_child()) : ?>
			
			<p>
				<label for="package_min_persons"><?php _e( 'Minimum Number of participants', 'dynamicpackages' ); ?></label><br />
				
				<?php self::select_number('package_min_persons', 1, 100); ?>
				
			</p>
			<p>
				<label for="package_max_persons"><?php _e( 'Maximum Number of participants', 'dynamicpackages' ); ?></label><br />
				<?php self::select_number('package_max_persons', (intval(package_field( 'package_min_persons' ))+1), 100); ?>
			</p>
			<p>
				<label for="package_free"><span><?php _e( 'Children free up to', 'dynamicpackages' ); ?></span></br>
				<?php self::select_number('package_free', 0, 17); ?>
				 <?php _e( 'year old', 'dynamicpackages' ); ?></label>
			</p>
			<p>
				<label for="package_discount"><span><?php _e( 'Children Discount up to', 'dynamicpackages' ); ?></span></br>
				<?php self::select_number('package_discount', 0, 17); ?>
				 <?php _e( 'year old', 'dynamicpackages' ); ?></label>
			</p>
			<p>
				<label for="package_increase_persons"><?php _e( 'Increase maximum number of participants by', 'dynamicpackages' ); ?></label><br />
				<span><input type="number" min="0" name="package_increase_persons" id="package_increase_persons" value="<?php echo package_field( 'package_increase_persons' ); ?>"> <?php _e( 'get more leads even if the prices are not defined', 'dynamicpackages' ); ?>.</span>
			</p>	
		<?php endif; ?>
			
		<?php if(!$is_child) : ?>

			<?php if(intval(package_field( 'package_auto_booking' )) > 0): ?>
				<p>
					<label for="package_payment"><?php _e( 'Payment', 'dynamicpackages' ); ?></label><br />
					<select name="package_payment" id="package_payment">
						<option value="0" <?php echo ($payment == 0 ) ? 'selected' : ''; ?> ><?php echo esc_html(__('Full Payment', 'dynamicpackages')); ?></option>
						<option value="1" <?php echo ($payment == 1 ) ? 'selected' : ''; ?> ><?php echo esc_html(__('Deposit', 'dynamicpackages')); ?></option>
					</select>
					<?php if($payment == 1): ?>
						<label for="package_deposit"><input type="number" step="0.1" name="package_deposit" id="package_deposit" value="<?php echo $deposit; ?>">%</label>
					<?php endif; ?>			
				</p>
			<?php endif; ?>
		<?php endif; ?>
		
		<?php
			if($package_type == 1 || $package_type == 2)
			{
				$occupancy_day_surcharge = dy_utilities::get_week_days_abbr();

				echo '<fieldset>';
				echo '<h3 id="occupancy_day_surcharge">'.esc_html(__('Surcharge per day of the week', 'dynamicpackages')).'</h3>';

				for($x = 0; $x < 7; $x++)
				{
					$day = $occupancy_day_surcharge[$x];
					$name = 'package_occupancy_day_surcharge_' . $day;
					$value = intval(package_field($name));
					
					echo '<p><label for="'.$name.'">';
					echo '<input value="'.$value.'" name="'.$name.'" id="'.$name.'" type="number" />% ';
					echo $day . '</label></p>';
				}

				echo '</fieldset>';				
			}		
		?>		
		
		<?php if($package_type == 1 ): ?>
			<fieldset>		
			<h3><?php _e( 'Number of Special Seasons', 'dynamicpackages' ); ?> <?php self::select_number('package_num_seasons', 1, 10, $disable_child); ?></h3>
			
			<?php 
			
			$args = array(
						'container' => 'seasons_chart',
						'textarea' => 'package_seasons_chart',
						'headers' => array(__('Name', 'dynamicpackages'), __('From', 'dynamicpackages'), __('To', 'dynamicpackages'), __('Duration', 'dynamicpackages'), __('ID', 'dynamicpackages')),
						'type' => array('text','date','date','dropdown','readonly'),
						'dropdown' => array(1,2,3,4,5,6,7),
						'min' => 'package_num_seasons',
						'max' => 'package_num_seasons',
						'value' => package_field('package_seasons_chart'),
						'disabled' => $disable_child
					);		
					
			echo dy_utilities::handsontable($args); ?>			
		
			</fieldset>	
		<?php endif; ?>	

		
		
		<?php
	}
	
	public static function package_rates_html( $post) { ?>
		
		
		<fieldset>	
		<?php 
		
		$package_type = package_field('package_package_type');
		$price_chart = package_field('package_price_chart');
		$occupancy_chart = package_field('package_occupancy_chart');
		
		if($package_type > 1): ?>
			<?php if($package_type == 2): ?>
				<h3><?php esc_html_e( 'Daily Rental Per Person', 'dynamicpackages' ); ?></h3>
			<?php elseif($package_type == 3): ?>
				<h3><?php esc_html_e( 'Hourly Rental Per Person', 'dynamicpackages' ); ?></h3>
			<?php elseif($package_type == 4): ?>
				<h3><?php esc_html_e( 'One-way price per person', 'dynamicpackages' ); ?></h3>
			<?php endif; ?>
		<?php else: ?>
			<h3><?php esc_html_e( 'Base Prices Per Person', 'dynamicpackages' ); ?></h3>
		<?php endif; ?>

		<?php
	
			$args = array(
						'container' => 'price_chart',
						'textarea' => 'package_price_chart',
						'headers' => array(__('Regular', 'dynamicpackages'), __('Discount', 'dynamicpackages')),
						'type' => array('currency', 'currency'),
						'min' => 'package_min_persons',
						'max' => 'package_max_persons',
						'value' => $price_chart
					);		
					
			echo dy_utilities::handsontable($args);
		?>	
		</fieldset>
	
		<?php if($package_type == 1): ?>
		<fieldset>			
			<h3 id="accommodation"><?php echo dy_Admin::get_duration_unit()?> <?php _e( 'Accomodation Prices Per Person', 'dynamicpackages' ); ?></h3>
			
			
		<?php
	
			$args = array(
						'container' => 'occupancy_chart',
						'textarea' => 'package_occupancy_chart',
						'headers' => array(__('Regular', 'dynamicpackages'), __('Discount', 'dynamicpackages')),
						'type' => array('currency', 'currency'),
						'min' => 'package_min_persons',
						'max' => 'package_max_persons',
						'value' => $occupancy_chart
					);		
					
			echo dy_utilities::handsontable($args);
		?>	
				
		</fieldset>
		<?php endif; ?>
		
		
		<?php if($package_type == 1): ?>
			<div id="special_seasons"></div>
		<?php endif; ?>
		<?php
	}	
	
	public static function package_availability_html( $post) { ?>
		
		<?php if(!dy_validators::is_child()) : ?>
			<h4><?php _e( 'Event Date', 'dynamicpackages' ); ?></h4>
				<p>
					<input type="text" name="package_event_date" id="package_event_date" class="datepicker" value="<?php echo package_field( 'package_event_date' ); ?>">
				</p>
			<h4><?php _e( 'Accept Bookings', 'dynamicpackages' ); ?></h4>
			<p>
				<label for="package_booking_from"><?php _e( 'Between', 'dynamicpackages' ); ?> 
				<?php self::select_number('package_booking_from', 0, 366); ?>
				</label> <?php _e( 'to', 'dynamicpackages' ); ?>
				<label for="package_booking_to">
				<?php self::select_number('package_booking_to', 0, 366); ?> 
				<?php _e( 'days', 'dynamicpackages' ); ?></label>
			</p>		
			
			<h4><?php _e( 'Disable Days', 'dynamicpackages' ); ?></h4>
			<p>
				<label for="package_day_mon"><input type="checkbox" name="package_day_mon" id="package_day_mon" value="1" <?php checked( package_field( 'package_day_mon' ) , 1 ); ?> > <?php _e( 'Monday', 'dynamicpackages' ); ?> </label><br />
				<label for="package_day_tue"><input type="checkbox" name="package_day_tue" id="package_day_tue" value="1" <?php checked( package_field( 'package_day_tue' ) , 1 ); ?> > <?php _e( 'Thuesday', 'dynamicpackages' ); ?></label><br />
				<label for="package_day_wed"><input type="checkbox" name="package_day_wed" id="package_day_wed" value="1" <?php checked( package_field( 'package_day_wed' ) , 1 ); ?> > <?php _e( 'Wednesday', 'dynamicpackages' ); ?></label><br />
				<label for="package_day_thu"><input type="checkbox" name="package_day_thu" id="package_day_thu" value="1" <?php checked( package_field( 'package_day_thu' ) , 1 ); ?> > <?php _e( 'Thursday', 'dynamicpackages' ); ?></label><br />
				<label for="package_day_fri"><input type="checkbox" name="package_day_fri" id="package_day_fri" value="1" <?php checked( package_field( 'package_day_fri' ) , 1 ); ?> > <?php _e( 'Friday', 'dynamicpackages' ); ?></label><br />
				<label for="package_day_sat"><input type="checkbox" name="package_day_sat" id="package_day_sat" value="1" <?php checked( package_field( 'package_day_sat' ) , 1 ); ?> > <?php _e( 'Saturday', 'dynamicpackages' ); ?></label><br />
				<label for="package_day_sun"><input type="checkbox" name="package_day_sun" id="package_day_sun" value="1" <?php checked( package_field( 'package_day_sun' ) , 1 ); ?> > <?php _e( 'Sunday', 'dynamicpackages' ); ?></label>
			</p>

			<h4><?php _e( 'Book by Hour', 'dynamicpackages' ); ?></h4>
			<p>
				<select name="package_by_hour" id="package_by_hour">
					<option value="0" <?php echo (package_field( 'package_by_hour' ) == 0 ) ? 'selected' : ''; ?> ><?php _e( 'No', 'dynamicpackages' ); ?></option>			
					<option value="1" <?php echo (package_field( 'package_by_hour' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'Yes', 'dynamicpackages' ); ?></option>			
				</select>
				<?php _e( 'between', 'dynamicpackages' ); ?>
				<input type="text" class="timepicker" name="package_min_hour" id="package_min_hour" value="<?php echo package_field( 'package_min_hour' ); ?>" > 
				<?php esc_html_e( 'and', 'dynamicpackages' ); ?>
				<input type="text" class="timepicker" name="package_max_hour" id="package_max_hour" value="<?php echo package_field( 'package_max_hour' ); ?>" > 			
			</p>	
		<?php endif; ?>
		<fieldset>		
		<h3><?php esc_html_e( 'Disabled Dates', 'dynamicpackages' ); ?> <?php self::select_number('package_disabled_num', 0, 20); ?></h3>
		
		
		<?php
	
			$args = array(
						'container' => 'disabled_dates',
						'textarea' => 'package_disabled_dates',
						'headers' => array(__('From', 'dynamicpackages'), __('To', 'dynamicpackages')),
						'type' => array('date', 'date'),
						'min' => 'package_disabled_num',
						'max' => 'package_disabled_num',
						'value' => package_field('package_disabled_dates')
					);		
					
			echo dy_utilities::handsontable($args);
		?>		
		
		<h3><?php esc_html_e('Disabled Dates API Endpoint', 'dynamicpackages'); ?></h3>
		<p><input type="url" name="package_disabled_dates_api" id="package_disabled_dates_api" value="<?php echo esc_url(package_field('package_disabled_dates_api' )); ?>" > </p>
		</fieldset>
		
		<h3><?php esc_html_e( 'Force Enabled Dates', 'dynamicpackages' ); ?> <?php self::select_number('package_enabled_num', 0, 20); ?></h3>
		
		
		<?php
	
			$args = array(
						'container' => 'enabled_dates',
						'textarea' => 'package_enabled_dates',
						'headers' => array(__('From', 'dynamicpackages'), __('To', 'dynamicpackages')),
						'type' => array('date', 'date'),
						'min' => 'package_enabled_num',
						'max' => 'package_enabled_num',
						'value' => package_field('package_enabled_dates')
					);		
					
			echo dy_utilities::handsontable($args);
		?>		
	

		<?php
	}		

	public static function package_description_html( $post) { ?>
		
		<?php 

			wp_nonce_field( '_package_nonce', 'package_nonce' );

			if(dy_validators::is_child())
			{
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
								array_push($language_list, $value);
								
								?>
									<p>
										<label for="package_child_title_<?php echo esc_html($value); ?>"><?php _e( 'Subpackage Short Title', 'dynamicpackages' ); ?> - <?php echo esc_html($value);?></label></br>
										<input type="text" value="<?php echo package_field( 'package_child_title_'.$value ); ?>" name="package_child_title_<?php echo esc_html($value); ?>" id="package_child_title_<?php echo esc_html($value); ?>">
									</p>	
								<?php
							}
						}	
					}
				}
				else
				{
					?>
						<p>
							<label for="package_child_title"><?php _e( 'Subpackage Short Title', 'dynamicpackages' ); ?></label></br>
							<input type="text" value="<?php echo package_field( 'package_child_title' ); ?>" name="package_child_title" id="package_child_title" >
						</p>				
					<?php
				}				
			}
		?>		
		
		<?php if(!dy_validators::is_child()) : ?>
			<p>
				<label for="package_display"><?php _e( 'Hide Package', 'dynamicpackages' ); ?></label><br />
				<select name="package_display" id="package_display">
					<option value="0" <?php echo (package_field( 'package_display' ) == 0 ) ? 'selected' : ''; ?> ><?php _e( 'No', 'dynamicpackages' ); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>
					<option value="1" <?php echo (package_field( 'package_display' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'Yes', 'dynamicpackages' ); ?></option>
				</select>
			</p>			
			
			<p>
				<label for="package_trip_code"><?php _e( 'Code', 'dynamicpackages' ); ?></label><br />
				<input type="text" name="package_trip_code" id="package_trip_code" value="<?php echo package_field( 'package_trip_code' ); ?>">
			</p>

			<p>
				<label for="package_package_type"><?php _e( 'Package Type', 'dynamicpackages' ); ?></label><br />
				<select name="package_package_type" id="package_package_type">
					<option value="0" <?php echo (package_field( 'package_package_type' ) == 0 ) ? 'selected' : ''; ?> ><?php _e( 'One day', 'dynamicpackages' ); ?></option>
					<option value="1" <?php echo (package_field( 'package_package_type' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'Multi-day', 'dynamicpackages' ); ?></option>
					<option value="2" <?php echo (package_field( 'package_package_type' ) == 2 ) ? 'selected' : ''; ?> ><?php _e( 'Rental (per day)', 'dynamicpackages' ); ?></option>
					<option value="3" <?php echo (package_field( 'package_package_type' ) == 3 ) ? 'selected' : ''; ?> ><?php _e( 'Rental (per hour)', 'dynamicpackages' ); ?></option>
					<option value="4" <?php echo (package_field( 'package_package_type' ) == 4 ) ? 'selected' : ''; ?> ><?php _e( 'Transport', 'dynamicpackages' ); ?></option>	
				</select>
			</p>	
			<?php if(package_field( 'package_package_type' ) < 2 || package_field( 'package_package_type' ) == 4) : ?>
				<p>
					<label for="package_length_unit"><?php _e( 'Length Unit', 'dynamicpackages' ); ?></label><br />
					<select name="package_length_unit" id="package_length_unit">
					
						<?php if(package_field( 'package_package_type' ) == 0 || package_field( 'package_package_type' ) == 4): ?>
							<option value="0" <?php echo (package_field( 'package_length_unit' ) == 0 ) ? 'selected' : ''; ?> ><?php _e( 'Minutes', 'dynamicpackages' ); ?></option>		
							<option value="1" <?php echo (package_field( 'package_length_unit' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'Hours', 'dynamicpackages' ); ?></option>
						<?php endif; ?>
						
						<?php if(intval(package_field( 'package_package_type' )) > 0): ?>
							<option value="2" <?php echo (package_field( 'package_length_unit' ) == 2 ) ? 'selected' : ''; ?> ><?php _e( 'Days', 'dynamicpackages' ); ?></option>
							<?php if(intval(package_field( 'package_package_type' )) == 1): ?>
								<option value="3" <?php echo (package_field( 'package_length_unit' ) == 3 ) ? 'selected' : ''; ?> ><?php _e( 'Nights', 'dynamicpackages' ); ?></option>		<option value="4" <?php echo (package_field( 'package_length_unit' ) == 4 ) ? 'selected' : ''; ?> ><?php _e( 'Weeks', 'dynamicpackages' ); ?></option>
							<?php endif; ?>
						<?php endif; ?>
						
					</select>
				</p>

				<p>
					<label for="package_duration"><?php _e( 'Duration', 'dynamicpackages' ); ?></label><br />
					<input type="number" name="package_duration" id="package_duration" value="<?php echo (intval(package_field( 'package_duration' )) > 0) ? package_field( 'package_duration' ) : 1; ?>">
				</p>
			<?php endif; ?>
			
			<?php if(intval(package_field( 'package_package_type' )) > 0) : ?>
			<p>
				<label for="package_duration_max"><?php _e( 'Maximum Duration', 'dynamicpackages' ); ?></label><br />
				<input type="number" step="0.1" name="package_duration_max" id="package_duration_max" value="<?php echo package_field( 'package_duration_max' ); ?>">
			</p>
			<?php endif; ?>
			<p>
				<label for="package_fixed_price"><?php _e( 'Show Prices Per Person', 'dynamicpackages' ); ?></label><br />
				<select name="package_fixed_price" id="package_fixed_price">
					<option value="0" <?php echo (package_field( 'package_fixed_price' ) == 0 ) ? 'selected' : ''; ?> ><?php _e( 'Yes', 'dynamicpackages' ); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>
					<option value="1" <?php echo (package_field( 'package_fixed_price' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'No', 'dynamicpackages' ); ?> (<?php echo esc_html(__('fixed prices', 'dynamicpackages')); ?>)</option>				
				</select>
			</p>
			
			<p>
				<label for="package_badge"><?php _e( 'Show Badge', 'dynamicpackages' ); ?></label><br />
				<select name="package_badge" id="package_badge">
					<option value="0" <?php echo (package_field( 'package_badge' ) == 0 ) ? 'selected' : ''; ?> >None</option>			
					<option value="1" <?php echo (package_field( 'package_badge' ) == 1 ) ? 'selected' : ''; ?> ><?php _e( 'Best Seller', 'dynamicpackages' ); ?></option>
					<option value="2" <?php echo (package_field( 'package_badge' ) == 2 ) ? 'selected' : ''; ?> ><?php _e( 'New', 'dynamicpackages' ); ?></option>
					<option value="3" <?php echo (package_field( 'package_badge' ) == 3 ) ? 'selected' : ''; ?> ><?php _e( 'Offer', 'dynamicpackages' ); ?></option>
					<option value="4" <?php echo (package_field( 'package_badge' ) == 4 ) ? 'selected' : ''; ?> ><?php _e( 'Featured', 'dynamicpackages' ); ?></option>
					<option value="5" <?php echo (package_field( 'package_badge' ) == 5 ) ? 'selected' : ''; ?> ><?php _e( 'Last Minute Deal', 'dynamicpackages' ); ?></option>					
				</select>
			</p>
			
			<p>
				<label for="package_badge_color"><?php _e( 'Badge Color', 'dynamicpackages' ); ?></label><br />
				<select  name="package_badge_color" id="package_badge_color">

				<option value="white" <?php echo (package_field( 'package_badge_color' ) == 'white' ) ? 'selected' : ''; ?> ><?php _e( 'White', 'dynamicpackages' ); ?></option>
				
				<option value="black" <?php echo (package_field( 'package_badge_color' ) == 'black' ) ? 'selected' : ''; ?> ><?php _e( 'Black', 'dynamicpackages' ); ?></option>
				
				<option value="grey" <?php echo (package_field( 'package_badge_color' ) == 'grey' ) ? 'selected' : ''; ?> ><?php _e( 'Grey', 'dynamicpackages' ); ?></option>
				
				<option value="blue" <?php echo (package_field( 'package_badge_color' ) == 'blue' ) ? 'selected' : ''; ?> ><?php _e( 'Blue', 'dynamicpackages' ); ?></option>	
				
				<option value="green" <?php echo (package_field( 'package_badge_color' ) == 'green' ) ? 'selected' : ''; ?> ><?php _e( 'Green', 'dynamicpackages' ); ?></option>	
				
				<option value="turquoise" <?php echo (package_field( 'package_badge_color' ) == 'turquoise' ) ? 'selected' : ''; ?> ><?php _e( 'Turquoise', 'dynamicpackages' ); ?></option>	
				
				<option value="purple" <?php echo (package_field( 'package_badge_color' ) == 'purple' ) ? 'selected' : ''; ?> ><?php _e( 'Purple', 'dynamicpackages' ); ?></option>	
				
				<option value="red" <?php echo (package_field( 'package_badge_color' ) == 'red' ) ? 'selected' : ''; ?> ><?php _e( 'Red', 'dynamicpackages' ); ?></option>
				
				<option value="orange" <?php echo (package_field( 'package_badge_color' ) == 'orange' ) ? 'selected' : ''; ?> ><?php _e( 'Orange', 'dynamicpackages' ); ?></option>	
				
				<option value="yellow" <?php echo (package_field( 'package_badge_color' ) == 'yellow' ) ? 'selected' : ''; ?> ><?php _e( 'Yellow', 'dynamicpackages' ); ?></option>				
				</select>
			</p>		
		<?php endif; ?>
		<?php
	}
}


?>