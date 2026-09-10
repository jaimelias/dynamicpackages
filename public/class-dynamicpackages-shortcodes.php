<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Shortcodes {
	
	public function __construct()
	{
		$this->plugin_dir_path = plugin_dir_path(__DIR__);
		$this->init();
	}
	
	public function init()
	{
		add_shortcode('packages', array($this, 'package_shortcode_full'));
		add_shortcode('package_filter', array($this, 'package_filter'));
		add_shortcode('package_contact', array($this, 'contact'));
		add_shortcode('package_categories', array($this, 'categories'));
		add_shortcode('package_locations', array($this, 'locations'));
		add_action('dy_contact_inquiry_textarea', array($this, 'inquiry_textarea'));
	}

	public function contact($content = null)
	{
		global $post;
			
        if(($post instanceof WP_Post) && has_shortcode( $post->post_content, 'package_contact')) {

			ob_start();
			require_once $this->plugin_dir_path.'public/partials/quote-form.php';
			$output = ob_get_contents();
			ob_end_clean();
			
		}

		
		return $output;		
	}

	public function inquiry_textarea()
	{
		if(!is_singular('packages'))
		{
			?>
			<p>
				<label for="inquiry"><?php echo esc_html(__('Message', 'dynamicpackages')); ?></label>
				<textarea id="inquiry" name="inquiry" class="required"></textarea>
			</p>
			<?php
		}
	}
	
	public function package_filter($content = '')
	{
		return apply_filters('dy_package_filter_form_cb', $content);
	}	
	
	/**
	 * Renders the packages shortcode using normalized archive filters.
	 *
	 * Taxonomy filters are represented internally as arrays of term slugs.
	 * The values "", "any", and invalid input are normalized to null.
	 *
	 * GET parameters override shortcode attributes, including "any", which
	 * explicitly clears the corresponding shortcode filter.
	 *
	 * @param mixed  $attr    Shortcode attributes.
	 * @param string $content Shortcode content.
	 *
	 * @return string Rendered packages archive HTML.
	 */
	public function package_shortcode_full(mixed $attr, string|null $content = ''): string
	{
		global $polylang;

		$attr = is_array($attr) ? $attr : [];

		$cat_imp = $this->normalize_tax_filter($attr['category'] ?? null);
		$loc_imp = $this->normalize_tax_filter($attr['location'] ?? null);
		$sort_imp = $this->normalize_sort_filter($attr['sortby'] ?? null);

		if (!empty($attr['cols'])) {
			$cols = absint($attr['cols']);

			if ($cols > 0) {
				/*
				* archive.php currently expects $col_imp[0].
				*/
				$col_imp = [$cols];
			}
		}

		if (!empty($attr['display'])) {
			$display = (int) $attr['display'];

			if ($display > 0) {
				$dis_imp = $display;
			}
		}

		if (array_key_exists('pagination', $attr)) {
			if (
				filter_var(
					$attr['pagination'],
					FILTER_VALIDATE_BOOLEAN
				) === true
			) {
				$pagination_imp = true;
			}
		} elseif (
			is_tax('package_category')
			|| is_tax('package_location')
		) {
			$pagination_imp = true;
		} else {
			$package_main = get_option('dy_breadcrump')
				? (int) get_option('dy_breadcrump')
				: (int) get_option('page_on_front');

			if (function_exists('pll_get_post')) {
				$current_language = current_language();
				$default_language = default_language();

				if ($current_language !== $default_language) {
					$package_main = (int) pll_get_post(
						$package_main,
						$current_language
					);
				}
			}

			if (get_dy_id() === $package_main) {
				$pagination_imp = true;
			}
		}

		/*
		* GET values have priority over shortcode attributes.
		*
		* Presence is checked instead of !empty() so ?location=any can
		* intentionally remove a location imposed by the shortcode.
		*/
		if (get_has('location')) {
			$loc_imp = $this->normalize_tax_filter(
				secure_get('location')
			);
		}

		if (get_has('category')) {
			$cat_imp = $this->normalize_tax_filter(
				secure_get('category')
			);
		}

		if (get_has('sort')) {
			$sort_imp = $this->normalize_sort_filter(
				secure_get('sort')
			);
		}

		ob_start();

		require $this->plugin_dir_path . 'public/partials/archive.php';

		return (string) ob_get_clean();
	}

	/**
	 * Normalizes a taxonomy filter into an array of term slugs.
	 *
	 * "any", empty values, and invalid values represent no restriction.
	 * If "any" appears together with other values, "any" wins because it
	 * explicitly means that the taxonomy must not restrict the query.
	 *
	 * @param mixed $value Raw shortcode or GET taxonomy value.
	 *
	 * @return array<string>|null Term slugs, or null when unrestricted.
	 */
	private function normalize_tax_filter(mixed $value): ?array
	{
		if ($value === null || $value === '' || $value === false) {
			return null;
		}

		$values = is_array($value)
			? $value
			: explode(',', (string) $value);

		$terms = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn(mixed $term): string => sanitize_title(
							trim((string) $term)
						),
						$values
					),
					static fn(string $term): bool => $term !== ''
				)
			)
		);

		if (
			$terms === []
			|| in_array('any', $terms, true)
		) {
			return null;
		}

		return $terms;
	}

	/**
	 * Normalizes the archive sort parameter.
	 *
	 * "any", empty values, and unsupported sort values become null.
	 *
	 * @param mixed $value Raw shortcode or GET sort value.
	 *
	 * @return string|null Valid sort identifier, or null for default ordering.
	 */
	private function normalize_sort_filter(mixed $value): ?string
	{
		if (!is_scalar($value)) {
			return null;
		}

		$sort = sanitize_key((string) $value);

		if ($sort === '' || $sort === 'any') {
			return null;
		}

		$allowed = method_exists('dy_utilities', 'sort_by_arr')
			? dy_utilities::sort_by_arr()
			: [
				'new',
				'low',
				'high',
				'today',
				'tomorrow',
				'week',
				'month',
			];

		if (
			!is_array($allowed)
			|| !in_array($sort, $allowed, true)
		) {
			return null;
		}

		return $sort;
	}

	public function categories()
	{
		return dy_utilities::get_tax_list('package_category');
	}
	public function locations()
	{
		return dy_utilities::get_tax_list('package_location');
	}
}
?>