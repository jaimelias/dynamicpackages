<?php

	$posts_per_page = isset($dis_imp) ? $dis_imp : 12;
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$today = date("Y-m-d");
	$tomorrow = date("Y-m-d", strtotime('tomorrow midnight'));
	$week = date('Y-m-d', strtotime('+7 day', strtotime('today midnight')));
	$month = date('Y-m-d', strtotime('+30 day', strtotime('today midnight')));

	$args = array(
		'post_type' => 'packages',
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'post_parent' => 0,
		'meta_query' => array(),
		'paged' => $paged
	);
	
	//yesterday exclude
	$filter_yesterday = array(
		'key' => 'package_event_date',
		'type' => 'DATE',
		'value' => date("Y-m-d", strtotime('yesterday')),
		'compare' => '>'
	);

	//troubleshoot if null
	$filter_null = array(
		'value' => '',
		'key' => 'package_event_date',
		'compare' => '='
	);

	//troubleshoot if not exist in old versions
	$filter_not_exist = array(
		'value' => '',
		'key' => 'package_event_date',
		'key' => 'NOT EXISTS'
	);	
	
	array_push($args['meta_query'], array('relation' => 'OR', $filter_yesterday, $filter_null, $filter_not_exist));	
		
	if(is_tax('package_location') || is_tax('package_category'))
	{
		global $wp_query;
		$term = $wp_query->get_queried_object();
		$args['tax_query'] = array(
			array(
				'taxonomy' => get_query_var( 'taxonomy' ), 
				'field' => 'term_id', 
				'terms' => $term->term_id
			)
		);
	}
	else
	{
		$args['tax_query'] = array();
			
		if(isset($_GET['package_search']))
		{
			if($_GET['package_search'] != '')
			{
				$args['search_tax_query'] =  true;
				$args['s'] =  sanitize_text_field($_GET['package_search']);
			}
		}
		
		if(isset($cat_imp))
		{
			if($cat_imp != 'any')
			{
				$cat_imp_args = array(
					'taxonomy' => 'package_category',
					'field' => 'slug',
					'terms' => $cat_imp
				);
	
				array_push($args['tax_query'], $cat_imp_args);					
			}
		}
		
		if(isset($loc_imp))
		{
			if($loc_imp != 'any')
			{
				$loc_imp_args = array(
					'taxonomy' => 'package_location',
					'field' => 'slug',
					'terms' => $loc_imp
				);

				array_push($args['tax_query'], $loc_imp_args);					
			}
		}

		if(isset($sort_imp))
		{
			if($sort_imp == 'new')
			{
				$args['orderby'] = 'date';
				$args['order'] = 'DESC';				
			}
			else if($sort_imp == 'low')
			{
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = 'package_starting_at';
				$args['order'] = 'ASC';					
			}
			else if($sort_imp == 'high')
			{
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = 'package_starting_at';
				$args['order'] = 'DESC';					
			}
			else if($sort_imp == 'today')
			{
				//today
				$filter_today = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $today,
					'compare' => '='
				);
 
				array_push($args['meta_query'], $filter_today);
			}
			else if($sort_imp == 'tomorrow')
			{
				
				//today
				$filter_today = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $today,
					'compare' => '>='
				);				
				
				//tomorrow start
				$filter_tomorrow = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $tomorrow,
					'compare' => '<='
				);
			
				array_push($args['meta_query'], $filter_today, $filter_tomorrow);
			}			
			else if($sort_imp == 'week')
			{
				//today
				$filter_today = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $today,
					'compare' => '>='
				);
				
				//+7 days
				$filter_week = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => '$week',
					'compare' => '<='
				);

				array_push($args['meta_query'], $filter_today, $filter_week);
			}
			else if($sort_imp == 'month')
			{
				//today
				$filter_today = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $today,
					'compare' => '>='
				);
				
				//+30 days
				$filter_month = array(
					'key' => 'package_date',
					'type' => 'DATE',
					'value' => $month,
					'compare' => '<='
				);

				array_push($args['meta_query'], $filter_today, $filter_month);
			}
		}		

		if(is_array($args['tax_query']))
		{
			if(count($args['tax_query']) > 1)
			{
				$args['tax_query']['relation'] = 'AND';
			}				
		}
	}


$display = array(
	'key' => 'package_display',
	'value' => '1',
	'compare' => '!='
);	

array_push($args['meta_query'], $display);

$args['posts_per_page'] = $posts_per_page;		
	
$archive_query = new WP_Query( $args );

$break_sm = '1-1';
$break_md = '1-1';
$break_lg = '1-1';

if(isset($col_imp))
{
	$cols = $col_imp[0];
	$cols_md = 2;
	
	if(intval($cols) > 2)
	{	
		if(intval($cols) == 3)
		{
			$cols_md = 1;
		}			
	}	
}
else
{
	$cols_md = 3;
	$cols = 3;
}


?>

<?php if(is_tax('package_location') || is_tax('package_category')): ?>
	<?php $Parsedown = new Parsedown(); ?>
	<?php $term_description = get_term(get_queried_object()->term_id)->description; ?>
	<?php $term_description = do_shortcode($term_description); ?>
	<?php $term_description = $Parsedown->text($term_description); ?>
	<div class="bottom-20"><?php echo $term_description; ?> </div>
	<?php echo dy_Shortcodes::package_filter_form();?>
<?php endif; ?>

	<div id="dy_archive" class="dy_archive">
	
	
	
	<div class="pure-g gutters">
	<link itemprop="url" href="<?php the_permalink(); ?>" />
	<?php if ( $archive_query->have_posts() ) :?>
				
		<?php $count=0; ?>
		<?php while ( $archive_query->have_posts() ) : $archive_query->the_post(); global $post; ?>		

		<?php
			dy_Public::event_date_update($post->ID);
			$package_code = package_field( 'package_trip_code' );
		?>

			<div class="bottom-40 pure-u-1 pure-u-sm-1-1 pure-u-md-1-<?php echo $cols_md; ?> pure-u-lg-1-<?php echo $cols; ?>" <?php if(dy_Validators::is_valid_schema($post->ID)): ?> itemscope itemtype="http://schema.org/Product" <?php endif; ?>>
				
				
				<?php if(dy_Validators::is_valid_schema($post->ID)): ?>
					<link itemprop="url" href="<?php the_permalink(); ?>" />
					<meta itemprop="brand" content="<?php bloginfo('name'); ?>" />
					<meta itemprop="sku" content="<?php echo esc_html(md5(package_field( 'package_trip_code' ))); ?>" />
					<meta itemprop="gtin8" content="<?php echo esc_html(substr(md5(package_field( 'package_trip_code' )), 0, 8)); ?>" />
				<?php endif; ?>
				
				<div class="padding-10 dy_package">
					<div class="pure-g gutters">
						<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-<?php echo esc_html($break_md); ?> pure-u-lg-<?php echo esc_html($break_lg); ?>">
							<?php if(has_post_thumbnail()): ?>
							<div class="dy_thumbnail relative text-center">
								<a title="<?php echo esc_html($post->post_title); ?>" href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumbnail', array('class' => 'img-responsive', 'itemprop' => 'image')); ?></a>
								<?php dy_Public::show_event_date(); ?>
								<?php dy_Public::show_badge(); ?>
							</div>
							<?php endif;?>
						</div>	
							
						<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-<?php echo esc_html($break_md); ?> pure-u-lg-<?php echo esc_html($break_lg); ?>">
						
							<?php if($package_code != ''): ?>
								<div class="hide-sm bottom-10 text-right uppercase light small text-muted"><?php echo esc_html(__('ID', 'dynamicpackages')).esc_html($package_code); ?></div>
							<?php endif; ?>								
						
							<div class="dy_package_title_h3">
								<h3><a title="<?php echo esc_html($post->post_title); ?>" itemprop="url" href="<?php the_permalink(); ?>"><span itemprop="name"><?php echo esc_html($post->post_title); ?></span></a></h3>
							</div>
							
							
							<div class="dy_reviews small bottom-10">
							<?php dy_Reviews::stars($post->ID); ?>
							</div>

							<?php if(!dy_Validators::is_package_transport()) : ?>
								<div class="dy_pad bottom-10 semibold">
									<?php echo esc_html(dy_Public::show_duration()); ?>
								</div>
							<?php endif; ?>

							<?php if(has_excerpt()): ?>
								<p itemprop="description" class="bottom-10 small hide-sm"><?php echo (get_the_excerpt()); ?></p>
							<?php endif; ?>
							
							<div class="small hide-sm"><?php echo apply_filters('dy_package_details', null); ?></div>
							
							
							<?php if(dy_utilities::starting_at_archive() > 0): ?>
								<div class="dy_pad bottom-10">
									<span class="tp_starting_at semibold" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
									<link itemprop="availability" href="http://schema.org/InStock" />
									<link itemprop="url" href="<?php the_permalink(); ?>" />
									<meta itemprop="priceValidUntil" content="<?php echo esc_html(date('Y-m-d', strtotime('+1 year'))); ?>" />
									<meta itemprop="priceCurrency" content="<?php echo esc_html(dy_utilities::currency_name()); ?>" />
									<?php echo esc_html(__('Starting at', 'dynamicpackages')); ?> <?php echo esc_html(dy_utilities::currency_symbol()); ?><span itemprop="price" class="strong" content="<?php echo esc_html(dy_utilities::starting_at_archive());?>"><?php echo esc_html(number_format(dy_utilities::starting_at_archive(), 0, '.', ','));?></span>
									</span> <small class="text-muted"> <?php echo esc_html(dy_Public::price_type());?></small>
								</div>
							<?php endif;?>
												

							
							<div class="text-right strong uppercase hide-sm">
								<a href="<?php the_permalink(); ?>" title="<?php echo esc_html($post->post_title); ?>"><?php echo esc_html(__('More details', 'dynamicpackages')); ?> <span class="large"><span class="large"><i class="fas fa-chevron-circle-right"></i></span></span></a>
							</div>					
							
							
						</div>	
					</div>	
					
				</div>	<!-- .padding-10 -->
			</div><!-- .col -->
			
			<?php $count++; ?>
			
			<?php if (($count == $cols || $archive_query->found_posts==0) && (($archive_query->current_post +1) != ($archive_query->post_count))  ) : ?>
				</div><div class="pure-g gutters">
				<?php $count=0; ?>
			<?php endif; ?>
			
		<?php endwhile; wp_reset_postdata(); ?>
			</div><!-- .pure-g -->
		<?php else: ?>
		
		<div class="pure-u-1-1"><p><?php echo esc_html(__('Not found', 'dynamicpackages')); ?>.</p></div></div>
		
	<?php endif; ?>
	</div><!-- .tp_grid -->
	
<?php if(isset($pagination_imp) || is_tax('package_location') || is_tax('package_category')): ?>	
	<?php dynamicpackages_Forms::pagination($archive_query, $posts_per_page); ?>
<?php endif; ?>
