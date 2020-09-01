<?php


$currency_symbol = dy_utilities::currency_symbol();
$total = dy_utilities::total();
$company_name = get_bloginfo('name');
$company_phone = get_option('dy_phone');
$company_email = get_option('dy_email');
$company_contact = ($company_phone) ?  $company_phone . ' / ' . $company_email : $company_email;
$company_address = get_option('dy_address');
$company_tax_id = get_option('dy_tax_id');
$label_estimate = __('Estimate', 'dynamicpackages');
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
$accept = __('We accept', 'dynamicpackages');
$all_gateways = dy_Gateways::join_gateways();
$notes = (dy_Gateways::join_gateways()) ? $accept . ' ' . $all_gateways : null;
$footer = $company_address;

$email_pdf = <<<EOT
	<page orientation="P" format="A4" backtop="7mm" backbottom="7mm" backleft="10mm" backright="10mm" style="font: arial;">
		<table style="width: 99%; border: none;" cellspacing="4mm" cellpadding="0">
			<tr class="top">
				<td colspan="2">
					<div>
						<div>
							<h1>${company_name}</h1>
							<div>${company_tax_id}</div>					
						</div>
						<div>
							<div>${label_estimate}</div>
						</div>					
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div>
						<div>${label_client}</div>
						<br/> ${client_name}					
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<b>${label_item}</b>
				</td>
				<td>
					<b>${label_subtotal}</b>
				</td>
			</tr>
			
			<tr>
				<td>
					<div>
						<div>${description}</div>
						<div>${label_included}: ${included}</div>
						<div>${label_not_included}: ${not_included}</div>						
					</div>
				</td>
				<td>
					<div>
						<div>${currency_symbol}${total}</div>
					</div>
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<div><b>${label_total}</b><br/>${currency_symbol}${total}</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div>${notes}.</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div>${company_contact}</div>
					<br/>
					<div>${footer}</div>
				</td>
			</tr>          
		</table>
	</page>
EOT;

?>