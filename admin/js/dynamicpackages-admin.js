jQuery(() => {
	'use strict';
	
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});
	handleParentAttr();
	handlePackageType();
	handlePackagePayment();
	handlePackageAutoBooking();
	handlePackageSchema();
	handleMinMaxPax();
	initSeasonGrids();


	initGridsFromTextArea(hotDataFilter);

	jQuery('#package_package_type').change(() => {
		handlePackageType();
		handlePackageSchema();
		initSeasonGrids();
		initGridsFromTextArea(hotDataFilter);
		handleSaveAndRefresh();
	});

	jQuery('#package_max_persons').change(() => {

		initGridsFromTextArea(hotDataFilter);	

	});

	jQuery('#package_num_seasons').change(() => {
		initSeasonGrids();
		initGridsFromTextArea(hotDataFilter);
	});
	
	jQuery('#package_payment').change(()=>  {
		handlePackagePayment();
	});

	jQuery('#package_auto_booking').change(()=>  {
		handlePackageAutoBooking();
	});
});


const hotDataFilter = ({gridData, gridId}) => {

	if(gridId === 'seasons_chart')
	{
		return gridData.map((v, i) => {

			v[4] = `seasons_chart_${i+1}`;
			return v;
		});		
	}
	else {
		return gridData;
	}
};


const initSeasonGrids = () => {

	if(jQuery('#package_occupancy_chart').length === 0)
	{
		return false;
	}

    const buildOccupancyDOM = () => {
        return jQuery('<div>').attr({
            id: 'occupancy_chart',
            class: 'hot',
            'data-sensei-container': 'occupancy_chart',
            'data-sensei-max': 'package_max_persons',
            'data-sensei-textarea': 'package_occupancy_chart',
            'data-sensei-headers': 'Regular,Discount',
            'data-sensei-type': 'currency,currency',
            'data-sensei-disabled': '',
        });
    }

	const packageType = parseInt(jQuery('#package_package_type').val());
	const occupancyDOM = buildOccupancyDOM();
	const defaultOccupancyData = getDefaultData({el: occupancyDOM, hotDataFilter});

	if(packageType !== 1)
	{
		jQuery('#package_occupancy_chart').text(JSON.stringify(defaultOccupancyData));
		jQuery('#special_seasons').html('');
		return false;
	}

	const preRender = jQuery('<div>');
	jQuery(preRender).append(occupancyDOM);
	const numSeasons = parseInt(jQuery('[name="package_num_seasons"]').val());


	let seasonConfigData = getDataFromTextarea({el: '#seasons_chart', hotDataFilter});
	let occupancyChartData = getDataFromTextarea({el: occupancyDOM, hotDataFilter});

	let {seasons_chart} = seasonConfigData;
	let newRows = [];
	const diff = numSeasons - seasons_chart.length;

	if(numSeasons > seasons_chart.length)
	{
		for(let x = 0; x < diff; x++)
		{
			const thisIndex = seasons_chart.length + x + 1;
			const gridId = `seasons_chart_${thisIndex}`;
			let lastRow = ['', '', '', '', gridId];
			newRows.push(lastRow);
		}

		seasons_chart = [...seasons_chart, ...newRows];
	}

	for(let x = 0; x < numSeasons; x++)
	{
		const season = seasons_chart[x];
		const seasonIndex = season[season.length - 1];
		const clone = jQuery(occupancyDOM).clone();
		const containerId = jQuery(occupancyDOM).attr('id');
		const gridKey = containerId+seasonIndex;
		jQuery(clone).attr({'id': gridKey, 'data-sensei-container': gridKey});			

		const {maxId} = getDataSenseiIds(clone);
		const maxRows = parseInt(jQuery(maxId).val());

		if(!occupancyChartData.hasOwnProperty(gridKey))
		{
			occupancyChartData[gridKey] = [...Array(maxRows).keys()].map(()=> [null, null]);
		}

		let title = jQuery('#package_variable_duration_price_title').text();
		title = (season[0]) 
			? `${title} - ${season[4]} [${season[0]}]` 
			: `${title} - ${season[4]}`;

		jQuery(preRender).append(jQuery('<h3></h3>').text(title));
		jQuery(preRender).append(clone);
	}

	for(let k in occupancyChartData)
	{
		if(k.startsWith('occupancy_chartseasons_chart_'))
		{
			const index = parseInt(k.replace('occupancy_chartseasons_chart_', ''));
			
			if(index > numSeasons)
			{
				console.log(k);
				delete occupancyChartData[k];
			}
		}
	}

	jQuery('#package_occupancy_chart').html(JSON.stringify(occupancyChartData));
	jQuery('#special_seasons').html(preRender);
};




const handlePackagePayment = () => {

	if(jQuery('#package_auto_booking').length === 0)
	{
		return false;
	}

	jQuery('#package_payment').each(function(){
		const value = parseInt(jQuery(this).val());
		const deposit = jQuery('#package_deposit');

		if(value === 0)
		{
			jQuery(deposit).val('').prop('disabled', true);
		}
		else
		{
			jQuery(deposit).prop('disabled', false);
		}
	});
};

const handlePackageAutoBooking = () => {

	if(jQuery('#package_auto_booking').length === 0)
	{
		return false;
	}


	jQuery('#package_auto_booking').each(function(){
		const value = parseInt(jQuery(this).val());
		const payment = jQuery('#package_payment');
		const paymentVal = parseInt(jQuery(payment).val());
		const deposit = jQuery('#package_deposit');

		if(value === 0)
		{
			jQuery(deposit).val('').prop('disabled', true);
			jQuery(payment).prop('disabled', true);
		}
		else
		{
			jQuery(payment).prop('disabled', false);

			if(paymentVal === 1)
			{
				jQuery(deposit).prop('disabled', false);
			}
		}
	});

};

const handlePackageType = () => {

	if(jQuery('#package_package_type').length === 0)
	{
		return false;
	}	

	jQuery('#package_package_type').each(function(){
		const packageType = parseInt(jQuery(this).val());
		const duration_max = jQuery('#package_duration_max');
		const lengthUnitField = jQuery('#package_length_unit');
		const lengthUnitFieldValue = parseInt(jQuery(lengthUnitField).val());
		const num_seasons = jQuery('#package_num_seasons');
		const allLengthUnits = [4, 3, 2, 1, 0];
		const disableLengthUnits = [];
		const hasMaxDuration = [1, 2, 3];
		const isLengthUnitSelected = false;

		if(packageType === 1)
		{
			jQuery('#package_variable_duration_price_title').removeClass('hidden');
			
			jQuery(num_seasons).prop('disabled', false);
			disableLengthUnits.push(0, 1);
		}
		else
		{
			jQuery('#package_variable_duration_price_title').addClass('hidden');
			
			jQuery(num_seasons).val('0').prop('disabled', true).trigger('change');

			if(packageType === 0)
			{
				disableLengthUnits.push(4, 3, 2);
			}
			else if(packageType === 2)
			{
				disableLengthUnits.push(4, 3, 1, 0);
			}
			else if(packageType === 3)
			{
				disableLengthUnits.push(4, 3, 2, 0);
			}
			else if(packageType === 4)
			{
				disableLengthUnits.push(4);
			}
		}

		if(hasMaxDuration.includes(packageType))
		{
			jQuery(duration_max).prop('disabled', false);
		}
		else
		{
			jQuery(duration_max).val('').prop('disabled', true);
		}

		const lengthUnitFieldValueValid = !disableLengthUnits.includes(lengthUnitFieldValue);

		allLengthUnits.forEach(v => {

			let option = jQuery(lengthUnitField).find(`option[value="${v}"]`);
			

			if(!disableLengthUnits.includes(v))
			{
				jQuery(option).prop('disabled', false);

				if(!isLengthUnitSelected && !lengthUnitFieldValueValid)
				{
					jQuery(option).prop('selected', true);
				}
			}
			else
			{
				jQuery(option).prop({
					selected: false,
					disabled: true
				});
			}
		});

	});
};

const handleMinMaxPax = () => {


	const disabledOptions = [];

	jQuery('#package_min_persons').each(function(){

		
		const minField = jQuery(this);
		const minFieldValue = parseInt(jQuery(minField).val());

		jQuery(minField).find('option').each(function() {
			const thisOption = jQuery(this);
			const optionValue = parseInt(jQuery(thisOption).val());

			if(minFieldValue >= optionValue)
			{
				disabledOptions.push(optionValue);
			}
		});

		const maxField = jQuery('#package_max_persons');
		let maxFieldValue = parseInt(jQuery(maxField).val());

		if(minFieldValue >= maxFieldValue)
		{
			maxFieldValue = minFieldValue+1;
		}

		jQuery(maxField).val(maxFieldValue).trigger('change');

		jQuery(maxField).find('option').each(function(){
			const thisOption = jQuery(this);
			const optionValue = parseInt(jQuery(thisOption).val());

			if(disabledOptions.includes(optionValue))
			{
				jQuery(thisOption).prop('selected', false).prop('disabled', true);
			}
			else
			{
				jQuery(thisOption).prop('disabled', false);
			}
		});
	});

	jQuery('#package_min_persons').change(function(){
		handleMinMaxPax();
	});

};

const handlePackageSchema  = () => {

	if(jQuery('#package_schema').length === 0 || jQuery('#package_package_type').length === 0)
	{
		return false;
	}

	const eventPackageTypes = [0, 1];
	const schemaField = jQuery('#package_schema');
	const packageTypeValue = parseInt(jQuery('#package_package_type').val());
	const eventOption = jQuery(schemaField).find(`option[value="0"]`);
	const productOption = jQuery(schemaField).find(`option[value="1"]`);

	if(!eventPackageTypes.includes(packageTypeValue))
	{
		jQuery(productOption).prop('selected', true).prop('disabled', false);
		jQuery(eventOption).prop('selected', false).prop('disabled', true);
	}
	else
	{
		jQuery(eventOption).prop('disabled', false);
		jQuery(productOption).prop('disabled', false);
	}
};

const handleParentAttr = () => {

	if(jQuery('#package_package_type').length === 0)
	{
		return false;
	}	

	const { subscribe } = wp.data;
	let prevParentId = undefined;
	let changed = false;

	subscribe(() => {

		const thisParentId = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'parent' );
		prevParentId = (typeof prevParentId === 'undefined' && thisParentId !== '') 
			? thisParentId 
			: prevParentId;

		if(prevParentId !== thisParentId && !changed)
		{
			changed = true;
			console.log({prevParentId, thisParentId});

			setTimeout(() => {
				wp.data.dispatch('core/editor').savePost().then(() => {
				
					if(wp.data.select('core/editor').didPostSaveRequestSucceed() === true)
					{
						
						const {location} = window;
						const url = new URL(location);
						url.searchParams.append('dy_parent', thisParentId);
						window.location.replace(url.href);						
					}
				});
			}, 500);
		}
	});

};


const handleSaveAndRefresh = () => {

	const { subscribe } = wp.data;
	let updated = false;

	subscribe(() => {

		setTimeout(() => {


			if(updated === false)
			{
				wp.data.dispatch('core/editor').savePost().then(r => {
				
					if(wp.data.select('core/editor').didPostSaveRequestSucceed() === true)
					{
						const {location} = window;
						const url = new URL(location);
						window.location.replace(url.href);
						updated = true;	
					}
				});

			}


		}, 500);
	});

}
