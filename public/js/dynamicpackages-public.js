$(function()
{
	booking_datepicker();
	booking_hourpicker();
	booking_quote();
	booking_submit();
	booking_affiliate();
	booking_populate($('#dynamic_form'));
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
	
	$('#dynamic_table').find('select.add_ons').each(function(){
		var field = $(this);
		
		if($(field).val() == 1)
		{
			add_ons_id.push(parseFloat($(field).attr('data-id')));
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
	$(document).on('change', '#dynamic_table select.add_ons', function(){
		
		var args = booking_args();
		
		$('input[name="total"]').val(args.total);
				
		$('.dy_calc').each(function(){
			
			if($(this).hasClass('dy_calc_amount'))
			{
				$(this).text(args.amount);
			}
			if($(this).hasClass('dy_calc_regular'))
			{
				$(this).text(args.regular_amount);
			}			
			if($(this).hasClass('dy_calc_total'))
			{
				$(this).text(args.total);
			}
			if($(this).hasClass('dy_calc_outstanding'))
			{
				$(this).text(args.outstanding);
			}
			if($(this).hasClass('dy_calc_tax_amount'))
			{
				$(this).text(args.tax_amount);
			}				
		});		
	});
}

function booking_if_country()
{
	$(window).on('load', function(){
		if($('.dy_show_country').length)
		{
			if(dy_ipgeolocation() != '')
			{
				if(getCookie('country_code') == '')
				{
					$.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey='+dy_ipgeolocation(), function(data){
						if(data.hasOwnProperty('country_code2'))
						{
							$('.dy_show_country_' + data.country_code2).closest('.dy_coupon').removeClass('hidden');
							setCookie('country_code', data.country_code2, 30);
						}
					});				
				}
				else
				{
					$('.dy_show_country_' + getCookie('country_code')).closest('.dy_coupon').removeClass('hidden');
				}
			}
		}		
	});
}

function booking_populate(form)
{
	var input = $(form).find('input');
	
	$(input).each(function(){
		
		var field = $(this);
		var name = $(field).attr('name');
		
		if (typeof(Storage) !== 'undefined')
		{
			if (typeof name !== typeof undefined && name !== false)
			{
				if(sessionStorage.getItem(name) != null && sessionStorage.getItem(name) != '')
				{
					var item = sessionStorage.getItem(name);
					$(field).val(item);
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
	
	if($('#dynamic-checkout').length)
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
	if($('#dynamic_form').length)
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

function booking_affiliate()
{
	if(getCookie('affiliate') != '')
	{
		$('form.booking_form').each(function()
		{
			var form = $(this);
			
			if($(form).find('input[name="ref"]').length == 0)
			{
				var input = $('<input/>');
				input.attr({'value': getCookie('affiliate'), 'name': 'ref'});
				input.addClass('hidden');
				$(form).append(input);		
				console.log(form);
			}
		});
	}
}

function dy_request_form(token)
{
	var exclude = ['country_code3', 'is_eu', 'country_tld', 'languages', 'country_flag', 'geoname_id', 'time_zone_current_time', 'time_zone_dst_savings', 'time_zone_is_dst'];
	var form = $('#dynamic_form');
	var exclude_storage = ['dy_recaptcha', 'total', 'dy_platform'];
	
	$.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey='+dy_ipgeolocation(), function(data) {
		var obj = {};

		for(var k in data)
		{
		  if(typeof data[k] !== 'object')
		  {
			  if(exclude.indexOf(k) == -1)
			  {
				obj[k] = data[k];
				$(form).find('input.'+k).val(data[k]);
			  }
		  }
		  else
		  {
			  for(var sk in data[k])
			  {
				  if(exclude.indexOf(k+'_'+sk) == -1)
				  {
					obj[k+'_'+sk] = data[k][sk];
					$(form).find('input.'+k+'_'+sk).val(data[k][sk]);
				  }	   
			  }
		  }
		}		
	}).always(function(){
		
		var invalids = 0;
		
		$(form).find('input').each(function(){
			
			var field = $(this);
			var name = $(field).attr('name');
			
			if($(this).hasClass('required') && $(this).val() == '')
			{
				invalids++;
				$(this).addClass('invalid_field');
				console.log($(this).attr('name')+ ' invalid');
			}
			else
			{
				$(this).removeClass('invalid_field');
				
				if (typeof name !== typeof undefined && name !== false)
				{
					if(!exclude_storage.includes(name))
					{
						sessionStorage.setItem(name, $(field).val());
					}
				}				
			}
		});
		
		if(invalids == 0)
		{
			dy_populate_form(form);
			$(form).find('input[name="dy_recaptcha"]').val(token);
			//console.log($(form).serializeArray());
			//console.log(token); 

			//facebook pixel
			if(typeof fbq !== typeof undefined)
			{
				if($(form).find('input[name="dy_platform"]').val() == 'quote')
				{
					console.log('Lead');
					fbq('track', 'Lead');
				}
				else
				{
					console.log('Purchase');
					var total = parseFloat($(form).find('input[name="total"]').val());
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
				
				if($(form).find('input[name="dy_platform"]').val() == 'quote')
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

			$(form).submit();
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
				form.append($('<input>').attr({'type': 'hidden', 'name': key, 'value': checkout_obj[key]}));						
			}
		}
	}		
}

function dy_country_dropdown(pluginurl, htmllang)
{
	var pluginurl = dy_url();
	var htmllang = $("html").attr("lang").slice(0, -3);
	
	$(window).on('load', function (e) {
		
		if($('.countrylist').length > 0)
		{
			$.getJSON( pluginurl + 'languages/countries/'+htmllang+'.json')
				.done(function(data) 
				{
					dy_country_options(data);
				})
				.fail(function()
				{
					$.getJSON(pluginurl + 'languages/countries/en.json', function(data) {

						dy_country_options(data);
					});				
				});				
		}		
	});
}	

function dy_country_options(data)
{
	$('.countrylist').each(function() {
		
		var field = $(this);
		var name = $(field).attr('name');
		
		for (var x = 0; x < data.length; x++) 
		{
			var this_option = $('<option></option>').attr({'value': data[x][0]}).html(data[x][1]);
			
			if (typeof(Storage) !== 'undefined')
			{
				if (typeof name !== typeof undefined && name !== false)
				{
					if(sessionStorage.getItem(name) != null && sessionStorage.getItem(name) != '')
					{
						var item = sessionStorage.getItem(name);
						
						if(item ==  data[x][0])
						{
							$(this_option).attr({'selected': 'selected'});
						}
					}
				}
			}
			
			$(this).append(this_option);
		}
	});		
}	

function booking_datepicker()
{
	$('body').append($('<div>').attr({'id': 'availability_calendar'}));
	
	$('.booking_form').find('input[name="booking_date"]').each(function()
	{
		var field = $(this);
		var d = new Date();
		
		$.getJSON(dy_permalink()+'?json=disabled_dates&stamp='+d.getTime(), function(data){
			var args = {};
			args.container = '#availability_calendar';
			args.format = 'yyyy-mm-dd';
			args.disable = [];
			args.firstDay = 1;
			
			var json_parse = data;
			args.disable = json_parse.disable;
			args.min = json_parse.min;
			args.max = json_parse.max;
						
			if($(field).attr('type') == 'text')
			{
				$(field).pickadate(args);
			}
			else if($(field).attr('type') == 'date')
			{
				$(field).attr({'type': 'text'});
				$(field).pickadate(args);
			}
			$(field).removeAttr('disabled').attr({'placeholder': ''});
		});
	});			

}

function booking_hourpicker()
{
	$(window).on('load', function (e) {
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
		
		$('.booking_form').find('input[name="booking_hour"]').each(function()
		{
			$(this).pickatime(args);
		});			
	});
}
function booking_submit()
{	
	$('.booking_form').submit(function(event){
		
		event.preventDefault();
		var form = $(this);
		
		if(booking_validate(form) == true)
		{
			//facebook pixel
			if($(form).find('input[name="quote"]').length == 0)
			{
				//google analytics
				ga_click(form, 'Checkout');

				if(typeof fbq !== typeof undefined)
				{
					//facebook pixel
					fbq('track', 'AddToCart');		
				}
			}
			$(form).unbind('submit').submit();
		}			
	});
}

function booking_quote()
{
	$('.booking_form').find('.booking_quote').click(function(){
		
		var form = $(this).closest('form');
		
		if(booking_validate(form) == true)
		{
			$(form).find('input[name="quote"]').remove();
			var args = {};
			args.name = 'quote';
			args.type = 'hidden';
			args.value = true;
			$(form).append($('<input />').attr(args));
			ga_click(form, 'Quote');
			$(form).submit();				
		}
	});
}

function booking_validate(form)
{
	var invalids = 0;
				
	$(form).find('input[type="text"]').each(function(){
		if($(this).val() == '' && $(this).hasClass('required'))
		{
			$(this).addClass('invalid_field');
			invalids++;
		}
		else
		{
			$(this).removeClass('invalid_field');
		}
	});
		
	if(invalids == 0)
	{	

		var booking_date = $(form).find('input[name="booking_date"]').val();
		var pax_num = parseInt($(form).find('select[name="pax_regular"]').val());
				
		if($(form).find('select[name="pax_free"]').length > 0)
		{
			pax_num = pax_num + parseInt($(form).find('select[name="pax_free"]').val());
		}
		if($(form).find('select[name="pax_discount"]').length > 0)
		{
			pax_num = pax_num + parseInt($(form).find('select[name="pax_discount"]').val());
		}

		console.log();
		var hash = (pax_num+booking_date);
		
		$(form).find('input[name="hash"]').remove();
		var args = {};
		args.name = 'hash';
		args.type = 'hidden';
		args.value = sha512(hash);
		$(form).append($('<input />').attr(args));
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
		var booking_date = $(form).find('input[name="booking_date"]');
		var pax_regular = $(form).find('select[name="pax_regular"]');
		var departure = Date.parse($(booking_date).val());
		var today = new Date();
		today.setDate(today.getDate() - 2);
		today = Date.parse(today);
		var days_between = Math.round((departure-today)/(1000*60*60*24));
		var eventArgs = {};
		eventArgs.eventCategory = event_category;
		eventArgs.eventAction = $('.entry-title').text();
		eventArgs.eventLabel = days_between+'/'+$(booking_date).val()+'/'+$(pax_regular).val();
		ga('send', 'event', eventArgs);
	}
	else
	{
		console.error('dynamicpackages: GA not defined');
	}	
}

function booking_coupon()
{
	var el = $('#booking_coupon');
	
	$(el).find('a').click(function(e){
		e.preventDefault();
		var input = $(el).find('input[name="booking_coupon"]');
		$(input).toggleClass('hidden');
		$(input).focus();
	});	
}