jQuery(() => {
	'use strict';
	more_details_event();
	booking_filter();
});

const more_details_event = () => {
	
	jQuery('.dy_archive').find('a').click(function(e) {
		
		if(typeof ga !== typeof undefined)
		{
			var eventArgs = {};
			eventArgs.eventCategory = 'Package Click';
			eventArgs.eventAction = jQuery(this).attr('title');
			console.log(eventArgs);
			ga('send', 'event', eventArgs);	
		}
		
		if(typeof fbq !== typeof undefined)
		{
			fbq('track', 'ViewContent');
		}				
		
		
	});
}

const booking_filter = () => {
	
	const this_form = jQuery('#dy_package_filter')[0];
	
	jQuery(this_form).find('select').change(() => {
		
		console.log('hello');
		jQuery(this_form).submit();
		
	});	
	
	jQuery(this_form).submit(e => {
		e.preventDefault();
		booking_filter_events(jQuery(this_form));
		jQuery(this_form).unbind('submit').submit();
	});
}

const booking_filter_events = (form) => {
	if(typeof ga !== typeof undefined)
	{
		var eventArgs = {};
		eventArgs.eventAction = 'Search';
		
		if(jQuery(form).find('select[name="package_location"]').length > 0)
		{
			if(jQuery(form).find('select[name="package_location"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Filter by location';
				eventArgs.eventLabel = jQuery(form).find('select[name="package_location"]').val();
				ga('send', 'event', eventArgs);	
				console.log(eventArgs);
			}
		}
		
		if(jQuery(form).find('select[name="package_category"]').length > 0)
		{
			if(jQuery(form).find('select[name="package_category"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Filter by category';
				eventArgs.eventLabel = jQuery(form).find('select[name="package_category"]').val();
				ga('send', 'event', eventArgs);	
				console.log(eventArgs);
			}
		}		
		
		if(jQuery(form).find('select[name="package_sort"]').length > 0)
		{
			if(jQuery(form).find('select[name="package_sort"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Sort by';
				eventArgs.eventLabel = jQuery(form).find('select[name="package_sort"]').val();
				ga('send', 'event', eventArgs);	
				console.log(eventArgs);
			}
		}			
	}
	
	//facebook pixel
	if(typeof fbq !== typeof undefined)
	{
		fbq('track', 'Search');
	}	
}