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
			var this_option = jQuery('<option></option>').attr({'value': data[x][0]}).html(`${data[x][1]} +${data[x][2]}`);
			
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


const isValidValue = ({ name, value }) => {
	if (!value) {
	  return false;
	}
  
	switch (name) {
	  case 'CVV2':
		return value.length === 3;
	  case 'CCNum':
		return isValidCard(value);
	  case 'email':
		return isEmail(value);
	  case 'repeat_email':
		return isEmail(value) && value === jQuery('#dy_package_request_form').find('input[name="email"]').val();
	  case 'inquiry':
		return !isSpam(value);
	  default:
		return true;
	}
  };
  

const isValidCard = value => {
  
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

const isEmail = email => {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

const isSpam = str => {
	const emailRegex = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/;
	const domainRegex = /\b(?:https?:\/\/)?(?:www\.)?([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/;
	const urlRegex = /\bhttps?:\/\/[^\s]+\b/;
  
	return (
	  emailRegex.test(str) ||
	  domainRegex.test(str) ||
	  urlRegex.test(str)
	);
}