<?php

if (!defined('WPINC')) exit;

/** Package presentation for the endpoint registered by Dy_Confirmation_Page. */
class Dynamicpackages_Confirmation_Page
{
    private string $version;
    private ?object $tx = null;

    public function __construct(int|string $version)
    {
        $this->version = (string) $version;
        add_action('init', [$this, 'localized_routes'], 20);
        add_action('wp_loaded', [$this, 'refresh_routes']);
        add_action('pre_get_posts', [$this, 'prepare_query'], 101);
        add_filter('posts_pre_query', [$this, 'transaction_post'], 10, 2);
        add_action('wp', [$this, 'prepare_confirmation'], 20);
        add_filter('redirect_canonical', [$this, 'redirect_canonical']);
        add_filter('the_content', [$this, 'the_content'], 101);
        add_filter('pre_get_document_title', [$this, 'wp_title'], 101);
        add_filter('the_title', [$this, 'the_title'], 101);
        add_filter('get_the_excerpt', [$this, 'get_the_excerpt'], 101);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function localized_routes(): void
    {
        if (!function_exists('pll_languages_list')) return;
        $languages = array_map(static fn(string $language): string => preg_quote($language, '#'), get_languages());
        if ($languages !== []) {
            add_rewrite_rule('^(' . implode('|', $languages) . ')/dy-tx/([^/]+)/?$',
                'index.php?lang=$matches[1]&dy-tx=$matches[2]', 'top');
        }
    }

    public function refresh_routes(): void
    {
        if (dy_get_option('dy_checkout_rewrite_version') === '1') return;
        flush_rewrite_rules(false);
        update_option('dy_checkout_rewrite_version', '1', false);
    }

    public static function is_confirmation(): bool
    {
        return secure_server('REQUEST_METHOD') === 'GET'
            && !is_admin() && !wp_doing_ajax() && !wp_doing_cron()
            && !(defined('REST_REQUEST') && REST_REQUEST)
            && is_string(get_query_var('dy-tx')) && get_query_var('dy-tx') !== '';
    }

    public function prepare_query(WP_Query $query): void
    {
        if (!$query->is_main_query() || !self::is_confirmation()) return;

        // The shared endpoint selects page.php; supply its actual transaction post to the loop.
        $query->is_home = false;
        $query->is_archive = false;
        $query->is_404 = false;
        $query->is_single = false;
        $query->is_page = true;
        $query->is_singular = true;
        $query->set('no_found_rows', true);
    }

    public function transaction_post(?array $posts, WP_Query $query): ?array
    {
        if (!$query->is_main_query() || !self::is_confirmation()) return $posts;

        $tx_id = (string) get_query_var('dy-tx');
        $tx = wp_is_uuid($tx_id, 4) ? dy_tx::get_stored_tx($tx_id) : null;
        $post = $tx !== null ? get_post((int) $tx->dy_id) : null;
        if ($tx !== null && $post instanceof WP_Post && $post->post_status === 'publish'
            && dy_tx::validate($tx_id, get_object_vars($tx))
            && in_array($tx->dy_request, dy_utilities::all_dy_request_types(), true)
            && ($post->post_type === 'packages' || has_shortcode($post->post_content, 'package_contact'))) {
            $this->tx = $tx;
        } else {
            // A loop is needed to display the shared endpoint's invalid/expired-ID errors.
            $post = new WP_Post((object) [
                'ID' => 0, 'post_type' => 'page', 'post_status' => 'publish',
                'post_title' => '', 'post_content' => '', 'post_excerpt' => '',
                'post_parent' => 0, 'post_name' => '', 'post_author' => 0,
            ]);
        }
        $query->found_posts = 1;
        $query->max_num_pages = 1;
        $query->queried_object = $post;
        $query->queried_object_id = $post->ID;
        return [$post];
    }

    public function prepare_confirmation(): void
    {
        if (!self::is_confirmation()) return;
        if ($this->tx === null) {
            dy_errors::add(__('Invalid or expired transaction ID.', 'dynamicpackages'));
            return;
        }

        // Carry queued conversions across the POST/redirect/GET boundary once per browser.
        $cookie = 'dy_tx_seen_' . $this->tx->tx_id;
        $events = $this->tx->confirmation['events'] ?? [];
        if (!cookie_has($cookie) && $events !== []) {
            foreach ($events as $event) {
                $params = $event['params'];
                dy_gtag_queue_server_event($event['name'], $this->tx->tx_id,
                    $params['value'], $params['currency'], $params['items'] ?? []);
            }
            setcookie($cookie, '1', [
                'expires' => time() + DAY_IN_SECONDS,
                'path' => (string) (wp_parse_url(home_lang(), PHP_URL_PATH) ?: '/'),
                'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
    }

    public function redirect_canonical(string|false $url): string|false
    {
        return self::is_confirmation() ? false : $url;
    }

    public function the_content(mixed $content): string
    {
        if (self::is_confirmation() && in_the_loop() && is_main_query()) {
            return (string) ($this->tx->confirmation['content'] ?? $content);
        }
        return is_string($content) ? $content : '';
    }

    public function wp_title(mixed $title): string
    {
        return self::is_confirmation()
            ? (string) ($this->tx->confirmation['title'] ?? $title)
            : (is_string($title) ? $title : '');
    }

    public function the_title(mixed $title): string
    {
        return in_the_loop() && is_main_query() ? $this->wp_title($title) : (string) $title;
    }

    public function get_the_excerpt(mixed $excerpt): string
    {
        return self::is_confirmation()
            ? (string) ($this->tx->confirmation['excerpt'] ?? '')
            : (is_string($excerpt) ? $excerpt : '');
    }

    public function enqueue_scripts(): void
    {
        if (!self::is_confirmation()) return;
        wp_enqueue_script('dynamicpackages-confirmation', plugin_dir_url(__FILE__) . 'js/dynamicpackages-confirmation-page.js', ['jquery'], $this->version, true);
        wp_localize_script('dynamicpackages-confirmation', 'dyPackageConfirmationArgs', [
            'textCopiedToClipBoard' => __('Copied to Clipboard!', 'dynamicpackages'),
        ]);
        if (str_contains((string) ($this->tx->confirmation['content'] ?? ''), 'addeventatc')) {
            wp_enqueue_script('dy_add_to_calendar', 'https://addevent.com/libs/atc/1.6.1/atc.min.js', [], null, true);
            wp_add_inline_style('minimalLayout', '.addeventatc{visibility:hidden}.addevent_container{height:42px}');
        }
    }
}
