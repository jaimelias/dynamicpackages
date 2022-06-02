jQuery(() => {
	'use strict';
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});
	buildSeasonsGrid();
	submitSavePost();
	loadGrids();
});
	
const loadGrids = () => {
	jQuery('[data-sensei-container]').each(function(x){
	
		const thisTextArea = jQuery(this).attr('data-sensei-textarea');
		const thisMin = jQuery(this).attr('data-sensei-min');
		const thisMax = jQuery(this).attr('data-sensei-max');
		const thisContainer = jQuery(this).attr('data-sensei-container');
	
		const textareas = (thisTextArea) ? `#${thisTextArea}` : null;
		const container = (thisContainer) ? `#${thisContainer}` : null;
		const min = (thisMin) ? `#${thisMin}`: null;
		const max = (thisMax) ? `#${thisMax}`: null;
				
		setTimeout(() => { 
			if(textareas && container && max)
			{
				registerGrid(textareas, container, min, max);
			}
		}, 1000);
		
	});
};



const registerGrid = (textareas, container, min, max) => {	

	//unescape textarea
	let data = jQuery('<textarea />').html(jQuery(textareas).val()).text();
	let maxNum = parseInt(jQuery(max).val());
	const gridIdName = jQuery(container).attr('id');
	const grid = jQuery(container);
	const headers = getHeaders(jQuery(container));
	const columns = getColType(jQuery(container));
	
	try
	{
		data = JSON.parse(data);

		if(data.hasOwnProperty(gridIdName))
		{
			if(data[gridIdName].length === 0)
			{
				data[gridIdName] = [headers.map(i => null)];
			}
					
			data = addRowId(data[gridIdName], container);
		}
	}
	catch(e)
	{
		data = initialGrid(textareas, max, container);
		data = data[gridIdName];
	}
	
	let colsNum = 2;
	
	if(headers.length > colsNum)
	{
		colsNum = headers.length;
	}

	const menu = {};
	menu.items = {};
	menu.items.undo = {name: 'undo'};
	menu.items.redo = {name: 'redo'};
		
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

				updateGrid(textareas, grid.handsontable('getData'), container);
			}
		}
	}
				
	grid.handsontable(args);
	
	jQuery(min).add(max).on('change blur keyup click', () => {
		
		const rowNum = parseInt(grid.handsontable('countRows'));
		const maxNum = parseInt(jQuery(max).val());
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

			updateGrid(textareas, grid.handsontable('getData'), container)
		}
		
		instance.updateSettings({maxRows: maxNum, data: addRowId(grid.handsontable('getData'), container)});
		instance.render();
	});		
}


const getHeaders = (container) => {
	let headers = [];
	headers = jQuery(container).attr('data-sensei-headers');
	headers = headers.split(',');
	return headers;
}

const getColType = (container) => {
	let columns = [];
	columns = jQuery(container).attr('data-sensei-type');
	columns = columns.replace(/\s+/g, '');
	columns = columns.split(',');
	let selectOption = null;
	const output = [];
	let readOnly = false;
	const isDisabled = jQuery(container).attr('data-sensei-disabled');
	
	if(typeof isDisabled != 'undefined')
	{
		if(isDisabled == 'disabled')
		{
			readOnly = true;
		}
		
	}
	
	for(var x = 0; x < columns.length; x++)
	{
		var row = {};
		
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
			
			selectOption = jQuery(container).attr('data-sensei-dropdown');
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

const initialGrid = (textareas, max, container) => {
	const headers = getHeaders(jQuery(container));
	const maxNum = parseInt(jQuery(max).val());  
	const scale = {};
	const newGrid = [];
	const gridIdName = jQuery(container).attr('id');
	
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
	
	jQuery(textareas).text(JSON.stringify(scale));
	
	return scale;
}

const updateGrid = (textareas, changes, container) => {
	
	const gridIdName = jQuery(container).attr('id');
	let oldData = jQuery('<textarea />').html(jQuery(textareas).val()).text();

	try{
		oldData = JSON.parse(oldData);
	}
	catch(e)
	{
		console.log(e.message);
		console.log(oldData);
	}

	jQuery(textareas).text(JSON.stringify({...oldData, [gridIdName]: changes}));
}

const addRowId = (data, container) => {
	const output = [];
	const gridIdName = jQuery(container).attr('id');
	
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

const buildSeasonsGrid = () => {

	let data = jQuery('<textarea />').html(jQuery('#package_seasons_chart').val()).text();
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
		
		var max = data.length;
				
		if(max > numSeasons)
		{
			max = numSeasons;
		}
				
		for(var x = 0; x < max; x++)
		{
			var isRowReady = true;
			
			for(var y = 0; y < data[x].length; y++)
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
				const occupancyWrap = jQuery('<div>').addClass('hot-container');
				jQuery(occupancyChart).attr({'id': jQuery(occupancyChart).attr('id')+lastCell, 'data-sensei-container': jQuery(occupancyChart).attr('id')+lastCell});				
				jQuery(occupancyWrap).html(occupancyChart);
				jQuery(preRender).append(jQuery('<h3></h3>').text(jQuery('#accommodation').text()+': '+data[x][0]+' ('+data[x][4]+')'));
				jQuery(preRender).append(occupancyWrap);	
			}

		}
		
		jQuery('#special_seasons').html(preRender);
	}
}
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