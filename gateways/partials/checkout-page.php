<?php

	$add_to_calendar = apply_filters('dy_add_to_calendar', null);
	$discount = 0;
	$pax_discount = 0;
	$free = 0;
	$pax_free = 0;
	$start_free = 0;
	$start_discount = 0;
	$price_regular = dy_utilities::get_price_regular();
	$price_discount = dy_utilities::get_price_discount();
	$payment = 0;
	$deposit = 25;
	$total = dy_utilities::total();
	$payment_amount = $total;
	$pax_regular = intval($_GET['pax_regular']);
	$participants = $pax_regular;
	$deposit_label = '';
	
	if(package_field('package_free') > 0 && isset($_GET['pax_free']))
	{
		$pax_free = intval(sanitize_text_field($_GET['pax_free']));
		$free = package_field('package_free');
	}	
	if(package_field('package_discount') > 0 && isset($_GET['pax_discount']))
	{
		$pax_discount = intval(sanitize_text_field($_GET['pax_discount']));
		$discount = package_field('package_discount');

		if(package_field('package_free') > 0)
		{
			$start_discount = $free + 1;
		}
	}

	if($pax_free > 0)
	{
		$participants = $participants + intval(sanitize_text_field($_GET['pax_free']));
	}

	if($pax_discount > 0)
	{
		$participants = $participants + $pax_discount;
	}
	
	if(package_field('package_payment' ) == 1)
	{
		$payment = 1;
		$deposit = dy_utilities::get_deposit();
		$payment_amount = dy_utilities::total()*($deposit*0.01);
		$outstanding_amount = floatval($total)-$payment_amount;
		$outstanding_label = esc_html(__('Outstanding Balance', 'dynamicpackages')).' '.dy_money($outstanding_amount, 'dy_calc dy_calc_outstanding');
		$deposit_label = esc_html(__('Deposit', 'dynamicpackages')).' '.dy_money($payment_amount, 'dy_calc dy_calc_total').' ('.esc_html($deposit).'%)';
	}
	
?>
<hr/>

	<div class="clearfix relative small text-right">
		<a class="pure-button rounded pure-button-bordered bottom-20" href="<?php the_permalink(); ?>"><i class="fas fa-chevron-left"></i>&nbsp; <?php echo (esc_html__('Go back', 'dynamicpackages')); ?></a>
	</div>

<hr/>

<?php do_action('dy_coupon_confirmation'); ?>
<?php do_action('dy_invalid_min_duration'); ?>

<div class="pure-g gutters">
	<div class="pure-u-1 pure-u-md-1-3">
		<div class="bottom-20">
			<?php echo apply_filters('dy_details', null); ?>
			<?php if(isset($add_to_calendar)) : ?>
				<div class="text-center bottom-10"><?php echo $add_to_calendar; ?></div>
			<?php endif; ?>
			<div class="text-center"><?php echo whatsapp_button(__('Support via Whatsapp', 'dynamicpackages'), apply_filters('dy_description', null).' '.dy_money()); ?></div>
		</div>
	</div>
	<div class="pure-u-1 pure-u-md-2-3">
		<?php if(intval($total) > 0): ?>
			<table id="dynamic_table" class="text-center pure-table pure-table-bordered">
				<thead>
					<tr>
						<th><?php echo (esc_html__('Description', 'dynamicpackages')); ?></th>
						<th><?php echo (esc_html__('Unit Price', 'dynamicpackages')); ?></th>
						<th><?php echo (esc_html__('Subtotal', 'dynamicpackages')); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<?php if($price_discount == 0): ?>
						<td><?php echo (esc_html__('Participants', 'dynamicpackages')); ?>: <strong><?php esc_html_e(sanitize_text_field($_GET['pax_regular'])); ?></strong></td>
						<?php else: ?>
						<td><?php echo (esc_html__('Adults', 'dynamicpackages')); ?>: <strong><?php esc_html_e(sanitize_text_field($_GET['pax_regular'])); ?></strong></td>
						<?php endif; ?>
						<td><?php echo dy_money($price_regular); ?></td>
						<td><?php echo dy_money($price_regular * $pax_regular); ?></td>
					</tr>
					
					<?php if($free > 0 && $pax_free > 0): ?>
					<tr>
						<td><?php echo esc_html(__('Children', 'dynamicpackages')).' '.esc_html($start_free.' - '.$free).' '.esc_html(__('years old', 'dynamicpackages')); ?>: <strong><?php esc_html_e(sanitize_text_field($_GET['pax_free'])); ?></strong></td>
						<td>0.00</td>
						<td>0.00</td>
					</tr>
					<?php endif; ?>
					
					<?php if($discount > 0 && $pax_discount > 0): ?>
					<tr>
						<td><?php echo (esc_html__('Children', 'dynamicpackages')).' '.esc_html($start_discount.' - '.$discount).' '.esc_html(__('years old', 'dynamicpackages')); ?>: <strong><?php esc_html_e(sanitize_text_field($_GET['pax_discount'])); ?></strong></td>
						<td><?php echo dy_money($price_discount); ?></td>
						<td><?php echo dy_money($price_discount * $pax_discount); ?></td>
					</tr>
					<?php endif; ?>
					
					<tr><td colspan="3"><p class="small text-left"><strong><?php echo __('Included', 'dynamicpackages'); ?>:</strong> <?php esc_html_e(dy_utilities::implode_taxo_names('package_included')); ?></p></td></tr>
					<tr><td colspan="3"><p class="small text-left"><strong><?php echo __('Not Included', 'dynamicpackages'); ?>:</strong> <?php esc_html_e(dy_utilities::implode_taxo_names('package_not_included')); ?></p></td></tr>
					
				</tbody>

				<?php if(apply_filters('dy_has_add_ons', null)): ?>
					<thead>
						<tr>
							<th colspan="2"><?php echo (esc_html__('Add-ons', 'dynamicpackages')); ?></th>
							<th><?php echo (esc_html__('Include?', 'dynamicpackages')); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php do_action('dy_checkout_items'); ?>
					</tbody>
				<?php endif; ?>
								
				<tfoot class="text-center strong">
					<tr>
						<td colspan="3">
							<?php if(dy_validators::validate_coupon()): ?>
								<s class="small light text-muted"><?php echo (esc_html__('Regular Price', 'dynamicpackages')); ?> <?php echo dy_money(dy_utilities::total('regular'), 'dy_calc dy_calc_regular'); ?></span></s><br/>
							<?php endif; ?>
							<?php echo (esc_html__('Total', 'dynamicpackages')); ?> <?php echo dy_money(dy_utilities::total(), 'dy_calc dy_calc_amount'); ?></span>
						</td>
					</tr>
					
					<?php if(dy_validators::has_deposit()): ?>
						<tr>
							<td colspan="3"><?php echo ($deposit_label) ?></td>
						</tr>
					<?php endif; ?>
					
				</tfoot>	
			</table>

			<?php if($payment == 1 && intval(package_field('package_auto_booking')) == 1): ?>
				<div class="text-muted large strong text-center bottom-20"><?php echo ($outstanding_label); ?></div>
			<?php endif; ?>
		<?php endif; ?>	
		
		<hr />

		<?php do_action('dy_checkout_area'); ?>		
		
	</div>
</div>


