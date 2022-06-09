jQuery(() => {
	'use strict';



	
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});
	handleParentAttr();
	handlePackageType();
	handlePackagePayment();
	handlePackageAutoBooking();
	handleMinMaxPax();
	initSeasonGrids();
	initGridsFromTextArea();

	jQuery('#package_package_type').change(() => {
		handlePackageType();
		initSeasonGrids();
		initGridsFromTextArea();
	});	

	jQuery('#package_num_seasons').change(() => {
		initSeasonGrids();
		initGridsFromTextArea();
	});
	
	jQuery('#package_payment').change(()=>  {
		handlePackagePayment();
	});

	jQuery('#package_auto_booking').change(()=>  {
		handlePackageAutoBooking();
	});
});

const cellHeight = 23+2;
const headerHeight = 26+2;
	
const initGridsFromTextArea = () => {

	jQuery('[data-sensei-container]').each(function(){
	
		const {textareaId, containerId, maxId, isDisabled} = getDataSenseiIds(this);
				
		setTimeout(() => { 
			if(textareaId && containerId && maxId)
			{
				registerGrid({textareaId, containerId, maxId, isDisabled});
			}
		}, 1000);
	});
};


const getInitialGrid = ({rows, cols}) => [...Array(rows).keys()].map(() => [...Array(cols).keys()].map(() => ''));

const registerGrid = ({textareaId, containerId, maxId, isDisabled}) => {	


	if(jQuery(textareaId).length === 0 || jQuery(containerId).length === 0)
	{
		return false;
	}

	//unescape textarea
	let data = {};
	let hasError = false;
	let content = jQuery('<textarea />').html(jQuery(textareaId).val()).text();
	let maxNum = parseInt(jQuery(maxId).val());
	const gridId = jQuery(containerId).attr('id');
	const grid = jQuery(containerId);
	const headers = getHeaders(containerId);
	const columns = getColType(containerId);
	const colsNum = (headers.length > 2) ? headers.length : 2;
	const defaultRows = getInitialGrid({rows: maxNum, cols: colsNum});

	console.log({isDisabled, gridId});
	
	try
	{
		const parsedContent = JSON.parse(content);

		if(parsedContent.hasOwnProperty(gridId))
		{
			data = parsedContent[gridId];
		}
		else
		{
			hasError = true;
			data = defaultRows;
		}
	}
	catch(e)
	{
		hasError = true;
		data = defaultRows;
	}

	
	if(hasError)
	{
		jQuery(textareaId).text(JSON.stringify({[gridId]: data}));
	}

	data = populateSeasons({gridData: data, gridId: gridId});

	const menu = {
		items: {
			undo: {
				name: 'undo'
			},
			redo : {
				name: 'redo'
			}
		}
	};

	const height = (maxNum > data.length) ? (cellHeight*maxNum)+headerHeight : (cellHeight*data.length)+headerHeight;
	
	jQuery(containerId).height(height);
		
	const args = {
		licenseKey: 'non-commercial-and-evaluation',
		data: data,
		stretchH: 'all',
		columns: columns,
		startCols: colsNum,
		minCols: colsNum,
		rowHeaders: true,
		colHeaders: headers,
		readOnly: isDisabled,
		contextMenu: menu,
		minRows: maxNum,
		height,
		afterChange: (changes, source) => {
			if (source !== 'loadData')
			{
				let gridData = grid.handsontable('getData');
				
				const maxNum = parseInt(jQuery(maxId).val());

				if(gridData.length > maxNum)
				{
					gridData = gridData.filter((v, i) => i+1 <= maxNum);
				}

				updateTextArea({textareaId, changes: gridData, containerId});
			}
		}
	}
				
	jQuery(grid).handsontable(args);
	
	jQuery(maxId).on('change click', function() {

		if(jQuery(containerId).length === 0)
		{
			return false;
		}

		const thisField = jQuery(this);
		const maxNum = parseInt(jQuery(thisField).val());
		let rowNum = parseInt(jQuery(grid).handsontable('countRows'));
		const instance = jQuery(grid).handsontable('getInstance');
		let diff = 1;
		
		if(rowNum !== maxNum)
		{
			if(rowNum < maxNum)
			{
				diff = maxNum - rowNum;
				instance.alter('insert_row', rowNum, diff);
			}
			else
			{
				diff = rowNum - maxNum;

				instance.alter('remove_row', (rowNum-diff), diff);				
			}
		}
		
		let gridData = jQuery(grid).handsontable('getData');
		gridData = populateSeasons({gridData, gridId: gridId});

		if(gridData.length > maxNum)
		{
			gridData = gridData.filter((v, i) => i+1 <= maxNum);
		}

		const height = (cellHeight*maxNum)+headerHeight;
	
		jQuery(containerId).height(height);
		
		const textAreaData = updateTextArea({textareaId, changes: gridData, containerId});
		instance.updateSettings({maxRows: maxNum, data: textAreaData[gridId], height});
		instance.render();
	});		
}

const populateSeasons = ({gridData, gridId}) => {



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


const getHeaders = containerId => {
	let headers = jQuery(containerId).attr('data-sensei-headers');
	return headers.split(',');
}

const getColType = containerId => {
	let columns = jQuery(containerId).attr('data-sensei-type');
	columns = columns.replace(/\s+/g, '');
	columns = columns.split(',');
	let selectOption = null;
	const output = [];
	
	for(let x = 0; x < columns.length; x++)
	{
		let row = {};
		
		if(columns[x] == 'numeric')
		{
			row.type = 'numeric';
			row.format = '0';
		}
		else if(columns[x] == 'currency')
		{
			row.type = 'numeric';
			row.format = '0.00';
		}		
		else if(columns[x] == 'date')
		{
			row.type = 'date';
			row.dateFormat = 'YYYY-MM-DD',
			row.correctFormat = true;
		}
		else if(columns[x] == 'dropdown')
		{
			selectOption = jQuery(containerId).attr('data-sensei-dropdown');
			selectOption = selectOption.replace(/\s+/g, '');
			selectOption = selectOption.split(',');
			row.type = 'dropdown';
			row.source = selectOption;
		}
		else if(columns[x] == 'readonly')
		{
			row.readOnly = true;
		}
		else if(columns[x] == 'checkbox')
		{
			row.type = 'checkbox';
			row.className = 'htCenter';
		}
		else
		{
			row.type = 'text';
		}
		
		output.push(row);
	}
	
	return output;	
}

const updateTextArea = ({textareaId, changes, containerId}) => {
	
	let output = {};
	const gridId = jQuery(containerId).attr('id');
	let oldData = jQuery('<textarea />').html(jQuery(textareaId).val()).text();

	try{
		oldData = JSON.parse(oldData);
	}
	catch(e)
	{
		console.log(e.message);
		console.log(oldData);
	}

	const height = (cellHeight * changes.length) + headerHeight;

	jQuery(containerId).height(height);
	output = {...oldData, [gridId]: changes};
	jQuery(textareaId).text(JSON.stringify(output));
	return output;
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

const getDefaultData = el => {

	const gridId = jQuery(el).attr('data-sensei-container');
	const maxId = jQuery(el).attr('data-sensei-max');
	const headers = jQuery(el).attr('data-sensei-headers').split(',');
	const maxNum = parseInt(jQuery(`#${maxId}`).val());

	let gridData = [...Array(maxNum).keys()].map(() => [...Array(headers.length).keys()].map(() => ''));
	let rows = populateSeasons({gridId, gridData});

	return {[gridId]: rows};
};

const getDataFromTextarea = el => {
	const textAreaId = jQuery(el).attr('data-sensei-textarea');
	const gridId = jQuery(el).attr('data-sensei-container');
	let output = {[gridId]: getDefaultData(el)};
	let content = jQuery('<textarea />').html(jQuery(`#${textAreaId}`).val()).text();

	try
	{
		content = JSON.parse(content);

		if(content.hasOwnProperty(gridId))
		{
			output = content;
		}
	}
	catch(e)
	{
		console.log(e.message);
	}


	return output;
};

const initSeasonGrids = () => {

	if(jQuery('#package_occupancy_chart').length === 0)
	{
		return false;
	}


	const packageType = parseInt(jQuery('#package_package_type').val());
	const occupancyDOM = buildOccupancyDOM();
	const defaultOccupancyData = getDefaultData(occupancyDOM);

	if(packageType !== 1)
	{
		jQuery('#package_occupancy_chart').text(JSON.stringify(defaultOccupancyData));
		jQuery('#special_seasons').html('');
		return false;
	}

	const preRender = jQuery('<div>');
	jQuery(preRender).append(occupancyDOM);
	const numSeasons = parseInt(jQuery('[name="package_num_seasons"]').val());


	let seasonConfigData = getDataFromTextarea('#seasons_chart');
	let occupancyChartData = getDataFromTextarea(occupancyDOM);

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

const getDataSenseiIds = obj => {
	const thisTextArea = jQuery(obj).attr('data-sensei-textarea');
	const disabled = jQuery(obj).attr('data-sensei-disabled');
	const thisMax = jQuery(obj).attr('data-sensei-max');
	const thisContainer = jQuery(obj).attr('data-sensei-container');

	const textareaId = (thisTextArea) ? `#${thisTextArea}` : null;
	const containerId = (thisContainer) ? `#${thisContainer}` : null;
	const isDisabled = (disabled === 'disabled') ? true : false;
	const maxId = (thisMax) ? `#${thisMax}`: null;

	return {textareaId, containerId, isDisabled, maxId};
}


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
		const length_unit = jQuery('#package_length_unit');
		const num_seasons = jQuery('#package_num_seasons');
		const all_length_units = [4, 3, 2, 1, 0];
		const disable_length_units = [];
		const hasMaxDuration = [1, 2, 3];

		if(packageType === 1)
		{
			jQuery('#package_variable_duration_price_title').removeClass('hidden');
			
			jQuery(num_seasons).prop('disabled', false);
			disable_length_units.push(0, 1);
		}
		else
		{
			jQuery('#package_variable_duration_price_title').addClass('hidden');
			
			jQuery(num_seasons).val('0').prop('disabled', true).trigger('change');

			if(packageType === 0)
			{
				disable_length_units.push(4, 3, 2);
			}
			else if(packageType === 2)
			{
				disable_length_units.push(4, 3, 1, 0);
			}
			else if(packageType === 3)
			{
				disable_length_units.push(4, 3, 2, 0);
			}
			else if(packageType === 4)
			{
				disable_length_units.push(4);
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

		jQuery(length_unit).each(function() {
			const thisField = jQuery(this);
			disable_length_units.forEach(v => {

				jQuery(thisField).find('option[value="'+v+'"]').each(function(){
					const thisOption = jQuery(this);
					jQuery(thisOption).prop('selected', false);
					jQuery(thisOption).prop('disabled', true);
					
				});
			});

			all_length_units.forEach(v => {
				if(!disable_length_units.includes(v))
				{
					jQuery(thisField).find('option[value="'+v+'"]').each(function(){
						const thisOption = jQuery(this);
						jQuery(thisOption).prop('disabled', false);
					});
				}
			});
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