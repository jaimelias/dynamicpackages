jQuery(() => {
	'use strict';
	jQuery('.timepicker').pickatime();
	jQuery('.datepicker').pickadate({format: 'yyyy-mm-dd'});
	buildSeasonsGrid();
	submitSavePost();
	loadGrids();
});
	
const loadGrids = () => {
	jQuery(window).on('load', () =>{
		jQuery('[data-sensei-container]').each(function(x){
							
			const textareas = (jQuery(this).attr('data-sensei-textarea')) ? String('#'+jQuery(this).attr('data-sensei-textarea')) : null;
			const container = (jQuery(this).attr('data-sensei-container')) ? String('#'+jQuery(this).attr('data-sensei-container')) : null;
			const min = (jQuery(this).attr('data-sensei-min')) ? String('#'+jQuery(this).attr('data-sensei-min')) : null;
			const max = (jQuery(this).attr('data-sensei-max')) ? String('#'+jQuery(this).attr('data-sensei-max')) : null;
			const index = x+1;
			
			if(textareas && container && max)
			{
				registerGrid(textareas, container, min, max, index);
			}						
		});				
	});
};

const registerGrid = (textareas, container, min, max, index) => {	
	let data = jQuery(textareas).text();	
	let maxNum = parseInt(jQuery(max).val());
	const gridIdName = jQuery(container).attr('id');
	
	try
	{
		data = JSON.parse(data);
		data = addRowId(data[gridIdName], container);
	}
	catch(e)
	{
		data = initialGrid(textareas, max, container, index);
		data = data[gridIdName];
		//console.log(data);
	}	
		
	const grid = jQuery(container);
	const headers = getHeaders(jQuery(container));
	const columns = getColType(jQuery(container));
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
		maxRows: maxNum,
		afterChange: (changes, source) => {
			if (source !== 'loadData')
			{
				jQuery(textareas).text(JSON.stringify(updateGrid(textareas, grid.handsontable('getData'), container, index)));
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

			jQuery(textareas).text(JSON.stringify(updateGrid(textareas, grid.handsontable('getData'), container, index)));
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

const initialGrid = (textareas, max, container, index) => {
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

const updateGrid = (textareas, data, container, index) => {
	const gridIdName = jQuery(container).attr('id');
	let textAreasData = {};
	
	try
	{
		textAreasData = JSON.parse(jQuery(textareas).text());
	}
	catch(e)
	{
		textAreasData = {};
	}	
	
	textAreasData[gridIdName] = data;
	return textAreasData;
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
	let data = jQuery('#package_seasons_chart').text();
	const numSeasons = parseInt(jQuery('[name="package_numSeasons"]').val());
	const preRender = jQuery('<div>');

	try
	{
		data = JSON.parse(data);
	}
	catch(e)
	{
		console.log(data);
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
					location.reload();					
				}
			});
		}
	});
}