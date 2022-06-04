jQuery(() => {
	'use strict';
	more_details_event();
	booking_filter();
});

const more_details_event = () => {
	
	jQuery('.dy_archive').find('a').click(function(e) {
		
		if(typeof gtag !== 'undefined')
		{
			gtag('event', 'view_item', {
				items : jQuery(this).attr('title')
			});
		}
		
		if(typeof fbq !== 'undefined')
		{
			fbq('track', 'ViewContent');
		}
	});
}

const booking_filter = () => {
	
	const this_form = jQuery('#dy_form_filter')[0];
	
	jQuery(this_form).find('select').change(() => {
		jQuery(this_form).submit();
	});	
	
	jQuery(this_form).submit(e => {
		e.preventDefault();
		booking_filter_events(jQuery(this_form));
		jQuery(this_form).unbind('submit').submit();
	});
}

const booking_filter_events = form => {
	
	const selectField = name => jQuery(form).find(`select[name="${name}"]`);
		
	if(typeof gtag !== 'undefined')
	{		
		['package_location', 'package_category', 'package_sort'].forEach(r => {
			if(selectField(r).length > 0)
			{
				if(selectField(r).val() != 'any')
				{
					gtag('event', 'select_item', {
						items : `filter_${r}`,
						item_list_name: selectField(r).val()
					});
				}
			}			
		});			
	}
	
	//facebook pixel
	if(typeof fbq !== 'undefined')
	{
		fbq('track', 'Search');
	}	
}