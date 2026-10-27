<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\I18n;

#[CoversClass(Sayamaapps\Higawari\I18n::class)]
final class I18nTest extends TestCase
{
	protected function setUp(): void
	{
		higawari_texts_without_app();
	}

	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
		$en = I18n::table('en');

		$de = I18n::table('de');

		$enKeys = array_keys($en);

		$deKeys = array_keys($de);

		sort($enKeys);

		sort($deKeys);

		expect($enKeys, $deKeys, 'de and en have the same keys');

		foreach ($en as $key => $text) {
			holds(is_string($de[$key] ?? null) && trim($de[$key]) !== '', "de has a text for $key");
			preg_match_all('/\{\w+\}/', $text, $enPlaceholders);
			preg_match_all('/\{\w+\}/', (string)($de[$key] ?? ''), $dePlaceholders);
			$enPlaceholders = $enPlaceholders[0];
			$dePlaceholders = $dePlaceholders[0];
			sort($enPlaceholders);
			sort($dePlaceholders);
			expect($enPlaceholders, $dePlaceholders, "placeholders of $key match");
		}

		I18n::setLanguage('de');

		expect('Tageskarte', I18n::translate('snippet.heading'), 'German heading');

		expect('Kennzeichnungen: V', I18n::translate('snippet.markers', ['markers' => 'V']), 'placeholder filled');

		I18n::setLanguage('fr');

		expect('en', I18n::language(), 'unknown language falls back to English');

		expect('Daily menu', I18n::translate('snippet.heading'), 'English heading after fallback');

		expect('no.such.key', I18n::translate('no.such.key'), 'missing key returns the key');

		I18n::setLanguage('en');

		expect(I18n::translate('snippet.heading'), I18n::t('snippet.heading'), 'the 1.x wrapper t() gives the same text');
	}
}
