(function( $ ) {
	'use strict';

	$(function(){
	
		$('.timepicker').pickatime();
		$('.datepicker').pickadate({format: 'yyyy-mm-dd'});
		build_season_grids();
		submit_save_post();
		
		$(window).on('load', function(e){
			$('[data-sensei-container]').each(function(x){
								
				var textareas = String('#'+$(this).attr('data-sensei-table'));
				var container = String('#'+$(this).attr('data-sensei-container'));
				
				if($(this).attr('data-sensei-min'))
				{
					var min = String('#'+$(this).attr('data-sensei-min'));
				}
				if($(this).attr('data-sensei-max'))
				{
					
					var max = String('#'+$(this).attr('data-sensei-max'));
				}
				var index = x+1;
				register_grid(textareas, container, min, max, index);			
			});				
		});
	});

function register_grid(textareas, container, min, max, index)
{	
	var data = $(textareas).text();	
	var max_num = parseInt($(max).val());
	var grid_id_name = $(container).attr('id');
	
	try
	{
		data = JSON.parse(data);
		data = add_row_id(data[grid_id_name], container);
	}
	catch(e)
	{
		data = initial_grid(textareas, max, container, index);
		data = data[grid_id_name];
		//console.log(data);
	}	
		
	var grid = $(container);
	var headers = get_headers($(container));
	var columns = get_col_type($(container));
	var cols_num = 2;
	
	if(headers.length > cols_num)
	{
		cols_num = headers.length;
	}

	var menu = {};
	menu.items = {};
	menu.items.undo = {name: 'undo'};
	menu.items.redo = {name: 'redo'};
		
	var args = {
		data: data,
		stretchH: 'all',
		columns: columns,
		startCols: cols_num,
		minCols: cols_num,
		rowHeaders: true,
		colHeaders: headers,
		contextMenu: menu,
		maxRows: max_num,
		afterChange: function(changes, source)
		{
			if (source !== 'loadData')
			{
				$(textareas).text(JSON.stringify(update_grid(textareas, grid.handsontable('getData'), container, index)));
			}
		}
	}
				
	grid.handsontable(args);
	
	$(min).add(max).on('change blur keyup click', function(){
		
		var row_num = parseInt(grid.handsontable('countRows'));
		var max_num = parseInt($(max).val());
		var instance = grid.handsontable('getInstance');
		
		if(row_num != max_num)
		{
			if(row_num < max_num)
			{
				var diff = max_num - row_num;
				instance.alter('insert_row', row_num, diff);
			}
			else
			{
				var diff = row_num - max_num;
				instance.alter('remove_row', (row_num-diff), diff);				
			}

			$(textareas).text(JSON.stringify(update_grid(textareas, grid.handsontable('getData'), container, index)));
		}
		
		instance.updateSettings({maxRows: max_num, data: add_row_id(grid.handsontable('getData'), container)});
		instance.render();
	});		
}


function get_headers(container)
{
	var headers = [];
	headers = $(container).attr('data-sensei-headers');
	//headers = headers.replace(/\s+/g, '');
	headers = headers.split(',');
	return headers;
}

function get_col_type(container)
{
	var columns = [];
	columns = $(container).attr('data-sensei-type');
	columns = columns.replace(/\s+/g, '');
	columns = columns.split(',');
	var select_option = [];
	var output = [];
	var readOnly = false;
	var isDisabled = $(container).attr('data-sensei-disabled');
	
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
			
			select_option = $(container).attr('data-sensei-dropdown');
			select_option = select_option.replace(/\s+/g, '');
			select_option = select_option.split(',');
			row.type = 'dropdown';
			row.source = select_option;
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

function initial_grid(textareas, max, container, index)
{
	var headers = get_headers($(container));
	var max_num = parseInt($(max).val());  
	var scale = {};
	var new_grid = [];
	var grid_id_name = $(container).attr('id');
	
	for(var x = 0; x < max_num; x++)
	{
		var row = [];
		
		for(var y = 0; y < headers.length; y++)
		{
			if(grid_id_name == 'seasons_chart')
			{
				if((y+1) == headers.length)
				{
					row.push(grid_id_name+'_'+(x+1));
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
		new_grid.push(row);
	}
	scale[grid_id_name] = new_grid;
	
	$(textareas).text(JSON.stringify(scale));
	
	return scale;
}

function update_grid(textareas, data, container, index)
{
	var grid_id_name = $(container).attr('id');
	var textareas_data = {};
	
	try
	{
		textareas_data = JSON.parse($(textareas).text());
	}
	catch(e)
	{
		textareas_data = {};
	}	
	
	textareas_data[grid_id_name] = data;
	return textareas_data;
}

function add_row_id(data, container)
{
	var output = [];
	var grid_id_name = $(container).attr('id');
	
	if(data)
	{
		for(var x = 0; x < (data).length; x++)
		{
			var row = [];
			
			for(var y = 0; y < data[x].length; y++)
			{
				var item = [];
				
				if(grid_id_name == 'seasons_chart')
				{
					if((y+1) == data[x].length)
					{
						item = grid_id_name+'_'+(x+1);
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

function build_season_grids()
{
	var data = $('#package_seasons_chart').text();
	var num_seasons = parseInt($('[name="package_num_seasons"]').val());
	
	var pre_render = $('<div>');

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
				
		if(max > num_seasons)
		{
			max = num_seasons;
		}
				
		for(var x = 0; x < max; x++)
		{
			var is_row_ready = true;
			
			for(var y = 0; y < data[x].length; y++)
			{
				if(data[x][y] === null || data[x][y] == '')
				{
					is_row_ready = false;
				}
			}
			
			if(is_row_ready === true)
			{
				var last_cell = data[x][data[x].length - 1];
				var occupancy_chart = $('#occupancy_chart').clone();
				var occupancy_chart_wrap = $('<div>').addClass('hot-container');
				$(occupancy_chart).attr({'id': $(occupancy_chart).attr('id')+last_cell, 'data-sensei-container': $(occupancy_chart).attr('id')+last_cell});				
				$(occupancy_chart_wrap).html(occupancy_chart);
				$(pre_render).append($('<h3></h3>').text($('#accommodation').text()+': '+data[x][0]+' ('+data[x][4]+')'));
				$(pre_render).append(occupancy_chart_wrap);	
			}

		}
		
		$('#special_seasons').html(pre_render);
	}
}
function submit_save_post()
{
	var this_form = $('#post');
	
	$('#package_package_type').add('#package_payment').add('#package_auto_booking').change(function(){
		
		if(parseInt(dy_wp_version()) < 5)
		{
			$('#post').submit();
		}
		else
		{
			wp.data.dispatch('core/editor').savePost();
			
			setTimeout(function(){
				var reloader = setInterval(function(){
					if(wp.data.select('core/editor').didPostSaveRequestSucceed())
					{
						clearInterval(reloader);
						window.location.reload(true);
					}
				}, 1000);				
			}, 1000);
		}
	});
}
})( jQuery );