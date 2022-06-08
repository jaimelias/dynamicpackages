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

	jQuery('#dy_form_filter').each(function(){
		const thisForm = jQuery(this);
		const homeUrl = new URL(jQuery(thisForm).attr('data-home-url'));
		const homePathname = homeUrl.pathname;
		const nullParams = {
			location: 'any',
			category: 'any',
			sort: 'any',
			keywords: ''
		};

		jQuery(thisForm).submit(e => {
			e.preventDefault();
			booking_filter_events(jQuery(thisForm));
			jQuery(thisForm).unbind('submit').submit();
		});

		jQuery(thisForm).find('select').change(function () {
			
			const changedField = jQuery(this);
			const changedName = jQuery(changedField).attr('name');
			const changedValue = jQuery(changedField).val();
			const formData = jQuery(thisForm).serializeArray();
			let countAllChanges = 0;
			let taxChanges = [];

			formData.forEach(arr => {
				const {name, value} = arr;

				if(value !== nullParams[name])
				{
					
					if(['location', 'category'].includes(name))
					{
						taxChanges.push({name, value});
					}

					countAllChanges++;
				}
			});
			
			const countTaxChanges = taxChanges.length;
			const isAny = arr => arr.value === 'any';

			const submitForm = (countAllChanges === countTaxChanges) 
				? (countTaxChanges === 1 && !taxChanges.every(isAny)) 
				? false : true : true;

			if(submitForm)
			{
				jQuery(thisForm).submit();
			}
			else
			{
				const {value, name} = taxChanges[0];
				window.location.replace(new URL(`${homePathname}/package_${name}/${value}`, homeUrl).href);
			}
		});
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