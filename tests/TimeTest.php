<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Cache;
use Sayamaapps\Higawari\I18n;
use Sayamaapps\Higawari\Menu;
use Sayamaapps\Higawari\Time;

/**
 * Time of day per dish (1.8): `bis` disappears at its time, `ab` stays all day
 * with a note, and the page cache expires when a dish disappears.
 */
#[CoversClass(Menu::class)]
#[CoversClass(Cache::class)]
#[CoversClass(Time::class)]
final class TimeTest extends TestCase
{
	/** A lunch dish until 15:00, an afternoon dish from 15:00, one all day. */
	private const ROWS = [
		['gericht' => 'Gemüsecurry mit Reis', 'preis' => '9,9', 'zeit' => 'bis', 'uhrzeit' => '15:00:00'],
		['gericht' => 'Rinderroulade', 'preis' => '14', 'zeit' => 'ab', 'uhrzeit' => '15:00:00'],
		['gericht' => 'Suppe', 'preis' => '7', 'zeit' => 'ganztags', 'uhrzeit' => '15:00:00'],
	];

	protected function setUp(): void
	{
		higawari_texts_without_app();
		I18n::setLanguage('de');
	}

	protected function tearDown(): void
	{
		App::destroy();
	}

	/** @return list<string> */
	private static function names(array|null $card): array
	{
		return $card === null ? [] : array_column($card['dishes'], 'name');
	}

	public function testClock(): void
	{
		foreach (['15:00' => '15:00', '9:30' => '09:30', '15:00:00' => '15:00', ' 07:05 ' => '07:05', '23:59' => '23:59', '0:00' => '00:00'] as $in => $out) {
			expect($out, Time::clock($in), "clock('$in')");
		}
		foreach (['', '15', '15 Uhr', '24:00', '15:60', '1500', '15:0', 'ab 15:00', '15:00:00:00'] as $in) {
			expect(null, Time::clock($in), "not a time: '$in'");
		}
	}

	public function testDishTime(): void
	{
		$dishes = Menu::dishes(self::ROWS);
		expect(['mode' => 'until', 'at' => '15:00'], $dishes[0]['time'], 'bis 15:00 from Kirby storage (15:00:00)');
		expect(['mode' => 'from', 'at' => '15:00'], $dishes[1]['time'], 'ab 15:00');
		expect(null, $dishes[2]['time'], 'ganztags: no time even with a stored time of day');

		// Anything not readable is all day: the dish is shown rather than lost.
		$odd = Menu::dishes([
			['gericht' => 'A', 'zeit' => 'bis'],
			['gericht' => 'B', 'zeit' => 'bis', 'uhrzeit' => ''],
			['gericht' => 'C', 'zeit' => 'bis', 'uhrzeit' => '25:00'],
			['gericht' => 'D', 'zeit' => 'mittags', 'uhrzeit' => '15:00'],
			['gericht' => 'E', 'uhrzeit' => '15:00'],
		]);
		expect([null, null, null, null, null], array_column($odd, 'time'), 'bis without a readable time, unknown zeit, no zeit: all day');
		$more = Menu::dishes([
			['gericht' => 'F', 'zeit' => ' BIS ', 'uhrzeit' => '14:30'],
			['gericht' => 'G', 'zeit' => ['bis'], 'uhrzeit' => 900],
		]);
		expect(['mode' => 'until', 'at' => '14:30'], $more[0]['time'], 'zeit is trimmed and case-insensitive');
		expect(null, $more[1]['time'], 'non-string values: all day');
	}

	public function testCardOverTheDay(): void
	{
		$names = fn (string $when): array => self::names(Menu::card('2026-11-20', self::ROWS, at($when)));
		$all = ['Gemüsecurry mit Reis', 'Rinderroulade', 'Suppe'];
		$after = ['Rinderroulade', 'Suppe'];
		expect($all, $names('2026-11-20 00:00'), 'midnight: all three');
		expect($all, $names('2026-11-20 11:00'), 'morning: lunch and afternoon dish both shown');
		expect($all, $names('2026-11-20 14:59:59'), '14:59:59: lunch dish still there');
		expect($after, $names('2026-11-20 15:00'), '15:00 sharp: lunch dish gone');
		expect($after, $names('2026-11-20 15:00:30'), '15:00:30: gone');
		expect($after, $names('2026-11-20 23:59:59'), 'late evening: gone');
		expect([], $names('2026-11-21 10:00'), 'next day: card over (days 1)');

		// The zone decides, not UTC: 14:30Z is 15:30 in Berlin in November.
		expect($after, self::names(Menu::card('2026-11-20', self::ROWS, new DateTimeImmutable('2026-11-20T14:30:00Z'))), '14:30 UTC = 15:30 Berlin: gone');
		expect($all, self::names(Menu::card('2026-11-20', self::ROWS, new DateTimeImmutable('2026-11-20T13:59:00Z'))), '13:59 UTC = 14:59 Berlin: there');
		// Summer time: 13:00Z is 15:00 CEST.
		expect($after, self::names(Menu::card('2026-07-10', self::ROWS, new DateTimeImmutable('2026-07-10T13:00:00Z'))), 'summer time: 13:00 UTC = 15:00 CEST: gone');

		// Multi-day window: the lunch dish is back the next morning.
		$days = fn (string $when): array => self::names(Menu::card('2026-11-20', self::ROWS, at($when), 2));
		expect($after, $days('2026-11-20 16:00'), 'two days, first afternoon: gone');
		expect($all, $days('2026-11-21 10:00'), 'two days, second morning: back');
		expect($all, self::names(Menu::card('2026-11-21', self::ROWS, at('2026-11-20 10:00'), 1, 'Europe/Berlin', '€', Menu::MODE_UNTIL)), 'mode until, morning: there');

		// Only a lunch dish: after 15:00 there is no card at all (no empty section).
		$lunch = [self::ROWS[0]];
		expect(['Gemüsecurry mit Reis'], self::names(Menu::card('2026-11-20', $lunch, at('2026-11-20 12:00'))), 'lunch only, noon: card');
		expect(null, Menu::card('2026-11-20', $lunch, at('2026-11-20 15:00')), 'lunch only, 15:00: no card');

		// Two lunch dishes with different times.
		$two = [
			['gericht' => 'Früh', 'zeit' => 'bis', 'uhrzeit' => '11:30'],
			['gericht' => 'Mittag', 'zeit' => 'bis', 'uhrzeit' => '15:00'],
			['gericht' => 'Abend', 'zeit' => 'ab', 'uhrzeit' => '18:00'],
		];
		expect(['Mittag', 'Abend'], self::names(Menu::card('2026-11-20', $two, at('2026-11-20 12:00'))), 'each bis has its own time');

		// Five rows, one gone: four shown, a sixth row never moves up.
		$six = [];
		foreach (range(1, 6) as $i) {
			$six[] = ['gericht' => "G$i"] + ($i === 1 ? ['zeit' => 'bis', 'uhrzeit' => '15:00'] : []);
		}
		expect(['G2', 'G3', 'G4', 'G5'], self::names(Menu::card('2026-11-20', $six, at('2026-11-20 16:00'))), 'cap of five counts before the time');

		// Without the new fields nothing changes (content from 1.7).
		$old = [['gericht' => 'Suppe', 'preis' => '7']];
		expect(['Suppe'], self::names(Menu::card('2026-11-20', $old, at('2026-11-20 23:00'))), '1.7 content: shown all day');
	}

	public function testLabel(): void
	{
		expect('bis 15 Uhr', Time::label(['mode' => 'until', 'at' => '15:00']), 'full hour without minutes');
		expect('ab 14:30 Uhr', Time::label(['mode' => 'from', 'at' => '14:30']), 'with minutes');
		expect('bis 9 Uhr', Time::label(['mode' => 'until', 'at' => '09:00']), 'no leading zero');
		I18n::setLanguage('en');
		expect('until 15:00', Time::label(['mode' => 'until', 'at' => '15:00']), 'English full hour');
		expect('from 9:30', Time::label(['mode' => 'from', 'at' => '09:30']), 'English with minutes');
	}

	public function testCache(): void
	{
		$ts = fn (string $when): int => at($when)->getTimestamp();
		// The day itself: expires when the lunch dish goes, then at midnight.
		expect($ts('2026-11-20 15:00'), Cache::until('2026-11-20', at('2026-11-20 10:00'), 1, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), 'morning: until 15:00');
		expect($ts('2026-11-20 15:00'), Cache::until('2026-11-20', at('2026-11-20 14:59:59'), 1, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), '14:59:59: until 15:00');
		expect($ts('2026-11-21 00:00'), Cache::until('2026-11-20', at('2026-11-20 15:00'), 1, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), '15:00: until midnight');
		// Several days: not the end of the window, because the dish is back tomorrow.
		expect($ts('2026-11-21 00:00'), Cache::until('2026-11-20', at('2026-11-20 16:00'), 3, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), 'three days, afternoon: next midnight');
		expect($ts('2026-11-21 15:00'), Cache::until('2026-11-20', at('2026-11-21 08:00'), 3, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), 'three days, second morning: 15:00');
		// Only `ab` and all-day dishes: as in 1.7, the end of the window.
		$noUntil = [self::ROWS[1], self::ROWS[2]];
		expect($ts('2026-11-23 00:00'), Cache::until('2026-11-20', at('2026-11-20 10:00'), 3, 'Europe/Berlin', Menu::MODE_FROM, $noUntil), 'ab only: end of window');
		expect($ts('2026-11-23 00:00'), Cache::until('2026-11-20', at('2026-11-20 10:00'), 3), 'no rows given: as before');
		// Not valid today: next midnight, the time does not matter.
		expect($ts('2026-11-20 00:00'), Cache::until('2026-11-20', at('2026-11-19 10:00'), 1, 'Europe/Berlin', Menu::MODE_FROM, self::ROWS), 'day before: midnight');
		expect(null, Time::nextChange(self::ROWS, at('2026-11-20 15:00')), 'no change after 15:00');
		expect(at('2026-11-20 15:00')->getTimestamp(), Time::nextChange(self::ROWS, at('2026-11-20 08:00'))?->getTimestamp(), 'next change 15:00');
		holds(Time::hasUntil(self::ROWS) && !Time::hasUntil($noUntil), 'hasUntilTime');
	}
}
