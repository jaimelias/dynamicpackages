(function( $ ) {
	
	'use strict';
	
	more_details_event();
	booking_filter();
	
	function more_details_event()
	{
		if(typeof ga !== typeof undefined && $('.dy_archive').find('a').length)
		{
			$('.dy_archive').find('a').click(function(){
				var eventArgs = {};
				eventArgs.eventCategory = 'Tour Click';
				eventArgs.eventAction = $(this).attr('title');
				console.log(eventArgs);
				ga('send', 'event', eventArgs);
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					fbq('track', 'ViewContent');
				}				
				
			});
		}
	}	
	
})( jQuery );

function booking_filter()
{
	var form = $('#dy_package_filter')[0];

	$(form).find('select').change(function(e){
		
		$(form).submit();
		
	});	
	
	$(form).submit(function(e){
		e.preventDefault();
		booking_filter_events($(form));
		$(form).unbind('submit').submit();
	});
}

function booking_filter_events(form)
{
	if(typeof ga !== typeof undefined)
	{
		var eventArgs = {};
		eventArgs.eventAction = 'Search';
		
		if($(form).find('select[name="package_location"]').length > 0)
		{
			if($(form).find('select[name="package_location"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Filter by location';
				eventArgs.eventLabel = $(form).find('select[name="package_location"]').val();
				ga('send', 'event', eventArgs);	
				console.log(eventArgs);
			}
		}
		
		if($(form).find('select[name="package_category"]').length > 0)
		{
			if($(form).find('select[name="package_category"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Filter by category';
				eventArgs.eventLabel = $(form).find('select[name="package_category"]').val();
				ga('send', 'event', eventArgs);	
				console.log(eventArgs);
			}
		}		
		
		if($(form).find('select[name="package_sort"]').length > 0)
		{
			if($(form).find('select[name="package_sort"]').val() != 'any')
			{
				eventArgs.eventCategory = 'Sort by';
				eventArgs.eventLabel = $(form).find('select[name="package_sort"]').val();
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