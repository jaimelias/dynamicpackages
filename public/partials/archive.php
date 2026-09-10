<?php

/**
 * Dynamic Packages archive template.
 *
 * Builds the package query, applies archive filters and renders
 * the package grid, pagination and ItemList structured data.
 */

if (!defined('WPINC')) {
	exit;
}

$GLOBALS['dy_is_archive'] = true;

$posts_per_page = (int) ($dis_imp ?? 12);
$paged = (int) current_page_number();

$today_timestamp = strtotime('today midnight');

$today = date('Y-m-d', $today_timestamp);
$tomorrow = date('Y-m-d', strtotime('+1 day', $today_timestamp));
$week = date('Y-m-d', strtotime('+7 days', $today_timestamp));
$month = date('Y-m-d', strtotime('+30 days', $today_timestamp));

$is_package_taxonomy = is_tax(['package_location', 'package_category']);

$args = [
	'post_type' => 'packages',
	'orderby' => 'menu_order',
	'order' => 'ASC',
	'post_parent' => 0,
	'meta_query' => [],
	'paged' => $paged,
];

if ($is_package_taxonomy) {
	$term = get_queried_object();

	$args['tax_query'] = [
		[
			'taxonomy' => get_query_var('taxonomy'),
			'field' => 'term_id',
			'terms' => $term->term_id,
		],
	];
} else {
	$args['tax_query'] = [];

	/*
	 * Keyword search.
	 */
	if (!empty($_GET['keywords']) && is_string($_GET['keywords'])) {
		$keywords = sanitize_text_field(wp_unslash($_GET['keywords']));

		if (!empty($keywords)) {
			$args['search_tax_query'] = true;
			$args['s'] = $keywords;
		}
	}

	/*
	 * Category filter.
	 */
	if (isset($cat_imp) && !empty($cat_imp) && $cat_imp !== 'any') {
		$args['tax_query'][] = [
			'taxonomy' => 'package_category',
			'field' => 'slug',
			'terms' => $cat_imp,
		];
	}

	/*
	 * Location filter.
	 */
	if (isset($loc_imp) && !empty($loc_imp) && $loc_imp !== 'any') {
		$args['tax_query'][] = [
			'taxonomy' => 'package_location',
			'field' => 'slug',
			'terms' => $loc_imp,
		];
	}

	$sort = $sort_imp ?? null;

	/*
	 * Ordering.
	 */
	$sort_args = match ($sort) {
		'new' => [
			'orderby' => 'date',
			'order' => 'DESC',
		],
		'low' => [
			'orderby' => 'meta_value_num',
			'meta_key' => 'package_starting_at',
			'order' => 'ASC',
		],
		'high' => [
			'orderby' => 'meta_value_num',
			'meta_key' => 'package_starting_at',
			'order' => 'DESC',
		],
		default => [],
	};

	if ($sort_args !== []) {
		$args = [
			...$args,
			...$sort_args,
		];
	}

	/*
	 * Date filters.
	 *
	 * Existing behavior is intentionally preserved:
	 * - today: exact current date
	 * - tomorrow: today through tomorrow
	 * - week: today through +7 days
	 * - month: today through +30 days
	 */
	if ($sort === 'today') {
		$args['meta_query'][] = [
			'key' => 'package_date',
			'type' => 'DATE',
			'value' => $today,
			'compare' => '=',
		];
	} else {
		$date_limit = match ($sort) {
			'tomorrow' => $tomorrow,
			'week' => $week,
			'month' => $month,
			default => null,
		};

		if ($date_limit !== null) {
			$args['meta_query'][] = [
				'key' => 'package_date',
				'type' => 'DATE',
				'value' => $today,
				'compare' => '>=',
			];

			$args['meta_query'][] = [
				'key' => 'package_date',
				'type' => 'DATE',
				'value' => $date_limit,
				'compare' => '<=',
			];
		}
	}

	if (count($args['tax_query']) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}
}

/*
 * Hide packages explicitly marked as package_display = 1.
 */
$args['meta_query'][] = [
	'key' => 'package_display',
	'value' => '1',
	'compare' => '!=',
];

$args['posts_per_page'] = $posts_per_page;

$archive_query = new WP_Query($args);

$break_md = '1-1';
$break_lg = '1-1';

if (isset($col_imp) && isset($col_imp[0])) {
	$cols = (int) $col_imp[0];
	$cols_md = $cols === 3 ? 1 : 2;
} else {
	$cols = 3;
	$cols_md = 3;
}

$itemlist_elements = [];
?>

<?php if ($is_package_taxonomy): ?>
	<?php
	$term = get_queried_object();
	$term_description = $term instanceof WP_Term
		? $term->description
		: '';

	$term_description = do_shortcode($term_description);

	$parsedown = new Parsedown();
	$term_description = $parsedown->text($term_description);
	?>

	<hr/>

	<div class="bottom-20 large">
		<?php echo $term_description; ?>
	</div>

	<?php do_action('dy_package_filter_form'); ?>
<?php endif; ?>

<div id="dy_archive" class="dy_archive">

	<div class="pure-g gutters">

		<link itemprop="url" href="<?php the_permalink(); ?>" />

		<?php if ($archive_query->have_posts()): ?>

			<?php
			$count = 0;
			$position_counter = 1;
			?>

			<?php while ($archive_query->have_posts()): ?>

				<?php
				$archive_query->the_post();

				global $post;

				dy_utilities::update_package_date_in_db($post->ID);

				$package_code = package_field('package_trip_code');
				$package_code = $package_code ?: 'ID' . $post->ID;

				$starting_at = dy_utilities::starting_at_archive();
				$starting_at = $starting_at > 0 ? $starting_at : 0;

				$itemlist_elements[] = [
					'@type' => 'ListItem',
					'position' => $position_counter++,
					'url' => get_permalink(),
					'name' => $post->post_title,
				];
				?>

				<div
					class="bottom-40 pure-u-1 pure-u-sm-1-1 pure-u-md-1-<?php echo esc_attr((string) $cols_md); ?> pure-u-lg-1-<?php echo esc_attr((string) $cols); ?>"
				>
					<div class="padding-10 dy_package">

						<div class="pure-g gutters">

							<div
								class="pure-u-1 pure-u-md-<?php echo esc_attr($break_md); ?> pure-u-lg-<?php echo esc_attr($break_lg); ?>"
							>

								<?php if (has_post_thumbnail()): ?>
									<div class="dy_thumbnail relative text-center">

										<a
											data-starting-at="<?php echo esc_attr((string) $starting_at); ?>"
											title="<?php echo esc_attr($post->post_title); ?>"
											href="<?php the_permalink(); ?>"
										>
											<?php
											the_post_thumbnail(
												'thumbnail',
												[
													'class' => 'img-responsive',
												]
											);
											?>
										</a>

										<?php do_action('dy_show_event_date'); ?>
										<?php do_action('dy_show_badge'); ?>

									</div>
								<?php endif; ?>

							</div>

							<div
								class="pure-u-1 pure-u-md-<?php echo esc_attr($break_md); ?> pure-u-lg-<?php echo esc_attr($break_lg); ?>"
							>

								<?php if (!empty($package_code)): ?>
									<div class="hide-sm bottom-10 text-right uppercase light small text-muted">
										<?php echo esc_html($package_code); ?>
									</div>
								<?php endif; ?>

								<div class="dy_title_h3">
									<h3 class="small">

										<a
											data-starting-at="<?php echo esc_attr((string) $starting_at); ?>"
											title="<?php echo esc_attr($post->post_title); ?>"
											href="<?php the_permalink(); ?>"
										>
											<span><?php echo esc_html($post->post_title); ?></span>
										</a>

									</h3>
								</div>

								<div class="dy_reviews small bottom-10">
									<?php echo apply_filters('dy_reviews_stars', $post->ID); ?>
								</div>

								<div class="dy_pad bottom-10 semibold">
									<?php echo esc_html(dy_utilities::show_duration(true)); ?>
								</div>

								<?php if (has_excerpt()): ?>
									<p
										class="bottom-10 small <?php echo get_option('dy_archive_hide_excerpt') ? 'hidden' : 'hide-sm'; ?>"
									>
										<?php echo get_the_excerpt(); ?>
									</p>
								<?php endif; ?>

								<div class="small">
									<?php echo apply_filters('dy_details', false); ?>
								</div>

								<?php if ($starting_at): ?>
									<div class="dy_pad bottom-10">

										<?php echo esc_html__('Starting at', 'dynamicpackages'); ?>

										<span class="strong">
											<?php
											echo esc_html(
												wrap_money_rounded(
													round($starting_at)
												)
											);
											?>
										</span>

										<small class="text-muted">
											<?php
											echo esc_html(
												apply_filters('dy_price_type', false)
											);
											?>
										</small>

									</div>
								<?php endif; ?>

								<?php do_action('dy_edit_link'); ?>

							</div>

						</div>

					</div><!-- .padding-10 -->
				</div><!-- .col -->

				<?php $count++; ?>

				<?php
				if (
					$count === $cols
					&& ($archive_query->current_post + 1) !== $archive_query->post_count
				):
					$count = 0;
					?>
					</div>
					<div class="pure-g gutters">
				<?php endif; ?>

			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>

		</div><!-- .pure-g -->

		<?php else: ?>

			<div class="pure-u-1-1">
				<p><?php echo esc_html__('Not found', 'dynamicpackages'); ?>.</p>
			</div>

		</div><!-- .pure-g -->

		<?php endif; ?>

</div><!-- .dy_archive -->


<?php if (
	isset($pagination_imp)
	|| $is_package_taxonomy
): ?>

	<?php
	do_action(
		'dy_archive_pagination',
		[
			'archive_query' => $archive_query,
			'posts_per_page' => $posts_per_page,
		]
	);
	?>

<?php endif; ?>


<?php
if ($itemlist_elements !== []) {
	$ld = [
		'@context' => 'https://schema.org',
		'@type' => 'ItemList',
		'name' => 'Product listing',
		'numberOfItems' => count($itemlist_elements),
		'itemListElement' => $itemlist_elements,
	];

	printf(
		'<script type="application/ld+json" id="json_ld_item_list">%s</script>',
		wp_json_encode(
			$ld,
			JSON_UNESCAPED_SLASHES
			| JSON_UNESCAPED_UNICODE
			| JSON_PRETTY_PRINT
		)
	);
}
?>