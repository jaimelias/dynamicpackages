<?php

if (!defined('WPINC')) exit;

final class Dynamicpackages_Schedule
{
	public const CHECK_IN_FIELDS = [
		'start_hour' => 'package_check_in_hour',
		'end_hour'   => 'package_check_in_end_hour',
	];

	/** A null result signals invalid data. A single legacy check-in may exist on its own. */
	public static function parse(string $hours, string $check_ins): ?array
	{
		$hours = self::decode($hours);
		$check_ins = self::decode($check_ins);
		if ($hours === null || $check_ins === null) {
			return null;
		}
		if ($hours === [] && count($check_ins) === 1) {
			return [['hour' => '', 'check_in' => $check_ins[0]]];
		}
		if ($check_ins !== [] && count($hours) !== count($check_ins)) {
			return null;
		}

		$rows = [];
		$seen = [];
		foreach ($hours as $index => $hour) {
			if ($hour === null) {
				return null;
			}
			$seconds = hour_to_seconds($hour);
			if (isset($seen[$seconds])) {
				return null;
			}
			$seen[$seconds] = true;
			$rows[] = ['hour' => $hour, 'check_in' => $check_ins[$index] ?? null];
		}
		return $rows;
	}

	private static function decode(string $value): ?array
	{
		$value = trim($value);
		if ($value === '') {
			return [];
		}
		$values = $value[0] === '[' ? json_decode($value) : [$value];
		if (!is_array($values)) {
			return null;
		}
		foreach ($values as $time) {
			if ($time !== null && (!is_string($time) || trim($time) !== $time || !is_valid_time($time))) {
				return null;
			}
		}
		return $values;
	}

	public static function encode(array $values): string
	{
		return count($values) > 1 ? (string) wp_json_encode($values) : ($values[0] ?? '');
	}

	public static function rows(string $type): ?array
	{
		$hours = package_field('package_' . $type);
		$check_ins = package_field(self::CHECK_IN_FIELDS[$type]);
		$rows = self::parse($hours, $check_ins);
		if ($rows === null && is_valid_time($hours) && !str_starts_with(ltrim($check_ins), '[')) {
			return self::parse($hours, '');
		}
		return $rows;
	}

	public static function departures(string $type): array
	{
		return array_values(array_filter(self::rows($type) ?? [], static fn(array $row): bool => $row['hour'] !== ''));
	}

	public static function uses_hour_range(): bool
	{
		if ((int) package_field('package_by_hour') !== 1) {
			return false;
		}
		$min = package_field('package_min_hour');
		$max = package_field('package_max_hour');
		if (($min !== '' && $max !== '')
			|| ($min !== '' && hour_to_seconds($min) === false)
			|| ($max !== '' && hour_to_seconds($max) === false)) {
			return true;
		}
		return self::departures('start_hour') === [] && self::departures('end_hour') === [];
	}

	/** Selectable schedules use trip direction. Legacy fixed hours retain their original ordering. */
	public static function uses_options(): bool
	{
		return !self::uses_hour_range() && (
			(int) package_field('package_by_hour') === 1
			|| count(self::departures('start_hour')) > 1
			|| count(self::departures('end_hour')) > 1
		);
	}

	public static function is_reversed(): bool
	{
		return (string) dy_tx::request_value('route') === '1'
			&& dy_utilities::get_package_type(get_dy_id()) === 'transport';
	}

	/** Return a scalar booking time, or null for an invalid selection. */
	public static function resolve(string $type, string $request_hour): ?string
	{
		if (self::uses_hour_range()) {
			return is_hour_in_range($request_hour, [package_field('package_min_hour'), package_field('package_max_hour')])
				? $request_hour : null;
		}
		$uses_options = self::uses_options();
		if ($uses_options && self::is_reversed()) {
			$type = $type === 'start_hour' ? 'end_hour' : 'start_hour';
		}
		$rows = self::rows($type);
		if ($rows === null) {
			return null;
		}
		$first_hour = $rows[0]['hour'] ?? '';
		if ($first_hour === '') {
			return $request_hour === '' ? '' : null;
		}
		if (!$uses_options || (count($rows) === 1 && $request_hour === '')) {
			return $first_hour;
		}
		$requested = hour_to_seconds($request_hour);
		foreach ($rows as $row) {
			if ($requested === hour_to_seconds($row['hour'])) {
				return $row['hour'];
			}
		}
		return null;
	}

	public static function check_in(string $type, string $hour): string
	{
		$rows = self::rows($type) ?? [];
		if (count($rows) === 1) {
			return $rows[0]['check_in'] ?? '';
		}
		if (self::uses_hour_range()) {
			return '';
		}
		foreach ($rows as $row) {
			if ($row['hour'] === $hour) {
				return $row['check_in'] ?? '';
			}
		}
		return '';
	}
}
