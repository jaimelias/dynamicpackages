jQuery(() => {
	'use strict';
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});
	inputHandlers();
	initGridsFromTextArea();
});

const cellHeight = 23+2;
const headerHeight = 26+2;
	
const initGridsFromTextArea = () => {
	jQuery('[data-sensei-container]').each(function(){
	
		const {textareaId, containerId, minId, maxId} = getDataSenseiIds(this);
				
		setTimeout(() => { 
			if(textareaId && containerId && maxId)
			{
				registerGrid(textareaId, containerId, minId, maxId);
			}
		}, 1000);
		
	});
};

const registerGrid = (textareaId, containerId, minId, maxId) => {	

	//unescape textarea
	let data = jQuery('<textarea />').html(jQuery(textareaId).val()).text();
	let maxNum = parseInt(jQuery(maxId).val());
	const gridId = jQuery(containerId).attr('id');
	const grid = jQuery(containerId);
	const headers = getHeaders(containerId);
	const columns = getColType(containerId);
	
	const colsNum = (headers.length > 2) ? headers.length : 2;
	
	try
	{
		data = JSON.parse(data);

		if(data.hasOwnProperty(gridId))
		{
			data = data[gridId];
		}
	}
	catch(e)
	{
		data = initialGrid(textareaId, maxId, containerId);
		data = data[gridId];
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
	
	jQuery(minId).add(maxId).on('change click', function() {
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
			v[4] = (v[4] === null) ? `seasons_chart_${i+1}` : v[4];
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
	let readOnly = false;
	const isDisabled = jQuery(containerId).attr('data-sensei-disabled');
	
	if(typeof isDisabled != 'undefined')
	{
		if(isDisabled == 'disabled')
		{
			readOnly = true;
		}
	}
	
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
		
		if(readOnly === true)
		{
			row.readOnly = true;
		}
		
		output.push(row);
	}
	
	return output;	
}

const initialGrid = (textareaId, maxId, containerId) => {
	const headers = getHeaders(containerId);
	const maxNum = parseInt(jQuery(maxId).val());  
	const scale = {};
	const newGrid = [];
	const gridId = jQuery(containerId).attr('id');
	
	for(let x = 0; x < maxNum; x++)
	{
		const row = [];
		
		for(let y = 0; y < headers.length; y++)
		{
			if(gridId == 'seasons_chart')
			{
				if((y+1) == headers.length)
				{
					row.push(gridId+'_'+(x+1));
				}
				else
				{
					row.push(null);
				}				
			}
			else
			{
				row.push(null);
			}
		}
		newGrid.push(row);
	}

	scale[gridId] = newGrid;
	
	jQuery(textareaId).text(JSON.stringify(scale));
	
	return scale;
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

const initSeasonGrids = () => {

	
	const seasonContainer = jQuery('#package_seasons_chart');

	if(jQuery(seasonContainer).length === 0)
	{
		return false;
	}

	let data = jQuery('<textarea />').html(jQuery(seasonContainer).val()).text();
	const numSeasons = parseInt(jQuery('[name="package_num_seasons"]').val());
	const preRender = jQuery('<div>');

	try
	{
		data = JSON.parse(data);
	}
	catch(e)
	{
		console.log(e.message);
		data = {};
	}

	let occupancyChartData = jQuery('<textarea />').html(jQuery('#package_occupancy_chart').val()).text();

	try
	{
		occupancyChartData = JSON.parse(occupancyChartData);
	}
	catch(e)
	{
		console.log(e.message);
		occupancyChartData = {};
	}

	if(data.hasOwnProperty('seasons_chart'))
	{
		let {seasons_chart} = data;
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
			const lastCell = season[season.length - 1];
			const occupancyContainer = jQuery('#occupancy_chart').clone();
			const id = jQuery(occupancyContainer).attr('id');
			const gridKey = id+lastCell;
			jQuery(occupancyContainer).attr({'id': gridKey, 'data-sensei-container': gridKey});			

			const {maxId} = getDataSenseiIds(occupancyContainer);
			const maxRows = parseInt(jQuery(maxId).val());

			if(!occupancyChartData.hasOwnProperty(gridKey))
			{
				occupancyChartData[gridKey] = [...Array(maxRows).keys()].map(()=> [null, null]);
			}


			let title = jQuery('#package_variable_duration_price_title').text();
			title = (season[0]) 
				? `${title} - ${season[4]} [${season[0]}]` 
				: `${title} - ${season[4]}`;

			const wrapper = jQuery('<div>').addClass('hot-container');			
			jQuery(wrapper).html(occupancyContainer);
			jQuery(preRender).append(jQuery('<h3></h3>').text(title));
			jQuery(preRender).append(wrapper);
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

		setTimeout(() => {
			
			jQuery(preRender).find('.hot').each(function() {

				const {textareaId, containerId, minId, maxId} = getDataSenseiIds(this);

				registerGrid(textareaId, containerId, minId, maxId);
			})

		}, 1000);


		jQuery('#package_num_seasons').change(() => {
			initSeasonGrids();
		});
	}
};

const getDataSenseiIds = obj => {
	const thisTextArea = jQuery(obj).attr('data-sensei-textarea');
	const thisMin = jQuery(obj).attr('data-sensei-min');
	const thisMax = jQuery(obj).attr('data-sensei-max');
	const thisContainer = jQuery(obj).attr('data-sensei-container');

	const textareaId = (thisTextArea) ? `#${thisTextArea}` : null;
	const containerId = (thisContainer) ? `#${thisContainer}` : null;
	const minId = (thisMin) ? `#${thisMin}`: null;
	const maxId = (thisMax) ? `#${thisMax}`: null;

	return {textareaId, containerId, minId, maxId};
}


const handlePackagePayment = () => {

	if(jQuery('#package_payment').length === 0 && jQuery('#package_deposit').length === 0)
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


	jQuery('#package_payment').change(()=>  {
		handlePackagePayment();
	});

};

const handlePackageAutoBooking = () => {


	if(jQuery('#package_auto_booking').length && jQuery('#package_payment').length === 0 && jQuery('#package_deposit').length === 0)
	{
		return false;
	}

	jQuery('#package_auto_booking').each(function(){
		const value = parseInt(jQuery(this).val());
		const payment = jQuery('#package_payment');
		const deposit = jQuery('#package_deposit');

		if(value === 0)
		{
			jQuery(deposit).val('').prop('disabled', true);
			jQuery(payment).prop('disabled', true);
		}
		else
		{
			jQuery(deposit).prop('disabled', false);
			jQuery(payment).prop('disabled', false);
		}
	});


	jQuery('#package_auto_booking').change(()=>  {
		handlePackageAutoBooking();
	});	

};

const handlePackageType = () => {

	if(jQuery('#package_package_type').length === 0)
	{
		return false;
	}

	jQuery('#package_package_type').each(function(){
		const value = parseInt(jQuery(this).val());
		const duration_max = jQuery('#package_duration_max');
		const length_unit = jQuery('#package_length_unit');
		const num_seasons = jQuery('#package_num_seasons');
		const all_length_units = [4, 3, 2, 1, 0];
		const disable_length_units = [];

		console.log(value);

		if(value === 1)
		{
			jQuery(duration_max).prop('disabled', false);
			jQuery(num_seasons).prop('disabled', false);
			disable_length_units.push(0, 1);

			initSeasonGrids();
		}
		else
		{
			jQuery(duration_max).val('').prop('disabled', true);
			jQuery(num_seasons).val('0').prop('disabled', true).trigger('change');

			if(value === 0)
			{
				disable_length_units.push(4, 3, 2);
			}
			else if(value === 2)
			{
				disable_length_units.push(4, 3, 1, 0);
			}
			else if(value === 3)
			{
				disable_length_units.push(4, 3, 2, 0);
			}
			else if(value === 4)
			{
				disable_length_units.push(4);
			}
		}

		disable_length_units.forEach(v => {

			jQuery(length_unit).find('option[value="'+v+'"]').each(function(){
				const thisOption = jQuery(this);
				jQuery(thisOption).prop('selected', false);
				jQuery(thisOption).prop('disabled', true);
			});

		});

		all_length_units.forEach(v => {
			if(!disable_length_units.includes(v))
			{
				jQuery(length_unit).find('option[value="'+v+'"]').each(function(){
					const thisOption = jQuery(this);
					jQuery(thisOption).prop('selected', true);
					jQuery(thisOption).prop('disabled', false);
				});
			}
		});

	});

	jQuery('#package_package_type').change(() => {
		handlePackageType();
	});	

};

const handleParentAttr = () => {

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

const inputHandlers = () => {

	handleParentAttr();
	handlePackageType();
	handlePackagePayment();
	handlePackageAutoBooking();
};