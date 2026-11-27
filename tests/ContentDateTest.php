<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Daily;
use Sayamaapps\Higawari\Menu;
use Sayamaapps\Higawari\Time;

/**
 * Dates from a content file written as Kirby 5 wrote it, unquoted. The card's
 * own date is a page field, text in Kirby 5 and 6. A date inside YAML (a
 * structure field of the site) is text in Kirby 5 and a Unix time in Kirby 6:
 * Menu reads both.
 */
#[CoversClass(Daily::class)]
#[CoversClass(Menu::class)]
#[CoversClass(Time::class)]
#[RunTestsInSeparateProcesses]
final class ContentDateTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testUnquotedDatesFromAContentFile(): void
	{
		$fields = [
			'tageskarteDatum' => '2026-11-21',
			'tagesgerichte' => [['gericht' => 'Suppe', 'preis' => '5']],
			'termine' => "\n\n- datum: 2026-11-21\n",
		];

		higawari_with_site($fields, function (string $tmp): void {
			$page = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true])->page('home');

			$card = $page->higawariCard(at('2026-11-21 12:00'));
			expect('2026-11-21', $card['from'] ?? null, 'the card from the page field');

			$date = (string)$page->content()->get('termine')->toStructure()->first()?->datum();
			expect(['from' => '2026-11-21', 'to' => '2026-11-21'], Menu::window($date, 1), 'a date from a structure field');
		});
	}
}
