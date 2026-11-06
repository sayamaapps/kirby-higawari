<?php

namespace Sayamaapps\Higawari;

use DateTimeImmutable;
use DateTimeZone;

/**
 * How long a page with the daily menu stays as it is, for Kirby's page cache
 * (README, Caching). No App object is needed; Daily::cacheUntil() reads the
 * options.
 *
 * @package   Kirby Higawari
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
class Cache
{
	/**
	 * Until when a page showing a menu dated $date stays as it is: the Unix
	 * time of the next midnight in $timezone after $now, or, while the menu is
	 * valid, the midnight after its last day (the card and its label stay the
	 * same over the whole window). For Kirby's page cache:
	 * `kirby()->response()->expires(...)`. A changed menu is a Panel save,
	 * which flushes Kirby's page cache anyway.
	 *
	 * @param non-empty-string $timezone
	 */
	public static function until(
		string $date,
		DateTimeImmutable $now,
		int $days = 1,
		string $timezone = Menu::DEFAULT_TIMEZONE,
		string $mode = Menu::MODE_FROM
	): int {
		$zone = new DateTimeZone($timezone);
		$local = $now->setTimezone($zone);
		$last = $local->format('Y-m-d');
		$window = Menu::window($date, $days, $timezone, $mode);
		if ($window !== null && Menu::validOn($date, $now, $days, $timezone, $mode)) {
			$last = $window['to'];
		}
		$midnight = DateTimeImmutable::createFromFormat('!Y-m-d', $last, $zone);
		assert($midnight !== false);
		return $midnight->modify('+1 day')->getTimestamp();
	}
}
