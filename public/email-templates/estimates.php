<?php
$greeting = apply_filters('dy_email_greeting', sprintf(__('Hello %s,'), sanitize_text_field($_POST['first_name'])));
$intro = apply_filters('dy_email_intro', __('Thank you for your request!'));
$message = apply_filters('dy_email_message', '<p>' .__('Please check this estimate in detail and the Terms & Conditions (attached) before booking.', 'dynamicpackages') . '</p>');
$currency_symbol = dy_utilities::currency_symbol();
$total = apply_filters('dy_email_total', dy_utilities::total());
$company_name = get_bloginfo('name');
$company_phone = get_option('dy_phone');
$company_email = get_option('dy_email');
$company_contact = ($company_phone) ?  $company_phone . ' / ' . $company_email : $company_email;
$company_address = get_option('dy_address');
$company_tax_id = get_option('dy_tax_id');
$label_doc = apply_filters('dy_email_label_doc', __('Estimate', 'dynamicpackages'));
$label_client = __('Client', 'dynamicpackages');
$client_name = sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['lastname']);
$client_email = sanitize_text_field($_POST['email']);
$client_phone = sanitize_text_field($_POST['phone']);
$label_item = __('Service', 'dynamicpackages');
$label_total = __('Total', 'dynamicpackages');
$label_subtotal = __('Subtotal', 'dynamicpackages');
$description = sanitize_text_field($_POST['description']);
$included = sanitize_text_field($_POST['package_included']);
$label_included = __('Included', 'dynamicpackages');
$not_included = sanitize_text_field($_POST['package_not_included']);
$label_not_included = __('Not Included', 'dynamicpackages');
$notes = apply_filters('dy_email_notes', __('We accept', 'dynamicpackages') .' '. dy_Gateways::join_gateways());
$label_notes = ($notes) ? __('Notes', 'dynamicpackages') : null;
$footer = $company_address;
$label_whatsapp = (get_option('dy_whatsapp')) ? __('Feel free to contact us using Whatsapp:', 'dynamicpackages') : null;
$whatsapp_url = 'https://wa.me/' . get_option('dy_whatsapp') . '?text=' . urlencode($description);
$whatsapp = (get_option('dy_whatsapp')) ? '<a style="padding: 16px; text-align: center; background-color: #25d366; color: #fff; font-size: 18px; line-height: 18px; display: block; width: 100%; box-sizing: border-box; text-decoration: none; font-weight: 900;" href="'.esc_url($whatsapp_url).'">Whatsapp</a>' : null;

$email_template = <<<EOT
<!DOCTYPE html>
<html>
	<head>
		<title>${company_name}</title>
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<style type="text/css">
			@media only screen and (max-width: 600px) {
				.estimate-box {
					font-size: 14px;
				}
				.estimate-box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}
				.estimate-box table tr.information table td {
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

	<body style="font-family: Arial, sans-serif; color: #666666; line-height: 1.5; font-size: 16px;">
	
	
		<div style="max-width: 800px; margin: 20px auto 60px auto; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 40px;">
			<p>${greeting}</p>
			<p>${intro}</p>
			<div>${message}</div>
		</div>
	
		<div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">${description}</div>
	
		<div class="estimate-box" style="max-width: 800px;margin: 0 auto 20px auto;padding: 20px;border: 1px solid #eee; box-sizing: border-box">
			<table cellpadding="0" cellspacing="0" style="width: 100%">
				<tr class="top">
					<td colspan="2" style="padding: 5px;vertical-align: top">
						<table style="width: 100%; line-height: inherit; text-align: left">
							<tr>
								<td class="title" style="padding: 0;vertical-align: top; padding: 5px 5px 20px 5px">
									<h1 style="font-size: 25px;line-height: 25px; padding: 0; margin: 0">${company_name}</h1>
									<small style="color: #777777">${company_tax_id}</small>
								</td>
								<td style="padding: 0;vertical-align: top;text-align: right;padding: 5px 5px 20px 5px">
									<small style="color: #777777">${label_doc}</small>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr class="information">
					<td colspan="2" style="padding: 5px;vertical-align: top; text-align: right;">
						<small style="color: #777777">${label_client}</small>
						<br/> ${client_name}
						<br/>${client_phone}
						<br />${client_email}
						<br/>
						<br/>
					</td>
				</tr>
				<tr>
					<td style="padding: 5px; vertical-align: top; border-bottom: 1px solid #dddddd;">
						<strong style="color:#666666;">${label_item}</strong>
					</td>
					<td style="padding: 5px; vertical-align: top; border-bottom: 1px solid #dddddd; text-align: right;">
						<strong style="color:#666666;">${label_subtotal}</strong>
					</td>
				</tr>
				
				<tr>
					<td style="padding: 5px;vertical-align: top; border-bottom: solid 1px #eeeeee;">
						<span style="color:#666666;">${description}</span>
					</td>
					<td style="padding: 5px;vertical-align: top; text-align: right; ">
						<span style="color:#666666;">${currency_symbol}${total}</span>
					</td>
				</tr>
				
				<tr>
					<td style="padding: 5px;vertical-align: top; border-bottom: solid 1px #eeeeee;">
						<span style="color:#666666;">${label_included}: ${included}</span>
					</td>
					<td></td>
				</tr>
				
				<tr>
					<td style="padding: 5px;vertical-align: top; line-height: 2;">
						<span style="color:#666666;">${label_not_included}: ${not_included}</span>
					</td>
					<td></td>
				</tr>				
				
				<tr>
					<td style="padding: 5px; vertical-align: top"></td>
					<td style="padding: 5px; vertical-align: top; text-align: right; line-height: 2;">
						<span style="color: #666666"><strong>${label_total}</strong><br/>${currency_symbol}${total}</span>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="padding: 5px; vertical-align: top; border-bottom: solid 1px #eeeeee;">
						<strong style="color: #666666;">${label_notes}</strong>
						<br>
						<div style="line-height: 2;">${notes}</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="padding: 5px; vertical-align: top; text-align: center;">
						<small style="color: #666666">${company_contact}</small>
						<br/>
						<small style="color: #666666;">${footer}</small>
					</td>
				</tr>          
			</table>
		</div>
		<p style="text-align: center;">
			<small style="color: #666666">${label_whatsapp}</small>
		</p>
		<p style="max-width: 800px; margin: 0 auto;">${whatsapp}</p>
	</body>
</html>
EOT;

?>