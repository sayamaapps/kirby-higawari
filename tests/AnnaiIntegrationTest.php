<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * With Annai (2.6 or newer) next to this package (HIGAWARI_ANNAI_PATH, else
 * ../kirby-annai), its business node carries today's menu as hasMenu.
 * Skipped without it: Higawari does not depend on Annai.
 */
#[CoversNothing]
#[RunTestsInSeparateProcesses]
final class AnnaiIntegrationTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testBusinessNodeGetsTheMenu(): void
	{
		$env = getenv('HIGAWARI_ANNAI_PATH');
		$annai = is_string($env) && $env !== '' ? $env : dirname(__DIR__, 2) . '/kirby-annai';
		if (!is_file($annai . '/src/Business.php') || !str_contains((string)file_get_contents($annai . '/src/Business.php'), 'sayamaapps.annai.business')) {
			$this->markTestSkipped('Annai 2.6+ not found next to this package');
		}
		$today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
		higawari_with_site(['tageskarteDatum' => $today, 'tagesgerichte' => [['gericht' => 'Kartoffelsuppe', 'preis' => '6,9']]], function (string $tmp) use ($annai): void {
			symlink(realpath($annai), $tmp . '/site/plugins/annai');
			file_put_contents($tmp . '/content/site.txt', "Title: Gasthaus Muster\n");
			$kirby = higawari_kirby($tmp, [
				'sayamaapps.higawari.active' => true,
				'sayamaapps.annai.active' => true,
				'sayamaapps.annai.type' => 'Restaurant',
			]);
			$node = Sayamaapps\Annai\Business::jsonLd($kirby, $kirby->site()->homePage());
			expect('Restaurant', $node['@type'] ?? null, 'Annai business node');
			expect('Menu', $node['hasMenu']['@type'] ?? null, 'hasMenu from Higawari');
			expect('6.90', $node['hasMenu']['hasMenuSection']['hasMenuItem'][0]['offers']['price'] ?? null, 'the dish price');
		});
	}
}
