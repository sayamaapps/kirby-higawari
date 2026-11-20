<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Daily;

/**
 * Time of day (1.8) through Kirby: values saved like the Panel saves them,
 * the snippet, the note field, JSON-LD and the cache expiry of the page.
 */
#[CoversClass(Daily::class)]
#[RunTestsInSeparateProcesses]
final class KirbyTimeTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
		$on = ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'de'];
		higawari_with_site(['tageskarteDatum' => '2026-11-20'], function (string $tmp) use ($on) {
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			// As the Panel saves it: a time field stores HH:MM:SS.
			$kirby->page('home')->update([
				'tagesgerichte' => [
					['gericht' => 'Gemüsecurry mit Reis', 'preis' => '9.9', 'zeit' => 'bis', 'uhrzeit' => '15:00:00'],
					['gericht' => 'Rinderroulade', 'preis' => '14', 'zeit' => 'ab', 'uhrzeit' => '15:00:00'],
					['gericht' => 'Käsespätzle', 'preis' => '16.9', 'zeit' => 'ganztags', 'uhrzeit' => '15:00:00'],
				],
			]);

			// A fresh app reads the stored file.
			$kirby = higawari_kirby($tmp, $on);
			$page = $kirby->page('home');
			$raw = (string)file_get_contents($tmp . '/content/home/home.txt');
			holds(str_contains($raw, 'zeit: bis') && str_contains($raw, 'uhrzeit:'), 'stored with zeit and uhrzeit');

			$noon = at('2026-11-20 12:00');
			$late = at('2026-11-20 15:00');
			$html = $kirby->snippet('higawari', ['source' => $page, 'now' => $noon], true);
			holds(str_contains($html, '<li class="higawari-gericht higawari-gericht-bis">'), 'noon: lunch dish marked bis');
			holds(str_contains($html, '<span class="higawari-zeit">bis 15 Uhr</span>'), 'noon: „bis 15 Uhr“');
			holds(str_contains($html, '<li class="higawari-gericht higawari-gericht-ab">'), 'noon: afternoon dish marked ab');
			holds(str_contains($html, '<span class="higawari-zeit">ab 15 Uhr</span>'), 'noon: „ab 15 Uhr“');
			holds(str_contains($html, '<li class="higawari-gericht">' . "\n" . '			<span class="higawari-name">Käsespätzle</span>'), 'all-day dish without a time class');
			expect(1, substr_count($html, 'higawari-zeit">ab'), 'one ab note');

			$html = $kirby->snippet('higawari', ['source' => $page, 'now' => $late], true);
			holds(!str_contains($html, 'Gemüsecurry'), '15:00: lunch dish gone from the snippet');
			holds(str_contains($html, 'Rinderroulade') && str_contains($html, 'ab 15 Uhr'), '15:00: afternoon dish still with its note');

			expect(['Rinderroulade', 'Käsespätzle'], array_column($page->higawariCard($late)['dishes'], 'name'), 'page method follows the clock');
			$items = fn ($now): array => array_column($page->higawariSchema($now)['hasMenuSection']['hasMenuItem'], 'name');
			expect(3, count($items($noon)), 'JSON-LD at noon: three items');
			expect(['Rinderroulade', 'Käsespätzle'], $items($late), 'JSON-LD after 15:00: lunch dish gone');
			expect(at('2026-11-20 15:00')->getTimestamp(), $page->higawariCacheUntil($noon), 'page expires at 15:00');
			expect(at('2026-11-21 00:00')->getTimestamp(), $page->higawariCacheUntil($late), 'then at midnight');

			// Note: the field wins, the option is the fallback, neither prints nothing.
			holds(!str_contains($html, 'higawari-hinweis'), 'no note by default');
			$kirby = higawari_kirby($tmp, $on + ['sayamaapps.higawari.note' => 'Preise in Euro']);
			holds(str_contains($kirby->snippet('higawari', ['source' => $kirby->page('home'), 'now' => $noon], true), '<p class="higawari-hinweis">Preise in Euro</p>'), 'option note');
			$kirby->impersonate('kirby');
			$kirby->page('home')->update(['tageskarteHinweis' => '  Di–Fr bis 15 Uhr: Mittagsgericht 9,90 €  ']);
			$kirby = higawari_kirby($tmp, $on + ['sayamaapps.higawari.note' => 'Preise in Euro']);
			$html = $kirby->snippet('higawari', ['source' => $kirby->page('home'), 'now' => $noon], true);
			holds(str_contains($html, '<p class="higawari-hinweis">Di–Fr bis 15 Uhr: Mittagsgericht 9,90 €</p>'), 'field note wins, trimmed');
			holds(!str_contains($html, 'Preise in Euro'), 'option note not printed beside it');
			expect('Di–Fr bis 15 Uhr: Mittagsgericht 9,90 €', Daily::note($kirby, $kirby->page('home')), 'Daily::note');
			expect(null, Daily::note(higawari_kirby($tmp), null), 'no page, no option: null');

			// The note stays when the menu is cleared. (The line above made another app current.)
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			$kirby->page('home')->update(Sayamaapps\Higawari\Menu::CLEARED);
			$kirby = higawari_kirby($tmp, $on);
			expect('Di–Fr bis 15 Uhr: Mittagsgericht 9,90 €', (string)$kirby->page('home')->tageskarteHinweis(), 'clearing keeps the note');
		});
	}
}
