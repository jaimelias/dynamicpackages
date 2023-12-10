<?php

$post_id = (isset($_POST['post_id'])) ? intval($_POST['post_id']) : 0;
$today = dy_utilities::format_date(strtotime('today UTC'));
$label_doc = apply_filters('dy_email_label_doc', __('Estimate', 'dynamicpackages'));
$greeting = apply_filters('dy_email_greeting', sprintf(__('Hello %s,', 'dynamicpackages'), sanitize_text_field($_POST['first_name'])));
$intro = apply_filters('dy_email_intro', __('Thank You for Your Request', 'dynamicpackages'). '!');
$message = apply_filters('dy_email_message', '<p>' . sprintf(__('Please find a detailed copy of your %s this email. Remember to check our Terms & Conditions (attached) before booking.', 'dynamicpackages'), $label_doc) . '</p>');
$confirmation_message = apply_filters('dy_confirmation_message', null);
$currency_symbol = currency_symbol();
$total = apply_filters('dy_email_total', money(dy_utilities::total()));
$company_name = get_bloginfo('name');
$company_phone = get_option('dy_phone');
$company_email = sanitize_email(get_option('dy_email'));
$company_contact = ($company_phone) ?  $company_phone . ' / ' . $company_email : $company_email;
$company_address = get_option('dy_address');
$company_tax_id = get_option('dy_tax_id');
$label_client = __('Client', 'dynamicpackages');
$client_name = sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['lastname']);
$client_email = sanitize_email($_POST['email']);
$client_phone = sanitize_text_field($_POST['country_calling_code']).sanitize_text_field($_POST['phone']);
$label_item = __('Service', 'dynamicpackages');
$label_total = __('Total', 'dynamicpackages');
$label_subtotal = __('Subtotal', 'dynamicpackages');
$description = apply_filters('dy_description', null);
$included = sanitize_text_field($_POST['package_included']);
$label_included = __('Included', 'dynamicpackages');
$not_included = sanitize_text_field($_POST['package_not_included']);
$label_not_included = __('Not Included', 'dynamicpackages');
$join_gateways = apply_filters('dy_join_gateways', null);
$details = '<strong style="color: #666666">'.esc_html(__('Itinerary', 'dynamicpackages')).':</strong><br/>' . apply_filters('dy_details', null);
$notes_content = ($join_gateways && $_POST['dy_request'] === 'estimate_request') ? __('We accept', 'dynamicpackages') .' '. $join_gateways . '.<br/><br/>' : null;
$notes = apply_filters('dy_email_notes', $notes_content);
$label_notes = ($notes) ? apply_filters('dy_email_label_notes', __('Notes', 'dynamicpackages')) : null;
$footer = $company_address;
$whatsapp_url = 'https://wa.me/' . get_option('dy_whatsapp') . '?text=' . urlencode($description);
$whatsapp = (get_option('dy_whatsapp')) ? '<a style="border: 16px solid #25d366; text-align: center; background-color: #25d366; color: #fff; font-size: 18px; line-height: 18px; display: block; width: 100%; box-sizing: border-box; text-decoration: none; font-weight: 900;" href="'.esc_url($whatsapp_url).'">'.__('Whatsapp Advisory', 'dynamicpackages').'</a>' : null;
$action_button = apply_filters('dy_email_action_button', $whatsapp);
$totals_area = apply_filters('dy_totals_area', '<strong style="color: #666666">'.$label_total.'</strong><br/>'.$currency_symbol.$total);
$add_ons = apply_filters('dy_included_add_ons_list', null);

$label_show_package = esc_html(__('Show Package', 'dynamicpackages'));
$package_url = get_the_permalink($post_id);

$email_template = <<<EOT
<!DOCTYPE html>
<html>
	<head>
		<title>{$company_name}</title>
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<style type="text/css">
			@media (max-width: 600px) {
				.sm-hide
				{
					display: none;
				}
				.doc_box {
					font-size: 14px;
				}
				.doc_box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}
				.doc_box table tr.information table td {
					width: 100%;
					display: block;
					text-align: center;
				}
			}
			body, table, td, a
			{
				-ms-text-size-adjust: 100%;
				-webkit-text-size-adjust: 100%;
			}

			table, td 
			{
				mso-table-rspace: 0pt;
				mso-table-lspace: 0pt;
			}
			img {
				-ms-interpolation-mode: bicubic;
			}
			a[x-apple-data-detectors] 
			{
				font-family: inherit !important;
				font-size: inherit !important;
				font-weight: inherit !important;
				line-height: inherit !important;
				color: inherit !important;
				text-decoration: none !important;
			}
			body 
			{
				width: 100% !important;
				height: 100% !important;
				padding: 0 !important;
				margin: 0 !important;
			}
			table {
				border-collapse: collapse !important;
			}
			img {
				height: auto;
				line-height: 100%;
				text-decoration: none;
				border: 0;
				outline: none;
			}			
		</style>
	</head>

	<body style="font-family: Arial, sans-serif; line-height: 1.5; font-size: 14px;">
		<div style="max-width: 800px; width: 100%; margin: 0 auto 0 auto;">
			<div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">{$description}</div>
		
			<div style="margin: 20px 0 40px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
				<p>{$greeting}</p>
				<p>{$intro}</p>
				<div>{$message}</div>
				<div>{$confirmation_message}</div>
			</div>
		
			<div class="doc_box" style="margin-bottom: 40px; padding: 20px; border: 1px solid #eee; box-sizing: border-box">
				<table cellpadding="0" cellspacing="0" style="width: 100%">
					<tr class="top">
						<td colspan="2" style="padding: 5px;vertical-align: top">
							<table style="width: 100%; line-height: inherit; text-align: left">
								<tr>
									<td class="title" style="padding: 0;vertical-align: top; padding: 5px 5px 20px 5px">
										<h1 style="font-size: 25px;line-height: 25px; padding: 0; margin: 0">{$company_name}</h1>
										<small style="color: #666666">{$company_tax_id}</small>
									</td>
									<td style="padding: 0;vertical-align: top;text-align: right;padding: 5px 5px 20px 5px">
										<strong style="color: #666666">{$label_doc}</strong>
										<br/>{$today}
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr class="information">
						<td colspan="2" style="padding: 5px;vertical-align: top; text-align: right;">
							<strong style="color: #666666">{$label_client}</strong>
							<br/> {$client_name}
							<br/>+{$client_phone}
							<br />{$client_email}
							<br/>
							<br/>
						</td>
					</tr>
					<tr>
						<td style="padding: 5px; vertical-align: top; border-bottom: 1px solid #dddddd;">
							<strong style="color:#666666;">{$label_item}</strong>
						</td>
						<td style="width: 100px; padding: 5px; vertical-align: top; border-bottom: 1px solid #dddddd; text-align: right;">
							<strong style="color:#666666;">{$label_subtotal}</strong>
						</td>
					</tr>
					
					<tr>
						<td style="padding: 5px; vertical-align: top;">
							{$description}
							<br>
							<strong><a href="{$package_url}">{$label_show_package} &#128279;</a></strong>
							<hr height="1" style="height:1px; border:0 none; color: #eeeeee; background-color: #eeeeee;">
							{$details}
							{$add_ons}
							<hr height="1" style="height:1px; border:0 none; color: #eeeeee; background-color: #eeeeee;">
							<strong style="color:#666666;">{$label_included}:</strong> {$included}
							<hr height="1" style="height:1px; border:0 none; color: #eeeeee; background-color: #eeeeee;">
							<strong style="color:#666666;">{$label_not_included}:</strong> {$not_included}
						</td>
						<td style="width: 100px; padding: 5px;vertical-align: top; text-align: right; ">
							{$currency_symbol}{$total}
						</td>
					</tr>			
					
					<tr>
						<td style="padding: 5px; vertical-align: top"></td>
						<td style="width: 100px; padding: 5px; vertical-align: top; text-align: right; line-height: 2;">
							{$totals_area}
						</td>
					</tr>
					
					<tr>
						<td colspan="2" style="padding: 5px; vertical-align: top;">
							<hr height="1" style="height:1px; border:0 none; color: #eeeeee; background-color: #eeeeee;">
							<strong style="color: #666666;">{$label_notes}</strong>
							<br/>
							{$notes}
						</td>
					</tr>
					<tr>
						<td colspan="2" style="padding: 5px; vertical-align: top; text-align: center;">
							<small style="color: #666666">{$company_contact}</small>
							<br/>
							<small style="color: #666666;">{$footer}</small>
						</td>
					</tr>          
				</table>
			</div>
			
			{$action_button}

		</div>		
	</body>
</html>
EOT;

?>