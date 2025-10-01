<?php
if ( ! defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Location_Category {

	private static $cache = [
		'term'        => [], // for translate_term_to_lang_slug: ["$taxonomy:$lang:$key" => "translated-slug"]
		'term_by_id'  => [], // ["$taxonomy:$id" => WP_Term|false]
		'term_by_slug'=> [], // ["$taxonomy:$slug" => WP_Term|false]
	];

	public function __construct() {
		$priority = DY_IS_PACKAGE_PAGE_PRIORITY;

		add_action('wp',              [$this, 'remove_default_canonicals']);
		add_action('wp_head',         [$this, 'location_category_canonical']);
		add_filter('pll_translation_url', [$this, 'location_category_alternate'], $priority, 2);
		add_filter('pre_get_document_title', [$this, 'wp_title'],  $priority);
		add_filter('wp_title',               [$this, 'wp_title'],  $priority);
		add_filter('the_title',              [$this, 'the_title'], $priority);
		add_filter('get_the_excerpt', [$this, 'modify_excerpt'], $priority);
	}



	/* ------------------------------------------------------------
	 * Public API kept identical in behavior
	 * ------------------------------------------------------------ */

	public function get_sort_title_labels() {
		// micro-cache the array so we don't rebuild it every time
		static $labels = null;
		if ( $labels !== null ) return $labels;

		$labels = array(
			'new'      => __('Newest', 'dynamicpackages'),
			'low'      => __('low to high', 'dynamicpackages'),
			'high'     => __('high to low', 'dynamicpackages'),
			'today'    => __('today', 'dynamicpackages'),
			'tomorrow' => __('tomorrow', 'dynamicpackages'),
			'week'     => __('next 7 days', 'dynamicpackages'),
			'month'    => __('next 30 days', 'dynamicpackages'),
		);
		return $labels;
	}

	public function the_title( $title ) {

		if(!in_the_loop()) return $title;

		$q = get_queried_object();

		// --- Taxonomy archives: package_location / package_category ---
		if ( is_tax( 'package_location' ) || is_tax( 'package_category' ) ) {
			$base = single_term_title( '', false );
			$meta = ( $q && isset( $q->term_id ) ) ? (string) get_term_meta( (int) $q->term_id, 'tax_title_modifier', true ) : '';

			// Prefer meta if present; otherwise build default for package_location
			if ( $meta !== '' ) {
				$title = $meta;
			} else {
				$title = $base;

				if ( is_tax( 'package_location' ) ) {
					$title = sprintf(
						/* translators: %s: location name (wrapped in span.linkcolor) */
						__( 'Packages in %s', 'dynamicpackages' ),
						sprintf( '<span class="linkcolor">%s</span>', $title )
					);

					if ( ! empty( $q->parent ) ) {
						$parent = $this->get_term_cached( (int) $q->parent, 'package_location' );
						if ( $parent ) {
							$title = sprintf( '%s, %s', $title, $parent->name );
						}
					}
				}
			}

		// --- Filtered page (?keywords / category / location / sort) ---
		} elseif ( is_page() && dy_validators::validate_category_location() ) {
			$bits   = [ __( 'Find Packages', 'dynamicpackages' ) . ':' ];
			$catStr = '';
			$locStr = '';

			// keywords → “term”
			if ( get_has('keywords') ) {
				$kw = $this->sanitize_keywords( secure_get('keywords') );
				if ( $kw !== '' ) {
					$bits[] = sprintf( '“%s”', $kw );
				}
			}

			// category name
			if ( get_has('category') ) {
				$slug = secure_get('category');
				if ( $slug !== '' && $slug !== 'any' ) {
					$cat = $this->get_term_by_slug_cached( $slug, 'package_category' );
					if ( $cat ) {
						$catStr = $cat->name;
					}
				}
			}
			if ( $catStr !== '' ) {
				$bits[] = $catStr;
			}

			// location name (with parent)
			if ( get_has('location') ) {
				$slug = secure_get('location');
				if ( $slug !== '' && $slug !== 'any' ) {
					$loc = $this->get_term_by_slug_cached( $slug, 'package_location' );
					if ( $loc ) {
						$locStr = $loc->name;
						if ( ! empty( $loc->parent ) ) {
							$parent = $this->get_term_cached( (int) $loc->parent, 'package_location' );
							if ( $parent ) {
								$locStr = sprintf( '%s, %s', $locStr, $parent->name );
							}
						}
						$bits[] = $locStr;
					}
				}
			}

			// sort label
			if ( get_has('sort') ) {
				$sort   = secure_get('sort');
				$labels = $this->get_sort_title_labels();
				if ( isset( $labels[ $sort ] ) ) {
					$bits[] = sprintf( '(%s)', $labels[ $sort ] );
				}
			}

			$title = trim( implode( ' ', $bits ) );
		}

		// pagination suffix
		$paged = $this->current_page_number();
		if ( $paged > 1 ) {
			$title = sprintf( '%s | %s %d', $title, esc_html__( 'Page', 'dynamicpackages' ), $paged );
		}

		return $title;
	}

	public function wp_title( $title ) {
		$q     = get_queried_object();
		$parts = [];

		if ( is_tax() ) {
			$base = single_term_title( '', false );

			if ( is_tax( 'package_location' ) || is_tax( 'package_category' ) ) {
				$meta = ( $q && isset( $q->term_id ) ) ? (string) get_term_meta( (int) $q->term_id, 'tax_title_modifier', true ) : '';

				if ( $meta !== '' ) {
					$base = $meta;
				} elseif ( is_tax( 'package_location' ) ) {
					$place = $base;
					if ( ! empty( $q->parent ) ) {
						$parent = $this->get_term_cached( (int) $q->parent, 'package_location' );
						if ( $parent ) {
							$place = sprintf( '%s, %s', $place, $parent->name );
						}
					}
					$base = sprintf( __( 'Packages in %s', 'dynamicpackages' ), $place );
				}
			}

			$parts[] = $base;

		} elseif ( is_page() && dy_validators::validate_category_location() ) {
			$bits   = [ __( 'Find Packages', 'dynamicpackages' ) . ':' ];
			$catStr = '';
			$locStr = '';

			if ( get_has('keywords') ) {
				$kw = $this->sanitize_keywords( secure_get('keywords') );
				if ( $kw !== '' ) {
					$bits[] = sprintf( '“%s”', $kw );
				}
			}

			if ( get_has('category') ) {
				$slug = secure_get('category');
				if ( $slug !== '' && $slug !== 'any' ) {
					$cat = $this->get_term_by_slug_cached( $slug, 'package_category' );
					if ( $cat ) {
						$catStr = $cat->name;
					}
				}
			}
			if ( $catStr !== '' ) {
				$bits[] = $catStr;
			}

			if ( get_has('location') ) {
				$slug = secure_get('location');
				if ( $slug !== '' && $slug !== 'any' ) {
					$loc = $this->get_term_by_slug_cached( $slug, 'package_location' );
					if ( $loc ) {
						$locStr = $loc->name;
						if ( ! empty( $loc->parent ) ) {
							$parent = $this->get_term_cached( (int) $loc->parent, 'package_location' );
							if ( $parent ) {
								$locStr = sprintf( '%s, %s', $locStr, $parent->name );
							}
						}
						$bits[] = $locStr;
					}
				}
			}

			if ( get_has('sort') ) {
				$sort   = secure_get('sort');
				$labels = $this->get_sort_title_labels();
				if ( isset( $labels[ $sort ] ) ) {
					$bits[] = sprintf( '(%s)', $labels[ $sort ] );
				}
			}

			$parts[] = trim( implode( ' ', $bits ) );
		}

		// Pagination and site name
		$paged = $this->current_page_number();
		if ( $paged > 1 ) {
			$parts[] = sprintf( __( 'Page %d', 'dynamicpackages' ), $paged );
		}
		$parts[] = get_bloginfo( 'name' );

		return implode( ' | ', $parts );
	}

	public static function remove_default_canonicals() {
		if ( dy_validators::validate_category_location() ) {
			remove_action('wp_head', 'rel_canonical');
		} else {
			// Use the same pagination helper as the rest of the class
			$paged = function_exists('current_page_number') ? (int) current_page_number() : max( 1, (int) get_query_var('paged') );
			if ( $paged > 1 ) {
				remove_action('wp_head', 'rel_canonical');
			}
		}
	}

	/* ------------------------------------------
	 * Canonical tag for location/category pages
	 * ------------------------------------------ */
	public function location_category_canonical() : void {
		// Only act on the intended context.
		if ( ! dy_validators::validate_category_location() ) {
			$paged = $this->current_page_number();
			if ( $paged > 1 ) {
				$url = $this->url_with_paged( get_the_permalink(), $paged );
				printf('<link rel="canonical" href="%s" />', esc_url( $url ));
			}
			return;
		}

		$paged = $this->current_page_number();
		$url   = ( $paged > 1 )
			? $this->url_with_paged( get_the_permalink(), $paged )
			: get_the_permalink();

		$args = $this->collect_query_args_from_get([
			'translate' => false, // no language transform in canonical
			'lang'      => null,
		]);

		// Build final canonical URL.
		$final = ! empty( $args ) ? add_query_arg( $args, $url ) : $url;
		printf('<link rel="canonical" href="%s" />', esc_url( $final ));
	}

	/* ------------------------------------------
	 * Polylang alternate URL filter
	 * ------------------------------------------ */
	public function location_category_alternate( $url, $lang ) {
		if ( empty( $url ) ) {
			return $url; // no translation available
		}

		$args = $this->collect_query_args_from_get([
			'translate' => true,
			'lang'      => $lang,
		]);

		// Strip then add clean args.
		$url = remove_query_arg( ['location', 'category', 'sort', 'keywords'], $url );
		return ! empty( $args ) ? add_query_arg( $args, $url ) : $url;
	}

	/* ======================================================================
	 * Helpers (private)
	 * ====================================================================== */

	private function current_page_number() : int {
		// Avoid calling external helpers more than needed.
		if ( function_exists('current_page_number') ) {
			return (int) current_page_number();
		}
		// Sensible fallback if custom helper is absent.
		$paged = get_query_var('paged');
		return $paged ? (int) $paged : 1;
	}

	private function url_with_paged( string $base, int $paged ) : string {
		if ( $paged <= 1 ) return $base;

		$slug = sprintf('page/%d', $paged);
		if ( ! is_front_page() ) {
			$slug = sprintf('/%s', $slug);
		}
		return $base . $slug;
	}

	/**
	 * Collect & sanitize query args from $_GET = secure_get.
	 * Options:
	 *  - translate (bool) : translate taxonomy terms to $lang slugs via Polylang
	 *  - lang      (string|null)
	 */
	private function collect_query_args_from_get( array $opts ) : array {
		$translate = ! empty( $opts['translate'] );
		$lang      = $opts['lang'] ?? null;

		$args = [];

		// location
		if ( get_has('location') ) {
			$raw = secure_get('location');
			if ( $raw !== '' ) {
				$args['location'] = $translate
					? ( $this->translate_term_to_lang_slug( $raw, 'location', (string) $lang ) ?? '' )
					: $raw;

				if ( $args['location'] === '' ) unset( $args['location'] );
			}
		}

		// category
		if ( get_has('category') ) {
			$raw = secure_get('category');
			if ( $raw !== '' ) {
				$args['category'] = $translate
					? ( $this->translate_term_to_lang_slug( $raw, 'category', (string) $lang ) ?? '' )
					: $raw;

				if ( $args['category'] === '' ) unset( $args['category'] );
			}
		}

		// sort
		if ( get_has('sort') ) {
			$sort = secure_get('sort');
			$sort = $this->sanitize_sort( $sort );
			if ( $sort !== null ) {
				$args['sort'] = $sort;
			}
		}

		// keywords
		if ( get_has('keywords') ) {
			$kw = secure_get('keywords');
			if ( $kw !== '' ) {
				$args['keywords'] = $kw;
			}
		}

		return $args;
	}

	private function sanitize_keywords( string $kw ) : string {
		if ( $kw === '' ) return '';
		$kw = sanitize_text_field( wp_unslash( $kw ) ); // keep identical with callers
		if ( $kw === '' ) return '';
		$kw = strtolower( $kw );
		$kw = preg_replace( '/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $kw );
		$kw = preg_replace( '/\s+/', ' ', $kw );
		return substr( $kw, 0, 25 ) ?: '';
	}

	private function sanitize_sort( string $sort ) : ?string {
		// Must be non-empty AND not 'any'
		if ( $sort === '' || $sort === 'any' ) {
			return null;
		}

		if ( method_exists( 'dy_utilities', 'sort_by_arr' ) ) {
			$allow = dy_utilities::sort_by_arr();
			return ( is_array( $allow ) && in_array( $sort, $allow, true ) ) ? $sort : null;
		}

		// Minimal safe fallback (alphanumeric, _, - up to 30 chars)
		return preg_match( '/^[a-z0-9_-]{1,30}$/i', $sort ) ? $sort : null;
	}

	/* ---------- tiny WP_Term caches to avoid repeat lookups ---------- */

	private function get_term_cached( int $id, string $taxonomy ) {
		$key = "{$taxonomy}:{$id}";
		if ( array_key_exists( $key, self::$cache['term_by_id'] ) ) {
			return self::$cache['term_by_id'][ $key ] ?: null;
		}
		$t = get_term( $id, $taxonomy );
		self::$cache['term_by_id'][ $key ] = ( $t && ! is_wp_error( $t ) ) ? $t : false;
		return self::$cache['term_by_id'][ $key ] ?: null;
	}

	private function get_term_by_slug_cached( string $slug, string $taxonomy ) {
		$key = "{$taxonomy}:{$slug}";
		if ( array_key_exists( $key, self::$cache['term_by_slug'] ) ) {
			return self::$cache['term_by_slug'][ $key ] ?: null;
		}
		$t = get_term_by( 'slug', $slug, $taxonomy );
		self::$cache['term_by_slug'][ $key ] = ( $t && ! is_wp_error( $t ) ) ? $t : false;
		return self::$cache['term_by_slug'][ $key ] ?: null;
	}

	/**
	 * Translate a term value (id/slug/name) to a target language slug.
	 * Caches results aggressively in self::$cache['term'].
	 */
	private function translate_term_to_lang_slug( $value, string $taxonomy, string $lang ) : ?string {
		if ( ! function_exists('pll_get_term') ) return null;

		$key = sprintf(
			'%s:%s:%s',
			$taxonomy,
			$lang,
			is_scalar( $value ) ? (string) $value : md5( serialize( $value ) )
		);
		if ( isset( self::$cache['term'][ $key ] ) ) {
			return self::$cache['term'][ $key ];
		}

		$term = null;

		if ( is_numeric( $value ) ) {
			$term = $this->get_term_cached( (int) $value, $taxonomy );
		}
		if ( ! $term ) {
			$term = get_term_by( 'slug', (string) $value, $taxonomy );
			if ( $term && is_wp_error( $term ) ) {
				$term = null;
			}
		}
		if ( ! $term ) {
			$term = get_term_by( 'name', (string) $value, $taxonomy );
			if ( $term && is_wp_error( $term ) ) {
				$term = null;
			}
		}
		if ( ! $term ) {
			return null;
		}

		$translated_id = pll_get_term( (int) $term->term_id, $lang );
		if ( empty( $translated_id ) ) {
			return null;
		}

		$translated = $this->get_term_cached( (int) $translated_id, $taxonomy );
		if ( ! $translated ) {
			return null;
		}

		// Cache and return slug.
		return self::$cache['term'][ $key ] = (string) $translated->slug;
	}

	public function modify_excerpt( $excerpt ) {
		// Fast bail-outs first
		if ( ! is_page() || ! dy_validators::validate_category_location() ) {
			return $excerpt;
		}

		$post = get_post();
		if ( ! $post ) {
			return $excerpt;
		}

		// Memoize per post ID to avoid repeated has_shortcode() work
		static $has_packages_sc = [];
		$pid = (int) $post->ID;

		if ( ! array_key_exists( $pid, $has_packages_sc ) ) {
			// has_shortcode() is already efficient (uses strpos first), just cache its result
			$has_packages_sc[ $pid ] = has_shortcode( (string) $post->post_content, 'packages' );
		}

		if ( $has_packages_sc[ $pid ] ) {
			return null; // keep exact original behavior
		}

		return $excerpt;
	}

}

?>
