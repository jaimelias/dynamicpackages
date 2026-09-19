<?php

if (!defined('WPINC')) exit;

/** Durable mapping between a Yappy order and its Dynamic Packages transaction. */
final class Dy_Yappy_V2_Store
{
	private const SCHEMA_VERSION = '1';

	private static bool $installed = false;
	private static bool $read_failed = false;

	public static function read_failed(): bool
	{
		return self::$read_failed;
	}

	private static function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'dy_yappy_v2_orders';
	}

	public static function install(): bool
	{
		if (self::$installed) return true;

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table();
		$collation = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			order_id varchar(15) NOT NULL,
			tx_id char(36) NOT NULL,
			record longtext NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (order_id),
			UNIQUE KEY tx_id (tx_id)
		) {$collation};";

		dbDelta($sql);

		$found = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
		);

		self::$installed = is_string($found) && $found === $table;

		if (self::$installed) {
			update_option('yappy_v2_schema_version', self::SCHEMA_VERSION, false);
		}

		return self::$installed;
	}

	public static function find_by_order_id(string $order_id): ?array
	{
		return self::find($order_id, false);
	}

	public static function find_by_tx_id(string $tx_id): ?array
	{
		return self::find($tx_id, true);
	}

	private static function find(string $value, bool $by_transaction): ?array
	{
		self::$read_failed = false;

		global $wpdb;

		$table = self::table();
		$sql = $by_transaction
			? $wpdb->prepare("SELECT record FROM {$table} WHERE tx_id = %s", $value)
			: $wpdb->prepare("SELECT record FROM {$table} WHERE order_id = %s", $value);
		$json = $wpdb->get_var($sql);
		self::$read_failed = (string) ($wpdb->last_error ?? '') !== '';
		$record = is_string($json) ? json_decode($json, true) : null;

		return is_array($record) ? $record : null;
	}

	public static function save(array $record, bool $insert = false): bool
	{
		global $wpdb;

		$json = wp_json_encode($record);
		if (!is_string($json)) return false;

		$data = [
			'record' => $json,
			'updated_at' => current_time('mysql', true),
		];
		$identity = [
			'order_id' => (string) ($record['order_id'] ?? ''),
			'tx_id' => (string) ($record['tx_id'] ?? ''),
		];

		if ($identity['order_id'] === '' || $identity['tx_id'] === '') return false;

		if ($insert) {
			return $wpdb->insert(self::table(), [...$identity, ...$data]) === 1;
		}

		$changed = $wpdb->update(self::table(), $data, $identity);
		if ($changed === false) return false;
		if ($changed > 0) return true;

		$existing = self::find_by_tx_id($identity['tx_id']);

		return $existing !== null
			&& (string) ($existing['order_id'] ?? '') === $identity['order_id'];
	}

	public static function lock(string $tx_id, bool $release = false): bool
	{
		global $wpdb;

		$name = 'dy_submit_' . substr(hash('sha256', $wpdb->prefix . $tx_id), 0, 54);
		$sql = $release ? 'SELECT RELEASE_LOCK(%s)' : 'SELECT GET_LOCK(%s, 1)';

		return (string) $wpdb->get_var($wpdb->prepare($sql, $name)) === '1';
	}

	public static function seal(array $value): string
	{
		try {
			$iv = random_bytes(12);
		} catch (Throwable) {
			return '';
		}

		$tag = '';
		$key = hash('sha256', wp_salt('auth'), true);
		$json = wp_json_encode($value);
		if (!is_string($json)) return '';

		$cipher = openssl_encrypt(
			$json,
			'aes-256-gcm',
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		return is_string($cipher) ? base64_encode($iv . $tag . $cipher) : '';
	}

	public static function open(string $value): ?array
	{
		$bytes = base64_decode($value, true);
		if ($bytes === false || strlen($bytes) < 29) return null;

		$plain = openssl_decrypt(
			substr($bytes, 28),
			'aes-256-gcm',
			hash('sha256', wp_salt('auth'), true),
			OPENSSL_RAW_DATA,
			substr($bytes, 0, 12),
			substr($bytes, 12, 16)
		);
		$result = is_string($plain) ? json_decode($plain, true) : null;

		return is_array($result) ? $result : null;
	}
}
