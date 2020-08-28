<?php
	
	$price_chart = get_price_chart();
	$discount = 0;
	$free = 0;
	$start_free = 0;
	$start_discount = 0;
	
	$sum = 0;
	$sum_adults = 0;
	$each_adult = dy_utilities::get_price_adults();
	$sum_children = 0;
	$each_child = dy_utilities::get_price_discount();
	$payment = 0;
	$deposit = 25;
	$total = dy_utilities::total();
	$payment_amount = $total;
	$participants = intval(sanitize_text_field($_GET['pax_regular']));
	$traveling_children = '';
	$deposit_label = '';
	
	if(package_field('package_free') > 0)
	{
		$free = package_field('package_free');
	}	
	if(package_field('package_discount') > 0)
	{
		if(package_field('package_free') > 0)
		{
			$start_discount = $free + 1;
		}
		$discount = package_field('package_discount');
	}

	
	if(isset($_GET['pax_free']))
	{
		if(intval(sanitize_text_field($_GET['pax_free'])) > 0)
		{
			$participants = $participants + intval(sanitize_text_field($_GET['pax_free']));
			$traveling_children = 'yes';
		}
	}
	if(isset($_GET['pax_discount']))
	{
		if(intval(sanitize_text_field($_GET['pax_discount'])) > 0)
		{
			$participants = $participants + intval(sanitize_text_field($_GET['pax_discount']));
			$traveling_children = 'yes';
		}
	}
	
	if(package_field('package_payment' ) == 1)
	{
		$payment = 1;
		$deposit = floatval(dy_utilities::get_deposit());
		$payment_amount = dy_sum_tax(floatval(dy_utilities::total())*(floatval($deposit)*0.01));
		$outstanding_amount = dy_sum_tax(floatval($total)-$payment_amount);
		$outstanding_label = esc_html(__('Outstanding Balance', 'dynamicpackages')).' '.dy_money($outstanding_amount, 'dy_calc dy_calc_outstanding');
		$deposit_label = esc_html(__('Deposit', 'dynamicpackages')).' '.dy_money($payment_amount, 'dy_calc dy_calc_total').' ('.esc_html($deposit).'%)';
	}
	
	for($a = 0;  $a < count($price_chart); $a++)
	{
		if(floatval(sanitize_text_field($_GET['pax_regular'])) == ($a+1))
		{
			if(floatval($price_chart[$a][0]) > 0 && $price_chart[$a][0] != 0)
			{
				$each_adult = floatval($each_adult)+floatval($price_chart[$a][0]);
				
				if(dy_utilities::increase_by_hour() || dy_utilities::increase_by_day())
				{
					$each_adult = $each_adult * floatval(sanitize_text_field($_GET['booking_extra']));
				}
				
				if(dynamicpackages_Validators::valid_coupon())
				{
					$each_adult = $each_adult * ((100 - floatval(self::get_coupon('discount'))) /100);
				}
				
				$sum_adults = $each_adult*floatval(sanitize_text_field($_GET['pax_regular']));
				$sum = $sum + $sum_adults;
			}			
		}
		if(isset($_GET['pax_discount']))
		{
			if(floatval(sanitize_text_field($_GET['pax_discount'])) == ($a+1))
			{
				if(floatval($price_chart[$a][1]) > 0 && $price_chart[$a][1] != 0)
				{
					$each_child = floatval($each_child) + floatval($price_chart[$a][1]);
					
					if(dy_utilities::increase_by_hour() || dy_utilities::increase_by_day())
					{
						$each_child = $each_child * floatval(sanitize_text_field($_GET['booking_extra']));
					}	

					if(dynamicpackages_Validators::valid_coupon())
					{
						$each_child = $each_child * ((100 - floatval(self::get_coupon('discount'))) /100);
					}					
					
					$sum_children = $each_child*floatval(sanitize_text_field($_GET['pax_discount']));
					$sum = $sum + $sum_children;
				}			
			}			
		}		
	}
	
?>
<hr/>

	<div class="clearfix relative small text-right">
		<a class="pure-button rounded pure-button-bordered bottom-20" href="<?php the_permalink(); ?>"><i class="fas fa-chevron-left"></i>&nbsp;</a>
	</div>

<hr/>



<?php if(isset($_GET['booking_coupon'])): ?>
	<?php if($_GET['booking_coupon'] != ''): ?>
		<?php if(dynamicpackages_Validators::valid_coupon()): ?>
			<p class="minimal_success large"><?php echo esc_html(__('Coupon', 'dynamicpackages').' '.dy_utilities::get_coupon('code').' '.__('activated', 'dynamicpackages').'. '.dy_utilities::get_coupon('discount').'% '.__('discount already applied on rate. This coupon expires on', 'dynamicpackages').' '.date_i18n(get_option('date_format' ), strtotime(dy_utilities::get_coupon('expiration')))); ?></p>
		<?php else: ?> 
			<p class="minimal_alert"><?php echo esc_html(__('Invalid or expired coupon', 'dynamicpackages')); ?></p>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>

<div class="pure-g gutters">
	<div class="pure-u-1 pure-u-md-1-3">
		<div class="bottom-20">
			<?php dynamicpackages_Public::details(); ?>
			<div class="text-center bottom-10"><?php add_to_calendar::show(); ?></div>
			<div class="text-center"><?php echo whatsapp_button(__('Support via Whatsapp', 'dynamicpackages'), dynamicpackages_Public::description().' '.dy_money()); ?></div>
		</div>
	</div>
	<div class="pure-u-1 pure-u-md-2-3">
		<?php if(intval($total) > 0): ?>
			<table id="dynamic_table" class="text-center pure-table pure-table-bordered">
				<thead>
					<tr>
						<th><?php echo esc_html(__('Description', 'dynamicpackages')); ?></th>
						<th><?php echo esc_html(__('Unit Price', 'dynamicpackages')); ?></th>
						<th><?php echo esc_html(__('Subtotal', 'dynamicpackages')); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<?php if($sum_children == 0): ?>
						<td><?php echo esc_html(__('Participants', 'dynamicpackages')); ?>: <strong><?php echo esc_html(sanitize_text_field($_GET['pax_regular'])); ?></strong></td>
						<?php else: ?>
						<td><?php echo esc_html(__('Adults', 'dynamicpackages')); ?>: <strong><?php echo esc_html(sanitize_text_field($_GET['pax_regular'])); ?></strong></td>
						<?php endif; ?>
						<td><?php echo dy_money(dy_utilities::get_price_adults()); ?></td>
						<td><?php echo dy_money(dy_utilities::get_price_adults()*floatval($_GET['pax_regular'])); ?></td>
					</tr>
					
					<?php if(isset($_GET['pax_free'])): ?>
						<?php if(floatval(sanitize_text_field($_GET['pax_free'])) > 0 && $free != '' && intval($free) != 0): ?>
						<tr>
							<td><?php echo esc_html(__('Children', 'dynamicpackages')).' '.esc_html($start_free.' - '.$free).' '.esc_html(__('years old', 'dynamicpackages')); ?>: <strong><?php echo esc_html(sanitize_text_field($_GET['pax_free'])); ?></strong></td>
							<td>0.00</td>
							<td>0.00</td>
						</tr>
						<?php endif; ?>
					<?php endif; ?>
					
					<?php if($each_child > 0 && floatval(sanitize_text_field($_GET['pax_discount'])) > 0 &&$discount != '' && intval($discount) != 0): ?>
					<tr>
						<td><?php echo esc_html(__('Children', 'dynamicpackages')).' '.esc_html($start_discount.' - '.$discount).' '.esc_html(__('years old', 'dynamicpackages')); ?>: <strong><?php echo esc_html(sanitize_text_field($_GET['pax_discount'])); ?></strong></td>
						<td><?php echo dy_money(dy_utilities::get_price_discount()); ?></td>
						<td><?php echo dy_money(dy_utilities::get_price_discount()*floatval($_GET['pax_discount'])); ?></td>
					</tr>
					<?php endif; ?>
					
				<?php if(dynamicpackages_Public::get_included_list($post)) : ?>	
					<tr><td colspan="3"><p class="small text-left"><strong><?php echo __('Included', 'dynamicpackages'); ?>:</strong> <?php echo esc_html(dynamicpackages_Checkout::imp_taxo('package_included')); ?>.</p></td></tr>
				<?php endif; ?>
				<?php if(dynamicpackages_Public::get_not_included_list($post)) : ?>	
					<tr><td colspan="3"><p class="small text-left"><strong><?php echo __('Not Included', 'dynamicpackages'); ?>:</strong> <?php echo esc_html(dynamicpackages_Checkout::imp_taxo('package_not_included')); ?>.</p></td></tr>
				<?php endif; ?>				
					
				</tbody>

				<?php if(dynamicpackages_Taxmodifier::has_add_ons()): ?>
					<thead>
						<tr>
							<th colspan="2"><?php echo esc_html(__('Add-ons', 'dynamicpackages')); ?></th>
							<th><?php echo esc_html(__('Include?', 'dynamicpackages')); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php dynamicpackages_Gateways::checkout_items(); ?>
					</tbody>
				<?php endif; ?>
								
				<tfoot class="text-center strong">
					<?php if(get_option('dy_tax' )): ?>
						<?php $tax = get_option('dy_tax'); ?>
						<?php if(floatval($tax) > 0): ?>
							<tr>
							<td class="text-right" colspan="2"><?php echo esc_html(__('Tax', 'dynamicpackages')).' '.esc_html($tax); ?>%</td>
							<td><?php echo dy_money((dy_utilities::total()*(floatval($tax)/100)), 'dy_calc dy_calc_tax_amount'); ?></td>
							</tr>
						<?php endif; ?>
					<?php endif; ?>
					<tr>
						<td colspan="3">
							<?php if(dynamicpackages_Validators::valid_coupon()): ?>
								<s class="small light text-muted"><?php echo esc_html(__('Regular Price', 'dynamicpackages')); ?> <?php echo dy_money(dy_utilities::subtotal_regular(), 'dy_calc dy_calc_regular'); ?></span></s><br/>
							<?php endif; ?>
							<?php echo esc_html(__('Total', 'dynamicpackages')); ?> <?php echo dy_money(dy_sum_tax(dy_utilities::total()), 'dy_calc dy_calc_amount'); ?></span>
						</td>
					</tr>
					
					<?php if(dynamicpackages_Validators::has_deposit()): ?>
						<tr>
							<td colspan="3"><?php echo ($deposit_label) ?></td>
						</tr>
					<?php endif; ?>
					
				</tfoot>	
			</table>

			<div class="hidden" data-id="total"><?php echo esc_html(dy_utilities::total()); ?></div>
			<div class="hidden" data-id="participants"><?php echo esc_html($participants); ?></div>
			<div class="hidden" data-id="traveling-children"><?php echo esc_html($traveling_children); ?></div>

			<?php if($payment == 1 && !isset($_GET['quote']) && intval(package_field('package_auto_booking')) == 1): ?>
				<div class="text-muted large strong text-center bottom-20"><?php echo ($outstanding_label); ?></div>
			<?php endif; ?>
		<?php endif; ?>	
		
	</div>
</div>


<hr />

<?php dynamicpackages_Gateways::checkout_area(); ?>
