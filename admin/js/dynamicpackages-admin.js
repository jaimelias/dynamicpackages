jQuery(() => {
	'use strict';
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});

	submitSavePost();
	initGridsFromTextArea();
	initSeasonGrids();
});
	
const initGridsFromTextArea = () => {
	jQuery('[data-sensei-container]').each(function(){
	
		const thisTextArea = jQuery(this).attr('data-sensei-textarea');
		const thisMin = jQuery(this).attr('data-sensei-min');
		const thisMax = jQuery(this).attr('data-sensei-max');
		const thisContainer = jQuery(this).attr('data-sensei-container');
	
		const textareaId = (thisTextArea) ? `#${thisTextArea}` : null;
		const containerId = (thisContainer) ? `#${thisContainer}` : null;
		const minId = (thisMin) ? `#${thisMin}`: null;
		const maxId = (thisMax) ? `#${thisMax}`: null;
				
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
	const gridIdName = jQuery(containerId).attr('id');
	const grid = jQuery(containerId);
	const headers = getHeaders(containerId);
	const columns = getColType(containerId);
	
	try
	{
		data = JSON.parse(data);

		if(data.hasOwnProperty(gridIdName))
		{
			if(data[gridIdName].length === 0)
			{
				data[gridIdName] = [headers.map(i => null)];
			}
					
			data = addRowId(data[gridIdName], containerId);
		}
	}
	catch(e)
	{
		data = initialGrid(textareaId, maxId, containerId);
		data = data[gridIdName];
	}
	
	let colsNum = 2;
	
	if(headers.length > colsNum)
	{
		colsNum = headers.length;
	}

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
		afterChange: (changes, source) => {
			if (source !== 'loadData')
			{

				updateGrid(textareaId, grid.handsontable('getData'), containerId);
			}
		}
	}
				
	grid.handsontable(args);
	
	jQuery(minId).add(maxId).on('change blur keyup click', () => {
		
		const rowNum = parseInt(grid.handsontable('countRows'));
		const maxNum = parseInt(jQuery(maxId).val());
		const instance = grid.handsontable('getInstance');
		let diff = 1;
		
		if(rowNum != maxNum)
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

			updateGrid(textareaId, grid.handsontable('getData'), containerId)
		}
		
		instance.updateSettings({maxRows: maxNum, data: addRowId(grid.handsontable('getData'), containerId)});
		instance.render();
	});		
}


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
	const gridIdName = jQuery(containerId).attr('id');
	
	for(let x = 0; x < maxNum; x++)
	{
		const row = [];
		
		for(let y = 0; y < headers.length; y++)
		{
			if(gridIdName == 'seasons_chart')
			{
				if((y+1) == headers.length)
				{
					row.push(gridIdName+'_'+(x+1));
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

	scale[gridIdName] = newGrid;
	
	jQuery(textareaId).text(JSON.stringify(scale));
	
	return scale;
}

const updateGrid = (textareaId, changes, containerId) => {
	
	const gridIdName = jQuery(containerId).attr('id');
	let oldData = jQuery('<textarea />').html(jQuery(textareaId).val()).text();

	try{
		oldData = JSON.parse(oldData);
	}
	catch(e)
	{
		console.log(e.message);
		console.log(oldData);
	}

	jQuery(textareaId).text(JSON.stringify({...oldData, [gridIdName]: changes}));
}

const addRowId = (data, containerId) => {
	const output = [];
	const gridIdName = jQuery(containerId).attr('id');
	
	if(data)
	{
		for(var x = 0; x < (data).length; x++)
		{
			var row = [];
			
			for(var y = 0; y < data[x].length; y++)
			{
				var item = [];
				
				if(gridIdName == 'seasons_chart')
				{
					if((y+1) == data[x].length)
					{
						item = gridIdName+'_'+(x+1);
					}
					else
					{
						item = data[x][y];
					}				
				}
				else
				{
					item = data[x][y];
				}
				
				row.push(item);
				
			}
			output.push(row);
		}
		return output;		
	}

}

const initSeasonGrids = () => {

	const textareaId = '#package_seasons_chart';
	let data = jQuery('<textarea />').html(jQuery(textareaId).val()).text();
	const numSeasons = parseInt(jQuery('[name="package_num_seasons"]').val());
	const preRender = jQuery('<div>');

	try
	{
		data = JSON.parse(data);
	}
	catch(e)
	{
		data = {};
	}

	if(data.hasOwnProperty('seasons_chart'))
	{
		data = data.seasons_chart;

		for(let x = 0; x < numSeasons; x++)
		{
			let isRowReady = true;
			
			for(let y = 0; y < data[x].length; y++)
			{
				if(data[x][y] === null || data[x][y] == '')
				{
					isRowReady = false;
				}
			}
			
			if(isRowReady === true)
			{
				const lastCell = data[x][data[x].length - 1];
				const occupancyChart = jQuery('#occupancy_chart').clone();
				const occupancyChartId = jQuery(occupancyChart).attr('id');
				const occupancyWrap = jQuery('<div>').addClass('hot-container');
				jQuery(occupancyChart).attr({'id': occupancyChartId+lastCell, 'data-sensei-container': occupancyChartId+lastCell});				
				jQuery(occupancyWrap).html(occupancyChart);
				jQuery(preRender).append(jQuery('<h3></h3>').text(jQuery('#accommodation').text()+': '+data[x][0]+' ('+data[x][4]+')'));
				jQuery(preRender).append(occupancyWrap);	
			}

		}
		
		jQuery('#special_seasons').html(preRender);
	}


	setTimeout(() => {
		
		jQuery(preRender).find('.hot').each(function() {
			const thisTextArea = jQuery(this).attr('data-sensei-textarea');
			const thisMin = jQuery(this).attr('data-sensei-min');
			const thisMax = jQuery(this).attr('data-sensei-max');
			const thisContainer = jQuery(this).attr('data-sensei-container');
		
			const textareaId = (thisTextArea) ? `#${thisTextArea}` : null;
			const containerId = (thisContainer) ? `#${thisContainer}` : null;
			const minId = (thisMin) ? `#${thisMin}`: null;
			const maxId = (thisMax) ? `#${thisMax}`: null;

			registerGrid(textareaId, containerId, minId, maxId);
		})

	}, 1000);
};

const submitSavePost = () => {

	jQuery('#package_package_type').add('#package_payment').add('#package_auto_booking').add('#package_num_seasons').change(() => {
		
		if(parseInt(dy_wp_version()) < 5)
		{
			jQuery('#post').submit();
		}
		else
		{
			wp.data.dispatch('core/editor').savePost().then(() => {
				
				if(wp.data.select('core/editor').didPostSaveRequestSucceed() === true)
				{
					const {location} = window;
					location.reload(true);					
				}
			});
		}
	});
}