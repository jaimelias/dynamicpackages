<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_LD_JSON
{
	private static $cache = [];

	function __construct($reviews)
	{
		$this->reviews = $reviews;
		add_action('get_header', array($this, 'get_header'));
	}
	
	public function get_header()
	{
		add_filter('minimal_ld_json', array($this, 'product_ld_json'));
		add_filter('minimal_ld_json', array($this, 'website_ld_json'));
	}

	public function website_ld_json($arr = []) {
		global $polylang;

		$current_language = current_language();
		$default_language = default_language();
		$package_main = (int) get_option('dy_breadcrump') ?? get_option('page_on_front');

		
		if(isset($polylang))
		{	
			if($current_language !== $default_language)
			{
				$package_main = pll_get_post($package_main, $current_language);

			}
		}

		$package_url = get_permalink($package_main);

		$website = [
			'@context' => 'http://schema.org',
			'@type' => 'WebSite',
			'url' => home_lang(),
			'name' => get_bloginfo('name'),
			'potentialAction' => [
				'@type' => 'SearchAction',
				'target' => "{$package_url}?keywords={search_term}",
				'query' => 'required name=search_term',
			],
		];

		$arr['website'] = $website;

		return $arr;
	}

	public function product_ld_json($arr = [])
	{
		// only build on single "packages"
		if (!is_singular('packages')) return $arr;

		global $post;

		// precompute commonly used values
		$site_name   = get_bloginfo('name');
		$url         = get_the_permalink();
		$title       = get_the_title();
		$has_thumb   = has_post_thumbnail();
		$thumb_url   = $has_thumb ? get_the_post_thumbnail_url() : null;
		$rating_val  = (float) $this->reviews->get_rating($post->ID);
		$review_cnt  = (int) get_comments_number();
		$starting_at = (float) money(dy_utilities::starting_at($post->ID));
		$schema      = (int) package_field('package_schema');
		$is_valid    = dy_validators::is_valid_schema($post->ID);

		if (!$is_valid) return $arr;

		// base offers (mutated per context)
		$offers = [
			'@type' => 'Offer',
			'priceCurrency' => 'USD',
			'price' => $starting_at,
			'url' => $url,
			'availability' => 'https://schema.org/InStock',
			'validFrom' => esc_html(date('Y-m-d', dy_strtotime('now')))
		];

		$offers['hasMerchantReturnPolicy'] = [
			'@type' => 'MerchantReturnPolicy',
			'merchantReturnLink' => "{$url}#package_terms_conditions_list",
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'returnMethod' => 'https://schema.org/ReturnByMail',
			'returnFees' => 'https://schema.org/FreeReturn',
			'merchantReturnDays' => 30,
		];

		// aggregate rating (conditionally attached below)
		$aggregateRating = [
			'@type' => 'aggregateRating',
			'ratingValue' => esc_html($rating_val),
			'reviewCount' => esc_html($review_cnt),
		];

		// build reviews
		$reviews = [];
		$comments = $this->reviews->get_comments($post->ID);
		foreach ($comments as $comment) {
			$reviews[] = [
				'@type' => 'Review',
				'datePublished' => esc_html(date('Y-m-d', strtotime($comment->comment_date))),
				'description' => esc_html($comment->comment_content),
				'author' => [
					'@type' => 'Person',
					'name' => esc_html($comment->comment_author),
				],
				'reviewRating' => [
					'@type' => 'Rating',
					'bestRating' => '5',
					'ratingValue'=> get_comment_meta($comment->comment_ID, 'dy_rating', true),
				],
			];
		}

		if ($schema === 1) {
			// Product
			$product = [
				'@context' => 'https://www.schema.org',
				'@type' => 'Product',
				'brand' => [
					'@type' => 'Brand',
					'name' => $site_name,
				],
				'name' => $title,
				'sku' => md5(package_field('package_trip_code')),
				'url' => $url,
			];

			if (!empty($post->post_excerpt)) {
				$product['description'] = $post->post_excerpt;
			}
			if ($has_thumb) {
				$product['image'] = $thumb_url;
			}
			if ($rating_val > 0) {
				$product['aggregateRating'] = $aggregateRating;
			}

			// product-specific price validity
			$offers_product = $offers;
			$offers_product['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
			$product['offers'] = $offers_product;

			if (!empty($reviews)) {
				$product['review'] = $reviews;
			}

			$arr['product'] = $product;

		} else {
			// Events
			$events = apply_filters('dy_event_arr', []);
			$event_max = min(30, count($events));
			$event_arr = [];

			$duration = (int) package_field('package_duration');
			$unit     = package_field('package_length_unit');
			$start_hr = package_field('package_start_hour');
			$start_address = package_field('package_start_address');
			$site_url = get_bloginfo('url');

			for ($x = 0; $x < $event_max; $x++) {
				$event_date        = $events[$x] . ' ' . $start_hr;
				$event_ts          = strtotime($event_date);
				$event_date_name   = date_i18n('M d', $event_ts);
				$event_date_format = date_i18n('Y-m-d\TH:i', $event_ts);

				// compute end date by unit
				if ($unit == 0) {                // minutes
					$end_ts = $event_ts + (60 * $duration);
				} elseif ($unit == 1) {          // hours
					$end_ts = $event_ts + (3600 * $duration);
				} elseif ($unit == 4) {          // weeks
					$end_ts = $event_ts + (7 * 24 * 3600 * $duration);
				} else {                          // days (default)
					$end_ts = strtotime("+ {$duration} days", $event_ts);
				}

				$event = [
					'@context' => 'https://www.schema.org',
					'@type' => 'Event',
					'name' => esc_html($title . ' - ' . $event_date_name),
					'startDate' => esc_html($event_date_format),
					'endDate' => esc_html(date('Y-m-d\TH:i', $end_ts)),
					'description' => $post->post_excerpt,
					'organizer' => [
						'name' => $site_name,
						'url' => $site_url,
					],
					'performer' => $site_name,
					'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
					'eventStatus' => 'https://schema.org/EventScheduled',
				];

				if ($rating_val > 0) {
					$event['aggregateRating'] = $aggregateRating;
				}
				if ($has_thumb) {
					$event['image'] = $thumb_url;
				}

				$event_offers = $offers;
				$event_offers['priceValidUntil'] = esc_html($events[$x]);
				$event['offers'] = $event_offers;

				$event['location'] = [
					'@type' => 'Place',
					'name' => $site_name,
					'address' => esc_html($start_address),
				];

				$event_arr[] = $event;
			}

			$arr['event'] = $event_arr;
		}


		return $arr;
	}	

}

?>