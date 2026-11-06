<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Cache;
use Sayamaapps\Higawari\Menu;
use Sayamaapps\Higawari\Schema;

/**
 * Schema::menu(), Menu::amount() and Cache::until() without an App.
 */
#[CoversClass(Sayamaapps\Higawari\Cache::class)]
#[CoversClass(Sayamaapps\Higawari\Menu::class)]
#[CoversClass(Sayamaapps\Higawari\Schema::class)]
final class SchemaTest extends TestCase
{
	protected function setUp(): void
	{
		higawari_texts_without_app();
	}

	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testAmountIsADecimalWithAPoint(): void
	{
		expect('7.50', Menu::amount('7,5'), 'comma becomes a point, two places');
		expect('12.00', Menu::amount('12'), 'whole number gets two places');
		expect('6.90', Menu::amount('6.9'), 'point stays');
		expect('', Menu::amount(' '), 'empty is no price');
		expect(null, Menu::amount('abc'), 'unreadable');
		expect(null, Menu::amount('1000'), 'above 999');
		expect('7,50 €', Menu::formatPrice('7,5'), 'formatPrice unchanged');
	}

	public function testCacheUntil(): void
	{
		$berlin = new DateTimeZone('Europe/Berlin');
		$midnight = static fn (string $day): int => (new DateTimeImmutable($day . ' 00:00', $berlin))->getTimestamp();

		expect($midnight('2026-10-11'), Cache::until('2026-10-10', at('2026-10-10 12:00')), 'valid card: until the end of its day');
		expect($midnight('2026-10-13'), Cache::until('2026-10-10', at('2026-10-10 12:00'), 3), 'three-day window: until the end of the last day');
		expect($midnight('2026-10-13'), Cache::until('2026-10-10', at('2026-10-12 08:00'), 3), 'last day of the window');
		expect($midnight('2026-10-10'), Cache::until('2026-10-10', at('2026-10-09 18:00')), 'card of tomorrow: until midnight, when it starts');
		expect($midnight('2026-10-12'), Cache::until('2026-10-10', at('2026-10-11 09:00')), 'expired card: until the next midnight');
		expect($midnight('2026-10-11'), Cache::until('', at('2026-10-10 23:59:59')), 'no date: until the next midnight');
		expect($midnight('2026-10-31'), Cache::until('2026-10-30', at('2026-10-27 10:00'), 1, 'Europe/Berlin', Menu::MODE_UNTIL), 'mode until: the midnight after the last day');
		expect($midnight('2026-10-26'), Cache::until('2026-10-25', at('2026-10-25 12:00')), 'day of the clock change: the local midnight');
		expect(
			(new DateTimeImmutable('2026-10-11 00:00', new DateTimeZone('America/New_York')))->getTimestamp(),
			Cache::until('2026-10-10', at('2026-10-10 12:00', 'America/New_York'), 1, 'America/New_York'),
			'another zone: its own midnight'
		);
	}

	public function testSchemaOfACard(): void
	{
		$card = Menu::card('2026-10-10', [
			['gericht' => 'Kartoffelsuppe', 'preis' => '6,9', 'beschreibung' => "mit Brot\nund Kräutern"],
			['gericht' => 'Ohne Preis'],
			['gericht' => 'Linseneintopf', 'preis' => '9,9', 'variante' => 'mit Wiener', 'variantenpreis' => '11,5'],
			['gericht' => 'Salat', 'preis' => '8', 'variante' => 'groß'],
		], at('2026-10-10 12:00'));
		holds($card !== null, 'card valid on the day');
		$menu = Schema::menu($card, 'Tageskarte', 'Tageskarte Samstag, 10.10.2026', 'EUR', 'https://example.com/#tageskarte');

		expect('Menu', $menu['@type'], 'type Menu');
		expect('https://example.com/#tageskarte', $menu['@id'], '@id');
		expect('Tageskarte', $menu['name'], 'menu name');
		$section = $menu['hasMenuSection'];
		expect('MenuSection', $section['@type'], 'one MenuSection');
		expect('Tageskarte Samstag, 10.10.2026', $section['name'], 'section named after the date');
		$items = $section['hasMenuItem'];
		expect(4, count($items), 'one MenuItem per dish');

		expect([
			'@type' => 'MenuItem',
			'name' => 'Kartoffelsuppe',
			'description' => 'mit Brot, und Kräutern',
			'offers' => ['@type' => 'Offer', 'price' => '6.90', 'priceCurrency' => 'EUR'],
		], $items[0], 'dish: description from the lines, one Offer, decimal with a point');
		expect(['@type' => 'MenuItem', 'name' => 'Ohne Preis'], $items[1], 'dish without a price: no offers');
		expect([
			['@type' => 'Offer', 'price' => '9.90', 'priceCurrency' => 'EUR'],
			['name' => 'Linseneintopf mit Wiener', '@type' => 'Offer', 'price' => '11.50', 'priceCurrency' => 'EUR'],
		], $items[2]['offers'], 'variant with its own price: a second Offer of the same MenuItem');
		expect(['@type' => 'Offer', 'price' => '8.00', 'priceCurrency' => 'EUR'], $items[3]['offers'], 'variant without a price adds no Offer');

		$bare = Schema::menu($card, 'Tageskarte', 'Tageskarte', '', '');
		holds(!isset($bare['@id']), 'no @id when empty');
		holds(!isset($bare['hasMenuSection']['hasMenuItem'][0]['offers']['priceCurrency']), 'no priceCurrency when the code is empty');
	}
}
