<?php

	$GLOBALS['dy_is_archive'] = true;
	$posts_per_page = isset($dis_imp) ? $dis_imp : 12;
	$paged = current_page_number();
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
			
		if(isset($_GET['keywords']))
		{
			if(!empty($_GET['keywords']))
			{
				$args['search_tax_query'] =  true;
				$args['s'] =  sanitize_text_field($_GET['keywords']);
			}
		}
		
		if(isset($cat_imp))
		{
			if(!empty($cat_imp ) && $cat_imp != 'any')
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
			if(!empty($loc_imp) && $loc_imp != 'any')
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
					'value' => $week,
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
	<hr/>
	<div class="bottom-20 large"><?php echo $term_description; ?> </div>
	<?php do_action('dy_package_filter_form');?>
<?php endif; ?>

	<div id="dy_archive" class="dy_archive">
	
	<div class="pure-g gutters">
	<link itemprop="url" href="<?php the_permalink(); ?>" />
	<?php if ( $archive_query->have_posts() ) :?>
				
		<?php $count=0; ?>
		<?php while ( $archive_query->have_posts() ) : $archive_query->the_post(); global $post; ?>

		<?php
			dy_utilities::update_package_date_in_db($post->ID);
			$package_code = package_field('package_trip_code');
			$package_code = (!empty($package_code)) ? $package_code : 'ID'.$post->ID;
			$starting_at = (dy_utilities::starting_at_archive() > 0) ? dy_utilities::starting_at_archive() : 0;
		?>

			<div class="bottom-40 pure-u-1 pure-u-sm-1-1 pure-u-md-1-<?php echo $cols_md; ?> pure-u-lg-1-<?php echo $cols; ?>" <?php if(dy_validators::is_valid_schema($post->ID)): ?> itemscope itemtype="https://schema.org/Product" <?php endif; ?>>
				
				
				<?php if(dy_validators::is_valid_schema($post->ID)): ?>
					<link itemprop="url" href="<?php the_permalink(); ?>" />
					<meta itemprop="sku" content="<?php echo esc_attr(md5(package_field( 'package_trip_code' ))); ?>" />
				<?php endif; ?>
				
				<div class="padding-10 dy_package">
					<div class="pure-g gutters">
						<div class="pure-u-1 pure-u-md-<?php esc_html_e($break_md); ?> pure-u-lg-<?php esc_html_e($break_lg); ?>">
							<?php if(has_post_thumbnail()): ?>
							<div class="dy_thumbnail relative text-center">
								<a data-starting-at="<?php echo esc_attr($starting_at); ?>" title="<?php echo esc_attr($post->post_title); ?>" href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumbnail', array('class' => 'img-responsive', 'itemprop' => 'image')); ?></a>
								<?php do_action('dy_show_event_date'); ?>
								<?php do_action('dy_show_badge'); ?>
							</div>
							<?php endif;?>
						</div>	
							
						<div class="pure-u-1 pure-u-md-<?php esc_html_e($break_md); ?> pure-u-lg-<?php esc_html_e($break_lg); ?>">
						
							<?php if(!empty($package_code)): ?>
								<div class="hide-sm bottom-10 text-right uppercase light small text-muted"><?php echo esc_html($package_code); ?></div>
							<?php endif; ?>								
						
							<div class="dy_title_h3">
								<h3 class="small"><a data-starting-at="<?php echo esc_attr($starting_at); ?>" title="<?php echo esc_attr($post->post_title); ?>" itemprop="url" href="<?php the_permalink(); ?>"><span itemprop="name"><?php esc_html_e($post->post_title); ?></span></a></h3>
							</div>
							
							
							
							<div class="dy_reviews small bottom-10">
								<?php echo apply_filters('dy_reviews_stars', $post->ID); ?>
							</div>

							<div class="dy_pad bottom-10 semibold">
									<?php esc_html_e(dy_utilities::show_duration(true)); ?>
							</div>

							<?php if(has_excerpt()): ?>
								<p itemprop="description" class="bottom-10 small <?php echo (get_option('dy_archive_hide_excerpt')) ? 'hidden': 'hide-sm' ?>"><?php echo (get_the_excerpt()); ?></p>
							<?php endif; ?>
							
							<div class="small"><?php echo apply_filters('dy_details', false); ?></div>
							
							
							<?php if($starting_at): ?>
								<div class="dy_pad bottom-10">
									<span class="tp_starting_at semibold" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
									<link itemprop="availability" href="https://schema.org/InStock" />
									<link itemprop="url" href="<?php the_permalink(); ?>" />
									<meta itemprop="priceValidUntil" content="<?php echo esc_attr(date('Y-m-d', strtotime('+1 year'))); ?>" />
									<meta itemprop="priceCurrency" content="<?php echo esc_attr(currency_name()); ?>" />
									<?php echo (esc_html__('Starting at', 'dynamicpackages')); ?> <span itemprop="price" class="strong" content="<?php echo esc_attr($starting_at);?>"><?php echo esc_html(wrap_money_full($starting_at));?></span>
									</span> <small class="text-muted"> <?php esc_html_e(apply_filters('dy_price_type', false));?></small>
								</div>
							<?php endif;?>

							<?php do_action('dy_edit_link'); ?>
							
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
		
		<div class="pure-u-1-1"><p><?php echo (esc_html__('Not found', 'dynamicpackages')); ?>.</p></div></div>
		
	<?php endif; ?>
	</div><!-- .tp_grid -->
	
<?php if(isset($pagination_imp) || is_tax('package_location') || is_tax('package_category')): ?>	
	<?php do_action('dy_archive_pagination', array('archive_query' => $archive_query, 'posts_per_page' => $posts_per_page)); ?>
<?php endif; ?>
