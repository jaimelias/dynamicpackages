
const excludeGeolocation = ['country_code3', 'is_eu', 'country_tld', 'languages', 'country_flag', 'geoname_id', 'time_zone_current_time', 'time_zone_dst_savings', 'time_zone_is_dst', 'zipcode', 'continent_code', 'continent_name'];
const storeFieldNames = ['first_name', 'lastname', 'phone', 'email', 'repeat_email', 'country', 'city', 'address'];

jQuery(() => {

    storePopulate();
	
});

const formToArray = form => {
   
    let data = jQuery(form).serializeArray();
    
     jQuery(form).find('input:checkbox').each(function () { 
        const {name, checked: value} = this;

         data.push({ name, value });
     });
 
     jQuery(form).find(':disabled').each(function () { 
        const {name, value} = this;

         data.push({ name, value });
     });
     
     return data;
 };




const getGeoLocation = async () => {
    const {ipGeoLocation} = dyCoreArgs;
    let output = [];

    return fetch(`https://api.ipgeolocation.io/ipgeo?apiKey=${ipGeoLocation.token}`).then(resp => {
        if(resp.ok)
        {
            return resp.json();
        }
        else
        {
            throw Error(resp.statusText);
        }
    }).then(data => {

        for(let k in data)
        {
            if(typeof data[k] !== 'object')
            {
                if(!excludeGeolocation.includes(k))
                {
                    output.push({name: `geo_loc_${k}`, value: data[k]});
                }
            }
        }

        return output;
    })
};

const getNonce = async () => {
    const {homeUrl} = dyCoreArgs;

    return fetch(`${homeUrl}/wp-json/dy-core/args`).then(resp => {
        if(resp.ok)
        {
            return resp.json();
        }
        else
        {
            throw Error('Unable to get nonce');
        }
    }).then(data => data.dy_nonce);
};

const createFormSubmit = async (form) => {

    //disable button to prevent double-click
    jQuery(form).find('button').prop('disabled', true);

    const {ipGeoLocation, lang} = dyCoreArgs;
	let formFields = formToArray(form);
	const method = String(jQuery(form).attr('data-method')).toLocaleLowerCase();
	let action = jQuery(form).attr('data-action');  
	const nonce = jQuery(form).attr('data-nonce') || '';  
    const hasEmail = (typeof formFields.find(i => i.name === 'email') !== 'undefined') ? true : false;
    let hashParams = jQuery(form).attr('data-hash-params') || ''; 

    formFields.forEach(o => {
        const {name, value} = o;

        if(storeFieldNames.includes(name))
        {
            sessionStorage.setItem(name, value);
        }
    });

    if(nonce)
    {
        const nonceData = await getNonce();

        if(nonceData)
        {
            if(nonce === 'slug')
            {
                action += `/${nonceData}`;
            }
            else if(nonce === 'param')
            {
                formFields.push({name: 'dy_nonce', value: nonceData});
            }
        }
    }

    if(method.toLowerCase() === 'post' && hasEmail)
    {
        formFields.push({name: 'lang', value: lang});

        ['device', 'landing_domain', 'landing_path', 'channel'].forEach(x => {
            formFields.push({name: x, value: getCookie(x)});
        });

        if(ipGeoLocation)
        {
            const geoLocation = await getGeoLocation();

            if(geoLocation)
            {
                formFields = [...formFields, ...geoLocation];
            }
        }
    }

    if(hashParams)
    {
        let hash = '';
        hashParams = hashParams.split(',');

        if(Array.isArray(hashParams))
        {
            hashParams.forEach(v => {
                hash += jQuery(form).find(`[name="${v}"]`).val();
            });
        }

        if(hash)
        {
            formFields.push({name: 'hash', value: sha512(hash)});
        }
    }

    formSubmit({method, action, formFields});
	
};

const formSubmit = ({method, action, formFields}) => {

	const newForm =  document.createElement('form');
	newForm.method = method;
	newForm.action = action;    


    formFields.forEach(i => {
        let input = document.createElement('input');
        input.name = i.name;
        input.value = i.value;
        newForm.appendChild(input);
    });

    //console.log({formFields});

    document.body.appendChild(newForm);

    newForm.submit();
};

const storePopulate = () => {
	
	
    jQuery('form').each(function(){
        const thisForm = jQuery(this);

        if(jQuery(thisForm).attr('data-action') &&  jQuery(thisForm).attr('data-method'))
        {
            const formFields = formToArray(thisForm);

            formFields.forEach(i => {
                const name = i.name;
                const value = sessionStorage.getItem(name);
                const field = jQuery(thisForm).find('[name="'+name+'"]');
                const tag = jQuery(field).prop('tagName');
                const type = jQuery(field).attr('type');
                
                if(value && storeFieldNames.includes(name))
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
    });


}