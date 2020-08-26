<?php

if(!function_exists('package_field'))
{
	function package_field($name)
	{
		return $name;
	}
}
if(!function_exists('get_locale'))
{
	function get_locale()
	{
		return '';
	}
}
if(!function_exists('get_permalink'))
{
	function get_permalink()
	{
		return '';
	}
}

$gate = array();
$gate['name'] = 'Paguelo Facil';
$gate['currency'] = array('USD');
$gate['url'] = 'https://secure.paguelofacil.com/rest/ccprocessing';
$gate['scripts'] = array();
$gate['form'] = 'creditcard_form.php';
$gate['content_type'] = 'x-www-form-urlencoded';
$gate['headers'] = array();
array_push($gate['headers'], 'Content-Type: application/x-www-form-urlencoded');
array_push($gate['headers'], 'Accept: */*');
$gate['auth'] = array();
$gate['post_fields'] = array('CCNum', 'ExpMonth', 'ExpYear', 'CVV2', 'fname', 'lastname', 'phone', 'email', 'country', 'city', 'address', 'description', 'total', 'departure_date', 'check_in_hour', 'booking_hour', 'duration', 'pax_num', 'channel', 'message', 'affiliate', 'affiliate_hash', 'affiliate_total');
$gate['custom_fields'] = array('CCLW');
$gate['hidden_fields'] = array();
array_push($gate['hidden_fields'], array('TxType' => 'SALE'));
$gate['approved'] = array(array("Status" => "Approved"));
$gate['declined'] = array(array("Status" => "Declined"));

if(!is_admin())
{
	if(dynamicpackages_Validators::validate_checkout())
	{
		$gate['checkout'] = array();
		$gate_checkout = array();
		$gate_checkout['SecretHash'] = 'return hash("sha512", $sanitized_fields["CCNum"].$sanitized_fields["CVV2"].$sanitized_fields["email"]);';
		$gate_checkout['CMTN'] = 'return $sanitized_fields["total"];';
		$gate_checkout['CDSC'] = 'return substr($sanitized_fields["description"], 0, 150);';
		$gate_checkout['CCNum'] = 'return $sanitized_fields["CCNum"];';
		$gate_checkout['ExpMonth'] = 'return sprintf("%02d", $sanitized_fields["ExpMonth"]);';
		$gate_checkout['ExpYear'] = 'return $sanitized_fields["ExpYear"];';
		$gate_checkout['CVV2'] = 'return $sanitized_fields["CVV2"];';
		$gate_checkout['Name'] = 'return substr($sanitized_fields["fname"], 0, 25);';
		$gate_checkout['LastName'] = 'return substr($sanitized_fields["lastname"], 0, 25);';
		$gate_checkout['Email'] = 'return $sanitized_fields["email"];';
		$gate_checkout['Tel'] = 'return $sanitized_fields["phone"];';
		$gate_checkout['Address'] = 'return $sanitized_fields["country"].", ".$sanitized_fields["city"]."  ".$sanitized_fields["address"];';
		array_push($gate['checkout'], $gate_checkout);
		$gate['webhook'] = array();
		$gate_webhook = array();
		$gate_webhook['CMTN'] = 'return $sanitized_fields["total"];';
		$gate_webhook['CDSC'] = 'return substr($sanitized_fields["description"], 0, 150);';
		$gate_webhook['Name'] = 'return substr($sanitized_fields["fname"], 0, 25);';
		$gate_webhook['LastName'] = 'return substr($sanitized_fields["lastname"], 0, 25);';
		$gate_webhook['Email'] = 'return $sanitized_fields["email"];';
		$gate_webhook['Tel'] = 'return $sanitized_fields["phone"];';
		$gate_webhook['Address'] = 'return $sanitized_fields["country"].", ".$sanitized_fields["city"]."  ".$sanitized_fields["address"];';
		$gate_webhook['departure_date'] = 'return $sanitized_fields["departure_date"];';
		$gate_webhook['check_in_hour'] = 'return $sanitized_fields["check_in_hour"];';
		$gate_webhook['booking_hour'] = 'return $sanitized_fields["booking_hour"];';
		$gate_webhook['duration'] = 'return $sanitized_fields["duration"];';
		$gate_webhook['pax_num'] = 'return $sanitized_fields["pax_num"];';
		$gate_webhook['country'] = 'return $sanitized_fields["country"];';
		$gate_webhook['channel'] = 'return $sanitized_fields["channel"];';
		$gate_webhook['message'] = 'return $sanitized_fields["message"];';
		$gate_webhook['lang'] = 'return substr(get_bloginfo("language"), 0, 2);';
		$gate_webhook['package_code'] = 'return package_field("package_trip_code");';
		$gate_webhook['package_url'] = 'return get_permalink();';
		$gate_webhook['provider_name'] = 'return package_field("package_provider_name");';
		$gate_webhook['provider_email'] = 'return package_field("package_provider_email");';
		$gate_webhook['provider_tel'] = 'return package_field("package_provider_tel");';
		$gate_webhook['provider_mobile'] = 'return package_field("package_provider_mobile");';
		$gate_webhook['departure_address'] = 'return package_field("package_departure_address");';
		
		//affiliate
		$gate_webhook['affiliate'] = 'return $sanitized_fields["affiliate"];';
		$gate_webhook['affiliate_name'] = 'return dynamicpackages_Affiliates::get_affiliate("name");';
		$gate_webhook['affiliate_email'] = 'return dynamicpackages_Affiliates::get_affiliate("email");';
		array_push($gate['webhook'], $gate_webhook);
	}	
}

?>