$(function(){
	fields_paguelo();
	dy_payment_method();
	dy_populate_checkout($('#dy_checkout_form'));
});

function dy_populate_checkout(form)
{
	var input = $(form).find('input');
	input = $(input).add($('#dy_checkout_form').find('select'));
	
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
					
					if($(field).prop('tagName') == 'INPUT')
					{
						$(field).val(item);
					}
					else if($(field).prop('tagName') == 'SELECT')
					{
						$(field).find('option [value="'+item+'"]').attr({'selected': 'selected'});
					}
				}
			}
		}
	});
}

function dy_payment_method()
{
	$('.bycard').click(function(){
		
		//facebook pixel
		if(typeof fbq !== typeof undefined)
		{
			console.log('InitiateCheckout');
			fbq('track', 'InitiateCheckout');
		}
		
		//google analytics
		if(typeof ga !== typeof undefined)
		{
			var dy_vars = booking_args();
			var eventArgs = {};
			eventArgs.eventAction = 'Click';
			eventArgs.eventLabel = 'Card';
			eventArgs.eventCategory = 'Gateway';
			ga('send', 'event', eventArgs);	
		}		
		
		$('#dynamic_form').addClass('hidden');
		$('#dy_checkout_form').removeClass('hidden');
		$('#dy_checkout_form').find('input[name="name"]').focus();
	});
}

function fields_paguelo()
{
	var paguelo_prop = booking_args();
	var paguelo_form = $('#dy_checkout_form');
	
	for(var key in paguelo_prop)
	{
		if(paguelo_prop.hasOwnProperty(key))
		{
			//console.log(key);
			
			if(typeof paguelo_prop[key] == 'string' || Number.isInteger(paguelo_prop[key]))
			{
				//checks if is integer or string
				paguelo_form.append($('<input>').attr({'type': 'hidden', 'name': key, 'value': paguelo_prop[key]}));						
			}
			else if(typeof paguelo_prop[key] == 'object')
			{
				if(key == 'TERMS_CONDITIONS')
				{
					var fieldset = paguelo_form.find('fieldset.package_terms_conditions');
												
					for(var x = 0; x < paguelo_prop[key].length;  x++)
					{
						var label = $('<label></label>');
						label.append($('<input>').attr({'type': 'checkbox', 'name': 'checkbox_'+paguelo_prop[key][x].term_taxonomy_id, 'value': x}));
						label.append($('<span></span>').append(paguelo_prop['TRANSLATIONS'].i_accept));
						label.append($('<a></a>').attr({'href': paguelo_prop[key][x].url, 'target': '_blank'}).html(paguelo_prop[key][x].name));								
						fieldset.append(label);	
					}
				}
			}
		}
	}	
}

function checkout_paguelo(token)
{
	var paguelo_form = $('#dy_checkout_form');
	var invalid_field = 0;
	var exclude_storage = ['CCNum', 'CVV2', 'sea_recaptcha', 'sea_pax', 's_passengers'];
	
	 $(paguelo_form).find('input').add('select').each(function(){
			
		var field = $(this);
		var name = $(field).attr('name');
		
		if($(field).prop('tagName') == 'INPUT')
		{
			if($(field).attr('type') == 'text' || $(field).attr('type') == 'email' || $(field).attr('type') == 'number') 
			{
				if($(field).val() == '' && !$(field).hasClass('optional'))
				{
					$(field).addClass('invalid_field');
					invalid_field++;
					console.log($(field).attr('name'));
				}
				else
				{
					$(field).removeClass('invalid_field');
					
					if (typeof name !== typeof undefined && name !== false)
					{
						if(!exclude_storage.includes(name))
						{
							sessionStorage.setItem(name, $(field).val());
						}
					}					
				}
			}
			else if($(field).attr('type') == 'checkbox')
			{
				if(!$(field).is(':checked'))
				{
					invalid_field++;
					$('#dynamic_terms').find('fieldset.package_terms_conditions').addClass('minimal_alert');	
					console.log($(field).attr('name'));								
				}
				else
				{
					$('#dynamic_terms').find('fieldset.package_terms_conditions').removeClass('minimal_alert');	
				}
			}						
		}
		else if($(field).prop('tagName') == 'SELECT')
		{
			if($(field).val() == '--')
			{
				$(field).addClass('invalid_field');
				invalid_field++;
				console.log($(field).attr('name'));							
			}
			else
			{
				$(field).removeClass('invalid_field');
				
				if (typeof name !== typeof undefined && name !== false)
				{
					if(!exclude_storage.includes(name))
					{
						sessionStorage.setItem(name, $(field).val());
					}
				}				
			}						
		}
		else
		{
			$(field).removeClass('invalid_field');
		}
	 });
				
	if (invalid_field == 0)
	{
		$(paguelo_form).find('input[name="dy_recaptcha"]').val(token);
		
		//updated january 23rd 2020
		$(paguelo_form).find('input').each(function(){
			var this_field = $(this);
			var b_args = booking_args();
			
			for(var key in b_args)
			{
				if($(this_field).attr('name') == key)
				{
					$(this_field).val(b_args[key]);
				}
			}
			
		});
		
		//console.log(token);
		console.log(JSON.stringify($(paguelo_form).serializeArray()));		
		$(paguelo_form).submit();
	}
	return false;
}

Number.isInteger = Number.isInteger || function(value) {
    return typeof value === "number" && 
           isFinite(value) && 
           Math.floor(value) === value;
};