jQuery(() => {

    countryDropdown();
	
});


const countryDropdown = () => {
	
	const {pluginUrl, lang} = dyCoreArgs;

	if(jQuery('.countrylist').length > 0)
	{
		return fetch(`${pluginUrl}json/countries/${lang}.json`).then(resp => {
			if(resp.ok)
			{
				return resp.json();
			}
			else
			{
				return fetch(`${pluginUrl}json/countries/en.json`).then(resp2 => {
					if(resp2.ok)
					{
						return resp.json();
					}
					else
					{
						throw Error('unable to find countries');
					}
				}).then(data2 => {
					countryOptions(data2);
				})
			}
		}).then(data => {
			countryOptions(data);
		});		
	}
}	

const countryOptions = data => {

	data = data
		.filter(i => i[0] && i[1])
		.sort((a, b) => a[1].localeCompare(b[1]));

	jQuery('.countrylist').each(function() {
		
		var field = jQuery(this);
		var name = jQuery(field).attr('name');
		
		for (var x = 0; x < data.length; x++) 
		{
			var this_option = jQuery('<option></option>').attr({'value': data[x][0]}).html(data[x][1]);
			
			if (name && typeof Storage !== 'undefined')
			{
				if(sessionStorage.getItem(name))
				{
					var item = sessionStorage.getItem(name);
					
					if(item ===  data[x][0])
					{
						jQuery(this_option).attr({'selected': 'selected'});
					}
				}
			}
			
			jQuery(this).append(this_option);
		}
	});		
}