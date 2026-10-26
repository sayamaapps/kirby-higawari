<?php

namespace Sayamaapps\Higawari;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Rules of the daily menu, Kirby-free (tests/MenuTest.php).
 *
 * A menu is one date (`tageskarteDatum`, the day it is valid on) and up to
 * five dishes (`tagesgerichte`). It is shown on every day of its window: the
 * date itself, or `sayamaapps.higawari.days` days counted from it. In mode
 * `until` the date is the last day instead, and the window has no first day.
 * Without a date nothing is shown, so a forgotten card cannot linger for weeks.
 *
 * Rules: every price passes the same check, at most five dishes are shown,
 * clearing removes the date and all dishes together, the label shows one date
 * or a from-to range for a multi-day window, and a dish description may span
 * several lines.
 *
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
final class Menu
{
	/**
	 * What “clear the menu” writes: all dishes and the date together. Keeping
	 * the date would hide a card entered the next day, because the old date
	 * has already passed.
	 */
	public const CLEARED = [self::FIELD_DISHES => [], self::FIELD_DATE => ''];
	public const DEFAULT_TIMEZONE = 'Europe/Berlin';

	/** Content field names. Sites already using them need no migration. */
	public const FIELD_DATE = 'tageskarteDatum';
	public const FIELD_DISHES = 'tagesgerichte';

	/** Dishes shown at most; the blueprint allows the same number. */
	public const MAX_DISHES = 5;

	/**
	 * What the date means (`sayamaapps.higawari.dateMode`). `from`: the first
	 * day, the menu is shown for `days` days from it. `until`: the last day,
	 * the menu is shown on every day up to and including it, `days` ignored.
	 */
	public const MODE_FROM = 'from';
	public const MODE_UNTIL = 'until';
	public const MODES = [self::MODE_FROM, self::MODE_UNTIL];

	/**
	 * The card to show at $now, or null when the menu is not valid that day or
	 * has no usable dish. In mode `until` `from` is today (in $timezone): the
	 * card starts being shown on the day it is read.
	 *
	 * @param list<array<string, mixed>> $rows
	 * @return array{from: string, to: string, dishes: list<array{name: string, lines: list<string>, price: string, markers: string}>}|null
	 */
	public static function card(
		string $date,
		array $rows,
		DateTimeImmutable $now,
		int $days = 1,
		string $timezone = self::DEFAULT_TIMEZONE,
		string $currency = '€',
		string $mode = self::MODE_FROM
	): array|null {
		if (!self::validOn($date, $now, $days, $timezone, $mode)) {
			return null;
		}
		$dishes = self::dishes($rows, $currency);
		if ($dishes === []) {
			return null;
		}
		$window = self::window($date, $days, $timezone, $mode);
		$from = $window['from'] ?? $now->setTimezone(new DateTimeZone($timezone))->format('Y-m-d');
		return ['from' => $from, 'to' => $window['to'], 'dishes' => $dishes];
	}

	/**
	 * Cleaned dishes from the structure field, at most MAX_DISHES, in order.
	 * Each: name, lines (the description split at line breaks), price (already
	 * formatted, '' for none) and markers (free text).
	 *
	 * A row without a name is skipped, as is a row whose price cannot be read.
	 * Imported content without Panel validation is handled the same way.
	 *
	 * @return list<array{name: string, lines: list<string>, price: string, markers: string}>
	 */
	public static function dishes(array $rows, string $currency = '€'): array
	{
		$dishes = [];
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$name = is_string($row['gericht'] ?? null) ? trim($row['gericht']) : '';
			$raw = $row['preis'] ?? '';
			if ($name === '' || is_bool($raw) || !is_scalar($raw)) {
				continue;
			}
			$price = self::formatPrice((string)$raw, $currency);
			if ($price === null) {
				continue;
			}
			$dishes[] = [
				'name' => $name,
				'lines' => self::lines(is_string($row['beschreibung'] ?? null) ? $row['beschreibung'] : ''),
				'price' => $price,
				'markers' => is_string($row['kennzeichnungen'] ?? null) ? trim($row['kennzeichnungen']) : '',
			];
			if (count($dishes) === self::MAX_DISHES) {
				break;
			}
		}
		return $dishes;
	}

	/** „Samstag, 10.10.2026“ (German) or „Saturday, 10 October 2026“. Invalid date gives ''. */
	public static function displayDate(string $date): string
	{
		if (!self::isDate($date)) {
			return '';
		}
		$day = DateTimeImmutable::createFromFormat('!Y-m-d', trim($date), new DateTimeZone('UTC'));
		return I18n::day((int)$day->format('N')) . ', ' . $day->format(I18n::t('format.date'));
	}

	/**
	 * A price from the Panel as text with a decimal comma: "7,50 €" (or
	 * "7,50" without a currency). Empty means "no price" and gives ''. An
	 * unreadable price gives null, and the dish is left out rather than shown
	 * with a wrong amount.
	 */
	public static function formatPrice(string $value, string $currency = '€'): string|null
	{
		$value = str_replace(',', '.', trim($value));
		if ($value === '') {
			return '';
		}
		if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D', $value) || (float)$value > 999) {
			return null;
		}
		$formatted = number_format((float)$value, 2, ',', '.');
		return $currency === '' ? $formatted : $formatted . ' ' . $currency;
	}

	/** Strict `Y-m-d`, surrounding whitespace allowed, no overflow (2026-02-31 is not a date). */
	public static function isDate(string $date): bool
	{
		$date = trim($date);
		$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
		return $parsed !== false && $parsed->format('Y-m-d') === $date;
	}

	/**
	 * The label above a card: one date, or „from – to“ for a multi-day window.
	 * Mode `until`: „Bis einschließlich Freitag, 30.10.2026“ (the last day only).
	 */
	public static function label(string $from, string $to, string $mode = self::MODE_FROM): string
	{
		if ($mode === self::MODE_UNTIL) {
			return I18n::t('snippet.until', ['date' => self::displayDate($to)]);
		}
		$first = self::displayDate($from);
		if ($from === $to) {
			return $first;
		}
		return I18n::t('snippet.range', ['from' => $first, 'to' => self::displayDate($to)]);
	}

	/** @return list<string> */
	private static function lines(string $text): array
	{
		return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
	}

	/**
	 * Is a menu dated $date shown at $now? The day is taken in $timezone, so
	 * 00:30 on the 10th in Berlin is the 10th even if it is still the 9th in UTC.
	 */
	public static function validOn(
		string $date,
		DateTimeImmutable $now,
		int $days = 1,
		string $timezone = self::DEFAULT_TIMEZONE,
		string $mode = self::MODE_FROM
	): bool {
		$window = self::window($date, $days, $timezone, $mode);
		if ($window === null) {
			return false;
		}
		$today = $now->setTimezone(new DateTimeZone($timezone))->format('Y-m-d');
		return ($window['from'] === null || $today >= $window['from']) && $today <= $window['to'];
	}

	/**
	 * First and last day (inclusive, Y-m-d) on which a menu dated $date is
	 * shown. Mode `from`: `$days` days counted from that date, and fewer than
	 * one day gives null. Mode `until`: no first day (`from` is null), the
	 * last day is $date, and `$days` is not used.
	 *
	 * @return array{from: string|null, to: string}|null
	 */
	public static function window(
		string $date,
		int $days,
		string $timezone = self::DEFAULT_TIMEZONE,
		string $mode = self::MODE_FROM
	): array|null {
		if (!self::isDate($date)) {
			return null;
		}
		$day = DateTimeImmutable::createFromFormat('!Y-m-d', trim($date), new DateTimeZone($timezone));
		if ($mode === self::MODE_UNTIL) {
			return ['from' => null, 'to' => $day->format('Y-m-d')];
		}
		if ($days < 1) {
			return null;
		}
		return [
			'from' => $day->format('Y-m-d'),
			'to' => $day->modify('+' . ($days - 1) . ' days')->format('Y-m-d'),
		];
	}
}
