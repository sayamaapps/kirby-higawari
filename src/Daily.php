<?php

namespace Sayamaapps\Higawari;

use DateTimeImmutable;
use DateTimeZone;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Content\VersionId;

/**
 * The Kirby side of the menu: reads a page's fields and asks Menu for the card.
 * Not unit-tested on its own; tests/KirbyTest.php covers it through the page
 * method and the snippet.
 *
 * @package   Kirby Higawari
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
class Daily
{
	/**
	 * Handler of Annai's hook `sayamaapps.annai.business` (index.php): adds
	 * today's menu as `hasMenu` to the business node. Annai is optional; without
	 * it nobody calls this. The fields are returned as they are when the
	 * option `annai` is false, its page does not exist or no card is valid.
	 *
	 * @param array<string, mixed> $fields
	 * @return array<string, mixed>
	 */
	public static function annai(App $kirby, array $fields, DateTimeImmutable|null $now = null): array
	{
		$target = Options::output($kirby)['annai'];
		if ($target === false || isset($fields['hasMenu'])) {
			return $fields;
		}
		$page = $target === true ? $kirby->site()->homePage() : $kirby->page($target);
		$menu = $page instanceof Page ? self::schema($kirby, $page, $now) : null;
		return $menu === null ? $fields : $fields + ['hasMenu' => $menu];
	}

	/**
	 * Kirby's page cache for a response that shows a page's menu: with the
	 * option `cache` the response expires at cacheUntil(), else it is not
	 * cached at all (the default, as in 1.6). Used by both snippets. The
	 * preview of changes is never cached.
	 */
	public static function cacheResponse(App $kirby, Page|null $page, DateTimeImmutable|null $now = null): void
	{
		if ($page !== null && Options::output($kirby)['cache'] && !self::previewing($kirby)) {
			$kirby->response()->expires(self::cacheUntil($kirby, $page, $now));
			return;
		}
		$kirby->response()->cache(false);
	}

	/**
	 * Daily::card()'s arguments from the options, for cacheUntil().
	 *
	 * @return int Unix time, see Cache::until()
	 */
	public static function cacheUntil(App $kirby, Page $page, DateTimeImmutable|null $now = null): int
	{
		$zone = Options::timezone($kirby);
		$now = self::clock($kirby, $page, $now);
		$mode = Options::dateMode($kirby);
		return Cache::until(
			(string)$page->{Menu::FIELD_DATE}()->value(),
			$now,
			$mode === Menu::MODE_FROM ? Options::days($kirby) : Options::DEFAULT_DAYS,
			$zone,
			$mode,
			$page->{Menu::FIELD_DISHES}()->yaml(),
		);
	}

	/**
	 * The card of a page at $now (default: clock()), or null when the plugin is off, the menu is not valid that day or it has
	 * no usable dish. Nothing is cached: the answer changes with the clock.
	 *
	 * @return array{from: string, to: string, dishes: list<array{name: string, lines: list<string>, price: string, amount: string, markers: string, variant: array{label: string, price: string, amount: string}|null, time: array{mode: 'until'|'from', at: string}|null}>}|null
	 */
	public static function card(App $kirby, Page $page, DateTimeImmutable|null $now = null): array|null
	{
		if (!Options::active($kirby)) {
			return null;
		}
		$zone = Options::timezone($kirby);
		$now = self::clock($kirby, $page, $now);
		$mode = Options::dateMode($kirby);
		return Menu::card(
			(string)$page->{Menu::FIELD_DATE}()->value(),
			$page->{Menu::FIELD_DISHES}()->yaml(),
			$now,
			// `days` only counts in mode `from`; in mode `until` it is not read at all.
			$mode === Menu::MODE_FROM ? Options::days($kirby) : Options::DEFAULT_DAYS,
			$zone,
			Options::currency($kirby),
			$mode,
		);
	}

	/**
	 * The clock a page's menu is shown at: $now when given, else now in the
	 * configured zone. In the preview of changes (Kirby renders the `changes`
	 * version, a Panel preview or a link with `_version=changes` and its
	 * token) of a card whose first day is after today, it is 00:00 of that day
	 * instead: the card looks as it will on its day, a dish `bis` a time
	 * included. Mode `until` has no first day and is shown as it is. Option
	 * `preview` false switches this off.
	 */
	public static function clock(App $kirby, Page $page, DateTimeImmutable|null $now = null): DateTimeImmutable
	{
		if ($now !== null) {
			return $now;
		}
		$zone = Options::timezone($kirby);
		$now = new DateTimeImmutable('now', new DateTimeZone($zone));
		if (!self::previewing($kirby) || Options::dateMode($kirby) !== Menu::MODE_FROM) {
			return $now;
		}
		$date = trim((string)$page->{Menu::FIELD_DATE}()->value());
		if (!Menu::isDate($date) || $date <= $now->format('Y-m-d')) {
			return $now;
		}
		$day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone($zone));
		return $day === false ? $now : $day;
	}

	/**
	 * The line under the dishes: the page field `tageskarteHinweis`, or, when
	 * it is empty, the option `note`. Null shows nothing.
	 */
	public static function note(App $kirby, Page|null $page): string|null
	{
		$field = $page !== null ? trim((string)$page->{Menu::FIELD_NOTE}()->value()) : '';
		return $field !== '' ? $field : Options::note($kirby);
	}

	/**
	 * Is Kirby rendering the `changes` version (the preview of unsaved
	 * changes, Kirby's VersionId::$render) and is the option `preview` on?
	 */
	private static function previewing(App $kirby): bool
	{
		return VersionId::$render?->is(VersionId::CHANGES) === true && Options::output($kirby)['preview'];
	}

	/**
	 * The schema.org `Menu` of a page's card at $now (Schema::menu()), with
	 * the `@id` `<page url>#tageskarte`, or null when there is no card.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function schema(App $kirby, Page $page, DateTimeImmutable|null $now = null): array|null
	{
		$card = self::card($kirby, $page, $now);
		if ($card === null) {
			return null;
		}
		Options::applyLanguage($kirby);
		$heading = I18n::translate('snippet.heading');
		return Schema::menu(
			$card,
			$heading,
			$heading . ' ' . Menu::label($card['from'], $card['to'], Options::dateMode($kirby)),
			Options::output($kirby)['priceCurrency'],
			$page->url() . '#tageskarte',
		);
	}
}
