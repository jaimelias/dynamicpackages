const {postId} = dyPackageArgs;

jQuery(() => {

	timePicker();
	datePicker();
	validateCheckPricesForm();
	showCouponForm();
});

const datePicker = async () => {

	const formContainer = jQuery('.booking_form_container');
	const {permalink} = dyCoreArgs;
	
	if(formContainer.length === 0)
	{
		return false;
	}
	
	const d = new Date();
	let url = permalink+'?json=disabled_dates&stamp='+d.getTime();	
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

	const {booking_allowed_hours} = dyPackageArgs;
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



const validateCheckPricesForm = () => {

	const formContainer = jQuery('.booking_form_container');

	if(formContainer.length === 0)
	{
		return false;
	}

	jQuery(formContainer).each(function () {

		const thisForm = jQuery(this).find('.booking_form');
		const submitButton = jQuery(thisForm).find('button.dy_check_prices');

		formToArray(thisForm).forEach(v => {
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
			const data = formToArray(thisForm);
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

				formToArray(thisForm).forEach(v => {
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