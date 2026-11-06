<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Daily;

/**
 * The JSON-LD snippet, the page methods, the cache option and the hook for
 * Annai, in a real Kirby. Annai itself is not installed: the hook is called
 * by its name, as Annai calls it.
 */
#[CoversClass(Sayamaapps\Higawari\Daily::class)]
#[CoversClass(Sayamaapps\Higawari\Options::class)]
#[RunTestsInSeparateProcesses]
final class SchemaKirbyTest extends TestCase
{
	private const HOME = [
		'tageskarteDatum' => '2026-10-10',
		'tagesgerichte' => [
			['gericht' => 'Kartoffelsuppe', 'preis' => '6,9'],
			['gericht' => 'Linseneintopf', 'preis' => '9,9', 'variante' => 'mit Wiener', 'variantenpreis' => '11,5'],
		],
	];
	private const ON = ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.language' => 'de'];

	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testAnnaiHook(): void
	{
		higawari_with_site(self::HOME, function (string $tmp): void {
			$noon = at('2026-10-10 12:00');
			$kirby = higawari_kirby($tmp, self::ON);
			$fields = $kirby->apply('sayamaapps.annai.business', ['fields' => ['paymentAccepted' => 'Cash'], 'facts' => []], 'fields');
			expect('Cash', $fields['paymentAccepted'] ?? null, 'hook: keeps the fields of others');
			// The hook reads the clock; Daily::annai() with $now checks the content.
			$menu = Daily::annai($kirby, [], $noon)['hasMenu'] ?? null;
			expect('Menu', $menu['@type'] ?? null, 'annai: hasMenu is the Menu of the home page');
			expect([], Daily::annai($kirby, [], at('2026-10-11 12:00')), 'annai: nothing without a valid card');
			expect(['hasMenu' => 'https://example.com/karte'], Daily::annai($kirby, ['hasMenu' => 'https://example.com/karte'], $noon), 'annai: an existing hasMenu stays');

			$kirby = higawari_kirby($tmp, self::ON + ['sayamaapps.higawari.annai' => false]);
			expect([], Daily::annai($kirby, [], $noon), 'annai false: nothing');
			$kirby = higawari_kirby($tmp, self::ON + ['sayamaapps.higawari.annai' => 'gibt-es-nicht']);
			expect([], Daily::annai($kirby, [], $noon), 'annai: unknown page id gives nothing');
			$kirby = higawari_kirby($tmp, self::ON + ['sayamaapps.higawari.annai' => 'home']);
			expect('Menu', Daily::annai($kirby, [], $noon)['hasMenu']['@type'] ?? null, 'annai: a page id');

			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => false]);
			expect(['x' => 1], $kirby->apply('sayamaapps.annai.business', ['fields' => ['x' => 1], 'facts' => []], 'fields'), 'hook: off leaves the fields');
		});
	}

	public function testCacheOption(): void
	{
		higawari_with_site(self::HOME, function (string $tmp): void {
			$kirby = higawari_kirby($tmp, self::ON + ['sayamaapps.higawari.cache' => true]);
			$page = $kirby->page('home');
			$kirby->snippet('higawari', ['source' => $page, 'now' => at('2026-10-10 12:00')], true);
			expect(true, $kirby->response()->cache(), 'cache on: the page may be cached');
			expect((new DateTimeImmutable('2026-10-11 00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp(), $kirby->response()->expires(), 'cache on: expires at the end of the day');
		});
	}

	public function testPriceCurrency(): void
	{
		higawari_with_site(self::HOME, function (string $tmp): void {
			$noon = at('2026-10-10 12:00');
			$currency = static function (array $options) use ($tmp, $noon): string|null {
				$kirby = higawari_kirby($tmp, self::ON + $options);
				return $kirby->page('home')->higawariSchema($noon)['hasMenuSection']['hasMenuItem'][0]['offers']['priceCurrency'] ?? null;
			};
			expect('CHF', $currency(['sayamaapps.higawari.currency' => 'CHF']), 'a three-letter currency is the code');
			expect(null, $currency(['sayamaapps.higawari.currency' => '']), 'no currency: no priceCurrency');
			expect('USD', $currency(['sayamaapps.higawari.currency' => '$', 'sayamaapps.higawari.priceCurrency' => 'usd']), 'priceCurrency option');
		});
	}

	public function testSnippetAndPageMethods(): void
	{
		higawari_with_site(self::HOME, function (string $tmp): void {
			$noon = at('2026-10-10 12:00');
			$kirby = higawari_kirby($tmp);
			expect('', $kirby->snippet('higawari/schema', ['source' => $kirby->page('home'), 'now' => $noon], true), 'off: nothing');

			$kirby = higawari_kirby($tmp, self::ON);
			$page = $kirby->page('home');
			$html = $kirby->snippet('higawari/schema', ['source' => $page, 'now' => $noon], true);
			holds(preg_match('#^<script type="application/ld\+json">(.*)</script>\s*$#s', $html, $m) === 1, 'one script element');
			$data = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
			expect('https://schema.org', $data['@context'], '@context');
			expect('Menu', $data['@type'], 'Menu');
			expect('http://example.invalid#tageskarte', $data['@id'], '@id from the page URL');
			expect('Tageskarte Samstag, 10.10.2026', $data['hasMenuSection']['name'], 'section with the date');
			expect('6.90', $data['hasMenuSection']['hasMenuItem'][0]['offers']['price'], 'price with a point');
			expect('EUR', $data['hasMenuSection']['hasMenuItem'][0]['offers']['priceCurrency'], 'EUR from €');
			expect(false, $kirby->response()->cache(), 'cache option off: page kept out of the cache');

			expect('', $kirby->snippet('higawari/schema', ['source' => $page, 'now' => at('2026-10-11 12:00')], true), 'no valid card: nothing');
			expect(null, $page->higawariSchema(at('2026-10-11 12:00')), 'page method: null without a card');
			expect('Menu', $page->higawariSchema($noon)['@type'] ?? null, 'page method: the Menu');
			expect((new DateTimeImmutable('2026-10-11 00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp(), $page->higawariCacheUntil($noon), 'page method: cache until');
		});
	}
}
