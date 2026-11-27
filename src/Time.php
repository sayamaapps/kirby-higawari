<?php

namespace Sayamaapps\Higawari;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Time of day of a dish (1.8), the dish fields `zeit` and `uhrzeit`. `bis`
 * 15:00: served until then, gone from 15:00 on, every day of the window (a
 * lunch dish). `ab` 15:00: shown all day with the note „ab 15 Uhr“. Without a
 * readable time a dish is shown all day. No App object is needed
 * (tests/TimeTest.php).
 *
 * @package   Kirby Higawari
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
final class Time
{
	/** Values of the dish field `zeit`; empty or anything else means all day. */
	public const ALL_DAY = 'ganztags';
	public const FROM = 'ab';
	public const UNTIL = 'bis';

	/**
	 * A time of day as `HH:MM`: "15:00", "9:30" and Kirby's stored "15:00:00"
	 * are read, anything else (empty, "25:00", "15 Uhr") gives null.
	 */
	public static function clock(string $value): string|null
	{
		if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/D', trim($value), $match)) {
			return null;
		}
		[$hour, $minute] = [(int)$match[1], (int)$match[2]];
		return $hour > 23 || $minute > 59 ? null : sprintf('%02d:%02d', $hour, $minute);
	}

	/**
	 * A stored date as text, surrounding whitespace removed. Kirby 6 reads an
	 * unquoted YAML date (`2026-11-21`) as the Unix time 1795219200: that
	 * becomes "Y-m-d", read in UTC. Fewer than 9 digits are no Unix time.
	 */
	public static function date(string $value): string
	{
		$value = trim($value);
		return preg_match('/^\d{9,10}$/', $value) ? gmdate('Y-m-d', (int)$value) : $value;
	}

	/**
	 * The time of a dish row: null for all day (`zeit` empty, `ganztags` or
	 * unknown, `uhrzeit` not a time, or `bis` 00:00), else `until` or `from`
	 * with `at` as `HH:MM`.
	 *
	 * @return array{mode: 'until'|'from', at: string}|null
	 */
	public static function fromRow(array $row): array|null
	{
		$value = is_string($row['zeit'] ?? null) ? strtolower(trim($row['zeit'])) : '';
		$mode = match ($value) {
			self::UNTIL => 'until',
			self::FROM => 'from',
			default => null,
		};
		$raw = $row['uhrzeit'] ?? '';
		$at = is_string($raw) ? self::clock($raw) : null;
		// `bis` 00:00 would hide the dish all day long; it is read as all day.
		if ($mode === null || $at === null || ($mode === 'until' && $at === '00:00')) {
			return null;
		}
		return ['mode' => $mode, 'at' => $at];
	}

	/**
	 * Does any dish carry a `bis` time? Then the output can change during the
	 * day, and on every day of the window again at midnight.
	 *
	 * @param list<array<string, mixed>> $rows
	 */
	public static function hasUntil(array $rows): bool
	{
		foreach (Menu::dishes($rows) as $dish) {
			if ($dish['time'] !== null && $dish['time']['mode'] === 'until') {
				return true;
			}
		}
		return false;
	}

	/**
	 * The note beside a dish with a time: „bis 15 Uhr“, „ab 14:30 Uhr“
	 * (English: „until 15:00“, „from 14:30“).
	 *
	 * @param array{mode: 'until'|'from', at: string} $time
	 */
	public static function label(array $time): string
	{
		[$hour, $minute] = explode(':', $time['at']);
		$shown = $minute === '00'
			? I18n::translate('format.time.hour', ['hour' => (string)(int)$hour])
			: I18n::translate('format.time', ['hour' => (string)(int)$hour, 'minute' => $minute]);
		return I18n::translate($time['mode'] === 'until' ? 'snippet.time.until' : 'snippet.time.from', ['time' => $shown]);
	}

	/**
	 * The moment after $now (same day in $timezone) at which a dish `bis` a
	 * time disappears, or null when no shown dish changes later that day. For
	 * the page cache (Cache::until()).
	 *
	 * @param list<array<string, mixed>> $rows
	 * @param non-empty-string $timezone
	 */
	public static function nextChange(array $rows, DateTimeImmutable $now, string $timezone = Menu::DEFAULT_TIMEZONE): DateTimeImmutable|null
	{
		$local = $now->setTimezone(new DateTimeZone($timezone));
		$clock = $local->format('H:i');
		$next = null;
		foreach (Menu::dishes($rows) as $dish) {
			$time = $dish['time'];
			if ($time === null || $time['mode'] !== 'until' || $time['at'] <= $clock) {
				continue;
			}
			$next = $next === null || $time['at'] < $next ? $time['at'] : $next;
		}
		if ($next === null) {
			return null;
		}
		[$hour, $minute] = array_map('intval', explode(':', $next));
		return $local->setTime($hour, $minute);
	}

	/** Has a dish `bis` a time passed at $clock (`HH:MM`)? */
	public static function over(array|null $time, string $clock): bool
	{
		return $time !== null && $time['mode'] === 'until' && $clock >= $time['at'];
	}
}
