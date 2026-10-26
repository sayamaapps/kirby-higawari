<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/src/I18n.php';
require_once dirname(__DIR__) . '/src/Menu.php';

use Higawari\I18n;
use Higawari\Menu;

#[CoversClass(Higawari\Menu::class)]
final class MenuTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
		I18n::setLanguage('de');

		$zone = 'Europe/Berlin';

		// Dates: strict Y-m-d, no overflow, no trailing text.
		foreach (['2026-10-10', ' 2026-10-10 ', '2026-02-28'] as $date) {
			expect(true, Menu::isDate($date), "isDate('$date')");
		}

		foreach (['', '2026-02-31', 'morgen', '2026-10-10x', '10.10.2026', '2026-1-1'] as $date) {
			expect(false, Menu::isDate($date), "not a date: '$date'");
		}

		// Shown on the day itself, Berlin time.
		expect(true, Menu::validOn('2026-10-10', at('2026-10-10 00:00'), 1, $zone), 'valid from 00:00 on the day');

		expect(true, Menu::validOn('2026-10-10', at('2026-10-10 23:59:59'), 1, $zone), 'valid until 23:59:59');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-09 23:59:59'), 1, $zone), 'not on the day before');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-11 00:00'), 1, $zone), 'not on the day after');

		expect(false, Menu::validOn('', at('2026-10-10 12:00'), 1, $zone), 'no date: never shown');

		expect(false, Menu::validOn('2026-02-31', at('2026-03-03 12:00'), 1, $zone), 'impossible date: never shown');

		// Time zones: the day is the configured zone's day, not UTC's.
		expect(true, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-09T22:30:00Z'), 1, $zone), '00:30 in Berlin is already the 10th');

		expect(false, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-09T22:30:00Z'), 1, 'UTC'), 'the same instant is still the 9th in UTC');

		expect(true, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-10T21:59:59Z'), 1, $zone), 'last second of the 10th in Berlin');

		expect(false, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-10T22:00:00Z'), 1, $zone), '22:00 UTC is midnight in Berlin: the 11th');

		// Winter time starts on 25.10.2026 (clocks go back at 03:00 CEST).
		expect(true, Menu::validOn('2026-10-25', new DateTimeImmutable('2026-10-25T22:59:59Z'), 1, $zone), 'winter time: last moment of 25.10.');

		expect(false, Menu::validOn('2026-10-25', new DateTimeImmutable('2026-10-25T23:00:00Z'), 1, $zone), 'winter time: 26.10. has begun');

		// Windows of N days, inclusive.
		expect(['from' => '2026-10-10', 'to' => '2026-10-12'], Menu::window('2026-10-10', 3, $zone), 'window of three days');

		expect(null, Menu::window('2026-10-10', 0, $zone), 'zero days: no window');

		expect(null, Menu::window('morgen', 3, $zone), 'invalid date: no window');

		expect(true, Menu::validOn('2026-10-10', at('2026-10-12 23:59:59'), 3, $zone), 'last day of the window is valid');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-13 00:00'), 3, $zone), 'day after the window is not');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-09 12:00'), 3, $zone), 'day before the window is not');

		expect(true, Menu::validOn('2026-10-24', at('2026-10-26 12:00'), 3, $zone), 'window across the change to winter time');

		expect(false, Menu::validOn('2026-10-24', at('2026-10-27 00:00'), 3, $zone), 'window across the change ends on time');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-10 12:00'), 0, $zone), 'zero days: never valid');

		// Prices: decimal comma, euro sign, optional, unreadable refused.
		expect('7,50 €', Menu::formatPrice('7,5'), 'comma price with euro sign');

		expect('7,50', Menu::formatPrice('7.5', ''), 'no currency: bare number');

		expect('12,50 €', Menu::formatPrice('12.50'), 'dot price');

		expect('', Menu::formatPrice(' '), 'empty price means no price');

		expect('999,00 €', Menu::formatPrice('999'), 'upper bound');

		foreach (['1e2', 'abc', '1000', '12.345', '-1', '7,5,5'] as $price) {
			expect(null, Menu::formatPrice($price), "unreadable price '$price' is refused");
		}

		// Dishes: cleaned, broken rows dropped, order kept.
		expect([], Menu::dishes([]), 'no rows');

		$dishes = Menu::dishes([
			['gericht' => ' Suppe ', 'preis' => '7,5', 'beschreibung' => "Mit Brot\r\n\n  Kräutern  \n", 'kennzeichnungen' => ' V, L '],
			['gericht' => 'Gemüse', 'preis' => 12.5],
			['gericht' => 'Ohne Preis'],
			['gericht' => 'Verschrieben', 'preis' => 'abc'],
			['gericht' => '', 'preis' => '5'],
			['preis' => '5'],
			['gericht' => 'Schalter', 'preis' => true],
			['gericht' => 'Liste', 'preis' => []],
			null,
			'Text',
		]);

		expect(3, count($dishes), 'only the usable rows are kept');

		expect(
			['name' => 'Suppe', 'lines' => ['Mit Brot', 'Kräutern'], 'price' => '7,50 €', 'markers' => 'V, L'],
			$dishes[0],
			'first dish cleaned: trimmed, lines split, markers trimmed',
		);

		expect('12,50 €', $dishes[1]['price'], 'float price formatted');

		expect(['name' => 'Ohne Preis', 'lines' => [], 'price' => '', 'markers' => ''], $dishes[2], 'dish without price, lines or markers');

		expect('7,00', Menu::dishes([['gericht' => 'A', 'preis' => '7']], '')[0]['price'], 'dishes without currency');

		$many = Menu::dishes(array_map(static fn (int $i) => ['gericht' => "Gericht $i", 'preis' => 5], range(1, 8)));

		expect(5, count($many), 'at most five dishes');

		expect('Gericht 5', $many[4]['name'], 'the first five, in order');

		// Card: only on a valid day with at least one dish.
		$rows = [['gericht' => 'Suppe', 'preis' => '7']];

		expect(
			['from' => '2026-10-10', 'to' => '2026-10-10', 'dishes' => [['name' => 'Suppe', 'lines' => [], 'price' => '7,00 €', 'markers' => '']]],
			Menu::card('2026-10-10', $rows, at('2026-10-10 12:00'), 1, $zone, '€'),
			'card on its day',
		);

		expect(null, Menu::card('2026-10-11', $rows, at('2026-10-10 12:00'), 1, $zone), 'no card on another day');

		expect(null, Menu::card('2026-10-10', [['gericht' => '']], at('2026-10-10 12:00'), 1, $zone), 'no card without a usable dish');

		expect(null, Menu::card('', $rows, at('2026-10-10 12:00'), 1, $zone), 'no card without a date');

		// Labels, German and English.
		expect('Samstag, 10.10.2026', Menu::displayDate('2026-10-10'), 'German weekday and date');

		expect('', Menu::displayDate('2026-02-31'), 'invalid date: empty label');

		expect('Samstag, 10.10.2026', Menu::label('2026-10-10', '2026-10-10'), 'one day: one date');

		expect('Samstag, 10.10.2026 – Montag, 12.10.2026', Menu::label('2026-10-10', '2026-10-12'), 'several days: from – to');

		I18n::setLanguage('en');

		expect('Saturday, 10 October 2026', Menu::displayDate('2026-10-10'), 'English weekday and date');

		I18n::setLanguage('de');

		// Mode until: the date is the last day shown, there is no first day, days are ignored.
		$until = Menu::MODE_UNTIL;

		expect(['from' => null, 'to' => '2026-10-30'], Menu::window('2026-10-30', 3, $zone, $until), 'until: no first day, the date is the last day');

		expect(true, Menu::validOn('2026-10-30', at('2026-10-30 12:00'), 1, $zone, $until), 'until: shown on the date itself');

		expect(true, Menu::validOn('2026-10-30', at('2026-10-30 23:59:59'), 1, $zone, $until), 'until: shown until 23:59:59 of the date');

		expect(true, Menu::validOn('2026-10-30', at('2026-10-01 08:00'), 1, $zone, $until), 'until: shown long before the date (no first day)');

		expect(false, Menu::validOn('2026-10-30', at('2026-10-31 00:00'), 1, $zone, $until), 'until: not the day after the date');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-11 12:00'), 1, $zone, $until), 'until: a past date (yesterday) is not shown');

		expect(true, Menu::validOn('2026-10-25', at('2026-10-25 12:00'), 1, $zone, $until), 'until: a date of today is shown today');

		expect(false, Menu::validOn('', at('2026-10-25 12:00'), 1, $zone, $until), 'until: no date, never shown');

		expect(false, Menu::validOn('2026-02-31', at('2026-02-01 12:00'), 1, $zone, $until), 'until: impossible date, never shown');

		expect(true, Menu::validOn('2026-10-10', at('2026-10-10 12:00'), 5, $zone, $until), 'until: days=5 is ignored, the date itself is still the last day');

		expect(false, Menu::validOn('2026-10-10', at('2026-10-11 12:00'), 5, $zone, $until), 'until: days=5 does not extend the last day');

		expect(true, Menu::validOn('2026-10-10', at('2026-10-10 12:00'), 0, $zone, $until), 'until: days=0 does not matter');

		// Time zones in mode until: the last day is the zone's day.
		expect(true, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-10T21:59:59Z'), 1, $zone, $until), 'until: 23:59:59 in Berlin is still the 10th');

		expect(false, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-10T22:00:00Z'), 1, $zone, $until), 'until: 22:00 UTC is midnight in Berlin, the 11th');

		expect(true, Menu::validOn('2026-10-10', new DateTimeImmutable('2026-10-10T22:00:00Z'), 1, 'UTC', $until), 'until: in UTC the same instant is still the 10th');

		// Card in mode until: from is today in the zone, to is the date.
		expect(
			['from' => '2026-10-25', 'to' => '2026-10-30', 'dishes' => [['name' => 'Suppe', 'lines' => [], 'price' => '7,00 €', 'markers' => '']]],
			Menu::card('2026-10-30', $rows, at('2026-10-25 12:00'), 1, $zone, '€', $until),
			'until: card runs from today to the date',
		);

		expect(
			['from' => '2026-10-26', 'to' => '2026-10-26', 'dishes' => [['name' => 'Suppe', 'lines' => [], 'price' => '7,00 €', 'markers' => '']]],
			Menu::card('2026-10-26', $rows, new DateTimeImmutable('2026-10-25T23:30:00Z'), 1, $zone, '€', $until),
			'until: from is the zone\'s day (00:30 on 26.10. in Berlin)',
		);

		expect(
			'2026-10-25',
			Menu::card('2026-10-26', $rows, new DateTimeImmutable('2026-10-25T23:30:00Z'), 1, 'UTC', '€', $until)['from'],
			'until: the same instant is still the 25th in UTC',
		);

		expect(null, Menu::card('2026-10-24', $rows, at('2026-10-25 12:00'), 1, $zone, '€', $until), 'until: no card after the date');

		expect(null, Menu::card('', $rows, at('2026-10-25 12:00'), 1, $zone, '€', $until), 'until: no card without a date');

		expect(null, Menu::card('2026-10-30', [['gericht' => '']], at('2026-10-25 12:00'), 1, $zone, '€', $until), 'until: no card without a usable dish');

		// Label in mode until: the last day only.
		expect('Bis einschließlich Freitag, 30.10.2026', Menu::label('2026-10-25', '2026-10-30', $until), 'until: German label');

		I18n::setLanguage('en');

		expect('Until Friday, 30 October 2026', Menu::label('2026-10-25', '2026-10-30', $until), 'until: English label');

		I18n::setLanguage('de');

		expect('Samstag, 10.10.2026', Menu::label('2026-10-10', '2026-10-10', Menu::MODE_FROM), 'from: label unchanged by the mode argument');

		// Clearing empties dishes and date together.
		expect(['tagesgerichte' => [], 'tageskarteDatum' => ''], Menu::CLEARED, 'CLEARED');
	}
}
