<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Metaboxes
{

    public function __construct()
    {
		add_action('add_meta_boxes', array(&$this, 'package_add_meta_box'));
    }

    public function package_add_meta_box()
    {

		$this->set_args();

        add_meta_box('package-a', __('Description', 'dynamicpackages') , array(&$this,
            'package_description_html'
        ) , 'packages', 'normal', 'default');
        add_meta_box('package-b', __('Pricing Controls', 'dynamicpackages') , array(&$this,
            'package_pricing_html'
        ) , 'packages', 'normal', 'default');

        if (!$this->has_children)
        {
            add_meta_box('package-c', __('Rates', 'dynamicpackages') , array(&$this,
                'package_rates_html'
            ) , 'packages', 'normal', 'default');
        }

        add_meta_box('package-d', __('Availability', 'dynamicpackages') , array(&$this,
            'package_availability_html'
        ) , 'packages', 'normal', 'default');

        if (!$this->is_child)
        {

            add_meta_box('package-f', __('Messages', 'dynamicpackages') , array(&$this,
                'package_messages_html'
            ) , 'packages', 'normal', 'default');
        }

		if(!$this->is_child || ($this->is_transport && $this->is_child))
		{
            add_meta_box('package-e', __('Departure', 'dynamicpackages') , array(&$this,
                'package_departure_html'
            ) , 'packages', 'normal', 'default');
		}

		add_meta_box('package-g', __('Coupons', 'dynamicpackages') , array(&$this,
			'package_coupon_html'
		) , 'packages', 'normal', 'default');


	add_meta_box('package-h', __('Controls', 'dynamicpackages') , array(&$this,
		'package_controls_html'
	) , 'packages', 'normal', 'default');


    }

	public function set_args()
	{
		$this->coupons = package_field('package_coupons');
		$this->price_chart = package_field('package_price_chart');
		$this->occupancy_chart = package_field('package_occupancy_chart');
		$this->disabled_dates = package_field('package_disabled_dates');
		$this->enabled_dates = package_field('package_enabled_dates');
		$this->seasons_chart = package_field('package_seasons_chart');
        $this->package_type = intval(package_field('package_package_type'));
		$this->length_unit = intval(package_field('package_length_unit'));
		$this->fixed_price = intval(package_field('package_fixed_price'));
        $this->show_pricing = intval(package_field('package_show_pricing'));
        $this->auto_booking = intval(package_field('package_auto_booking'));
        $this->payment = intval(package_field('package_payment'));
        $this->deposit = floatval(package_field('package_deposit'));
		$this->max_coupons = intval(package_field('package_max_coupons'));
		$this->min_persons = intval(package_field('package_min_persons'));
		$this->max_persons = intval(package_field('package_max_persons'));
		$this->free = intval(package_field('package_free'));
		$this->discount = intval(package_field('package_discount'));
		$this->num_seasons = intval(package_field('package_num_seasons'));
		$this->booking_from = intval(package_field('package_booking_from'));
		$this->booking_to = intval(package_field('package_booking_to'));
		$this->disabled_num = intval(package_field('package_disabled_num'));
		$this->enabled_num = intval(package_field('package_enabled_num'));
		$this->package_check_in_end_hour = package_field('package_check_in_end_hour');
		$this->check_in_hour = package_field('package_check_in_hour');
		$this->one_way_surcharge = package_field('package_one_way_surcharge');
		$this->start_hour = package_field('package_start_hour');
		$this->start_address = package_field('package_start_address');
		$this->start_address_short = package_field('package_start_address_short');
		$this->return_hour = package_field('package_return_hour');
		$this->return_address = package_field('package_return_address');
		$this->return_address_short = package_field('package_return_address_short');
		$this->increase_persons = intval(package_field('package_increase_persons'));
		$this->event_date = package_field('package_event_date');
		$this->by_hour = intval(package_field('package_by_hour'));
		$this->min_hour = package_field('package_min_hour');
		$this->max_hour = package_field('package_max_hour');
		$this->disabled_dates_api = package_field('package_disabled_dates_api');
		$this->display = intval(package_field('package_display'));
		$this->code = package_field('package_trip_code');
		$this->duration = floatval(package_field('package_duration'));
		$this->duration_max = floatval(package_field('package_duration_max'));
		$this->badge = intval(package_field('package_badge'));
		$this->badge_color = package_field('package_badge_color');
		$this->schema = intval(package_field('package_schema'));
		$this->is_child = dy_validators::is_child();
		$this->disable_child = ($this->is_child) ? 'disabled' : '';
		$this->is_parent_with_no_child = dy_validators::is_parent_with_no_child();
		$this->has_children = dy_validators::has_children();
		$this->is_transport = package_field('package_package_type') === 'transport';
		$this->languages = get_languages();
		$this->count_languages = count($this->languages);
		$this->week_days = dy_utilities::get_week_days_abbr();
		$this->week_day_names = dy_utilities::get_week_day_names_long();
		$this->set_lang_fields();
		$this->set_week_day_fields();
		$this->set_handsontable_args();
		$this->redirect_page = intval(package_field('package_redirect_page'));
	}

	public function set_week_day_fields(){
		$week_day_fields = array('week_day_surcharge', 'day');

		for($x = 0; $x < 7; $x++)
		{
			for($w = 0; $w < count($week_day_fields); $w++)
			{
				$id =  $week_day_fields[$w].'_'.$this->week_days[$x];
				$name = 'package_' . $id;
				$this->$id = intval(package_field($name));
			}
		}
	}

	public function set_lang_fields()
	{
		//handles language based fields
		$lang_fields = array('confirmation_message', 'child_title', 'redirect_url');

		for($x = 0; $x < count($lang_fields); $x++)
		{
			for($l = 0; $l < $this->count_languages; $l++)
			{
				$id =  $lang_fields[$x].'_'.$this->languages[$l];
				$name = 'package_' . $id;
				$this->$id = package_field($name);
			}
		}		
	}

	public function set_handsontable_args()
	{
		$this->coupon_args = array(
			'container' => 'coupons',
			'textarea' => 'package_coupons',
			'headers' => array(
				__('Code', 'dynamicpackages') ,
				__('Discount (%)', 'dynamicpackages') ,
				__('Expiration', 'dynamicpackages') ,
				__('Publish', 'dynamicpackages') ,
				__('Min. Duration', 'dynamicpackages') ,
				__('Max. Duration', 'dynamicpackages'),
				__('Bookings After Expires', 'dynamicpackages'),
			) ,
			'type' => array(
				'text',
				'numeric',
				'date',
				'checkbox',
				'numeric',
				'numeric',
				'checkbox',
			) ,
			'min' => 'package_max_coupons',
			'max' => 'package_max_coupons',
			'value' => $this->coupons,
			'disabled' => $this->disable_child
		);

		$this->price_chart_args = array(
			'container' => 'price_chart',
			'textarea' => 'package_price_chart',
			'headers' => array(
				__('Regular', 'dynamicpackages') ,
				__('Discount', 'dynamicpackages')
			) ,
			'type' => array(
				'currency',
				'currency'
			) ,
			'min' => 'package_min_persons',
			'max' => 'package_max_persons',
			'value' => $this->price_chart
		);

		$this->occupancy_chart_args = array(
			'container' => 'occupancy_chart',
			'textarea' => 'package_occupancy_chart',
			'headers' => array(
				__('Regular', 'dynamicpackages') ,
				__('Discount', 'dynamicpackages')
			) ,
			'type' => array(
				'currency',
				'currency'
			) ,
			'min' => 'package_min_persons',
			'max' => 'package_max_persons',
			'value' => $this->occupancy_chart
		);

		$this->disabled_dates_args = array(
			'container' => 'disabled_dates',
			'textarea' => 'package_disabled_dates',
			'headers' => array(
				__('From', 'dynamicpackages') ,
				__('To', 'dynamicpackages')
			) ,
			'type' => array(
				'date',
				'date'
			) ,
			'min' => 'package_disabled_num',
			'max' => 'package_disabled_num',
			'value' => $this->disabled_dates
		);

		$this->enabled_dates_args = array(
			'container' => 'enabled_dates',
			'textarea' => 'package_enabled_dates',
			'headers' => array(
				__('From', 'dynamicpackages') ,
				__('To', 'dynamicpackages')
			) ,
			'type' => array(
				'date',
				'date'
			) ,
			'min' => 'package_enabled_num',
			'max' => 'package_enabled_num',
			'value' => $this->enabled_dates
		);


		$this->seasons_args = array(
			'container' => 'seasons_chart',
			'textarea' => 'package_seasons_chart',
			'headers' => array(
				__('Name', 'dynamicpackages') ,
				__('From', 'dynamicpackages') ,
				__('To', 'dynamicpackages') ,
				__('Duration', 'dynamicpackages') ,
				__('ID', 'dynamicpackages')
			) ,
			'type' => array(
				'text',
				'date',
				'date',
				'dropdown',
				'readonly'
			) ,
			'dropdown' => array(
				1,
				2,
				3,
				4,
				5,
				6,
				7
			) ,
			'min' => 'package_num_seasons',
			'max' => 'package_num_seasons',
			'value' => $this->seasons_chart,
			'disabled' => $this->disable_child
		);
	}

    public function select_number($name, $min = 1, $max = 50, $attr = '')
    {
		$max = $max + $min;
        $options = '';
        $value = $this->$name;
		$name_attr = 'package_'.$name;

        for ($x = $min; $x < $max; $x++)
        {
            $selected = '';

            if ($value == $x)
            {
                $selected = 'selected';
            }

            $options .= '<option ' . esc_attr($selected) . '>' . $x . '</option>';
        }

        echo '<select id="' . esc_attr($name_attr) . '" name="' . esc_attr($name_attr) . '" ' . esc_attr($attr) . '>' . $options . '</select>';

    }

    public function package_coupon_html($post)
    { 
		?>
		<p><label><?php echo esc_html(__('Number of coupons', 'dynamicpackages')); ?> <?php $this->select_number('max_coupons', 1, 10, $this->disable_child); ?></label></p>
		
		<?php echo dy_utilities::handsontable($this->coupon_args); ?>
		
		<?php
    }

    public function package_messages_html($post)
    { ?>

		<?php
			for($x = 0; $x < $this->count_languages; $x++)
			{
				$lang = $this->languages[$x];
				$id = 'confirmation_message_' . $lang;
				$name = 'package_' . $id;
				?>
					<p>
						<label for="<?php echo esc_attr($name); ?>"><?php echo esc_html(__('Confirmation Message', 'dynamicpackages')); ?> - <?php esc_html_e($lang); ?></label></br>
						<textarea cols="40" rows="6" type="text" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>"><?php echo esc_textarea($this->$id); ?></textarea>
					</p>
				<?php
			}
		?>
		
		<?php
    }

    public function package_controls_html($post)
    { ?>

		<?php
			for($x = 0; $x < $this->count_languages; $x++)
			{
				$lang = $this->languages[$x];
				$id = 'redirect_url_' . $lang;
				$name = 'package_' . $id;
				?>
					<p>
						<label for="<?php echo esc_attr($name); ?>"><?php echo esc_html(__('Redirect URL', 'dynamicpackages')); ?> - <?php esc_html_e($lang); ?></label></br>
						<input class="width-100" type="url" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_url($this->$id); ?>" />
					</p>
				<?php
			}

			
		?>

			<select name="package_redirect_page" id="package_redirect_page">
				<option value="0" <?php echo ($this->redirect_page == 0) ? 'selected' : ''; ?>>Package Page</option>
				<option value="1" <?php echo ($this->redirect_page == 1) ? 'selected' : ''; ?>>Booking Page</option>
			</select>
		
		<?php
    }

    public function package_departure_html($post)
    { ?>

		<?php if ($this->is_transport): ?>
			<h3><?php echo esc_html(__('Transport', 'dynamicpackages')); ?></h3>

			<p>
			<label for="package_one_way_surcharge"><?php echo esc_html(__('One-way Surcharge', 'dynamicpackages')); ?></label></br>
			<input type="number" name="package_one_way_surcharge" id="package_one_way_surcharge" value="<?php echo esc_attr($this->one_way_surcharge); ?>">%
		</p>

		<?php
        endif; ?>

		<p>
			<label for="package_check_in_hour"><?php echo esc_html(__('Check-in Hour', 'dynamicpackages')); ?></label></br>
			<input class="timepicker" type="text" name="package_check_in_hour" id="package_check_in_hour" value="<?php echo esc_attr($this->check_in_hour); ?>">
		</p>
		<p>
			<label for="package_start_hour"><?php echo esc_html(__('Departure Hour', 'dynamicpackages')); ?></label></br>
			<input class="timepicker" type="text" name="package_start_hour" id="package_start_hour" value="<?php echo esc_attr($this->start_hour); ?>">
		</p>				
		<p>
			<label for="package_start_address"><?php echo esc_html(__('Departure Address', 'dynamicpackages')); ?></label></br>
			<textarea cols="60" type="text" name="package_start_address" id="package_start_address"><?php echo esc_textarea($this->start_address); ?></textarea>
		</p>

		<p>
			<label for="package_start_address_short"><?php echo esc_html(__('Departure Address Short', 'dynamicpackages')); ?></label></br>
			<input type="text" name="package_start_address_short" id="package_start_address_short" value="<?php echo esc_attr($this->start_address_short); ?>"> 
		</p>

		<?php if ($this->is_transport): ?>
			<h3><?php esc_html_e('Return', 'dynamicpackages'); ?></h3>
			
			<p>
				<label for="package_check_in_end_hour"><?php echo esc_html(__('Check-in Hour', 'dynamicpackages')); ?></label></br>
				<input class="timepicker" type="text" name="package_check_in_end_hour" id="package_check_in_end_hour" value="<?php echo esc_attr($this->package_check_in_end_hour); ?>">
			</p>
			<p>
				<label for="package_return_hour"><?php echo esc_html(__('Departure Hour', 'dynamicpackages')); ?></label></br>
				<input class="timepicker" type="text" name="package_return_hour" id="package_return_hour" value="<?php echo esc_attr($this->return_hour); ?>">
			</p>				
			<p>
				<label for="package_return_address"><?php echo esc_html(__('Departure Address', 'dynamicpackages')); ?></label></br>
				<textarea cols="60" type="text" name="package_return_address" id="package_return_address"><?php echo esc_textarea($this->return_address); ?></textarea>
			</p>
			
			<p>
				<label for="package_return_address_short"><?php echo esc_html(__('Short Departure Address', 'dynamicpackages')); ?></label></br>
				<input type="text" name="package_return_address_short" id="package_return_address_short" value="<?php echo esc_attr($this->return_address_short); ?>">
			</p>		
			
		<?php endif; ?>

		<?php
    }

    public function package_pricing_html($post)
    { ?>		
		<?php if (!$this->is_child): ?>	
				<p>
					<label for="package_show_pricing"><?php echo esc_html(__('Show Price Table', 'dynamicpackages')); ?></label><br />
					<select name="package_show_pricing" id="package_show_pricing">
						<option value="0" <?php echo ($this->show_pricing == 0) ? 'selected' : ''; ?> ><?php echo esc_html__('Yes', 'dynamicpackages'); ?> (<?php echo esc_html__('default', 'dynamicpackages'); ?>)</option>			
						<option value="1" <?php echo ($this->show_pricing == 1) ? 'selected' : ''; ?> ><?php echo esc_html__('No', 'dynamicpackages'); ?></option>			
					</select>
				</p>

				<p>
					<label for="package_auto_booking"><?php echo esc_html(__('Enable Automatic Booking', 'dynamicpackages')); ?></label><br />
					<select name="package_auto_booking" id="package_auto_booking">
						<option value="0" <?php echo ($this->auto_booking == 0) ? 'selected' : ''; ?> >No</option>
						<option value="1" <?php echo ($this->auto_booking == 1) ? 'selected' : ''; ?> >Yes</option>
					</select>
				</p>				
			<?php
        endif; ?>
			
			<?php if ($this->is_child || $this->is_parent_with_no_child): ?>
			
			<p>
				<label for="package_min_persons"><?php echo esc_html(__('Minimum Number of participants', 'dynamicpackages')); ?></label><br />
				
				<?php $this->select_number('min_persons', 1, 99); ?>
				
			</p>
			<p>
				<label for="package_max_persons"><?php echo esc_html(__('Maximum Number of participants', 'dynamicpackages')); ?></label><br />
				<?php $this->select_number('max_persons', 1, 500); ?>
			</p>
			<p>
				<label for="package_free"><span><?php echo esc_html(__('Children free up to', 'dynamicpackages')); ?></span></br>
				<?php $this->select_number('free', 0, 17); ?>
				 <?php echo esc_html(__('year old', 'dynamicpackages')); ?></label>
			</p>
			<p>
				<label for="package_discount"><span><?php echo esc_html(__('Children Discount up to', 'dynamicpackages')); ?></span></br>
				<?php $this->select_number('discount', 0, 17); ?>
				 <?php echo esc_html(__('year old', 'dynamicpackages')); ?></label>
			</p>
			<p>
				<label for="package_increase_persons"><?php echo esc_html(__('Increase maximum number of participants by', 'dynamicpackages')); ?></label><br />
				<span><input type="number" min="0" name="package_increase_persons" id="package_increase_persons" value="<?php echo esc_attr($this->increase_persons); ?>"> <?php echo esc_html(__('get more leads even if the prices are not defined', 'dynamicpackages')); ?>.</span>
			</p>	
		<?php
        endif; ?>
			
		<?php if (!$this->is_child): ?>
			<p>
				<label for="package_payment"><?php echo esc_html(__('Payment', 'dynamicpackages')); ?></label><br />
				<select name="package_payment" id="package_payment">
					<option value="0" <?php echo ($this->payment === 0) ? 'selected' : ''; ?> ><?php echo esc_html__('Full Payment', 'dynamicpackages'); ?></option>
					<option value="1" <?php echo ($this->payment === 1) ? 'selected' : ''; ?> ><?php echo esc_html__('Deposit', 'dynamicpackages'); ?></option>
				</select>
				<label for="package_deposit"><input type="number" step="0.1" name="package_deposit" id="package_deposit" value="<?php echo esc_attr($this->deposit); ?>">%</label>
			</p>
		<?php endif; ?>


		<fieldset>	

			<h3><?php echo esc_html(__('Number of Special Seasons', 'dynamicpackages')); ?> <?php $this->select_number('num_seasons', 0, 10, $this->disable_child); ?></h3>
		
			<?php echo dy_utilities::handsontable($this->seasons_args); ?>			
	
		</fieldset>
		
		<?php
    }


	public function build_week_day_surcharge_fields()
	{
		$output = '<fieldset><h3 id="week_day_surcharges">' . esc_html(__('Surcharge per day of the week', 'dynamicpackages')) . '</h3>';

		for ($x = 0; $x < 7; $x++)
		{
			$day = $this->week_days[$x];
			$id = 'week_day_surcharge_'.$day;
			$name = 'package_' . $id;
			$output .= '<p><label for="' . esc_attr($name) . '">';
			$output .= '<input value="' . esc_attr($this->$id) . '" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" type="number" />% ';
			$output .= $day . '</label></p>';
		}

		return $output;
	}

    public function package_rates_html($post)
    { ?>
		

		<fieldset>
			<?php echo '<h3>'.esc_html(__('Base Prices - Per Person', 'dynamicpackages')).'</h3>'; ?>
			<?php echo dy_utilities::handsontable($this->price_chart_args); ?>
		</fieldset>
		

		<fieldset>			
			<h3 id="package_variable_duration_price_title"><?php echo esc_html(__('Variable Duration Prices - Per Person', 'dynamicpackages')); ?></h3>
			
			<textarea class="hidden" name="package_occupancy_chart" id="package_occupancy_chart"><?php echo esc_textarea($this->occupancy_chart); ?></textarea>
			<div id="special_seasons"></div>

			<?php echo $this->build_week_day_surcharge_fields(); ?>
		</fieldset>
		
		<?php
    }

    public function package_availability_html($post)
    { 
		?>
		
		<?php if (!$this->is_child): ?>
			<h4><?php echo esc_html(__('Event Date', 'dynamicpackages')); ?></h4>
				<p>
					<input type="text" name="package_event_date" id="package_event_date" class="datepicker" value="<?php echo esc_attr($this->event_date); ?>">
				</p>
			<h4><?php echo esc_html(__('Accept Bookings', 'dynamicpackages')); ?></h4>
			<p>
				<label for="package_booking_from"><?php echo esc_html(__('Between', 'dynamicpackages')); ?> 
				<?php $this->select_number('booking_from', 0, 366); ?>
				</label> <?php echo esc_html(__('to', 'dynamicpackages')); ?>
				<label for="package_booking_to">
				<?php $this->select_number('booking_to', 0, 366); ?> 
				<?php echo esc_html(__('days', 'dynamicpackages')); ?></label>
			</p>		
			


			<h4><?php echo esc_html(__('Book by Hour', 'dynamicpackages')); ?></h4>
			<p>
				<select name="package_by_hour" id="package_by_hour">
					<option value="0" <?php echo ($this->by_hour === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('No', 'dynamicpackages')); ?></option>			
					<option value="1" <?php echo ($this->by_hour === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Yes', 'dynamicpackages')); ?></option>			
				</select>
				<?php echo esc_html(__('between', 'dynamicpackages')); ?>
				<input type="text" class="timepicker" name="package_min_hour" id="package_min_hour" value="<?php echo esc_attr($this->min_hour); ?>" > 
				<?php esc_html_e('and', 'dynamicpackages'); ?>
				<input type="text" class="timepicker" name="package_max_hour" id="package_max_hour" value="<?php echo esc_attr($this->max_hour); ?>" > 			
			</p>	
		<?php endif; ?>
		<fieldset>
			

		<?php if(!$this->has_children): ?>

			<h4><?php echo esc_html(__('Disable Days', 'dynamicpackages')); ?></h4>
			<p>
				<?php echo $this->build_disabled_days(); ?>
			</p>

			<h3><?php esc_html_e('Disabled Dates', 'dynamicpackages'); ?> <?php $this->select_number('disabled_num', 1, 30); ?></h3>
			
			<?php echo dy_utilities::handsontable($this->disabled_dates_args); ?>
		<?php endif; ?>
		
		<h3><?php esc_html_e('Disabled Dates API Endpoint', 'dynamicpackages'); ?></h3>
		<p><input type="url" name="package_disabled_dates_api" id="package_disabled_dates_api" value="<?php echo esc_url($this->disabled_dates_api); ?>" > </p>
		</fieldset>
		
		<h3><?php echo esc_html(__('Force Enabled Dates', 'dynamicpackages')); ?> <?php $this->select_number('enabled_num', 1, 20); ?></h3>
		
		<?php echo dy_utilities::handsontable($this->enabled_dates_args); ?>	
	
		<?php
    }

	public function build_disabled_days()
	{
		$output = '';

		for($x = 0; $x < 7; $x++)
		{
			$day = $this->week_days[$x];
			$label = $this->week_day_names[$x];
			$id = 'day_' . $day;
			$name = 'package_'.$id;
			$checked = ($this->$id === 1) ? ' checked="checked" ' : '';
			$output .= '<label for="'.esc_attr($name).'"><input type="checkbox" name="'.esc_attr($name).'" id="'.esc_attr($name).'" value="1"  '.$checked.'/> '.esc_html($label).' </label>';
			$output .= '<br/>';
		}	

		return $output;
	}

    public function package_description_html($post)
    { ?>
		
		<?php

			wp_nonce_field('_package_nonce', 'package_nonce');

			if ($this->is_child || $this->has_children)
			{

				for($x = 0; $x < $this->count_languages; $x++)
				{
					$lang = $this->languages[$x];
					$id = 'child_title_'.$lang;
					$name = 'package_'.$id;
					?>
						<p>
							<label for="<?php echo esc_attr($name); ?>"><?php echo esc_html(__('Subpackage Short Title', 'dynamicpackages')); ?> - <?php echo esc_html($lang); ?></label></br>
							<input type="text" value="<?php echo esc_attr($this->$id); ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>">
						</p>			
					<?php
				}
			}
		?>		
		
		<?php if (!$this->is_child): ?>
			<p>
				<label for="package_display"><?php echo esc_html(__('Hide Package', 'dynamicpackages')); ?></label><br />
				<select name="package_display" id="package_display">
					<option value="0" <?php echo ($this->display === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('No', 'dynamicpackages')); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>
					<option value="1" <?php echo ($this->display === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Yes', 'dynamicpackages')); ?></option>
				</select>
			</p>
			
			<p>
				<label for="package_schema"><?php echo esc_html(__('Schema Type', 'dynamicpackages')); ?></label><br />
				<select name="package_schema" id="package_schema">
					<option value="0" <?php echo ($this->schema === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('Event', 'dynamicpackages')); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>
					<option value="1" <?php echo ($this->schema === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Product', 'dynamicpackages')); ?></option>
				</select>
			</p>			
			
			<p>
				<label for="package_trip_code"><?php echo esc_html(__('Code', 'dynamicpackages')); ?></label><br />
				<input type="text" name="package_trip_code" id="package_trip_code" value="<?php echo esc_attr($this->code); ?>">
			</p>
		<?php endif; ?>

		<p>
			<label for="package_package_type"><?php echo esc_html(__('Package Type', 'dynamicpackages')); ?></label><br />
			<select name="package_package_type" id="package_package_type" <?php echo esc_attr($this->disable_child); ?>>
				<option value="0" <?php echo ($this->package_type === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('One day', 'dynamicpackages')); ?></option>
				<option value="1" <?php echo ($this->package_type === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Multi-day', 'dynamicpackages')); ?></option>
				<option value="2" <?php echo ($this->package_type === 2) ? 'selected' : ''; ?> ><?php echo esc_html(__('Rental (per day)', 'dynamicpackages')); ?></option>
				<option value="3" <?php echo ($this->package_type === 3) ? 'selected' : ''; ?> ><?php echo esc_html(__('Rental (per hour)', 'dynamicpackages')); ?></option>
				<option value="4" <?php echo ($this->package_type === 4) ? 'selected' : ''; ?> ><?php echo esc_html(__('Transport', 'dynamicpackages')); ?></option>	
			</select>
		</p>	
		<p>
			<label for="package_length_unit"><?php echo esc_html(__('Length Unit', 'dynamicpackages')); ?></label><br />
			<select name="package_length_unit" id="package_length_unit" <?php echo esc_attr($this->disable_child); ?>>
					<option value="0" <?php echo ($this->length_unit === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('Minutes', 'dynamicpackages')); ?></option>		
					<option value="1" <?php echo ($this->length_unit === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Hours', 'dynamicpackages')); ?></option>
					<option value="2" <?php echo ($this->length_unit === 2) ? 'selected' : ''; ?> ><?php echo esc_html(__('Days', 'dynamicpackages')); ?></option>
					<option value="3" <?php echo ($this->length_unit === 3) ? 'selected' : ''; ?> ><?php echo esc_html(__('Nights', 'dynamicpackages')); ?></option>		
					<option value="4" <?php echo ($this->length_unit === 4) ? 'selected' : ''; ?> ><?php echo esc_html(__('Weeks', 'dynamicpackages')); ?></option>
			</select>
		</p>

		<p>
			<label for="package_duration"><?php echo esc_html(__('Duration', 'dynamicpackages')); ?></label><br />
			<input type="number" name="package_duration" id="package_duration" <?php echo esc_attr($this->disable_child); ?> value="<?php echo esc_attr($this->duration > 0) ? $this->duration : 1; ?>">
		</p>

		<p>
			<label for="package_duration_max"><?php echo esc_html(__('Maximum Duration', 'dynamicpackages')); ?></label><br />
			<input type="number" step="0.1" name="package_duration_max" <?php echo esc_attr($this->disable_child); ?> id="package_duration_max" value="<?php echo esc_attr($this->duration_max); ?>">
		</p>

		<?php if (!$this->is_child): ?>

			<p>
				<label for="package_fixed_price"><?php echo esc_html(__('Show Prices Per Person', 'dynamicpackages')); ?></label><br />
				<select name="package_fixed_price" id="package_fixed_price">
					<option value="0" <?php echo ($this->fixed_price === 0) ? 'selected' : ''; ?> ><?php echo esc_html(__('Yes', 'dynamicpackages')); ?> (<?php echo esc_html(__('default', 'dynamicpackages')); ?>)</option>
					<option value="1" <?php echo ($this->fixed_price === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('No', 'dynamicpackages')); ?> (<?php echo esc_html(__('fixed prices', 'dynamicpackages')); ?>)</option>				
				</select>
			</p>
			
			<p>
				<label for="package_badge"><?php echo esc_html(__('Show Badge', 'dynamicpackages')); ?></label><br />
				<select name="package_badge" id="package_badge">
					<option value="0" <?php echo ($this->badge === 0) ? 'selected' : ''; ?> >None</option>			
					<option value="1" <?php echo ($this->badge === 1) ? 'selected' : ''; ?> ><?php echo esc_html(__('Best Seller', 'dynamicpackages')); ?></option>
					<option value="2" <?php echo ($this->badge === 2) ? 'selected' : ''; ?> ><?php echo esc_html(__('New', 'dynamicpackages')); ?></option>
					<option value="3" <?php echo ($this->badge === 3) ? 'selected' : ''; ?> ><?php echo esc_html(__('Offer', 'dynamicpackages')); ?></option>
					<option value="4" <?php echo ($this->badge === 4) ? 'selected' : ''; ?> ><?php echo esc_html(__('Featured', 'dynamicpackages')); ?></option>
					<option value="5" <?php echo ($this->badge === 5) ? 'selected' : ''; ?> ><?php echo esc_html(__('Last Minute Deal', 'dynamicpackages')); ?></option>					
				</select>
			</p>
			
			<p>
				<label for="package_badge_color"><?php echo esc_html(__('Badge Color', 'dynamicpackages')); ?></label><br />
				<select  name="package_badge_color" id="package_badge_color">

				<option value="white" <?php echo ($this->badge_color === 'white') ? 'selected' : ''; ?> ><?php echo esc_html(__('White', 'dynamicpackages')); ?></option>
				
				<option value="black" <?php echo ($this->badge_color === 'black') ? 'selected' : ''; ?> ><?php echo esc_html(__('Black', 'dynamicpackages')); ?></option>
				
				<option value="grey" <?php echo ($this->badge_color === 'grey') ? 'selected' : ''; ?> ><?php echo esc_html(__('Grey', 'dynamicpackages')); ?></option>
				
				<option value="blue" <?php echo ($this->badge_color === 'blue') ? 'selected' : ''; ?> ><?php echo esc_html(__('Blue', 'dynamicpackages')); ?></option>	
				
				<option value="green" <?php echo ($this->badge_color === 'green') ? 'selected' : ''; ?> ><?php echo esc_html(__('Green', 'dynamicpackages')); ?></option>	
				
				<option value="turquoise" <?php echo ($this->badge_color === 'turquoise') ? 'selected' : ''; ?> ><?php echo esc_html(__('Turquoise', 'dynamicpackages')); ?></option>	
				
				<option value="purple" <?php echo ($this->badge_color === 'purple') ? 'selected' : ''; ?> ><?php echo esc_html(__('Purple', 'dynamicpackages')); ?></option>	
				
				<option value="red" <?php echo ($this->badge_color === 'red') ? 'selected' : ''; ?> ><?php echo esc_html(__('Red', 'dynamicpackages')); ?></option>
				
				<option value="orange" <?php echo ($this->badge_color === 'orange') ? 'selected' : ''; ?> ><?php echo esc_html(__('Orange', 'dynamicpackages')); ?></option>	
				
				<option value="yellow" <?php echo ($this->badge_color === 'yellow') ? 'selected' : ''; ?> ><?php echo esc_html(__('Yellow', 'dynamicpackages')); ?></option>				
				</select>
			</p>		
		<?php
        endif; ?>
		<?php
    }
}

?>
