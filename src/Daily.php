<?php

namespace Sayamaapps\Higawari;

use DateTimeImmutable;
use DateTimeZone;
use Kirby\Cms\App;
use Kirby\Cms\Page;

/**
 * The Kirby side of the menu: reads a page's fields and asks Menu for the card.
 * Not unit-tested on its own; tests/KirbyTest.php covers it through the page
 * method and the snippet.
 */
final class Daily
{
	/**
	 * The card of a page at $now (default: the clock in the configured zone),
	 * or null when the plugin is off, the menu is not valid that day or it has
	 * no usable dish. Nothing is cached: the answer changes with the clock.
	 *
	 * @return array{from: string, to: string, dishes: list<array{name: string, lines: list<string>, price: string, markers: string}>}|null
	 */
	public static function card(App $kirby, Page $page, DateTimeImmutable|null $now = null): array|null
	{
		if (!Options::active($kirby)) {
			return null;
		}
		$zone = Options::timezone($kirby);
		$now ??= new DateTimeImmutable('now', new DateTimeZone($zone));
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
}
