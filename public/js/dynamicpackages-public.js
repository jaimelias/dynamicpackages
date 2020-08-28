
jQuery(function()
{
	booking_datepicker();
	booking_hourpicker();
	booking_quote();
	booking_submit();
	booking_populate(jQuery('#dynamic_form'));
	booking_if_country();
	booking_coupon();
	
	if(typeof dy_url !== typeof undefined)
	{
		dy_country_dropdown();
	}
	
	if(typeof checkout_vars !== typeof undefined)
	{
		booking_calc();
	}	
	
});


function booking_args()
{
	var output = {};
	var args  = checkout_vars();
	var add_ons = args;
	var pax = parseFloat(args.pax_num);
	var amount = parseFloat(args.amount);
	var regular_amount = parseFloat(args.regular_amount);
	var total = parseFloat(args.total);
	var description = args.description;
	var deposit = 0;
	var payment_amount = 0;
	var add_ons_id = [];
	var tax = 0;
	var tax_amount = parseFloat(args.tax_amount);
	var new_args = {};
	var outstanding = 0;
	
	if(args.hasOwnProperty('deposit'))
	{
		if(args.deposit > 0)
		{
			deposit = parseFloat(args.deposit) / 100;
		}
	}
	if(args.hasOwnProperty('tax'))
	{
		if(args.tax > 0)
		{
			tax = parseFloat(args.tax) / 100;
		}
	}
	
	jQuery('#dynamic_table').find('select.add_ons').each(function(){
		var field = jQuery(this);
		
		if(jQuery(field).val() == 1)
		{
			add_ons_id.push(parseFloat(jQuery(field).attr('data-id')));
		}
	});
	
	var add_ons_description = add_ons_id.map(function(id){
		var output = [];
		var args = checkout_vars();
		var add_ons = args;
		
		if(add_ons.hasOwnProperty('add_ons'))
		{
			add_ons = add_ons.add_ons;
		}
		
		for(var x = 0; x < add_ons.length; x++)
		{
			if(id == add_ons[x].id)
			{
				output.push(add_ons[x].name);
			}
		}
		
		return output;
	});
	
	if(add_ons_id.length)
	{
		description = description + ' | ' + add_ons_description.join(', ');
	}

	var add_ons_price = add_ons_id.map(function(id){
		var output = 0;
		var args = checkout_vars();
		var add_ons = args;
		
		if(add_ons.hasOwnProperty('add_ons'))
		{
			add_ons = add_ons.add_ons;
		}
		
		for(var x = 0; x < add_ons.length; x++)
		{
			if(id == add_ons[x].id)
			{
				output = parseFloat(add_ons[x].price) * parseFloat(args.pax_num);
			}
		}
		return output;
	});
	
	add_ons_price = add_ons_price.reduce((a, b) => a + b, 0);
	
	if(tax > 0)
	{
		tax_amount = tax_amount + (add_ons_price * tax);
		add_ons_price = add_ons_price + (add_ons_price * tax);
	}
	
	
	if(add_ons_price > 0)
	{
		regular_amount = regular_amount + add_ons_price;
		amount = amount + add_ons_price;
		description = args.duration + ' - ' + args.title + ' (' + args.departure_date + ' ';
		
		if(args.hasOwnProperty('booking_hour'))
		{
			if(args.booking_hour != '')
			{
				description += '@ ' + args.booking_hour;
			}
		}
		
		description += '): ';
		var pax_label = 'pax';
		var discount_label = 'discount'
		var free_label = 'free';
		var deposit_label = 'deposit';
		var total_label = 'total';
		var outstanding_label = 'outstanding balance';
		
		if(args.hasOwnProperty('pax_num') && args.hasOwnProperty('pax_regular'))
		{
			if(args.pax_num > 0 && args.pax_regular > 0)
			{
				description += args.pax_regular + ' ';
				
				if(args.hasOwnProperty('pax_discount') || args.hasOwnProperty('pax_free'))
				{
					if(args.pax_discount > 0 || args.pax_free > 0)
					{
						pax_label = 'adults';
					}
				}
				description += pax_label + ' ';

				if(args.hasOwnProperty('pax_discount'))
				{
					if(args.pax_discount > 0)
					{
						description += ', ' + args.pax_discount + ' ' + discount_label;
					}
				}
				
				if(args.hasOwnProperty('pax_free'))
				{
					if(args.pax_free > 0)
					{
						description += ', ' + args.pax_free + ' ' + free_label;
					}
				}
				
			}
		}
		description += ' - ';
		
		if(args.deposit == 0)
		{
			description += total_label + ' ' + amount.toFixed(2) + '.';
		}
		else
		{
			description += deposit_label + ' ' + (amount * deposit).toFixed(2) + ', ' + outstanding_label + ' ' + (amount - (amount * deposit)).toFixed(2) + '.';
		}
		
	}
	
	payment_amount = amount;
	
	if(deposit != 0)
	{
		payment_amount = payment_amount * deposit;
		outstanding = amount - payment_amount;
	}
	
	if(payment_amount == amount)
	{
		outstanding = amount;
	}
	
	new_args.tax_amount = tax_amount.toFixed(2);
	new_args.outstanding = outstanding.toFixed(2);
	new_args.total = payment_amount.toFixed(2);
	new_args.amount = amount.toFixed(2);
	new_args.regular_amount = regular_amount.toFixed(2);
	new_args.description = description;	
	
	for(k in new_args)
	{
		for(k2 in args)
		{
			if(k == k2)
			{
				args[k2] = new_args[k];
			}
			
			output[k2] = args[k2];
		}
	}

	return output;
}


function booking_calc()
{
	jQuery(document).on('change', '#dynamic_table select.add_ons', function(){
		
		var args = booking_args();
		
		jQuery('input[name="total"]').val(args.total);
				
		jQuery('.dy_calc').each(function(){
			
			if(jQuery(this).hasClass('dy_calc_amount'))
			{
				jQuery(this).text(args.amount);
			}
			if(jQuery(this).hasClass('dy_calc_regular'))
			{
				jQuery(this).text(args.regular_amount);
			}			
			if(jQuery(this).hasClass('dy_calc_total'))
			{
				jQuery(this).text(args.total);
			}
			if(jQuery(this).hasClass('dy_calc_outstanding'))
			{
				jQuery(this).text(args.outstanding);
			}
			if(jQuery(this).hasClass('dy_calc_tax_amount'))
			{
				jQuery(this).text(args.tax_amount);
			}				
		});		
	});
}

function booking_if_country()
{
	jQuery(window).on('load', function(){
		if(jQuery('.dy_show_country').length)
		{
			if(dy_ipgeolocation() != '')
			{
				if(getCookie('country_code') == '')
				{
					jQuery.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey='+dy_ipgeolocation(), function(data){
						if(data.hasOwnProperty('country_code2'))
						{
							jQuery('.dy_show_country_' + data.country_code2).closest('.dy_coupon').removeClass('hidden');
							setCookie('country_code', data.country_code2, 30);
						}
					});				
				}
				else
				{
					jQuery('.dy_show_country_' + getCookie('country_code')).closest('.dy_coupon').removeClass('hidden');
				}
			}
		}		
	});
}

function booking_populate(form)
{
	var input = jQuery(form).find('input');
	
	jQuery(input).each(function(){
		
		var field = jQuery(this);
		var name = jQuery(field).attr('name');
		
		if (typeof(Storage) !== 'undefined')
		{
			if (typeof name !== typeof undefined && name !== false)
			{
				if(sessionStorage.getItem(name) != null && sessionStorage.getItem(name) != '')
				{
					var item = sessionStorage.getItem(name);
					jQuery(field).val(item);
				}
			}
		}
	});
}

function dy_recaptcha()
{	
	var args = {};
	args.sitekey = dy_recaptcha_sitekey();
	args.isolated = true;
	args.badge = 'inline';
	var checkout_widget;
	var quote_widget;
	
	if(jQuery('#dy_checkout_form').length)
	{
		args.callback = function(token){
			return new Promise(function(resolve, reject) { 
				if(checkout_paguelo(token) == false)
				{
					grecaptcha.reset(checkout_widget);
				}
				resolve();
			});
		};
		checkout_widget = grecaptcha.render('confirm_checkout', args);
	}
	if(jQuery('#dynamic_form').length)
	{
		args.callback = function(token){
			return new Promise(function(resolve, reject) { 
				if(dy_request_form(token) == false)
				{
					grecaptcha.reset(quote_widget);
				}
				resolve();
			});			
		};
		quote_widget = grecaptcha.render('dy_submit_form', args);
	}
}

function dy_request_form(token)
{
	var exclude = ['country_code3', 'is_eu', 'country_tld', 'languages', 'country_flag', 'geoname_id', 'time_zone_current_time', 'time_zone_dst_savings', 'time_zone_is_dst'];
	var form = jQuery('#dynamic_form');
	var exclude_storage = ['dy_recaptcha', 'total', 'dy_request'];
	
	jQuery.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey='+dy_ipgeolocation(), function(data) {
		var obj = {};

		for(var k in data)
		{
		  if(typeof data[k] !== 'object')
		  {
			  if(exclude.indexOf(k) == -1)
			  {
				obj[k] = data[k];
				jQuery(form).find('input.'+k).val(data[k]);
			  }
		  }
		  else
		  {
			  for(var sk in data[k])
			  {
				  if(exclude.indexOf(k+'_'+sk) == -1)
				  {
					obj[k+'_'+sk] = data[k][sk];
					jQuery(form).find('input.'+k+'_'+sk).val(data[k][sk]);
				  }	   
			  }
		  }
		}		
	}).always(function(){
		
		var invalids = 0;
		
		jQuery(form).find('input').each(function(){
			
			var field = jQuery(this);
			var name = jQuery(field).attr('name');
			
			if(jQuery(this).hasClass('required') && jQuery(this).val() == '')
			{
				invalids++;
				jQuery(this).addClass('invalid_field');
				console.log(jQuery(this).attr('name')+ ' invalid');
			}
			else
			{
				jQuery(this).removeClass('invalid_field');
				
				if (typeof name !== typeof undefined && name !== false)
				{
					if(!exclude_storage.includes(name))
					{
						sessionStorage.setItem(name, jQuery(field).val());
					}
				}				
			}
		});
		
		if(invalids == 0)
		{
			dy_populate_form(form);
			jQuery(form).find('input[name="dy_recaptcha"]').val(token);
			//console.log(jQuery(form).serializeArray());
			//console.log(token); 

			//facebook pixel
			if(typeof fbq !== typeof undefined)
			{
				if(jQuery(form).find('input[name="dy_request"]').val() == 'quote')
				{
					console.log('Lead');
					fbq('track', 'Lead');
				}
				else
				{
					console.log('Purchase');
					var total = parseFloat(jQuery(form).find('input[name="total"]').val());
					fbq('track', 'Purchase', {value: total, currency: 'USD'});
				}
			}
			
			//google analytics
			if(typeof ga !== typeof undefined)
			{
				var dy_vars = booking_args();
				var eventArgs = {};
				eventArgs.eventAction = 'Submit';
				eventArgs.eventLabel = dy_vars.title;
				
				if(jQuery(form).find('input[name="dy_request"]').val() == 'quote')
				{
					console.log('Lead');
					eventArgs.eventCategory = 'Lead';
				}
				else
				{
					console.log('Purchase');
					eventArgs.eventCategory = 'Purchase';
				}
				ga('send', 'event', eventArgs);	
			}			

			jQuery(form).submit();
		}	
	});
	return false;
}

function dy_populate_form(form)
{
	var checkout_obj = booking_args();
	
	for(var key in checkout_obj)
	{
		if(checkout_obj.hasOwnProperty(key))
		{
			if(typeof checkout_obj[key] == 'string' || Number.isInteger(checkout_obj[key]))
			{
				form.append(jQuery('<input>').attr({'type': 'hidden', 'name': key, 'value': checkout_obj[key]}));						
			}
		}
	}		
}

function dy_country_dropdown(pluginurl, htmllang)
{
	var pluginurl = dy_url();
	var htmllang = jQuery("html").attr("lang").slice(0, -3);
	
	jQuery(window).on('load', function (e) {
		
		if(jQuery('.countrylist').length > 0)
		{
			jQuery.getJSON( pluginurl + 'languages/countries/'+htmllang+'.json')
				.done(function(data) 
				{
					dy_country_options(data);
				})
				.fail(function()
				{
					jQuery.getJSON(pluginurl + 'languages/countries/en.json', function(data) {

						dy_country_options(data);
					});				
				});				
		}		
	});
}	

function dy_country_options(data)
{
	jQuery('.countrylist').each(function() {
		
		var field = jQuery(this);
		var name = jQuery(field).attr('name');
		
		for (var x = 0; x < data.length; x++) 
		{
			var this_option = jQuery('<option></option>').attr({'value': data[x][0]}).html(data[x][1]);
			
			if (typeof(Storage) !== 'undefined')
			{
				if (typeof name !== typeof undefined && name !== false)
				{
					if(sessionStorage.getItem(name) != null && sessionStorage.getItem(name) != '')
					{
						var item = sessionStorage.getItem(name);
						
						if(item ==  data[x][0])
						{
							jQuery(this_option).attr({'selected': 'selected'});
						}
					}
				}
			}
			
			jQuery(this).append(this_option);
		}
	});		
}	

function booking_datepicker()
{
	jQuery('body').append(jQuery('<div>').attr({'id': 'availability_calendar'}));
	
	jQuery('.booking_form').find('input[name="booking_date"]').each(function()
	{
		var field = jQuery(this);
		var d = new Date();
		
		jQuery.getJSON(dy_permalink()+'?json=disabled_dates&stamp='+d.getTime(), function(data){
			var args = {};
			args.container = '#availability_calendar';
			args.format = 'yyyy-mm-dd';
			args.disable = [];
			args.firstDay = 1;
			
			var json_parse = data;
			args.disable = json_parse.disable;
			args.min = json_parse.min;
			args.max = json_parse.max;
						
			if(jQuery(field).attr('type') == 'text')
			{
				jQuery(field).pickadate(args);
			}
			else if(jQuery(field).attr('type') == 'date')
			{
				jQuery(field).attr({'type': 'text'});
				jQuery(field).pickadate(args);
			}
			jQuery(field).removeAttr('disabled').attr({'placeholder': ''});
		});
	});			

}

function booking_hourpicker()
{
	jQuery(window).on('load', function (e) {
		if(typeof(booking_allowed_hours) == "function")
		{
			var allowed_hours = booking_allowed_hours();
			var args = {};
			
			if(allowed_hours.length > 1)
			{
				args.min = allowed_hours[0];
				args.max = allowed_hours[1];
			}
		}
		
		jQuery('.booking_form').find('input[name="booking_hour"]').each(function()
		{
			jQuery(this).pickatime(args);
		});			
	});
}
function booking_submit()
{	
	jQuery('.booking_form').submit(function(event){
		
		event.preventDefault();
		var form = jQuery(this);
		
		if(booking_validate(form) == true)
		{
			//facebook pixel
			if(jQuery(form).find('input[name="quote"]').length == 0)
			{
				//google analytics
				ga_click(form, 'Checkout');

				if(typeof fbq !== typeof undefined)
				{
					//facebook pixel
					fbq('track', 'AddToCart');		
				}
			}
			jQuery(form).unbind('submit').submit();
		}			
	});
}

function booking_quote()
{
	jQuery('.booking_form').find('.booking_quote').click(function(){
		
		var form = jQuery(this).closest('form');
		
		if(booking_validate(form) == true)
		{
			jQuery(form).find('input[name="quote"]').remove();
			var args = {};
			args.name = 'quote';
			args.type = 'hidden';
			args.value = true;
			jQuery(form).append(jQuery('<input />').attr(args));
			ga_click(form, 'Quote');
			jQuery(form).submit();				
		}
	});
}

function booking_validate(form)
{
	var invalids = 0;
				
	jQuery(form).find('input[type="text"]').each(function(){
		if(jQuery(this).val() == '' && jQuery(this).hasClass('required'))
		{
			jQuery(this).addClass('invalid_field');
			invalids++;
		}
		else
		{
			jQuery(this).removeClass('invalid_field');
		}
	});
		
	if(invalids == 0)
	{	

		var booking_date = jQuery(form).find('input[name="booking_date"]').val();
		var pax_num = parseInt(jQuery(form).find('select[name="pax_regular"]').val());
				
		if(jQuery(form).find('select[name="pax_free"]').length > 0)
		{
			pax_num = pax_num + parseInt(jQuery(form).find('select[name="pax_free"]').val());
		}
		if(jQuery(form).find('select[name="pax_discount"]').length > 0)
		{
			pax_num = pax_num + parseInt(jQuery(form).find('select[name="pax_discount"]').val());
		}

		console.log();
		var hash = (pax_num+booking_date);
		
		jQuery(form).find('input[name="hash"]').remove();
		var args = {};
		args.name = 'hash';
		args.type = 'hidden';
		args.value = sha512(hash);
		jQuery(form).append(jQuery('<input />').attr(args));
		return true;
	}	
	else
	{
		return false;
	}
}

function ga_click(form, event_category)
{
	if(typeof ga !== typeof undefined)
	{
		var booking_date = jQuery(form).find('input[name="booking_date"]');
		var pax_regular = jQuery(form).find('select[name="pax_regular"]');
		var departure = Date.parse(jQuery(booking_date).val());
		var today = new Date();
		today.setDate(today.getDate() - 2);
		today = Date.parse(today);
		var days_between = Math.round((departure-today)/(1000*60*60*24));
		var eventArgs = {};
		eventArgs.eventCategory = event_category;
		eventArgs.eventAction = jQuery('.entry-title').text();
		eventArgs.eventLabel = days_between+'/'+jQuery(booking_date).val()+'/'+jQuery(pax_regular).val();
		ga('send', 'event', eventArgs);
	}
	else
	{
		console.error('dynamicpackages: GA not defined');
	}	
}

function booking_coupon()
{
	var el = jQuery('#booking_coupon');
	
	jQuery(el).find('a').click(function(e){
		e.preventDefault();
		var input = jQuery(el).find('input[name="booking_coupon"]');
		jQuery(input).toggleClass('hidden');
		jQuery(input).focus();
	});	
}