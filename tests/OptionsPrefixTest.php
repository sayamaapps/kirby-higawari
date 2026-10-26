<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Options;

/**
 * Options are read as sayamaapps.higawari.<key>, with the spelling higawari.<key>
 * as fallback. The fallback is reported as a deprecation in debug mode.
 */
#[CoversClass(Options::class)]
final class OptionsPrefixTest extends TestCase
{
	public function testNewSpellingSwitchesTheMenuOn(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true]);
			expect(true, Options::active($kirby), 'sayamaapps.higawari.active is read');
		});
	}

	public function testNewSpellingWinsOverTheOldOne(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => false, 'higawari.active' => true]);
			expect(false, Options::active($kirby), 'sayamaapps.higawari.active wins when both are set');
		});
	}

	public function testOldSpellingStillWorksAndIsDeprecated(): void
	{
		$deprecations = [];
		set_error_handler(function (int $level, string $message) use (&$deprecations): bool {
			$deprecations[] = $message;
			return true;
		}, E_USER_DEPRECATED);

		try {
			higawari_with_site([], function (string $tmp): void {
				// Kirby reports deprecations in debug mode only.
				$kirby = higawari_kirby($tmp, ['debug' => true, 'higawari.active' => true]);
				expect(true, Options::active($kirby), 'higawari.active is read as fallback');
			});
		} finally {
			restore_error_handler();
		}

		expect(['Higawari: higawari.active is deprecated, use sayamaapps.higawari.active'], $deprecations, 'the old spelling is reported');
	}

	public function testUnsetOptionsGiveTheDefaults(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp);
			expect(false, Options::active($kirby), 'unset: menu off');
			expect(1, Options::days($kirby), 'unset: one day');
			expect('Europe/Berlin', Options::timezone($kirby), 'unset: Europe/Berlin');
			expect('€', Options::currency($kirby), 'unset: euro sign');
		});
	}
}
