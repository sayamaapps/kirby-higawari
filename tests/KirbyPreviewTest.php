<?php

use Kirby\Cms\App;
use Kirby\Content\VersionId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Daily;
use Sayamaapps\Higawari\Options;

/**
 * Preview of changes (1.9): a card for a later day, saved as the `changes`
 * version, is shown in Kirby's preview as on its day; the published page
 * keeps the real clock. Dates are relative to today, since the preview uses
 * the real clock.
 */
#[CoversClass(Daily::class)]
#[CoversClass(Options::class)]
#[RunTestsInSeparateProcesses]
final class KirbyPreviewTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
		$today = at('now');
		$day = fn (int $offset): string => $today->modify(($offset < 0 ? '' : '+') . $offset . ' days')->format('Y-m-d');
		$on = ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'de'];
		$live = ['tageskarteDatum' => $day(-1), 'tagesgerichte' => [['gericht' => 'Alte Suppe', 'preis' => '5']]];
		$tomorrow = [
			'tageskarteDatum' => $day(1),
			'tagesgerichte' => [
				['gericht' => 'Neues Curry', 'preis' => '9.9', 'zeit' => 'bis', 'uhrzeit' => '00:01:00'],
				['gericht' => 'Neue Roulade', 'preis' => '14'],
			],
		];

		higawari_with_site($live, function (string $tmp) use ($on, $tomorrow, $day) {
			file_put_contents($tmp . '/site/templates/home.php', "<?php snippet('higawari') ?>\n<?php snippet('higawari/schema') ?>");
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			$kirby->page('home')->version(VersionId::changes())->save($tomorrow);

			// A request as the draft link sends it: ?_version=changes&_token=…
			$token = higawari_kirby($tmp, $on)->page('home')->version(VersionId::changes())->previewToken();
			$request = fn (array $options = []): App => higawari_preview_kirby($tmp, $on + $options, ['_version' => 'changes', '_token' => $token]);

			$kirby = $request();
			$html = $kirby->page('home')->render();
			holds(str_contains($html, 'Neues Curry') && str_contains($html, 'Neue Roulade'), 'preview: tomorrow’s dishes');
			holds(str_contains($html, 'bis 0:01 Uhr'), 'preview: lunch dish shown as at 00:00 of its day');
			holds(str_contains($html, '<time datetime="' . $day(1) . '">'), 'preview: dated tomorrow');
			holds(str_contains($html, '"@type":"Menu"') && str_contains($html, 'Neue Roulade"'), 'preview: JSON-LD of tomorrow');
			holds(!str_contains($html, 'Alte Suppe'), 'preview: not the published card');
			holds($kirby->response()->cache() === false, 'preview: not cached');

			// The cache option does not let the preview into the cache.
			$kirby = $request(['sayamaapps.higawari.cache' => true]);
			$kirby->page('home')->render();
			holds($kirby->response()->cache() === false, 'preview with cache option: not cached');

			// Outside a render, and with an explicit clock, nothing changes.
			expect(null, $kirby->page('home')->higawariCard(), 'outside the render: real clock');
			$card = VersionId::render('changes', fn () => $kirby->page('home')->higawariCard(at($day(1) . ' 12:00')));
			expect(['Neue Roulade'], array_column($card['dishes'], 'name'), 'explicit $now wins in the preview');

			// Option preview false: the real clock, so nothing yet.
			$html = $request(['sayamaapps.higawari.preview' => false])->page('home')->render();
			holds(!str_contains($html, 'Neue'), 'preview off: tomorrow’s card not shown');

			// Wrong token: the published page.
			$html = higawari_preview_kirby($tmp, $on, ['_version' => 'changes', '_token' => 'falsch'])->page('home')->render();
			holds(!str_contains($html, 'Neue') && !str_contains($html, 'Alte Suppe'), 'wrong token: published page, yesterday’s card gone');

			// Published with tomorrow’s date: the live page keeps the real clock.
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			$kirby->page('home')->version(VersionId::changes())->publish();
			$html = higawari_kirby($tmp, $on)->page('home')->render();
			holds(!str_contains($html, 'Neue'), 'live: tomorrow’s card not shown today');

			// Preview of a card for today: the real clock (a lunch dish may be gone).
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			$kirby->page('home')->version(VersionId::changes())->save(['tageskarteDatum' => $day(0)] + $tomorrow);
			$token = higawari_kirby($tmp, $on)->page('home')->version(VersionId::changes())->previewToken();
			$html = higawari_preview_kirby($tmp, $on, ['_version' => 'changes', '_token' => $token])->page('home')->render();
			holds(str_contains($html, '<time datetime="' . $day(0) . '">') && str_contains($html, 'Neue Roulade'), 'preview of today: shown');
			if (at('now')->format('H:i') >= '00:01') {
				holds(!str_contains($html, 'Neues Curry'), 'preview of today: real clock, lunch dish bis 00:01 gone');
			}

			// A broken option throws with debug on (otherwise it is logged and the default, on, is used).
			expect(true, Options::output(higawari_kirby($tmp))['preview'], 'preview on by default');
			throws(InvalidArgumentException::class, fn () => Options::output(higawari_kirby($tmp, ['debug' => true, 'sayamaapps.higawari.preview' => 'ja'])), 'broken preview option');
		});
	}

	/** A date Kirby 6 reads as a Unix time keeps the preview on the card's day. */
	public function testUnixTimeDate(): void
	{
		$date = at('now')->modify('+1 day')->format('Y-m-d');
		$stored = (string)(new DateTimeImmutable($date, new DateTimeZone('UTC')))->getTimestamp();
		$on = ['sayamaapps.higawari.active' => true];

		higawari_with_site([], function (string $tmp) use ($on, $date, $stored) {
			$kirby = higawari_kirby($tmp, $on);
			$kirby->impersonate('kirby');
			$kirby->page('home')->version(VersionId::changes())->save([
				'tageskarteDatum' => $stored,
				'tagesgerichte' => [['gericht' => 'Neues Curry', 'preis' => '9.9']],
			]);
			$card = VersionId::render('changes', fn () => $kirby->page('home')->higawariCard());
			expect($date, $card['from'] ?? null, 'preview: the card of its day');
		});
	}
}

/** higawari_kirby() with a request query, as Kirby reads ?_version and ?_token. */
function higawari_preview_kirby(string $tmp, array $options, array $query): App
{
	\Kirby\Template\Snippet::$cache = [];
	return App::instance(new App([
		'roots' => [
			'base' => $tmp,
			'index' => $tmp . '/public',
			'content' => $tmp . '/content',
			'site' => $tmp . '/site',
			'storage' => $tmp . '/storage',
		],
		'options' => ['url' => 'http://example.invalid'] + $options,
		'request' => ['query' => $query],
	]));
}
