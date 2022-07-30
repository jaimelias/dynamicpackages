
jQuery(() => {
	timePicker();
	datePicker();
	storePopulate();
	selectGateway();
	copyToClipboard();
	validateCheckPricesForm();
	showCouponForm();
	
	const {pluginDirUrl} = dyStrings();

	if(typeof pluginDirUrl !== 'undefined')
	{
		countryDropdown(pluginDirUrl);
	}
	
	if(typeof checkout_vars !== 'undefined')
	{
		booking_calc();
	}	
	
});

const validateCheckPricesForm = () => {

	const formContainer = jQuery('.booking_form_container');
	const {postId} = dyStrings();

	if(formContainer.length === 0)
	{
		return false;
	}

	jQuery(formContainer).each(function () {

		const thisForm = jQuery(this).find('.booking_form');
		const submitButton = jQuery(thisForm).find('button.dy_check_prices');

		jQuery(thisForm).formToArray().forEach(v => {
			const {name, value} = v;
			const cookieName = `${name}_${postId}`;
			const cookieValue = getCookie(cookieName);
			const field = jQuery(thisForm).find('[name="'+name+'"]');

			if(value === '' && cookieValue)
			{
				jQuery(field).val(cookieValue);
			}
		});	

		jQuery(submitButton).click(() => {
			let invalids = [];
			let required = ['booking_date', 'booking_hour'];
			const data = jQuery(thisForm).formToArray();
			const bookingDate = data.find(v => v.name === 'booking_date');
			let paxNum = 0;

			data.forEach(v => {
				const {name, value} = v;

				if(name === 'end_date' && value !== '')
				{
					required = [...required, 'end_date', 'return_hour'];
				}
			});

			data.forEach(v => {
				const {name, value} = v;

				if(required.includes(name) && value === '')
				{
					invalids.push(name);
					jQuery(thisForm).find('[name="'+name+'"]').addClass('invalid_field');
				}
			});

			if(invalids.length === 0)
			{
				gaClick(thisForm, 'checkPrices');

				data.forEach(v => {
					const {name, value} = v;

					if(['pax_regular', 'pax_discount', 'pax_free'].includes(name))
					{
						paxNum += parseInt(value);
					}
				});

				jQuery(thisForm).append(jQuery('<input />').attr({
					name: 'hash',
					type: 'hidden',
					value: sha512(paxNum + bookingDate.value)
				}));

				jQuery(thisForm).formToArray().forEach(v => {
					const {name, value} = v;
					
					if(name)
					{
						if(name !== 'hash')
						{
							setCookie(`${name}_${postId}`, value, 1);
						}
					}
				});

				jQuery(thisForm).submit();
			}

		});
	});

};


const copyToClipboard = () => {

	const el = jQuery('.copyToClipboard');
	const {textCopiedToClipBoard} = dyStrings();

	jQuery(el).each(function(){
		const thisEl = jQuery(this);

		jQuery(thisEl).addClass('relative');

		jQuery(thisEl).wrapInner( "<div class='copy-to-clipboard-target'></div>");		

		jQuery(thisEl)
			.append('<span class="hidden absolute copy-to-clipboard-notification" style="padding: 10px; background-color: #000; color: #fff; left: 0; top: 0; right: 0; bottom: 0;">'+textCopiedToClipBoard+'</span>');

		jQuery(thisEl).click(function(){
			const thisClickedEl = jQuery(this);

			jQuery(thisClickedEl).find('.copy-to-clipboard-notification').removeClass('hidden');

			navigator.clipboard.writeText(jQuery(thisClickedEl).find('.copy-to-clipboard-target').text());

			setTimeout(()=> {
				jQuery(thisClickedEl).find('.copy-to-clipboard-notification').addClass('hidden');
			}, 1500);
		});

	});
};

const selectGateway = () => {
	
	const thisForm = jQuery('#dynamic_form');
	const cardRequiredFields = ['country', 'city', 'address', 'CCNum', 'ExpMonth', 'ExpYear', 'CVV2'];


	jQuery('#dy_payment_buttons').find('button').each(function(){

		jQuery(this).click(function(){

			const thisButton = jQuery(this);
			const id = jQuery(thisButton).attr('data-id');
			const type = jQuery(thisButton).attr('data-type');
			const branding = jQuery(thisButton).attr('data-branding');
			let networks = jQuery(thisButton).attr('data-networks') || '';
			const cryptoForm = jQuery('#dy_crypto_form');
			const networkSelect = jQuery(cryptoForm).find('select[name="dy_network"]');

			jQuery(networkSelect).removeClass('required').html('<option value="" selected>--</option>');
			jQuery('#dy_crypto_alert').addClass('hidden');

			if(type === 'card-on-site')
			{
				jQuery('#dy_card_payment_conditions').removeClass('hidden');
				$('.dy_card_form_fields').removeClass('hidden');

				cardRequiredFields.forEach(name => {
					jQuery(thisForm).find('[name="'+name+'"]').addClass('required');
				});
			}
			else
			{
				if(type === 'card-off-site')
				{
					jQuery('#dy_card_payment_conditions').removeClass('hidden');
				}
				else 
				{
					jQuery('#dy_card_payment_conditions').addClass('hidden');
				}
				
				$('.dy_card_form_fields').addClass('hidden');

				cardRequiredFields.forEach(name => {
					jQuery(thisForm).find('[name="'+name+'"]').removeClass('required').removeClass('invalid_field');
				});
			}

			if(type === 'crypto')
			{
				
				jQuery(networkSelect).addClass('required');
				jQuery(cryptoForm).removeClass('hidden');

				networks = JSON.parse(networks);

				for (let k in networks) 
				{
					const options = jQuery('<option></option>').attr({'value': k}).html(networks[k].name);
					jQuery(networkSelect).append(options);
				}

				setTimeout(()=>{
					jQuery(networkSelect).focus();
				}, 200)

				jQuery(networkSelect).change(function(){
					const thisField = jQuery(this);
					const value = jQuery(thisField).val();
					const text = jQuery(thisField).find('option:selected').text();

					jQuery('#dy_crypto_network_code').text(value.toUpperCase());
					jQuery('#dy_crypto_network_name').text(text);
					jQuery('#dy_crypto_alert').removeClass('hidden');
				});

			}
			else
			{
				jQuery(thisForm).find('input[name="first_name"]').focus();
				jQuery('#dy_crypto_form').addClass('hidden');
			}

			jQuery('#dy_checkout_branding').html(branding);
			jQuery(thisForm).removeClass('hidden');
			
			jQuery(thisForm).find('input[name="dy_request"]').val(id);

			//facebook pixel
			if(typeof fbq !== typeof undefined)
			{
				console.log('InitiateCheckout');
				fbq('track', 'InitiateCheckout');
			}
			
			//google analytics
			if(typeof gtag !== 'undefined')
			{
				gtag('event', 'selectGateway', {
					items : id
				});					
			}
		});
	});

};

jQuery.fn.formToArray = function () {
   
   let data = jQuery(this).serializeArray();
   
	jQuery(this).find('input:checkbox').each(function () { 
		data.push({ name: this.name, value: this.checked });
	});

	jQuery(this).find('input:disabled').each(function () { 
		data.push({ name: this.name, value: this.value });
	});
	
	return data;
};



const dy_lang = () => {
	const htmllang = jQuery('html').attr('lang');
	const lang = (htmllang.length === 2) ? htmllang : htmllang.slice(0, -3);	
	return lang;
};

const booking_args = () => {
	
	let output = {};
	
	if(typeof checkout_vars == 'function')
	{
		const args  = checkout_vars();
		const tax = 0;
		let amount = parseFloat(args.amount);
		let regular_amount = parseFloat(args.regular_amount);
		let deposit = 0;
		let payment_amount = 0;
		let add_ons_id = [];
		let tax_amount = parseFloat(args.tax_amount);
		
		let outstanding = 0;
		
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
		
		const thisForm = jQuery('#dynamic_form');
		jQuery(thisForm).find('[name="add_ons"]').val();
		
		jQuery('#dynamic_table').find('select.add_ons').each(function(){
			var field = jQuery(this);
			
			if(jQuery(field).val() == 1)
			{
				add_ons_id.push(parseFloat(jQuery(field).attr('data-id')));
			}
		});
		
		jQuery(thisForm).find('[name="add_ons"]').val(add_ons_id.join(','));
		
		let add_ons_price = add_ons_id.map(id => {
			let output = 0;
			
			for(let x = 0; x < args.add_ons.length; x++)
			{
				if(id == args.add_ons[x].id)
				{
					output = parseFloat(args.add_ons[x].price) * parseFloat(args.pax_num);
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
		

		payment_amount = getPaymentAmount({amount, deposit, add_ons_price});
		outstanding = getOutstandingAmount({amount, deposit});
		
		if(add_ons_price > 0)
		{
			regular_amount = regular_amount + add_ons_price;
			amount = amount + add_ons_price;		
		}		
		
		
		const new_args = {
			tax_amount: tax_amount.toFixed(2),
			outstanding: outstanding.toFixed(2),
			total: payment_amount.toFixed(2),
			amount: amount.toFixed(2),
			regular_amount: regular_amount.toFixed(2)
		};
		
		console.log(new_args);
		
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
	}
	

	return output;
}

const getPaymentAmount = ({amount, deposit, add_ons_price}) => {
	
	let output = amount;

	if(deposit > 0)
	{
		output = (output * deposit);
	}
	
	output = output + add_ons_price;
	
	return output;
};

const getOutstandingAmount = ({amount, deposit}) => {
	
	let output = (deposit > 0) ? (amount * deposit) : amount;

	return (amount === output) ? amount : amount - output;
};

const booking_calc = () => {
		
	jQuery(document).on('change', '#dynamic_table select.add_ons', () => {
		
		var args = booking_args();
				
		jQuery('.dy_calc').each(function(){
			
			jQuery(this).addClass('animate');
			jQuery(this).html('<span class="padding-10"><i class="fas fa-sync fa-spin"></i></span>');
			
			setTimeout(()=>{
				
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

				jQuery(this).removeClass('animate');
			}, 1000);
			
		});		
	});
}

const storePopulate = () => {
	
	const thisForm = jQuery('#dynamic_form');
	const formFields = jQuery(thisForm).formToArray();

	formFields.forEach(i => {
		const name = i.name;
		const value = sessionStorage.getItem(name);
		const field = jQuery(thisForm).find('[name="'+name+'"]');
		const tag = jQuery(field).prop('tagName');
		const type = jQuery(field).attr('type');
		
		if(value)
		{
			if(tag == 'INPUT')
			{
				if(type == 'checkbox' || type == 'radio')
				{
					jQuery(field).prop('checked', true);
				}
				else
				{
					jQuery(field).val(value);
				}
			}
			else if(tag == 'TEXTAREA' || tag == 'SELECT')
			{
				jQuery(field).val(value);
			}			
		}
	});
}

function dy_recaptcha()
{	
	var args = {};
	const {recaptchaSiteKey} = dyStrings();
	args.sitekey = recaptchaSiteKey;
	args.isolated = true;
	args.badge = 'inline';
	var quote_widget;
	
	if(jQuery('#dynamic_form').length)
	{
		args.callback = (token) => {
			return new Promise((resolve, reject) => { 
				if(checkoutFormSubmit(token) == false)
				{
					grecaptcha.reset(quote_widget);
				}
				resolve();
			});			
		};
		quote_widget = grecaptcha.render('dy_submit_form', args);
	}
}

const checkoutFormSubmit = token => {
	const excludeGeolocation = ['country_code3', 'is_eu', 'country_tld', 'languages', 'country_flag', 'geoname_id', 'time_zone_current_time', 'time_zone_dst_savings', 'time_zone_is_dst'];
	const thisForm = jQuery('#dynamic_form');
	const excludeStore = ['dy_recaptcha', 'dy_request'];
	const args = booking_args();
	const {ipGeolocation} = dyStrings();
	
	jQuery.getJSON('https://api.ipgeolocation.io/ipgeo?apiKey='+ipGeolocation, data => {

		for(let k in data)
		{			
		  if(typeof data[k] !== 'object')
		  {
			  if(!excludeGeolocation.includes(k))
			  {
				jQuery('[name="geo_'+k+'"]').val(data[k]);
			  }
		  }
		}		
	}).always(() => {
		
		let invalids = 0;
		const formFields = jQuery(thisForm).formToArray();
		
		formFields.forEach(i => {
			const name = i.name;
			const value = i.value;
			const field = jQuery(thisForm).find('[name="'+name+'"]');
			const label = jQuery(thisForm).find('label[for="'+name+'"]');
			const tag = jQuery(field).prop('tagName');
			const isRequired = (jQuery(field).hasClass('required')) ? true : false;
			const isNull = (value) ? false : true;
			
			if(isRequired)
			{
				if(isNull || isInvalid(name, value))
				{
					if(name.startsWith('terms_conditions_'))
					{
						invalids++;
						jQuery(label).addClass('invalid_checkmark');
						console.log(jQuery(field).attr('name')+ ' invalid');
					}
					else
					{
						invalids++;
						jQuery(field).addClass('invalid_field');
						console.log(jQuery(field).attr('name')+ ' invalid');
					}
				}
				else
				{
					if(name.startsWith('terms_conditions_'))
					{
						jQuery(label).removeClass('invalid_checkmark');
					}
					else
					{
						jQuery(this).removeClass('invalid_field');
					}
					
					if(!excludeStore.includes(name))
					{
						sessionStorage.setItem(name, value);
					}
				}
			}
		});
		
		if(invalids == 0)
		{
			populateCheckoutForm(thisForm);
			jQuery(thisForm).find('input[name="dy_recaptcha"]').val(token);
			//console.log(formFields);
			//console.log(token); 

			//facebook pixel
			if(typeof fbq !== typeof undefined)
			{
				if(jQuery(thisForm).find('input[name="dy_request"]').val() == 'quote')
				{
					fbq('track', 'Lead');
				}
				else
				{
					fbq('track', 'Purchase', {value: parseFloat(args.total), currency: args.currency_name});
				}
			}
			
			//google analytics
			if(typeof gtag !== 'undefined')
			{				
				if(jQuery(thisForm).find('input[name="dy_request"]').val() == 'quote')
				{
					gtag('event', 'generate_lead', {
						value : args.total
					});
				}
				else
				{
					gtag('event', 'purchase', {
						value : args.total,
						items: args.title
					});
				}	
			}
			
			//console.log(jQuery(thisForm).formToArray());
			
			jQuery('#dy_submit_form').prop('disabled', true);
			
			createFormSubmit(thisForm);
		}
		else
		{
			alert(booking_args().TRANSLATIONS.submit_error);
		}
	});
	return false;
}

const populateCheckoutForm = (form) => {
	
	var checkout_obj = booking_args();
	
	for(var key in checkout_obj)
	{
		if(checkout_obj.hasOwnProperty(key))
		{
			if(typeof checkout_obj[key] == 'string' || Number.isInteger(checkout_obj[key]) || checkout_obj[key] === null)
			{
				form.append(jQuery('<input>').attr({'type': 'hidden', 'name': key, 'value': checkout_obj[key]}));						
			}
		}
	}		
}

const countryDropdown = pluginDirUrl => {
	
	
	jQuery(window).on('load', e => {
		
		if(jQuery('.countrylist').length > 0)
		{
			jQuery.getJSON( pluginDirUrl + 'languages/countries/' + dy_lang() + '.json')
				.done(data => {
					countryOptions(data);
				})
				.fail(() =>	{
					jQuery.getJSON(pluginDirUrl + 'languages/countries/en.json', data => {

						countryOptions(data);
					});				
				});				
		}		
	});
}	

const countryOptions = (data) => {
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
					if(sessionStorage.getItem(name) != null && sessionStorage.getItem(name) != null)
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

const datePicker = async () => {

	const formContainer = jQuery('.booking_form_container');
	const {permaLink} = dyStrings();
	
	if(formContainer.length === 0)
	{
		return false;
	}
	
	const d = new Date();
	let url = permaLink+'?json=disabled_dates&stamp='+d.getTime();	
	jQuery('body').append(jQuery('<div>').attr({'id': 'availability_calendar'}));

	const buildPicker = () => {

		jQuery(formContainer).each(function () {
			const thisForm = jQuery(this).find('.booking_form');
			const field = jQuery(thisForm).find('input.dy_date_picker');
			const name = jQuery(field).attr('name');
			let fetchUrl = (name === 'end_date') ? url + '&return=true' : url;
			
			jQuery(thisForm).find('select.booking_select').each(function(){
				fetchUrl += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
			});
										
			fetch(fetchUrl)
			.then(response => {
				if(response.ok)
				{
					return response;
				}
				else
				{
				  let error = new Error('Error ' + response.status + ': ' + response.statusText);
				  error.response = response;
				  throw error;			
				}
			}, error => {
				throw new Error(error.message);
			})
			.then(response => response.json())
			.then(data => {
				
				const today = new Date();
				const hour = today.getHours();
				const weekDay = today.getDay();
				
				let args = {
					container: '#availability_calendar',
					format: 'yyyy-mm-dd',
					disable: data.disable,
					firstDay: 1,
					min: data.min,
					max: data.max
				};
				
				//stop tomorrow bookings
				if(args.min === 1)
				{
					if(hour >= 17)
					{
						args.min = 2;
					}
					if(weekDay === 0 || weekDay === 6)
					{
						if(hour >= 16)
						{
							args.min = 2;
						}
					}
				}

				if(jQuery(field).attr('type') == 'text')
				{
					jQuery(field).pickadate(args);
				}
				else if(jQuery(field).attr('type') == 'date')
				{
					jQuery(field).attr({
						'type': 'text'
					});
					jQuery(field).pickadate(args);
				}
				
				jQuery(field).removeAttr('disabled').attr({
					'placeholder': null
				});
			
			})
			.catch(error => {
				throw error;
			});			

		});
	};

	buildPicker();

	jQuery(formContainer).each(function(){
		const thisForm = jQuery(this).find('.booking_form');
		
		jQuery(thisForm).find('select.booking_select').change(function(){

			jQuery(thisForm).find('input.dy_date_picker').val('');
			jQuery(thisForm).find('input.dy_time_picker').val('');
		});
	});
};

const timePicker = () => {

	if(jQuery('input.dy_time_picker').length === 0)
	{
		return;
	}

	const {booking_allowed_hours} = dyStrings();
	let args = {};
	
	if(booking_allowed_hours.length > 1)
	{
		args.min = booking_allowed_hours[0];
		args.max = booking_allowed_hours[1];
	}
	
	jQuery('.booking_form').find('input.dy_time_picker').each(function()
	{
		jQuery(this).pickatime(args);
	});	
}



const gaClick = (form, eventName) => {
	if(typeof gtag !== 'undefined')
	{
		const booking_date = jQuery(form).find('input[name="booking_date"]');
		const pax_regular = jQuery(form).find('select[name="pax_regular"]');
		const departure = Date.parse(jQuery(booking_date).val());
		let today = new Date();
		today.setDate(today.getDate() - 2);
		today = Date.parse(today);
		const days_between = Math.round((departure-today)/(1000*60*60*24));		
		gtag('event', eventName, {
			items : jQuery('.entry-title').text(),
			days: days_between+'/'+jQuery(booking_date).val()+'/'+jQuery(pax_regular).val()
		});
	}
}

const showCouponForm = () => {
	const container = jQuery('#booking_coupon');
	const  link = jQuery(container).find('a');
	const field = jQuery(container).find('input[name="booking_coupon"]');

	if(field.val() !== '')
	{
		jQuery(field).removeClass('hidden');
	}
	
	jQuery(link).click(e => {
		e.preventDefault();
		jQuery(field).removeClass('hidden').focus();
	});	
}

const isInvalid = (name, value) => {
	let output = false;

	if(name === 'CVV2' && value.length !== 3)
	{
		output =  true;
	}
	else if(name === 'CCNum' && !isValidCard(value))
	{
		output =  true;
	}
	else if(name === 'email' && !isEmail(value))
	{
		output =  true;
	}
	else if(name === 'repeat_email' && !isEmail(value))
	{
		output =  true;
	}
	
	return output;
};

const isValidCard = (value) => {
  
	if (/[^0-9-\s]+/.test(value))
	{
		return false;
	}

	let nCheck = 0;
	let bEven = false;
	value = value.replace(/\D/g, null);

	for (let n = value.length - 1; n >= 0; n--)
	{
		let cDigit = value.charAt(n);
		let nDigit = parseInt(cDigit, 10);

		if (bEven && (nDigit *= 2) > 9){
			nDigit -= 9;
		};

		nCheck += nDigit;
		bEven = !bEven;
	}

	return (nCheck % 10) == 0;
}

const isEmail = (email) => {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

const createFormSubmit = form => {
	const formFields = jQuery(form).formToArray();
	const newForm =  document.createElement('form');
	const action = formFields.find(i => i.name === 'package_url').value;
	newForm.method = 'POST';
	newForm.action = action;
	
	formFields.forEach(i => {
		let input = document.createElement('input');
		input.name = i.name;
		input.value = i.value;
		newForm.appendChild(input);
	});
	
	document.body.appendChild(newForm);
	newForm.submit();
};