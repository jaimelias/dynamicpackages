<?php
/** Run with php tests/checkout.php. No WordPress database, email, or payment network is used. */
if (!isset($argv[1])) {
    $cases = ['contact', 'estimate_request', 'cuanto', 'paypal_me', 'yappy_direct', 'usdt', 'usdc',
        'card-approved', 'card-declined', 'card-error', 'card-debug', 'invalid-contact', 'invalid-card',
        'invalid-identity', 'wrong-destination', 'invalid-network', 'turnstile-failed', 'rate-limited', 'lock-failed',
        'store-failed', 'result-store-failed', 'processing', 'expired', 'tampered', 'legacy-update', 'redirect'];
    foreach ($cases as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $status);
        if ($status !== 0) exit($status);
    }
    echo count($cases) . " checkout scenarios passed.\n";
    exit;
}
error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$case = $argv[1];
define('WPINC', 'wp-includes');
define('DAY_IN_SECONDS', 86400);
define('HOUR_IN_SECONDS', 3600);
$method = 'POST';
$hooks = $storage = $cookies = $webhooks = $emails = [];
$charge_count = $turnstile_count = $rate_count = $writes = 0;
$tx_id = '12345678-1234-4234-8234-123456789abc';
$request_type = str_starts_with($case, 'card-') || in_array($case, ['invalid-card', 'store-failed', 'result-store-failed', 'processing']) ? 'paguelo_facil_on' : $case;
if (!in_array($request_type, ['contact', 'estimate_request', 'cuanto', 'paypal_me', 'yappy_direct', 'usdt', 'usdc', 'paguelo_facil_on'])) $request_type = 'contact';
if ($case === 'invalid-network') $request_type = 'usdt';
$input = ['dy_id' => 42, 'dy_request' => $request_type, 'email' => 'guest@example.com', 'repeat_email' => 'guest@example.com',
    'first_name' => 'Ada', 'lastname' => 'Guest', 'phone' => '1234567', 'country_calling_code' => '507',
    'tx_id' => $tx_id, 'start_date' => '2026-10-01', 'start_hour' => '09:00', 'end_date' => '2026-10-02',
    'end_hour' => '17:00', 'route' => '1', 'pax_regular' => 2, 'duration' => '2 days', 'pax_num' => 2,
    'title' => 'Island ferry', 'package_type' => 'transport', 'dy_network' => $case === 'invalid-network' ? 'bogus' : 'trx',
    'CCNum' => '4111111111111111', 'CVV2' => '123', 'cf-turnstile-response' => 'token', 'inquiry' => 'A question'];
if ($case === 'usdc') $input['dy_network'] = 'eth';

#[AllowDynamicProperties]
class WP_Post { public function __construct(object $data) { foreach ($data as $key => $value) $this->{$key} = $value; } }
#[AllowDynamicProperties]
class WP_Query {
    public array $query_vars = [];
    public function is_main_query(): bool { return true; }
    public function set(string $key, mixed $value): void { $this->query_vars[$key] = $value; }
}
$post = new WP_Post((object) ['ID' => 42, 'post_status' => 'publish', 'post_type' => $request_type === 'contact' ? 'page' : 'packages',
    'post_content' => '[package_contact]', 'post_title' => 'Island ferry']);
$wpdb = new class {
    public string $prefix = 'wp_';
    public function prepare(string $sql, mixed ...$args): string { return $sql; }
    public function get_var(string $sql): string { return $GLOBALS['case'] === 'lock-failed' && str_contains($sql, 'GET_LOCK') ? '0' : '1'; }
};
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($GLOBALS['case'] . ': ' . $message); }
function add_action(string $name, mixed $fn, int $priority = 10, int $args = 1): void { add_filter($name, $fn, $priority, $args); }
function add_filter(string $name, mixed $fn, int $priority = 10, int $args = 1): void { $GLOBALS['hooks'][$name][$priority][] = [$fn, $args]; }
function apply_filters(string $name, mixed $value, mixed ...$args): mixed {
    $groups = $GLOBALS['hooks'][$name] ?? []; ksort($groups);
    foreach ($groups as $callbacks) foreach ($callbacks as [$fn, $count]) $value = $fn(...array_slice([$value, ...$args], 0, $count));
    return $value;
}
function do_action(string $name, mixed ...$args): void {
    $groups = $GLOBALS['hooks'][$name] ?? []; ksort($groups);
    foreach ($groups as $callbacks) foreach ($callbacks as [$fn, $count]) $fn(...array_slice($args, 0, $count));
}
function secure_server(string $key): string { return $GLOBALS['method']; }
function secure_post(string $key, mixed $default = '', mixed $fn = 'sanitize_text_field'): mixed {
    return isset($GLOBALS['input'][$key]) ? $fn($GLOBALS['input'][$key]) : $default;
}
function secure_get(string $key, mixed $default = '', mixed $fn = 'sanitize_text_field'): mixed { return $default; }
function secure_request(string $key, mixed $default = '', mixed $fn = 'sanitize_text_field'): mixed { return secure_post($key, $default, $fn); }
function post_has(string $key): bool { return isset($GLOBALS['input'][$key]); }
function request_has(string $key): bool { return post_has($key); }
function cookie_has(string $key): bool { return isset($GLOBALS['cookies'][$key]); }
function sanitize_text_field(mixed $value): string { return trim(strip_tags((string) $value)); }
function sanitize_textarea_field(mixed $value): string { return sanitize_text_field($value); }
function sanitize_email(mixed $value): string { return (string) $value; }
function dy_sanitize_email(string $email): string { return sanitize_email(strtolower(trim($email))); }
function sanitize_key(mixed $value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function absint(mixed $value): int { return abs((int) $value); }
function is_email(string $email): bool { return (bool) filter_var($email, FILTER_VALIDATE_EMAIL); }
function wp_salt(string $scheme): string { return 'test-only'; }
function set_transient(string $key, mixed $value, int $expiry): bool {
    ++$GLOBALS['writes'];
    if (($GLOBALS['case'] === 'store-failed' && $GLOBALS['writes'] === 2)
        || ($GLOBALS['case'] === 'result-store-failed' && $GLOBALS['writes'] === 3)) return false;
    $GLOBALS['storage'][$key] = serialize($value); return true;
}
function get_transient(string $key): mixed { return isset($GLOBALS['storage'][$key]) ? unserialize($GLOBALS['storage'][$key]) : false; }
function wp_using_ext_object_cache(): bool { return false; }
function wp_cache_delete(string $key, string $group): void {}
function wp_kses_post(string $html): string { return $html; }
function wp_json_encode(mixed $value): string { return json_encode($value); }
function plugin_dir_path(string $file): string { return dirname($file) . '/'; }
function plugin_dir_url(string $file): string { return 'https://example.com/plugins/'; }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function wp_doing_cron(): bool { return false; }
function get_dy_id(): int { return 42; }
function get_queried_object_id(): int { return $GLOBALS['case'] === 'wrong-destination' ? 99 : 42; }
function get_post(int $id): ?WP_Post { return $id === 42 ? $GLOBALS['post'] : null; }
function has_shortcode(string $content, string $shortcode): bool { return str_contains($content, '[' . $shortcode . ']'); }
function is_singular(string $type): bool { return $GLOBALS['post']->post_type === $type; }
function is_main_query(): bool { return true; }
function in_the_loop(): bool { return $GLOBALS['method'] === 'GET'; }
function is_confirmation_page(): bool { return Dynamicpackages_Actions::is_submission() || Dynamicpackages_Confirmation_Page::is_confirmation(); }
function is_booking_page(): bool { return false; }
function get_query_var(string $key): mixed { return $GLOBALS['method'] === 'GET' && $key === 'dy-tx' ? $GLOBALS['tx_id'] : ''; }
function wp_is_uuid(string $uuid, int $version): bool { return preg_match('/^[a-f0-9-]{36}$/', $uuid) === 1; }
function __(string $text, string $domain = ''): string { return $text; }
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES); }
function esc_attr(mixed $text): string { return htmlspecialchars((string) $text, ENT_QUOTES); }
function esc_url(mixed $text): string { return (string) $text; }
function get_bloginfo(string $key): string { return $key === 'charset' ? 'UTF-8' : 'Demo'; }
function wp_strip_all_tags(string $value): string { return strip_tags($value); }
function get_option(string $key, mixed $default = ''): mixed {
    return match (true) {
        str_ends_with($key, '_min') => 1, str_ends_with($key, '_max') => 1000,
        str_ends_with($key, '_service_fee') => 5, str_ends_with($key, '_qrcode') => 'https://example.com/qr.png',
        str_ends_with($key, '_show') => 0,
        $key === 'dy_bidding_conversion_percentage' => 15,
        default => 'merchant',
    };
}
function dy_get_option(string $key, string $default = ''): mixed { return get_option($key, $default); }
function package_field(string $key, mixed $id = null): mixed { return match ($key) {
    'package_by_hour' => '0', 'package_start_hour' => '09:00', 'package_end_hour' => '17:00', default => '',
}; }
function currency_name(): string { return 'USD'; }
function money(mixed $value): string { return number_format((float) $value, 2, '.', ''); }
function wrap_money_full(mixed $value): string { return '$' . money($value); }
function implode_last(array $values, string $word): string { return implode(' ' . $word . ' ', $values); }
function is_user_logged_in(): bool { return false; }
function write_log(mixed ...$args): void {}
function validate_turnstile(string $token, string $action): bool { ++$GLOBALS['turnstile_count']; return $GLOBALS['case'] !== 'turnstile-failed'; }
function home_lang(): string { return 'https://example.com/es/'; }
function trailingslashit(string $url): string { return rtrim($url, '/') . '/'; }
function wp_parse_url(string $url, int $component): mixed { return parse_url($url, $component); }
function is_ssl(): bool { return true; }
function nocache_headers(): void {}
function wp_safe_redirect(string $url, int $status): bool {
    check($status === 303 && $url === 'https://example.com/es/dy-tx/' . $GLOBALS['tx_id'], 'localized 303 redirect');
    echo "PASS redirect\n"; return true;
}
function wp_enqueue_script(mixed ...$args): void {}
function wp_localize_script(mixed ...$args): void {}
function wp_add_inline_style(mixed ...$args): void {}
class dy_errors {
    public static array $errors = [];
    public static function add(mixed $error, int $status = 400): void { self::$errors[] = $error; }
    public static function has_errors(): bool { return self::$errors !== []; }
}
class dy_validators {
    public static function validate_unique_tx_id(): bool { return dy_tx::validate($GLOBALS['tx_id']); }
    public static function validate_contact_details(): bool { return $GLOBALS['case'] !== 'invalid-contact'; }
    public static function validate_booking_details(): bool { return true; }
    public static function validate_submission_rate_limits(): bool { ++$GLOBALS['rate_count']; return $GLOBALS['case'] !== 'rate-limited'; }
    public static function validate_post_id_rate_limits(): bool { return true; }
    public static function validate_gateway_rate_limits(): bool { return true; }
    public static function has_deposit(): bool { return true; }
    public static function validate_request(): bool { return Dynamicpackages_Actions::validate_request(); }
}
class dy_utilities {
    public static function all_dy_request_types(): array { return ['contact', 'estimate_request', 'cuanto', 'paypal_me', 'yappy_direct', 'usdt', 'usdc', 'paguelo_facil_on']; }
    public static function total(): float { return 200; }
    public static function payment_amount(float $fee = 0): float { return 100 * (1 + $fee / 100); }
    public static function get_taxonomies(string $name): array { return []; }
    public static function webhook(string $option, string $payload): void { $GLOBALS['webhooks'][] = [$option, json_decode($payload, true)]; }
}
$root = dirname(__DIR__);
require $root . '/dy-core/e-commerce/transactions.php';
require $root . '/dy-core/e-commerce/confirmation-page.php';
require $root . '/dy-core/integrations/gtag.php';
require $root . '/includes/class-dynamicpackages-form-actions.php';
require $root . '/public/class-dynamicpackages-confirmation-page.php';
require $root . '/gateways/matrix/paguelo_facil/paguelo_facil_on.php';
foreach (['cuanto/cuanto', 'paypal/paypal_me', 'yappy/yappy_direct', 'crypto/stable-coins'] as $gateway) require $root . '/gateways/matrix/' . $gateway . '.php';
class TestActions extends Dynamicpackages_Actions {
    public function send_email(): void { $GLOBALS['emails'][] = [apply_filters('dy_email_subject', 'estimate'), apply_filters('dy_email_label_doc', 'Estimate')]; }
}
class TestCard extends paguelo_facil_on {
    public function validate_card(): bool { return $GLOBALS['case'] !== 'invalid-card'; }
    public function process_request(): array {
        ++$GLOBALS['charge_count'];
        return match ($GLOBALS['case']) {
            'card-declined' => ['Status' => 'Declined', 'RespCode' => '05', 'RespText' => 'Declined <script>bad</script>'],
            'card-error' => ['error' => 'upstream_unavailable'],
            default => ['Status' => 'Approved'],
        };
    }
}
$card = new TestCard('dynamicpackages');
new cuanto('dynamicpackages'); new paypal_me('dynamicpackages'); new yappy_direct('dynamicpackages');
new stable_coins('dynamicpackages', 'usdt'); new stable_coins('dynamicpackages', 'usdc');
do_action('init');
if ($case === 'card-debug') $card->debug_mode = 2;
$actions = new TestActions();
check(dy_tx::create($tx_id, ['dy_id' => 42, 'dy_request' => $request_type, 'email' => 'guest@example.com']), 'transaction created');
if ($case === 'legacy-update') {
    check(dy_tx::update($tx_id, 'success', $input), 'legacy signature accepted');
    $tx = dy_tx::get_stored_tx($tx_id);
    check($tx->contact_details->email === 'guest@example.com' && !isset($tx->CCNum), 'legacy whitelist retained');
    echo "PASS $case\n"; exit;
}
if ($case === 'invalid-identity') $input['email'] = 'different@example.com';
if ($case === 'processing') { $tx = dy_tx::get_stored_tx($tx_id); $tx->status = 'processing'; dy_tx::update($tx); }
if ($case === 'redirect') $actions->submit();
$result = $actions->send_data();
$rejected = ['invalid-contact', 'invalid-card', 'invalid-identity', 'wrong-destination', 'invalid-network', 'turnstile-failed', 'rate-limited', 'lock-failed', 'store-failed', 'result-store-failed'];
if (in_array($case, $rejected)) {
    check(!$result && count($webhooks) === 0 && count($emails) === 0, 'invalid submission has no notifications');
    check($charge_count === ($case === 'result-store-failed' ? 1 : 0), 'validation/storage failure does not charge');
    if ($case === 'result-store-failed') { check($actions->send_data(), 'processing replay resolves'); check($charge_count === 1, 'failed storage never recharges'); }
    echo "PASS $case\n"; exit;
}
if ($case === 'processing') { check($result && !$charge_count && !$webhooks && !$turnstile_count, 'processing replay is read-only'); echo "PASS $case\n"; exit; }
check($result, 'submission accepted');
$tx = dy_tx::get_stored_tx($tx_id);
$expected = match ($case) { 'card-declined' => 'declined', 'card-error' => 'error', default => 'success' };
check($tx->status === $expected, 'expected transaction status');
check(count($emails) === 1 && count($webhooks) === 1, 'notifications delivered once');
check($tx->contact_details->email === 'guest@example.com' && $tx->booking_details->route === '1', 'payload stored');
check(!str_contains(serialize($tx), '4111111111111111') && !str_contains(json_encode($webhooks), '4111111111111111'), 'card number excluded');
check($webhooks[0][1]['start_hour'] === '17:00' && $webhooks[0][1]['end_hour'] === '09:00', 'transport reverse hours preserved');
$expected_text = match ($request_type) {
    'cuanto' => 'cuanto.app/merchant/c/10000', 'paypal_me' => 'paypal.me/merchant/105.00',
    'yappy_direct' => 'https://example.com/qr.png', 'usdt' => 'Tron (TRC-20)', 'usdc' => 'Ethereum (ERC-20)',
    'paguelo_facil_on' => match ($case) { 'card-declined' => 'contact your bank', 'card-error' => 'try again', default => 'Thank you for your deposit' },
    default => 'Thank you for contacting us',
};
check(str_contains($tx->confirmation['content'], $expected_text), 'gateway-specific result preserved');
if ($request_type === 'paguelo_facil_on') {
    check($webhooks[0][0] === ($expected === 'success' ? 'dy_webhook' : 'dy_quote_webhook'), 'card webhook selection');
    check(count($tx->confirmation['events']) === ($case === 'card-approved' ? 1 : 0), 'only production approval queues purchase');
} else check(count($tx->confirmation['events']) === 1, 'lead event stored');
$before = [$charge_count, count($emails), count($webhooks), $turnstile_count, $writes];
check($actions->send_data(), 'duplicate POST resolves to confirmation');
check($before === [$charge_count, count($emails), count($webhooks), $turnstile_count, $writes], 'duplicate POST has no side effects or revalidation');
$method = 'GET'; $input = []; dy_tx::$cache = []; $GLOBALS['dy_gtag_server_events'] = [];
if ($case === 'expired') unset($storage['tx_id_' . $tx_id]);
if ($case === 'tampered') { $bad = clone $tx; $bad->secret_tx_id = 'bad'; $storage['tx_id_' . $tx_id] = serialize($bad); }
$shared_page = new Dy_Confirmation_Page();
$page = new Dynamicpackages_Confirmation_Page('1'); $query = new WP_Query();
$page->prepare_query($query); $posts = $page->transaction_post(null, $query);
$shared_page->set_post_on_checkout_page();
$page->prepare_confirmation();
if (in_array($case, ['expired', 'tampered'])) check(dy_errors::has_errors() && $posts[0]->ID === 0, 'invalid GET discloses no stored result');
else {
    check($posts[0]->ID === 42 && $query->is_page && !$query->is_home, 'confirmation loop selects transaction destination');
    check($page->the_content($shared_page->the_content('old')) === $tx->confirmation['content'], 'confirmation renders snapshot without POST');
    check(count($GLOBALS['dy_gtag_server_events']) === count($tx->confirmation['events']), 'analytics restored on GET');
    $cookies['dy_tx_seen_' . $tx_id] = '1'; $GLOBALS['dy_gtag_server_events'] = [];
    $page->prepare_confirmation(); check(!$GLOBALS['dy_gtag_server_events'], 'refresh suppresses repeated conversions');
}
check(!$actions->send_data(), 'GET cannot submit');
check($before === [$charge_count, count($emails), count($webhooks), $turnstile_count, $writes], 'confirmation has no submission side effects');
echo "PASS $case\n";
