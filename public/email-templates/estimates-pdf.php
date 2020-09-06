<?php


$date = sanitize_text_field($_POST['departure_format_date']);
$currency_symbol = dy_utilities::currency_symbol();
$calculate_total = ($_POST['amount'] > dy_utilities::total()) ? $_POST['amount'] : dy_utilities::total();
$total = apply_filters('dy_email_total', $calculate_total);
$company_name = get_bloginfo('name');
$company_phone = get_option('dy_phone');
$company_email = get_option('dy_email');
$company_contact = ($company_phone) ?  $company_phone . ' / ' . $company_email : $company_email;
$company_address = get_option('dy_address');
$company_tax_id = get_option('dy_tax_id');
$label_doc = apply_filters('dy_email_label_doc', __('Estimate', 'dynamicpackages'));
$label_client = __('Client', 'dynamicpackages');
$client_name = sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['lastname']);
$label_item = __('Service', 'dynamicpackages');
$label_total = __('Total', 'dynamicpackages');
$label_subtotal = __('Subtotal', 'dynamicpackages');
$description = sanitize_text_field($_POST['description']);
$included = sanitize_text_field($_POST['package_included']);
$label_included = __('Included', 'dynamicpackages');
$not_included = sanitize_text_field($_POST['package_not_included']);
$label_not_included = __('Not Included', 'dynamicpackages');
$notes_content = (dy_Gateways::join_gateways()) ? __('We accept', 'dynamicpackages') .' '. dy_Gateways::join_gateways() : null;
$notes = apply_filters('dy_email_notes', $notes_content);
$label_notes = ($notes) ? apply_filters('dy_email_label_notes', __('Notes', 'dynamicpackages')) : null;
$footer = $company_address;

$totals_area = apply_filters('dy_totals_area', '<strong style="color: #666666">'.$label_total.'</strong><br/>'.$currency_symbol.$total);


$email_pdf = <<<EOT
	<style type="text/css">
	<!--
	table { vertical-align: top; }
	tr { vertical-align: top; }
	td { vertical-align: top; padding: 12pt 8pt; line-height: 1.5;}
	-->
	</style>
	<page backcolor="#ffffff" style="font-size: 12pt;" backtop="10mm" backbottom="10mm" backleft="10mm" backright="10mm">
		<bookmark title="${label_doc}" level="0" ></bookmark>
		
		<table style="width: 100%; border: 0;" cellspacing="0" cellpadding="0">
			<tr>
				<td style="width: 50%;">
					<div>
						<h1 style="margin: 0; padding: 0; font-size: 20pt;">${company_name}</h1>
						<div style="color: #666666;">${company_tax_id}</div>			
					</div>
				</td>
				<td style="width: 50%;">
					<div style="text-align: right;">
						<strong style="color: #666666;">${label_doc}</strong>
						<br>
						${date}
						<br>
						<br>
						<strong style="color: #666666;">${label_client}</strong>
						<br>
						${client_name}						
					</div>
				</td>
			</tr>			
		</table>
		
		<br>
		<br>
		<br>
		<br>
		
		<table style="width: 100%; border: 0;" cellspacing="0" cellpadding="0">
			<tr>
				<td style="width: 70%; border-bottom: 1pt solid #cccccc;">
					<strong style="color: #666666;">${label_item}</strong>
				</td>
				<td style="width: 30%; border-bottom: 1pt solid #cccccc;">
					<div style="text-align: right;">
						<strong style="color: #666666;">${label_subtotal}</strong>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width: 70%; border-bottom: 1pt solid #cccccc;">
					${description}
				</td>
				<td style="width: 30%;">
					<div style="text-align: right;">${currency_symbol}${total}</div>
				</td>
			</tr>
			<tr>
				<td style="width: 70%; border-bottom: 1pt solid #cccccc;">
					<strong style="color: #666666;">${label_included}:</strong> ${included}
				</td>
				<td style="width: 30%;">
					<div style="text-align: right;"></div>
				</td>
			</tr>
			<tr>
				<td style="width: 70%;">
					<strong style="color: #666666;">${label_not_included}:</strong> ${not_included}
				</td>
				<td style="width: 30%;"></td>
			</tr>
			<tr>
				<td style="width: 70%; border-top: 1pt solid #cccccc;"></td>
				<td style="width: 30%; border-top: 1pt solid #cccccc;">
					<div style="text-align: right; line-height: 1.5;">
						${totals_area}
					</div>
				</td>
			</tr>

			<tr>
				<td style="width: 70%;">
					<strong style="color: #666666;">${label_notes}</strong>
					<br>
					${notes}			
				</td>
				<td style="width: 30%;"></td>
			</tr>
			
		</table>


		<page_footer>
			<div style="line-height: 1.5; text-align: center;">
				${company_contact}
				<br>
				${footer}				
			</div>
		</page_footer>
		
	</page>
EOT;

?>