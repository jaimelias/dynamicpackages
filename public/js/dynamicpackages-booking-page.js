

jQuery(() => {
	selectGateway();
	addOnsCalc();
});

const selectGateway = () => {
	
	const thisForm = jQuery('#dy_package_request_form');
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
		});
	});

};

const getUpdatedCheckoutArgs = () => {
	
	let output = {};
	
	if(defaultCheckoutArgs)
	{
		let {amount, regular_amount, deposit, add_ons, pax_num} = defaultCheckoutArgs;
		let payment_amount = 0;
		let add_ons_id = [];
		let outstanding = 0;
		
		if(deposit)
		{
			deposit = deposit / 100;
		}

		const thisForm = jQuery('#dy_package_request_form');
		jQuery(thisForm).find('[name="add_ons"]').val();
		
		jQuery('#dynamic_table').find('select.add_ons').each(function(){
			const field = jQuery(this);
			
			if(parseInt(jQuery(field).val()) === 1)
			{
				add_ons_id.push(parseFloat(jQuery(field).attr('data-id')));
			}
		});
		
		jQuery(thisForm).find('[name="add_ons"]').val(add_ons_id.join(','));
		
		let add_ons_price = add_ons_id.map(id => {
			let output = 0;
			
			for(let x = 0; x < add_ons.length; x++)
			{
				if(id == add_ons[x].id)
				{
					output = parseFloat(add_ons[x].price) * parseFloat(pax_num);
				}
			}
			return output;
		});
		
		add_ons_price = add_ons_price.reduce((a, b) => a + b, 0);
		payment_amount = getPaymentAmount({amount, deposit, add_ons_price});
		outstanding = getOutstandingAmount({amount, deposit});
		
		if(add_ons_price > 0)
		{
			regular_amount = regular_amount + add_ons_price;
			amount = amount + add_ons_price;
		}
		
		const newArgs = {
			outstanding: parseFloat(outstanding.toFixed(2)),
			total: parseFloat(payment_amount.toFixed(2)),
			amount: parseFloat(amount.toFixed(2)),
			regular_amount: parseFloat(regular_amount.toFixed(2))
		};

		return {...defaultCheckoutArgs, ...newArgs};
	}

	
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

const addOnsCalc = () => {
		
	jQuery('#dynamic_table').find('select.add_ons').on('change', () => {
		
		const checkoutArgs = getUpdatedCheckoutArgs();
		const {amount, regular_amount, total, outstanding} = checkoutArgs;
				
		jQuery('.dy_calc').each(function(){
			
			jQuery(this).addClass('animate');
			jQuery(this).html('<span class="padding-10"><span class="dashicons dashicons-admin-generic animate-spin"></span></span>');

			setTimeout(()=>{
				
				if(jQuery(this).hasClass('dy_calc_amount'))
				{
					jQuery(this).text(amount);
				}
				if(jQuery(this).hasClass('dy_calc_regular'))
				{
					jQuery(this).text(regular_amount);
				}			
				if(jQuery(this).hasClass('dy_calc_total'))
				{
					jQuery(this).text(total);
				}
				if(jQuery(this).hasClass('dy_calc_outstanding'))
				{
					jQuery(this).text(outstanding);
				}

				jQuery(this).removeClass('animate');
			}, 1000);
			
		});		
	});
}



async function checkoutFormSubmit(token){


	const {submit_error} = dyPackageBookingArgs;
	const thisForm = jQuery('#dy_package_request_form');
	const checkoutArgs = getUpdatedCheckoutArgs();
	const {amount} = checkoutArgs;
	let invalids = [];
	const formFields = formToArray(thisForm);
	const dyRequestVal = jQuery(thisForm).find('[name="dy_request"]').val();
	const excludedPurchase = ['contact', 'estimate_request'];
	
	formFields.forEach(i => {

		const {name, value} = i;
		const field = jQuery(thisForm).find('[name="'+name+'"]');
		const label = jQuery(thisForm).find('label[for="'+name+'"]');
		const isRequired = (jQuery(field).hasClass('required')) ? true : false;
		const isNull = (value) ? false : true;
		
		if(isRequired)
		{
			if(isNull || isValidInput({name, value}))
			{
				if(name.startsWith('terms_conditions_'))
				{
					invalids.push(name);
					jQuery(label).addClass('invalid_checkmark');
					console.log(jQuery(field).attr('name')+ ' invalid');
				}
				else
				{
					invalids.push(name);
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
			}
		}
	});
	
	if(invalids.length === 0)
	{
		populateCheckoutForm(thisForm);

		//facebook pixel
		if(typeof fbq !== typeof undefined)
		{
			fbq('track', 'Lead');
		}
		
		//google analytics
		if(typeof gtag !== 'undefined')
		{	
			//send to call	
			gtag('event', 'generate_lead', {
				value: amount,
				currency: 'USD'
			});

			if(!excludedPurchase.includes(dyRequestVal))
			{
				let checkoutEventArgs = getCheckoutEventArgs({...checkoutArgs});

				//console.log(checkoutEventArgs);

				gtag('event', 'begin_checkout', checkoutEventArgs);
				gtag('event', 'add_payment_info', {...checkoutEventArgs, payment_type: dyRequestVal});
			}
		}
		
		//console.log(formToArray(thisForm));
		
		createFormSubmit(thisForm);
	}
	else
	{
		grecaptcha.reset();
		alert(`${submit_error}: ${invalids.join(', ')}`);
	}

	return false;
}

const getCheckoutEventArgs = checkoutArgs => {
	
	let {post_id, title, pax_num, amount, regular_amount, coupon_code, coupon_discount, coupon_discount_amount, categories} = checkoutArgs;

	if(!regular_amount)
	{
		regular_amount = amount;
	}

	let item1 = {
		item_name: title,
		item_id: `post_${post_id}`,
		price: (regular_amount / pax_num),
		quantity: pax_num
	};

	if(Array.isArray(categories))
	{
		if(categories.length > 0)
		{
			categories.forEach((c, i) => {
				let txt = document.createElement('textarea');
				txt.innerHTML = c;
				const category = txt.value;
				const key = (!i) ? 'item_category' : `item_category${i+2}`;
				item1[key] = category;
			});
		}
	}

	let output = {
		value : amount,
		currency: 'USD',
		transaction_id: Date.now().toString(),
		items: [item1],
	};

	if(coupon_code && coupon_discount > 0 && coupon_discount_amount > 0)
	{
		output.coupon = coupon_code;
		output.items[0].coupon = coupon_code;
		output.items[0].discount = (coupon_discount_amount / pax_num);
	}

	return output;
};

const populateCheckoutForm = form => {
	
	const checkoutArgs = getUpdatedCheckoutArgs();

	for(let key in checkoutArgs)
	{
		if(checkoutArgs.hasOwnProperty(key))
		{
			const value = checkoutArgs[key];

			if(typeof value === 'string' || typeof value === 'number' || value === null)
			{
				jQuery(form).append(jQuery('<input>').attr({'type': 'hidden', 'name': key, 'value': value}));						
			}
		}
	}		
}

