<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Menu;
use Sayamaapps\Higawari\Options;

/** Saturday 10.10.2026 with three dishes: one with line breaks and markers, one with a price in comma form, one without a price. */
const HIGAWARI_TEST_HOME = [
	'einleitung' => 'Willkommen',
	'tageskarteDatum' => '2026-10-10',
	'tagesgerichte' => [
		['gericht' => 'Kartoffelsuppe', 'preis' => 6.9, 'beschreibung' => "mit Brot\nund Kräutern", 'kennzeichnungen' => 'V, L'],
		['gericht' => '<b>Gulasch</b>', 'preis' => '12,5'],
		['gericht' => 'Ohne Preis'],
	],
];

/** Options for an active plugin with German texts. */
const HIGAWARI_TEST_ON = ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'de'];

function higawari_render(Kirby\Cms\App $kirby, array $data = []): string
{
	return $kirby->snippet('higawari', $data, true);
}

#[CoversNothing]
#[RunTestsInSeparateProcesses]
final class KirbyTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
		$noon = at('2026-10-10 12:00');

		$nextDay = at('2026-10-11 12:00');

		higawari_with_site(HIGAWARI_TEST_HOME, function (string $tmp) use ($noon, $nextDay) {
			// Off by default: nothing printed, the page method answers null.
			$kirby = higawari_kirby($tmp);
			expect('', higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]), 'off: snippet prints nothing');
			expect(null, $kirby->page('home')->higawariCard($noon), 'off: page method is null');

			// On, on the day itself.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON);
			$page = $kirby->page('home');
			$html = higawari_render($kirby, ['source' => $page, 'now' => $noon]);
			holds(str_starts_with($html, '<section class="higawari" aria-labelledby="higawari-titel">'), 'on: section with labelled heading');
			holds(str_contains($html, '<h2 class="higawari-titel" id="higawari-titel">Tageskarte</h2>'), 'on: German heading');
			holds(str_contains($html, '<time datetime="2026-10-10">Samstag, 10.10.2026</time>'), 'on: date with weekday');
			holds(str_contains($html, '<span class="higawari-name">Kartoffelsuppe</span>'), 'on: dish name');
			holds(str_contains($html, '<span class="higawari-preis">6,90 €</span>'), 'on: price with decimal comma and euro');
			holds(str_contains($html, '<p class="higawari-beschreibung">mit Brot<br>und Kräutern</p>'), 'on: description lines joined by line breaks');
			holds(str_contains($html, '<p class="higawari-kennzeichnungen">Kennzeichnungen: V, L</p>'), 'on: markers');
			holds(str_contains($html, '<span class="higawari-name">&lt;b&gt;Gulasch&lt;/b&gt;</span>'), 'on: dish name escaped');
			holds(str_contains($html, '<span class="higawari-preis">12,50 €</span>'), 'on: numeric text price formatted');
			expect(2, substr_count($html, 'class="higawari-preis"'), 'on: dish without price has no price span');
			expect(3, substr_count($html, '<li class="higawari-gericht">'), 'on: three dishes');
			holds(!str_contains($html, '<b>Gulasch'), 'on: no raw markup from the Panel');
			holds(!str_contains($html, 'higawari-leer') && !str_contains($html, 'higawari-hinweis'), 'on: no empty text, no note by default');
			holds(
				!str_contains($html, 'http') && !str_contains($html, '<script') && !str_contains($html, '<img') && !str_contains($html, 'src='),
				'on: no request to another server, no script, no image',
			);
			expect(3, count($page->higawariCard($noon)['dishes']), 'on: page method returns the dishes');

			// The next day: nothing (and no empty text by default).
			expect('', higawari_render($kirby, ['source' => $page, 'now' => $nextDay]), 'next day: nothing printed');
			expect(false, $kirby->response()->cache(), 'next day: the page is not cached either, so tomorrow\'s menu appears');
			expect(null, $page->higawariCard($nextDay), 'next day: page method null');

			// emptyText is shown instead when no menu is valid.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.emptyText' => 'Heute keine Tageskarte.']);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $nextDay]);
			holds(str_contains($html, '<p class="higawari-leer">Heute keine Tageskarte.</p>'), 'emptyText: shown when no menu is valid');
			holds(!str_contains($html, 'higawari-gericht'), 'emptyText: no dish list');
			holds(str_contains(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]), 'higawari-gericht'), 'emptyText: menu still shown on its day');

			// Window of three days, label as a range.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.days' => 3]);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => at('2026-10-12 23:59:59')]);
			holds(str_contains($html, '<time datetime="2026-10-10">Samstag, 10.10.2026 – Montag, 12.10.2026</time>'), 'days=3: range label on the last day');
			expect('', higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => at('2026-10-13 00:00')]), 'days=3: nothing after the window');

			// Currency: empty shows bare numbers.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.currency' => '']);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]);
			holds(str_contains($html, '<span class="higawari-preis">6,90</span>'), 'currency empty: bare price');

			// Note under the dishes.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.note' => 'Alle Preise inklusive MwSt.']);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]);
			holds(str_contains($html, '<p class="higawari-hinweis">Alle Preise inklusive MwSt.</p>'), 'note: shown under the dishes');

			// Nested spelling works too.
			$kirby = higawari_kirby($tmp, ['sayamaapps' => ['higawari' => ['active' => true, 'language' => 'de']]]);
			expect(
				3,
				substr_count(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]), '<li class="higawari-gericht">'),
				'nested options: plugin switched on',
			);

			// English texts follow the language option.
			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'en']);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]);
			holds(str_contains($html, '<h2 class="higawari-titel" id="higawari-titel">Daily menu</h2>'), 'English: heading');
			holds(str_contains($html, '<time datetime="2026-10-10">Saturday, 10 October 2026</time>'), 'English: date');
			holds(str_contains($html, 'Markers: V, L'), 'English: markers');

			// Time zone: the day is the configured zone's day.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON);
			$late = new DateTimeImmutable('2026-10-09T23:30:00Z');
			expect(true, $kirby->page('home')->higawariCard($late) !== null, 'Europe/Berlin default: 00:30 on the 10th shows the menu');
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.timezone' => 'UTC']);
			expect(null, $kirby->page('home')->higawariCard($late), 'timezone UTC: the same instant is still the 9th');
			expect(true, $kirby->page('home')->higawariCard(new DateTimeImmutable('2026-10-10T00:30:00Z')) !== null, 'timezone UTC: 00:30 UTC on the 10th shows it');

			// A broken days value: debug throws, otherwise it is logged and 1 is used.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['debug' => true, 'sayamaapps.higawari.days' => 'drei']);
			throws(InvalidArgumentException::class, fn () => Options::days($kirby), 'debug: broken days throws');
			$log = sys_get_temp_dir() . '/higawari-test-' . bin2hex(random_bytes(4)) . '.log';
			$previousLog = ini_get('error_log');
			ini_set('error_log', $log);
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.days' => 'drei']);
			expect(1, Options::days($kirby), 'broken days: default 1');
			expect(null, $kirby->page('home')->higawariCard($nextDay), 'broken days: default 1 means only the day itself');
			ini_set('error_log', (string)$previousLog);
			holds(is_file($log) && str_contains((string)file_get_contents($log), 'sayamaapps.higawari.days'), 'broken days: reported to the error log');
			if (is_file($log)) {
				unlink($log);
			}
		});

		// Clearing: the Panel's "Tageskarte leeren" writes Menu::CLEARED. Other fields stay.
		higawari_with_site(HIGAWARI_TEST_HOME, function (string $tmp) use ($noon) {
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON);
			$kirby->impersonate('kirby');
			$kirby->page('home')->update(Menu::CLEARED);

			$fresh = higawari_kirby($tmp, HIGAWARI_TEST_ON)->page('home');
			expect([], $fresh->tagesgerichte()->yaml(), 'cleared: no dishes');
			expect('', $fresh->tageskarteDatum()->value(), 'cleared: no date');
			expect('Willkommen', $fresh->einleitung()->value(), 'cleared: other fields untouched');
			expect(null, $fresh->higawariCard($noon), 'cleared: no card');
		});

		// A menu entered for tomorrow after clearing is not hidden by an old date.
		higawari_with_site(HIGAWARI_TEST_HOME, function (string $tmp) {
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON);
			$kirby->impersonate('kirby');
			$kirby->page('home')->update(Menu::CLEARED);
			$kirby->page('home')->update(['tageskarteDatum' => '2026-10-11', 'tagesgerichte' => [['gericht' => 'Brezn', 'preis' => '2']]]);
			$fresh = higawari_kirby($tmp, HIGAWARI_TEST_ON)->page('home');
			expect(true, $fresh->higawariCard(at('2026-10-11 09:00')) !== null, 'after clearing: a new menu is shown on its day');
		});

		// Mode until: the date is the last day shown (Anzeigen bis), no first day, days ignored.
		higawari_with_site(HIGAWARI_TEST_HOME, function (string $tmp) use ($noon, $nextDay) {
			$until = HIGAWARI_TEST_ON + ['sayamaapps.higawari.dateMode' => 'until'];
			$earlier = at('2026-10-08 12:00');

			$kirby = higawari_kirby($tmp, $until);
			$page = $kirby->page('home');
			$html = higawari_render($kirby, ['source' => $page, 'now' => $earlier]);
			holds(
				str_contains($html, '<time datetime="2026-10-10">Bis einschließlich Samstag, 10.10.2026</time>'),
				'until: German line with the last day, datetime is the last day',
			);
			holds(!str_contains($html, ' – '), 'until: no from-to range');
			expect(3, substr_count($html, '<li class="higawari-gericht">'), 'until: shown already before the date');
			$card = $page->higawariCard($earlier);
			expect(['2026-10-08', '2026-10-10'], [$card['from'], $card['to']], 'until: page method: from is today, to is the date');
			expect('', higawari_render($kirby, ['source' => $page, 'now' => $nextDay]), 'until: nothing on the day after the date');
			expect(null, $page->higawariCard($nextDay), 'until: page method null after the date');

			// days is ignored: a broken value is not even read, and days=3 does not extend the last day.
			$kirby = higawari_kirby($tmp, $until + ['debug' => true, 'sayamaapps.higawari.days' => 'drei']);
			holds(
				str_contains(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]), 'higawari-gericht'),
				'until: a broken days value is not read (debug on)',
			);
			$kirby = higawari_kirby($tmp, $until + ['sayamaapps.higawari.days' => 3]);
			expect('', higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $nextDay]), 'until: days=3 does not extend the last day');

			// A date of today: shown today.
			$kirby = higawari_kirby($tmp, $until);
			holds(
				str_contains(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => at('2026-10-10 23:59:59')]), 'Bis einschließlich Samstag, 10.10.2026'),
				'until: last second of the date',
			);

			// English line.
			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'en', 'sayamaapps.higawari.dateMode' => 'until']);
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $earlier]);
			holds(str_contains($html, '<time datetime="2026-10-10">Until Saturday, 10 October 2026</time>'), 'until: English line');

			// emptyText after the last day, as in mode from.
			$kirby = higawari_kirby($tmp, $until + ['sayamaapps.higawari.emptyText' => 'Keine Karte.']);
			holds(
				str_contains(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $nextDay]), '<p class="higawari-leer">Keine Karte.</p>'),
				'until: emptyText after the last day',
			);

			// A broken dateMode: debug throws, otherwise it is reported and `from` is used.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['debug' => true, 'sayamaapps.higawari.dateMode' => 'bis']);
			throws(InvalidArgumentException::class, fn () => Options::dateMode($kirby), 'debug: broken dateMode throws');
			$log = sys_get_temp_dir() . '/higawari-test-' . bin2hex(random_bytes(4)) . '.log';
			$previousLog = ini_get('error_log');
			ini_set('error_log', $log);
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.dateMode' => 'bis']);
			expect('from', Options::dateMode($kirby), 'broken dateMode: from');
			$html = higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]);
			holds(str_contains($html, '<time datetime="2026-10-10">Samstag, 10.10.2026</time>'), 'broken dateMode: the from line');
			expect('', higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $nextDay]), 'broken dateMode: from means only the day itself');
			ini_set('error_log', (string)$previousLog);
			holds(is_file($log) && str_contains((string)file_get_contents($log), 'sayamaapps.higawari.dateMode'), 'broken dateMode: reported to the error log');
			if (is_file($log)) {
				unlink($log);
			}

			// Mode from stays the default: the same page without the option.
			$kirby = higawari_kirby($tmp, HIGAWARI_TEST_ON + ['sayamaapps.higawari.dateMode' => 'from']);
			holds(
				str_contains(higawari_render($kirby, ['source' => $kirby->page('home'), 'now' => $noon]), '<time datetime="2026-10-10">Samstag, 10.10.2026</time>'),
				'explicit from: unchanged line',
			);
		});
	}
}
