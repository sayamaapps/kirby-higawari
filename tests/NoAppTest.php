<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\I18n;
use Sayamaapps\Higawari\Menu;

/**
 * Menu and I18n without a Kirby App and without the test loader: the bundled
 * texts are used, so the dates and labels read as they did in 1.x.
 */
#[CoversClass(Menu::class)]
#[CoversClass(I18n::class)]
final class NoAppTest extends TestCase
{
	protected function setUp(): void
	{
		// Other tests leave a loader and cached translations behind; this test needs neither.
		Kirby\Toolkit\I18n::$load = null;
		Kirby\Toolkit\I18n::$translations = [];
	}

	public function testDateAndLabelsAreEnglishWithoutAnApp(): void
	{
		I18n::setLanguage('en');

		expect('Saturday, 10 October 2026', Menu::displayDate('2026-10-10'), 'displayDate without an App');
		expect('Saturday, 10 October 2026', Menu::label('2026-10-10', '2026-10-10'), 'label of one day without an App');
		expect('Saturday, 10 October 2026 – Monday, 12 October 2026', Menu::label('2026-10-10', '2026-10-12'), 'label of a range without an App');
		expect('Until Saturday, 10 October 2026', Menu::label('2026-10-10', '2026-10-10', Menu::MODE_UNTIL), 'until label without an App');
		expect('Saturday', I18n::day(6), 'weekday without an App');
		expect('Daily menu', I18n::translate('snippet.heading'), 'heading without an App');
	}

	public function testDateAndLabelsAreGermanWithoutAnApp(): void
	{
		I18n::setLanguage('de');

		expect('Samstag, 10.10.2026', Menu::displayDate('2026-10-10'), 'German displayDate without an App');
		expect('Tageskarte', I18n::t('snippet.heading'), 'German heading through the 1.x wrapper without an App');
	}
}
